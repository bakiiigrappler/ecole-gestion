<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\Series;
use App\Models\StudentGrade;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ClassController extends Controller
{
    /**
     * Afficher la liste des classes
     */
    public function index(Request $request)
    {
        // Un enseignant ne pilote pas le parc de classes : sa page est celle
        // de ses classes a lui, avec ce qu'il y fait.
        if (\App\Support\PerimetreEnseignant::estEnseignant()) {
            return $this->mesClasses();
        }

        $query = SchoolClass::query()
            ->with([
                'level',
                // Equipe pedagogique : le pivot est desormais la seule source.
                'allTeachers' => fn ($q) => $q->orderByPivot('role'),
            ])
            // Effectif reel : seules les inscriptions actives de l'annee en cours.
            ->withCount(['enrollments as effectif' => function ($q) {
                $q->where('status', 'active')
                  ->whereHas('academicYear', fn ($a) => $a->where('is_current', true));
            }]);

        if ($terme = trim((string) $request->input('search'))) {
            $motif = '%'.mb_strtolower($terme).'%';
            $query->whereRaw('LOWER(name) LIKE ?', [$motif]);
        }

        if ($niveau = $request->input('level_id')) {
            $query->where('level_id', $niveau);
        }

        if ($cycle = $request->input('cycle')) {
            $query->byCycle($cycle);
        }

        if (($statut = $request->input('status')) !== null && $statut !== '') {
            $query->where('is_active', $statut === 'active');
        }

        // Tri : on n'accepte que des colonnes connues.
        $tri = $request->input('sort', 'name');
        $sens = $request->input('direction', 'asc');

        $tri = in_array($tri, ['name', 'capacity', 'effectif', 'created_at'], true) ? $tri : 'name';
        $sens = in_array($sens, ['asc', 'desc'], true) ? $sens : 'asc';

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $classes = $query->orderBy($tri, $sens)
            ->paginate($parPage)
            ->withQueryString();

        $levels = Level::active()->orderBy('order')->get();

        /*
         * Statistiques sur l'ensemble, pas sur la page courante : afficher la
         * somme des capacites de dix classes sur trente-sept induirait en erreur.
         */
        $statistiques = [
            'total' => SchoolClass::count(),
            'ouvertes' => SchoolClass::where('is_active', true)->count(),
            'places' => (int) SchoolClass::sum('capacity'),
            'effectif' => Enrollment::where('status', 'active')
                ->whereHas('academicYear', fn ($a) => $a->where('is_current', true))
                ->count(),
            'sans_enseignant' => SchoolClass::whereDoesntHave('allTeachers')->count(),
        ];

        return view('classes.index', compact('classes', 'levels', 'statistiques'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $levels = Level::active()->orderBy('order')->get();

        // Tous les enseignants actifs, avec leur cycle : le formulaire ne
        // propose que ceux du cycle du niveau choisi.
        $enseignants = Teacher::with('subjects')
            ->where('status', 'active')
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $chargeParEnseignant = DB::table('class_teacher')
            ->selectRaw('teacher_id, count(*) as classes')
            ->groupBy('teacher_id')
            ->pluck('classes', 'teacher_id');

        return view('classes.create', compact('levels', 'enseignants', 'chargeParEnseignant'));
    }

    /**
     * Enregistrer une nouvelle classe
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // La validation se limitait au nom et au niveau : capacite, serie et
            // equipe passaient sans aucun controle.
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:levels,id',
            'series_id' => 'nullable|exists:series,id',
            'capacity' => 'nullable|integer|min:1|max:200',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'enseignants' => 'nullable|array',
            'enseignants.*' => 'exists:teachers,id',
            'principal' => 'nullable|exists:teachers,id',
        ], [
            'name.required' => 'Le nom de la classe est obligatoire.',
            'level_id.required' => 'Choisissez le niveau de la classe.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $donnees = $validator->validated();
        $enseignants = collect($donnees['enseignants'] ?? [])->unique()->values();
        $principal = $donnees['principal'] ?? null;

        // Meme regle qu'a la modification de l'equipe : on ne designe pas un
        // principal qui n'intervient pas dans la classe.
        if ($principal && ! $enseignants->contains($principal)) {
            return redirect()->back()->withInput()
                ->withErrors(['principal' => 'Le professeur principal doit faire partie de l’équipe.']);
        }

        // Deux classes de meme nom sur un meme niveau se confondent partout.
        $existe = SchoolClass::where('level_id', $donnees['level_id'])
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($donnees['name']))])
            ->exists();

        if ($existe) {
            return redirect()->back()->withInput()
                ->withErrors(['name' => 'Une classe porte déjà ce nom à ce niveau.']);
        }

        try {
            $class = SchoolClass::create([
                'name' => trim($donnees['name']),
                'level_id' => $donnees['level_id'],
                'series_id' => $donnees['series_id'] ?? null,
                'capacity' => $donnees['capacity'] ?? null,
                'description' => $donnees['description'] ?? null,
                // Une case decochee n'est pas envoyee : sans `boolean()`, la classe
                // naissait fermee des que l'utilisateur touchait a la case.
                'is_active' => $request->boolean('is_active'),
            ]);

            /*
             * Une classe n'a qu'un professeur principal. L'ancienne version en
             * designait potentiellement plusieurs : le premier arrive, puis tout
             * enseignant polyvalent rencontre ensuite.
             */
            if ($enseignants->isNotEmpty()) {
                $roles = $enseignants->mapWithKeys(fn ($id) => [
                    $id => ['role' => (string) $id === (string) $principal ? 'principal' : 'teacher'],
                ])->all();

                $class->allTeachers()->sync($roles);
            }

            return redirect()->route('classes.show', $class->id)
                ->with('success', 'Classe créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la creation de la classe', ['erreur' => $e->getMessage()]);

            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la création de la classe.');
        }
    }

    /**
     * Nom propose pour une nouvelle classe, au format JSON.
     *
     * La regle vit dans le modele : le formulaire l'interroge plutot que de la
     * reecrire en JavaScript, comme le faisait `public/js/class-creation.js`
     * avec sa propre liste de series ecrite en dur.
     */
    public function proposerNom(Request $request)
    {
        $levelId = $request->filled('level_id') ? (int) $request->input('level_id') : null;
        $seriesId = $request->filled('series_id') ? (int) $request->input('series_id') : null;

        // La numerotation deja pratiquee au niveau : le formulaire s'y aligne
        // par defaut plutot que d'imposer les chiffres.
        $numerotation = in_array($request->input('numerotation'), ['chiffre', 'lettre'], true)
            ? $request->input('numerotation')
            : SchoolClass::numerotationDuNiveau($levelId, $seriesId);

        return response()->json([
            'nom' => SchoolClass::proposerNom($levelId, $seriesId, $numerotation),
            'numerotation' => $numerotation,
        ]);
    }

    /**
     * Series ouvertes a un niveau, au format JSON.
     */
    public function seriesDuNiveau(Request $request)
    {
        $niveau = $request->filled('level_id') ? Level::find($request->input('level_id')) : null;

        return response()->json([
            'series' => $niveau
                ? Series::where('is_active', true)->where('level_id', $niveau->id)
                    ->orderBy('order')->get(['id', 'code', 'name'])
                : [],
            'cycle' => $niveau?->cycle,
        ]);
    }

    public function show(SchoolClass $class)
    {
        $class->load([
            'level',
            'allTeachers.subjects',
            'schedules.subject',
            'schedules.teacher',
        ]);

        // Effectif de l'annee en cours : la relation students() ne filtre ni
        // l'annee ni le statut, elle remonterait tous les inscrits de toujours.
        $eleves = Student::whereHas('enrollments', fn ($q) => $q
                ->where('class_id', $class->id)
                ->where('status', 'active'))
            ->with(['enrollments' => fn ($q) => $q
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->with('academicYear')
                ->limit(1)])
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        // Enseignants du meme cycle : ce sont eux qu'on peut affecter.
        $cycle = $class->getSafeCycle();
        $enseignantsDisponibles = Teacher::with('subjects')
            ->where('status', 'active')
            ->when($cycle, fn ($q) => $q->where('cycle', $cycle))
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        // Matieres du cycle, pour signaler celles que l'equipe ne couvre pas.
        $matieresDuCycle = \App\Models\Subject::where('is_active', true)
            ->when($cycle, fn ($q) => $q->where('cycle', $cycle))
            ->orderBy('name')
            ->get();

        // Charge de travail : combien de classes chaque enseignant assure deja.
        // Sert a ne pas surcharger quelqu'un lors d'une affectation.
        $chargeParEnseignant = DB::table('class_teacher')
            ->selectRaw('teacher_id, count(*) as classes')
            ->groupBy('teacher_id')
            ->pluck('classes', 'teacher_id');

        $presences = $this->assiduiteDeLaClasse($class, $eleves);
        $notes = $this->notesDeLaClasse($class, $eleves);

        return view('classes.show', compact(
            'class', 'eleves', 'enseignantsDisponibles', 'matieresDuCycle',
            'chargeParEnseignant', 'presences', 'notes'
        ));
    }

    /**
     * Assiduite de la classe : repartition globale, seances recentes et
     * situation eleve par eleve. Les presences existaient deja mais n'etaient
     * consultables que depuis le module Presences, hors du contexte classe.
     */
    private function assiduiteDeLaClasse(SchoolClass $class, $eleves): array
    {
        // SUM(CASE ...) plutot que FILTER : la syntaxe reste valable hors PostgreSQL.
        $comptes = "
            count(*) as total,
            sum(case when status = 'present' then 1 else 0 end) as presents,
            sum(case when status = 'absent'  then 1 else 0 end) as absents,
            sum(case when status = 'late'    then 1 else 0 end) as retards,
            sum(case when status = 'excused' then 1 else 0 end) as excuses
        ";

        $global = DB::table('attendances')->where('class_id', $class->id)
            ->selectRaw($comptes)->first();

        $seances = DB::table('attendances')->where('class_id', $class->id)
            ->selectRaw('attendance_date, '.$comptes)
            ->groupBy('attendance_date')
            ->orderByDesc('attendance_date')
            ->limit(15)
            ->get();

        $parEleve = DB::table('attendances')->where('class_id', $class->id)
            ->selectRaw('student_id, '.$comptes)
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $total = (int) ($global->total ?? 0);

        return [
            'total' => $total,
            'presents' => (int) ($global->presents ?? 0),
            'absents' => (int) ($global->absents ?? 0),
            'retards' => (int) ($global->retards ?? 0),
            'excuses' => (int) ($global->excuses ?? 0),
            'taux' => $total > 0 ? round(($global->presents / $total) * 100) : null,
            'seances' => $seances,
            'par_eleve' => $parEleve,
        ];
    }

    /**
     * Notes de la classe pour un trimestre : moyenne generale, moyenne par
     * matiere et classement des eleves. Les notes vivent dans `student_grades`
     * (la table `grades`, vide, est un doublon mort du schema).
     */
    private function notesDeLaClasse(SchoolClass $class, $eleves): array
    {
        $trimestres = StudentGrade::where('class_id', $class->id)
            ->distinct()->orderBy('term')->pluck('term');

        $demande = request('trimestre');
        $trimestre = $trimestres->contains($demande) ? $demande : $trimestres->first();

        $lignes = $trimestre
            ? StudentGrade::with('subject')
                ->where('class_id', $class->id)
                ->where('term', $trimestre)
                ->get()
            : collect();

        // Une note peut etre sur 10, 20 ou 100 : on ramene tout sur 20 avant
        // de moyenner, sinon les matieres ne sont pas comparables.
        $sur20 = fn ($n) => $n->max_score > 0 ? ($n->score / $n->max_score) * 20 : null;

        $parMatiere = $lignes->groupBy('subject_id')->map(fn ($g) => [
            'matiere' => $g->first()->subject?->name ?? 'Matière supprimée',
            'moyenne' => round($g->map($sur20)->filter()->avg() ?? 0, 2),
            'notes' => $g->count(),
        ])->sortBy('matiere')->values();

        $parEleve = $lignes->groupBy('student_id')->map(fn ($g) => [
            'moyenne' => round($g->map($sur20)->filter()->avg() ?? 0, 2),
            'notes' => $g->count(),
        ]);

        // Rang : moyennes egales partagent le meme rang.
        $classement = $parEleve->sortByDesc('moyenne')->values();
        $rangs = [];
        foreach ($parEleve->sortByDesc('moyenne') as $id => $ligne) {
            $rangs[$id] = $classement->search(fn ($l) => $l['moyenne'] === $ligne['moyenne']) + 1;
        }

        return [
            'trimestres' => $trimestres,
            'trimestre' => $trimestre,
            'total' => $lignes->count(),
            'moyenne' => $lignes->isNotEmpty() ? round($lignes->map($sur20)->filter()->avg(), 2) : null,
            'par_matiere' => $parMatiere,
            'par_eleve' => $parEleve,
            'rangs' => $rangs,
            'evalues' => $parEleve->count(),
        ];
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(SchoolClass $class)
    {
        $levels = Level::active()->orderBy('order')->get();

        // Series ouvertes au niveau courant, prises dans le referentiel.
        // Le rattachement passe par la cle etrangere : rapprocher les libelles
        // avait fait disparaitre les quatorze series de Terminale.
        $series = Series::where('is_active', true)
            ->where('level_id', $class->level_id)
            ->orderBy('order')
            ->get();

        // Effectif actuel : on ne peut pas descendre la capacite en dessous.
        $effectif = Enrollment::where('class_id', $class->id)
            ->where('status', 'active')
            ->count();

        return view('classes.edit', compact('class', 'levels', 'series', 'effectif'));
    }

    /**
     * Mettre à jour une classe
     */
    public function update(Request $request, SchoolClass $class)
    {
        // Effectif inscrit : la capacite ne peut pas passer en dessous, sinon la
        // classe est immediatement en surcapacite sans qu'on l'ait vu.
        $effectif = Enrollment::where('class_id', $class->id)
            ->where('status', 'active')
            ->count();

        $validator = Validator::make($request->all(), [
            // `level` etait exige ici alors que la colonne n'existe pas : le champ
            // partait vide et bloquait tout enregistrement. Le cycle se lit sur
            // le niveau, il n'a pas a etre saisi.
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:levels,id',
            'series_id' => 'nullable|exists:series,id',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:'.max($effectif, 1).'|max:200',
            'is_active' => 'nullable|boolean',
        ], [
            'capacity.min' => $effectif > 0
                ? "La capacité ne peut pas être inférieure aux {$effectif} élève(s) déjà inscrit(s)."
                : 'La capacité doit être d’au moins 1.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $donnees = $validator->validated();
        // Une case a cocher decochee n'est pas envoyee : sans cette ligne, il
        // etait impossible de desactiver une classe depuis le formulaire.
        $donnees['is_active'] = $request->boolean('is_active');

        $class->update($donnees);

        return redirect()->route('classes.show', $class->id)
            ->with('success', 'Classe mise à jour avec succès !');
    }

    /**
     * Supprimer une classe
     */
    public function destroy(SchoolClass $class)
    {
        try {
            // Vérifier s'il y a des élèves inscrits dans cette classe
            $studentsCount = \App\Models\Student::whereHas('enrollments', function($query) use ($class) {
                $query->where('class_id', $class->id);
            })->count();
            
            if ($studentsCount > 0) {
                return redirect()->back()
                    ->with('error', "Impossible de supprimer la classe {$class->name}. Elle contient encore {$studentsCount} élève(s) inscrit(s). Veuillez d'abord transférer ou désinscrire tous les élèves.");
            }
            
            // Vérifier s'il y a des notes ou présences liées à cette classe
            $gradesCount = \App\Models\Grade::where('class_id', $class->id)->count();
            
            $attendancesCount = \App\Models\Attendance::where('class_id', $class->id)->count();
            
            if ($gradesCount > 0 || $attendancesCount > 0) {
                Log::warning("Suppression de classe avec données: {$class->name} (Notes: {$gradesCount}, Présences: {$attendancesCount})");
            }
            
            // Supprimer d'abord les enregistrements liés (dans l'ordre inverse des dépendances)
            // 1. Présences
            \App\Models\Attendance::where('class_id', $class->id)->delete();
            
            // 2. Notes
            \App\Models\Grade::where('class_id', $class->id)->delete();
            
            // 3. Emplois du temps
            \App\Models\Schedule::where('class_id', $class->id)->delete();
            
            // 4. Inscriptions historiques (si elles existent encore)
            \App\Models\Enrollment::where('class_id', $class->id)->delete();
            
            // 5. Enfin, supprimer la classe
            $className = $class->name;
            $class->delete();
            
            Log::info("Classe supprimée avec succès: {$className}");
            
            return redirect()->route('classes.index')
                ->with('success', "Classe '{$className}' supprimée avec succès !");
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de classe:', [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les classes d'un niveau spécifique
     */
    public function byLevel(Level $level)
    {
        $classes = $level->classes()->with('level')->paginate(10);
        
        return view('classes.index', compact('classes', 'level'));
    }

    /**
     * Afficher les élèves d'une classe spécifique
     */
    public function students(SchoolClass $class)
    {
        $class->load('level');
        
        // Récupérer les élèves inscrits dans cette classe pour l'année en cours
        $students = \App\Models\Student::whereHas('enrollments', function($query) use ($class) {
            $query->where('class_id', $class->id)
                  ->where('status', 'active');
        })->with(['enrollments' => function($query) use ($class) {
            $query->where('class_id', $class->id)
                  ->where('status', 'active');
        }])->orderBy('last_name')->get();
        
        return view('classes.students', compact('class', 'students'));
    }

    /**
     * « Mes classes » : la page d'un enseignant.
     *
     * Elle ne parle ni de capacite d'accueil, ni d'equipe pedagogique a
     * composer — cela ne le regarde pas. Elle dit ce qu'il fait dans chacune
     * de ses classes : sa ou ses matieres, ses heures, ses jours, son
     * effectif, et les gestes qui suivent (appel, notes, fiche, assiduite).
     */
    private function mesClasses()
    {
        $perimetre = \App\Support\PerimetreEnseignant::class;
        $enseignant = $perimetre::enseignant();
        $annee = \App\Models\AcademicYear::where('is_current', true)->first();

        $sesClasses = $perimetre::classes();
        $creneaux = $perimetre::creneaux();

        $classes = SchoolClass::with('level')
            ->whereIn('id', $sesClasses ?: [0])
            ->withCount(['enrollments as effectif' => fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('academicYear', fn ($a) => $a->where('is_current', true))])
            ->orderBy('name')
            ->get();

        // Les matieres, une fois pour toutes : une requete, pas une par classe.
        $matieres = \App\Models\Subject::whereIn('id', $perimetre::matieres() ?: [0])
            ->pluck('name', 'id');

        $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

        // Le professeur principal de chacune : c'est lui, ou un collegue.
        $principaux = DB::table('class_teacher')
            ->join('teachers', 'teachers.id', '=', 'class_teacher.teacher_id')
            ->whereIn('class_teacher.class_id', $sesClasses ?: [0])
            ->where('class_teacher.role', 'principal')
            ->select('class_teacher.class_id', 'teachers.id', 'teachers.first_name', 'teachers.last_name')
            ->get()
            ->keyBy('class_id');

        $fiches = $classes->map(function ($classe) use ($creneaux, $matieres, $jours, $principaux, $enseignant) {
            $siennes = $creneaux->where('class_id', $classe->id);
            $cours = $siennes->where('type', 'course');
            $principal = $principaux[$classe->id] ?? null;

            return [
                'classe' => $classe,
                'matieres' => $cours->pluck('subject_id')->filter()->unique()
                    ->map(fn ($id) => $matieres[$id] ?? null)->filter()->values()->all(),
                'heures' => $cours->count(),
                'jours' => $cours->pluck('day_of_week')->unique()->sort()
                    ->map(fn ($j) => $jours[$j] ?? null)->filter()->values()->all(),
                'principal' => $principal,
                'jeSuisPrincipal' => $principal && $enseignant && (int) $principal->id === (int) $enseignant->id,
            ];
        });

        return view('classes.mes-classes', [
            'enseignant' => $enseignant,
            'annee' => $annee,
            'fiches' => $fiches,
            'eleves' => $classes->sum('effectif'),
            'heures' => $creneaux->where('type', 'course')->count(),
        ]);
    }

    /**
     * La fiche de classe : la liste nominative des eleves, telle qu'on
     * l'affiche au mur et qu'on la garde au dossier.
     *
     * Un enseignant y a droit pour ses classes, et pour elles seules : c'est
     * le document qu'il descend pour faire l'appel ou preparer un conseil.
     */
    public function fiche(SchoolClass $class)
    {
        if (\App\Support\PerimetreEnseignant::estEnseignant()
            && ! in_array($class->id, \App\Support\PerimetreEnseignant::classes(), true)) {
            abort(403, "Cette classe ne fait pas partie de votre emploi du temps.");
        }

        $class->load('level');

        $annee = \App\Models\AcademicYear::where('is_current', true)->first();

        $eleves = Student::whereHas('enrollments', fn ($q) => $q
                ->where('class_id', $class->id)
                ->where('status', 'active'))
            ->with(['parents' => fn ($q) => $q->orderByPivot('is_primary_contact', 'desc')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Le professeur principal signe la fiche : il faut savoir qui c'est.
        $principal = $class->allTeachers()
            ->wherePivot('role', 'principal')
            ->first();

        return view('classes.fiche', [
            'class' => $class,
            'eleves' => $eleves,
            'annee' => $annee,
            'principal' => $principal,
        ]);
    }

    /**
     * API: Obtenir les professeurs par niveau
     */
    public function getTeachersByLevel($levelId)
    {
        try {
            $level = Level::findOrFail($levelId);
            
            // Récupérer les professeurs selon le cycle du niveau
            $teachers = \App\Models\Teacher::where('cycle', $level->cycle)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'teacher_type', 'specialization']);
            
            return response()->json([
                'success' => true,
                'teachers' => $teachers
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des professeurs par niveau:', [
                'level_id' => $levelId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des professeurs'
            ], 500);
        }
    }

    /**
     * API: Obtenir les matières par niveau
     */
    public function getSubjectsByLevel($levelId)
    {
        try {
            $level = Level::findOrFail($levelId);
            
            // Récupérer les matières selon le niveau
            $subjects = \App\Models\Subject::where('level_id', $levelId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            
            return response()->json([
                'success' => true,
                'subjects' => $subjects
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des matières par niveau:', [
                'level_id' => $levelId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des matières'
            ], 500);
        }
    }

    /**
     * Afficher la gestion des professeurs d'une classe
     */
    public function teachers(SchoolClass $class)
    {
        $class->load(['level', 'allTeachers']);
        
        // Récupérer tous les professeurs disponibles pour ce niveau
        // Déterminer le cycle en fonction du type de $class->level
        if (is_object($class->level)) {
            // Si level est un objet Level (relation chargée)
            $cycle = $class->level->cycle;
        } else {
            // Si level est une chaîne (champ direct)
            $cycle = $class->level;
        }
        
        $availableTeachers = \App\Models\Teacher::where('cycle', $cycle)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        
        // Récupérer les professeurs actuellement assignés
        $assignedTeachers = $class->allTeachers()->withPivot('role')->get();
        
        return view('classes.teachers', compact('class', 'availableTeachers', 'assignedTeachers'));
    }

    /**
     * API: Récupérer les classes existantes pour un niveau et suggérer le prochain nom
     */
    public function getExistingClassesForLevel(Request $request, $levelId)
    {
        try {
            $level = Level::findOrFail($levelId);
            $series = $request->get('series_id', $request->get('series'));
            
            // Récupérer les classes existantes pour ce niveau
            $existingClasses = SchoolClass::where('level_id', $levelId)
                ->when($series, function($query) use ($series) {
                    $query->where('series_id', $series);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'series_id']);
            
            // Analyser les noms existants pour déterminer le type d'incrémentation
            $incrementType = null;
            $nextIncrement = null;
            $usedIncrements = [];
            
            if ($existingClasses->count() > 0) {
                // Extraire les dernières parties des noms (incrémentation)
                foreach ($existingClasses as $class) {
                    $name = $class->name;
                    // Pattern pour extraire l'incrémentation à la fin
                    if (preg_match('/(\d+)$/', $name, $matches)) {
                        $incrementType = 'number';
                        $usedIncrements[] = (int) $matches[1];
                    } elseif (preg_match('/([A-Z])$/', $name, $matches)) {
                        $incrementType = 'letter';
                        $usedIncrements[] = $matches[1];
                    }
                }
                
                // Déterminer le prochain incrément
                if ($incrementType === 'number') {
                    $nextIncrement = max($usedIncrements) + 1;
                } elseif ($incrementType === 'letter') {
                    $lastLetter = max($usedIncrements);
                    $nextIncrement = chr(ord($lastLetter) + 1);
                }
            }
            
            // Générer le nom suggéré
            $suggestedName = $level->name;
            if ($level->cycle === 'lycee' && $series) {
                $suggestedName .= " {$series}";
            }
            if ($nextIncrement) {
                $suggestedName .= " {$nextIncrement}";
            }
            
            return response()->json([
                'success' => true,
                'level' => $level,
                'existing_classes' => $existingClasses,
                'increment_type' => $incrementType,
                'next_increment' => $nextIncrement,
                'suggested_name' => $suggestedName,
                'is_first_class' => $existingClasses->count() === 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des classes existantes:', [
                'level_id' => $levelId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes existantes'
            ], 500);
        }
    }

    /**
     * API: Récupérer les enseignants disponibles pour un niveau donné
     */
    public function getTeachersForLevel($levelId)
    {
        try {
            $level = Level::findOrFail($levelId);
            $cycle = $level->cycle;
            
            // Récupérer les enseignants selon le cycle et le type
            $teachersQuery = Teacher::where('cycle', $cycle)
                ->where('status', 'active');
            
            // Pour le primaire, ne prendre que les enseignants généralistes
            if ($cycle === 'primaire') {
                $teachersQuery->where('teacher_type', 'general');
            }
            
            $teachers = $teachersQuery->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'teacher_type', 'specialization', 'cycle']);
            
            return response()->json([
                'success' => true,
                'level' => $level,
                'cycle' => $cycle,
                'teachers' => $teachers,
                'is_primary' => $cycle === 'primaire',
                'max_teachers' => $cycle === 'primaire' ? 1 : null // Limiter à 1 pour le primaire
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des enseignants par niveau:', [
                'level_id' => $levelId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enseignants'
            ], 500);
        }
    }

    /**
     * Mettre à jour les professeurs d'une classe
     */
    public function updateTeachers(Request $request, SchoolClass $class)
    {
        $valide = $request->validate([
            'enseignants' => 'nullable|array',
            'enseignants.*' => 'exists:teachers,id',
            'principal' => 'nullable|exists:teachers,id',
        ]);

        $enseignants = collect($valide['enseignants'] ?? [])->unique()->values();
        $principal = $valide['principal'] ?? null;

        // Le professeur principal doit faire partie de l'équipe : sans cela on
        // désignerait un principal qui n'intervient pas dans la classe.
        if ($principal && ! $enseignants->contains($principal)) {
            return back()->withErrors([
                'principal' => 'Le professeur principal doit faire partie de l’équipe.',
            ]);
        }

        try {
            // Une classe n'a qu'un professeur principal : le rôle se déduit de
            // la désignation, il n'est plus attribué automatiquement au premier
            // arrivé comme le faisait l'ancienne version.
            $roles = $enseignants
                ->mapWithKeys(fn ($id) => [
                    $id => ['role' => (string) $id === (string) $principal ? 'principal' : 'teacher'],
                ])
                ->all();

            $class->allTeachers()->sync($roles);

            return back()->with('success', 'Équipe pédagogique mise à jour.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l’équipe pédagogique', [
                'class_id' => $class->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour de l’équipe pédagogique.');
        }
    }

    /**
     * Définir un professeur principal pour une classe
     */
    public function setPrincipalTeacher(Request $request, SchoolClass $class, \App\Models\Teacher $teacher)
    {
        try {
            // Récupérer tous les professeurs assignés à cette classe
            $assignedTeachers = $class->allTeachers()->withPivot('role')->get();
            
            // Préparer les données pour la synchronisation
            $teacherRoles = [];
            
            foreach ($assignedTeachers as $assignedTeacher) {
                if ($assignedTeacher->id == $teacher->id) {
                    // Définir ce professeur comme principal
                    $teacherRoles[$assignedTeacher->id] = ['role' => 'principal'];
                } else {
                    // Retirer le statut principal des autres professeurs
                    $teacherRoles[$assignedTeacher->id] = ['role' => 'teacher'];
                }
            }
            
            // Mettre à jour les associations
            $class->allTeachers()->sync($teacherRoles);
            
            return redirect()->back()
                ->with('success', "{$teacher->first_name} {$teacher->last_name} est maintenant le professeur principal de cette classe !");
        } catch (\Exception $e) {
            Log::error('Erreur lors de la définition du professeur principal:', [
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la définition du professeur principal');
        }
    }

    /**
     * Retirer le statut de professeur principal d'un enseignant
     */
    public function removePrincipalTeacher(Request $request, SchoolClass $class, \App\Models\Teacher $teacher)
    {
        try {
            // Vérifier que le professeur est bien le principal actuel
            $currentPrincipal = $class->allTeachers()
                ->where('pivot.role', 'principal')
                ->first();
            
            if (!$currentPrincipal || $currentPrincipal->id !== $teacher->id) {
                return redirect()->back()
                    ->with('error', 'Ce professeur n\'est pas le professeur principal actuel');
            }
            
            // Récupérer tous les professeurs assignés à cette classe
            $assignedTeachers = $class->allTeachers()->withPivot('role')->get();
            
            // Préparer les données pour la synchronisation
            $teacherRoles = [];
            
            foreach ($assignedTeachers as $assignedTeacher) {
                if ($assignedTeacher->id == $teacher->id) {
                    // Retirer le statut principal de ce professeur
                    $teacherRoles[$assignedTeacher->id] = ['role' => 'teacher'];
                } else {
                    // Préserver le rôle des autres professeurs
                    $teacherRoles[$assignedTeacher->id] = ['role' => $assignedTeacher->pivot->role];
                }
            }
            
            // Mettre à jour les associations
            $class->allTeachers()->sync($teacherRoles);
            
            return redirect()->back()
                ->with('success', "Le statut de professeur principal a été retiré à {$teacher->first_name} {$teacher->last_name}");
        } catch (\Exception $e) {
            Log::error('Erreur lors du retrait du statut de professeur principal:', [
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors du retrait du statut de professeur principal');
        }
    }
}
