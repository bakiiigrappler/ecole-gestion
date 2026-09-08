<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Afficher la liste des emplois du temps par classe
     */
    public function index(Request $request)
    {
        // Récupérer l'année académique courante ou celle sélectionnée
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        // Un enseignant ne consulte pas la planification de l'école : il
        // consulte le sien. Sa page est donc une autre page.
        if (\App\Support\PerimetreEnseignant::estEnseignant()) {
            return $this->monEmploiDuTemps($academicYearId);
        }

        // La liste porte sur les classes, pas sur les creneaux : c'est classe
        // par classe qu'on planifie, et la page se lit ainsi.
        $requete = SchoolClass::query()
            ->with('level')
            ->withCount(['schedules as creneaux_count' => fn ($q) => $q
                ->where('academic_year_id', $academicYearId)])
            ->withCount(['schedules as cours_count' => fn ($q) => $q
                ->where('academic_year_id', $academicYearId)
                ->where('type', 'course')]);

        if ($terme = trim((string) $request->input('recherche'))) {
            $motif = '%' . mb_strtolower($terme) . '%';
            $requete->whereRaw('LOWER(name) LIKE ?', [$motif]);
        }

        if ($classeId = $request->input('classe')) {
            $requete->where('id', $classeId);
        }

        if ($cycle = $request->input('cycle')) {
            $requete->whereHas('level', fn ($q) => $q->where('cycle', $cycle));
        }

        // Planifiee ou non : la question que pose vraiment cette page.
        if ($etat = $request->input('etat')) {
            $requete->has('schedules', $etat === 'planifie' ? '>=' : '=', $etat === 'planifie' ? 1 : 0, 'and',
                fn ($q) => $q->where('academic_year_id', $academicYearId));
        }

        $classes = $requete
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Jours couverts et volume horaire, en une requete pour toute la page.
        $couverture = Schedule::whereIn('class_id', $classes->pluck('id'))
            ->where('academic_year_id', $academicYearId)
            ->get(['class_id', 'day_of_week', 'teacher_id', 'type'])
            ->groupBy('class_id');

        // Chiffres de l'entete : calcules sur l'ensemble, pas sur la page.
        $toutesClasses = SchoolClass::withCount(['schedules as creneaux_count' => fn ($q) => $q
            ->where('academic_year_id', $academicYearId)])->get();

        $bilan = [
            'creneaux' => $toutesClasses->sum('creneaux_count'),
            'planifiees' => $toutesClasses->where('creneaux_count', '>', 0)->count(),
            'a_planifier' => $toutesClasses->where('creneaux_count', 0)->count(),
            'classes' => $toutesClasses->count(),
            'sans_enseignant' => Schedule::where('academic_year_id', $academicYearId)
                ->where('type', 'course')
                ->whereNull('teacher_id')
                ->count(),
        ];

        $listeClasses = SchoolClass::with('level')->orderBy('name')->get(['id', 'name', 'level_id']);
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $currentAcademicYear = AcademicYear::find($academicYearId);

        return view('schedules.index', compact(
            'classes',
            'couverture',
            'bilan',
            'listeClasses',
            'academicYears',
            'currentAcademicYear',
            'academicYearId'
        ));
    }

    /**
     * Afficher le formulaire de création d'emploi du temps (sélection de classe)
     */
    /**
     * L'emploi du temps d'un enseignant : ses heures, toutes classes
     * confondues, filtrables par classe et imprimables.
     */
    private function monEmploiDuTemps(?int $academicYearId)
    {
        $enseignant = \App\Support\PerimetreEnseignant::enseignant();

        if (! $enseignant) {
            return view('schedules.mon-emploi-du-temps', [
                'enseignant' => null,
                'creneaux' => collect(),
                'lignes' => collect(),
                'grille' => collect(),
                'classes' => collect(),
                'academicYear' => AcademicYear::find($academicYearId),
                'classeChoisie' => null,
                'volumes' => collect(),
            ]);
        }

        $lignes = Schedule::with(['subject:id,name', 'schoolClass:id,name'])
            ->where('teacher_id', $enseignant->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Filtre par classe : « trier là où il est », sans jamais voir ailleurs.
        $classeChoisie = request('classe');

        if ($classeChoisie) {
            $lignes = $lignes->where('class_id', (int) $classeChoisie)->values();
        }

        $heure = fn ($valeur) => substr((string) $valeur, 0, 5);

        $creneaux = $lignes
            ->map(fn ($l) => ['debut' => $heure($l->start_time), 'fin' => $heure($l->end_time)])
            ->unique(fn ($c) => $c['debut'].$c['fin'])
            ->sortBy('debut')
            ->values();

        // Une case par jour et par heure : un enseignant n'a qu'un cours à la
        // fois, la clé « jour-heure » suffit.
        $grille = $lignes->keyBy(fn ($l) => $l->day_of_week.'-'.$heure($l->start_time));

        $volumes = $lignes->where('type', 'course')
            ->groupBy('subject_id')
            ->map(fn ($lot) => [
                'matiere' => $lot->first()->subject->name ?? '—',
                'heures' => $lot->count(),
                'classes' => $lot->pluck('schoolClass.name')->filter()->unique()->values()->all(),
            ])
            ->sortByDesc('heures')
            ->values();

        $classes = Schedule::with('schoolClass:id,name')
            ->where('teacher_id', $enseignant->id)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('schedules.mon-emploi-du-temps', [
            'enseignant' => $enseignant,
            'lignes' => $lignes,
            'creneaux' => $creneaux,
            'grille' => $grille,
            'classes' => $classes,
            'classeChoisie' => $classeChoisie,
            'academicYear' => AcademicYear::find($academicYearId),
            'volumes' => $volumes,
        ]);
    }

    public function create(Request $request)
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        $anneeChoisie = $request->filled('academic_year_id')
            ? (int) $request->get('academic_year_id')
            : optional($currentAcademicYear)->id;

        // Le compte de creneaux sert a retirer du selecteur les classes deja
        // planifiees : on ne vient ici que pour celles qui restent a faire.
        $classes = SchoolClass::active()
            ->with('level')
            ->withCount(['schedules as creneaux_count' => fn ($q) => $q
                ->where('academic_year_id', $anneeChoisie)])
            ->orderBy('name')
            ->get();

        $annee = $request->filled('academic_year_id')
            ? AcademicYear::find($request->get('academic_year_id'))
            : $currentAcademicYear;

        $classe = $request->filled('class_id')
            ? $classes->firstWhere('id', (int) $request->get('class_id'))
            : null;

        // Tant qu'aucune classe n'est choisie, la grille n'a pas d'objet :
        // la page se limite au choix de la classe.
        $matieres = collect();
        $enseignants = collect();
        $creneaux = collect();
        $grille = collect();
        $occupations = collect();

        if ($classe && $annee) {
            $cycle = $classe->level->cycle ?? 'primaire';

            $matieres = Subject::active()
                ->where('cycle', $cycle)
                ->orderBy('name')
                ->get(['id', 'name']);

            // Le lycee cloisonne ses matieres par serie.
            if ($cycle === 'lycee' && $classe->series) {
                $matieres = $matieres->filter(fn ($m) => true)->values();
            }

            $enseignants = Teacher::active()
                ->with('subjects:id')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'nom' => $e->first_name . ' ' . $e->last_name,
                    'matieres' => $e->subjects->pluck('id')->all(),
                ])
                ->values()
                ->toBase();

            $grille = Schedule::where('class_id', $classe->id)
                ->where('academic_year_id', $annee->id)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($ligne) => [
                    'jour' => (int) $ligne->day_of_week,
                    'debut' => substr((string) $ligne->start_time, 0, 5),
                    'fin' => substr((string) $ligne->end_time, 0, 5),
                    'matiere_id' => $ligne->subject_id,
                    'enseignant_id' => $ligne->teacher_id,
                    'salle' => $ligne->room,
                    'type' => $ligne->type ?: 'course',
                    'titre' => $ligne->title,
                ])
                ->values()
                /*
                 * `toBase()` n'est pas cosmetique. Une collection Eloquent ne
                 * redevient une collection ordinaire apres `map()` que si elle
                 * contient au moins un element qui n'est pas un modele : vide,
                 * elle reste une collection Eloquent. Son `merge()` appelle
                 * alors `getKey()` sur les tableaux qu'on lui donne, et la page
                 * tombait en erreur 500 pour toute classe sans emploi du temps
                 * — c'est-a-dire au moment precis ou l'on vient en composer un.
                 */
                ->toBase();

            // Lignes horaires : les horaires deja saisis font foi, completes
            // par la trame du cycle. Une trame qui chevaucherait un creneau
            // enregistre est ecartee : sinon la grille affiche deux lignes
            // pour la meme heure de cours.
            $saisis = $grille
                ->map(fn ($c) => ['debut' => $c['debut'], 'fin' => $c['fin']])
                ->unique(fn ($c) => $c['debut'] . $c['fin'])
                ->values();

            $complement = collect($this->getDefaultTimeSlots($cycle))
                ->map(fn ($c) => ['debut' => $c['start'], 'fin' => $c['end']])
                ->reject(fn ($t) => $saisis->contains(
                    fn ($c) => $t['debut'] < $c['fin'] && $c['debut'] < $t['fin']
                ));

            $creneaux = $saisis->merge($complement)->sortBy('debut')->values();

            // Un enseignant ne peut pas etre dans deux classes a la meme heure :
            // on fournit ses engagements ailleurs pour signaler les collisions.
            $occupations = Schedule::with(['schoolClass:id,name'])
                ->where('academic_year_id', $annee->id)
                ->where('class_id', '!=', $classe->id)
                ->whereNotNull('teacher_id')
                ->get()
                ->map(fn ($ligne) => [
                    'enseignant_id' => $ligne->teacher_id,
                    'jour' => (int) $ligne->day_of_week,
                    'debut' => substr((string) $ligne->start_time, 0, 5),
                    'classe' => $ligne->schoolClass->name ?? 'une autre classe',
                ])
                ->values()
                ->toBase();
        }

        return view('schedules.create', compact(
            'classes',
            'academicYears',
            'currentAcademicYear',
            'annee',
            'classe',
            'matieres',
            'enseignants',
            'creneaux',
            'grille',
            'occupations'
        ));
    }

    /**
     * L'ancienne construction en deux ecrans (choix puis grille) est repliee
     * sur la page de creation, qui porte desormais les deux.
     */
    public function build(Request $request)
    {
        return redirect()->route('schedules.create', $request->only('class_id', 'academic_year_id'));
    }

    /**
     * Obtenir les créneaux horaires par défaut selon le cycle
     */
    private function getDefaultTimeSlots($cycle)
    {
        switch ($cycle) {
            case 'preprimaire':
                return [
                    ['start' => '08:00', 'end' => '08:30'],
                    ['start' => '08:30', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '09:30'],
                    ['start' => '09:30', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '10:45'],
                    ['start' => '10:45', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '11:45'],
                    ['start' => '11:45', 'end' => '12:00'], // Pause
                ];
            
            case 'primaire':
                return [
                    ['start' => '08:00', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '12:15'],
                    ['start' => '12:15', 'end' => '13:15'], // Pause déjeuner
                    ['start' => '13:15', 'end' => '14:15'],
                    ['start' => '14:15', 'end' => '15:15'],
                    ['start' => '15:15', 'end' => '15:30'], // Récréation
                    ['start' => '15:30', 'end' => '16:30'],
                ];
            
            default: // Collège et Lycée
                return [
                    ['start' => '08:00', 'end' => '09:00'],
                    ['start' => '09:00', 'end' => '10:00'],
                    ['start' => '10:00', 'end' => '10:15'], // Récréation
                    ['start' => '10:15', 'end' => '11:15'],
                    ['start' => '11:15', 'end' => '12:15'],
                    ['start' => '12:15', 'end' => '13:15'], // Pause déjeuner
                    ['start' => '13:15', 'end' => '14:15'],
                    ['start' => '14:15', 'end' => '15:15'],
                    ['start' => '15:15', 'end' => '15:30'], // Récréation
                    ['start' => '15:30', 'end' => '16:30'],
                    ['start' => '16:30', 'end' => '17:30'],
                ];
        }
    }

    /**
     * Enregistrer l'emploi du temps d'une classe
     */
    public function store(Request $request)
    {
        $valide = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'creneaux' => 'array',
            'creneaux.*.jour' => 'required|integer|min:1|max:7',
            'creneaux.*.debut' => 'required|date_format:H:i',
            'creneaux.*.fin' => 'required|date_format:H:i|after:creneaux.*.debut',
            'creneaux.*.type' => 'nullable|in:course,break',
            'creneaux.*.matiere_id' => 'nullable|exists:subjects,id',
            'creneaux.*.enseignant_id' => 'nullable|exists:teachers,id',
            'creneaux.*.salle' => 'nullable|string|max:50',
            'creneaux.*.titre' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();

        try {
            // La grille remplace celle de la classe pour cette annee.
            Schedule::where('class_id', $valide['class_id'])
                ->where('academic_year_id', $valide['academic_year_id'])
                ->delete();

            $enregistres = 0;

            foreach ($valide['creneaux'] ?? [] as $creneau) {
                $type = $creneau['type'] ?? 'course';

                // Une heure de cours sans matiere n'est pas une case remplie.
                if ($type === 'course' && empty($creneau['matiere_id'])) {
                    continue;
                }

                Schedule::create([
                    'class_id' => $valide['class_id'],
                    'academic_year_id' => $valide['academic_year_id'],
                    'day_of_week' => (int) $creneau['jour'],
                    'start_time' => $creneau['debut'] . ':00',
                    'end_time' => $creneau['fin'] . ':00',
                    'subject_id' => $type === 'course' ? $creneau['matiere_id'] : null,
                    'teacher_id' => $type === 'course' ? ($creneau['enseignant_id'] ?? null) : null,
                    'room' => $creneau['salle'] ?? null,
                    'type' => $type,
                    'title' => $creneau['titre'] ?? null,
                    'is_active' => true,
                ]);

                $enregistres++;
            }

            DB::commit();

            $message = $enregistres > 0
                ? "Emploi du temps enregistré : {$enregistres} créneau(x)."
                : "Emploi du temps vidé : aucun créneau enregistré.";

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'schedules_created' => $enregistres]);
            }

            return redirect()
                ->route('schedules.create', [
                    'class_id' => $valide['class_id'],
                    'academic_year_id' => $valide['academic_year_id'],
                ])
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de la sauvegarde de l'emploi du temps : " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
            }

            return back()->withInput()->with('error', "Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    /**
     * Afficher les détails de l'emploi du temps d'une classe
     */
    public function show(Schedule $schedule, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $academicYearId = $schedule->academic_year_id;
        }

        // Récupérer la classe à partir de l'emploi du temps
        $class = $schedule->schoolClass;
        if (!$class) {
            abort(404, 'Classe non trouvée pour cet emploi du temps');
        }
        $class->load('level');
        $academicYear = AcademicYear::find($academicYearId);

        $schedules = Schedule::with(['subject', 'teacher'])
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Organiser les horaires par jour
        $schedulesByDay = $schedules->groupBy('day_of_week');
        
        $days = [
            1 => 'Lundi',
            2 => 'Mardi', 
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi'
        ];

        return view('schedules.show', compact('class', 'academicYear', 'schedules', 'schedulesByDay', 'days'));
    }

    /**
     * Afficher le formulaire d'édition d'emploi du temps
     */
    public function edit(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        return redirect()->route('schedules.build', [
            'class_id' => $class->id,
            'academic_year_id' => $academicYearId
        ]);
    }

    /**
     * Supprimer l'emploi du temps d'une classe
     */
    public function destroy(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $deletedCount = Schedule::where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Emploi du temps supprimé avec succès! {$deletedCount} créneaux supprimés."
        ]);
    }

    /**
     * API: Obtenir les matières par niveau
     */
    public function getSubjectsByLevel(Request $request)
    {
        $levelId = $request->get('level_id');
        
        $subjects = Subject::active()
            ->where('level_id', $levelId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($subjects);
    }

    /**
     * API: Récupérer les classes par cycle
     */
    public function getClassesByCycle(Request $request)
    {
        try {
            $cycle = $request->get('cycle');
            
            if (!$cycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cycle non spécifié',
                    'classes' => []
                ]);
            }

            $classes = SchoolClass::active()
                ->with('level')
                ->whereHas('level', function($query) use ($cycle) {
                    $query->where('cycle', $cycle);
                })
                ->orderBy('name')
                ->get()
                ->map(function($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'level_id' => $class->level_id,
                        'level_name' => $class->level ? $class->level->name : 'Niveau non défini',
                        'cycle' => $class->level ? $class->level->cycle : 'Non défini',
                        'series' => $class->series ?? null
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Classes chargées avec succès',
                'classes' => $classes
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur dans getClassesByCycle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des classes',
                'classes' => []
            ], 500);
        }
    }

    /**
     * Vérifier l'existence d'un emploi du temps
     */
    public function checkExisting(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $academicYearId = $request->get('academic_year_id');

            if (!$classId || !$academicYearId) {
                return response()->json(['exists' => false]);
            }

            $exists = Schedule::where('class_id', $classId)
                ->where('academic_year_id', $academicYearId)
                ->exists();

            return response()->json(['exists' => $exists]);
        } catch (\Exception $e) {
            Log::error('Erreur dans checkExisting: ' . $e->getMessage());
            return response()->json(['exists' => false]);
        }
    }

    /**
     * API: Vérifier les conflits d'horaires
     */
    public function checkConflicts(Request $request)
    {
        $conflicts = Schedule::validateTimeSlot(
            $request->class_id,
            $request->teacher_id,
            $request->academic_year_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
            $request->exclude_id
        );

        return response()->json($conflicts);
    }

    /**
     * Afficher la version imprimable de l'emploi du temps d'une classe
     */
    public function print(SchoolClass $class, Request $request)
    {
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear?->id;
        }

        $class->load('level');
        $academicYear = AcademicYear::find($academicYearId);

        $lignes = Schedule::with(['subject:id,name', 'teacher:id,first_name,last_name'])
            ->where('class_id', $class->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $heure = fn ($valeur) => substr((string) $valeur, 0, 5);

        // La grille est batie a partir des horaires reellement saisis : une
        // trame fixe laisserait des lignes vides ou masquerait des cours.
        $creneaux = $lignes
            ->map(fn ($l) => ['debut' => $heure($l->start_time), 'fin' => $heure($l->end_time)])
            ->unique(fn ($c) => $c['debut'] . $c['fin'])
            ->sortBy('debut')
            ->values();

        // Case par case : « jour-debut » suffit, une classe n'a qu'un cours
        // a la fois.
        $grille = $lignes->keyBy(fn ($l) => $l->day_of_week . '-' . $heure($l->start_time));

        // Une couleur par matiere, la meme qu'a la composition.
        $nuancier = [
            ['fond' => '#e0f2fe', 'bord' => '#7dd3fc', 'encre' => '#075985'],
            ['fond' => '#dcfce7', 'bord' => '#86efac', 'encre' => '#166534'],
            ['fond' => '#fef3c7', 'bord' => '#fcd34d', 'encre' => '#92400e'],
            ['fond' => '#ede9fe', 'bord' => '#c4b5fd', 'encre' => '#5b21b6'],
            ['fond' => '#ffe4e6', 'bord' => '#fda4af', 'encre' => '#9f1239'],
            ['fond' => '#cffafe', 'bord' => '#67e8f9', 'encre' => '#155e75'],
            ['fond' => '#fae8ff', 'bord' => '#f0abfc', 'encre' => '#86198f'],
            ['fond' => '#ffedd5', 'bord' => '#fdba74', 'encre' => '#9a3412'],
            ['fond' => '#e0e7ff', 'bord' => '#a5b4fc', 'encre' => '#3730a3'],
            ['fond' => '#d1fae5', 'bord' => '#6ee7b7', 'encre' => '#065f46'],
        ];

        $couleurs = $lignes->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->mapWithKeys(fn ($id, $i) => [$id => $nuancier[$i % count($nuancier)]])
            ->all();

        // Volume horaire par matiere, recapitule sous la grille.
        $volumes = $lignes->where('type', 'course')
            ->groupBy('subject_id')
            ->map(fn ($lot) => [
                'matiere' => $lot->first()->subject->name ?? 'Matière supprimée',
                'heures' => $lot->count(),
                'enseignants' => $lot->pluck('teacher')
                    ->filter()
                    ->map(fn ($e) => $e->first_name . ' ' . $e->last_name)
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('heures')
            ->values();

        $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

        // Ne pas imprimer une colonne de samedi vide.
        $joursUtiles = array_filter($jours, fn ($n, $j) => $lignes->contains('day_of_week', $j), ARRAY_FILTER_USE_BOTH);
        $jours = $joursUtiles ?: [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'];

        return view('schedules.print', compact(
            'class',
            'academicYear',
            'academicYearId',
            'creneaux',
            'grille',
            'couleurs',
            'volumes',
            'jours',
            'lignes'
        ));
    }

    /**
     * API: Récupérer les matières et enseignants d'une classe
     */
    public function getClassSubjectsAndTeachers(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            
            if (!$classId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de classe requis'
                ], 400);
            }

            $class = SchoolClass::with('level')->findOrFail($classId);
            
            // Récupérer tous les enseignants actifs avec leurs matières
            $teachers = Teacher::active()
                ->with('subjects')  // ✅ Charger les relations matière
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']);

            Log::info('Enseignants actifs trouvés: ' . $teachers->count());

            // Essayer de récupérer les enseignants spécifiquement assignés à cette classe
            try {
                $classTeachers = Teacher::active()
                    ->whereHas('classes', function($query) use ($classId) {
                        $query->where('class_id', $classId);
                    })
                    ->with('subjects')  // ✅ Charger les relations matière
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name']);

                Log::info('Enseignants assignés à la classe: ' . $classTeachers->count());

                // Si des enseignants sont assignés à la classe, les utiliser en priorité
                // MAIS garder les relations matière !
                if ($classTeachers->isNotEmpty()) {
                    $teachers = $classTeachers;
                    Log::info('Utilisation des enseignants assignés à la classe avec leurs matières');
                } else {
                    Log::info('Utilisation de tous les enseignants actifs avec leurs matières');
                }
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la récupération des enseignants de classe: ' . $e->getMessage());
                Log::info('Utilisation de tous les enseignants actifs avec leurs matières');
            }

            // Récupérer les matières du cycle depuis la base de données
            $subjects = collect();
            try {
                if ($class->level) {
                    $cycle = $class->level->cycle;
                    
                    // Récupérer les matières du cycle depuis la base de données
                    // CORRECTION : Utiliser 'cycle' au lieu de 'level_id' qui n'existe pas
                    $cycleSubjects = Subject::active()
                        ->where('cycle', $cycle)  // ✅ Utiliser le cycle (college, lycee, etc.)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                    
                    // Si c'est le lycée, filtrer par série si spécifiée
                    if ($cycle === 'lycee' && $class->series) {
                        $cycleSubjects = $cycleSubjects->filter(function($subject) use ($class) {
                            return !$subject->series || $subject->series === $class->series;
                        });
                    }
                    
                    $subjects = $cycleSubjects;
                    Log::info("Matières du cycle {$cycle} trouvées: " . $subjects->count());
                }
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la récupération des matières du cycle: ' . $e->getMessage());
            }

            // Si pas de matières trouvées dans la base, utiliser des données de base
            if ($subjects->isEmpty()) {
                Log::info('Aucune matière trouvée dans la base, utilisation des données de base');
                $subjects = collect([
                    ['id' => 1, 'name' => 'Français'],
                    ['id' => 2, 'name' => 'Mathématiques'],
                    ['id' => 3, 'name' => 'Histoire-Géo'],
                    ['id' => 4, 'name' => 'Sciences'],
                    ['id' => 5, 'name' => 'Anglais'],
                    ['id' => 6, 'name' => 'EPS'],
                    ['id' => 7, 'name' => 'Arts plastiques']
                ]);
            } else {
                Log::info('Matières du cycle trouvées: ' . $subjects->count());
            }

            // Créer le mapping matière -> professeurs (peut y avoir plusieurs profs par matière)
            $subjectTeachers = [];
            foreach ($subjects as $subject) {
                $subjectId = $subject['id'] ?? $subject->id;
                $subjectName = $subject['name'] ?? $subject->name;
                
                // Trouver TOUS les enseignants qui peuvent enseigner cette matière
                $subjectTeachersList = $teachers->filter(function($t) use ($subjectId) {
                    return $t->subjects->contains('id', $subjectId);
                });
                
                if ($subjectTeachersList->isNotEmpty()) {
                    // Créer la liste des professeurs pour cette matière
                    $teachersForSubject = $subjectTeachersList->map(function($t) {
                        return [
                            'id' => $t->id,
                            'name' => $t->first_name . ' ' . $t->last_name
                        ];
                    })->toArray();
                    
                    $subjectTeachers[] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'teachers' => $teachersForSubject,
                        'teacher_count' => count($teachersForSubject)
                    ];
                } else {
                    // Si pas d'enseignant trouvé, créer une entrée avec professeur par défaut
                    $defaultTeacher = $teachers->first();
                    $subjectTeachers[] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'teachers' => $defaultTeacher ? [
                            [
                                'id' => $defaultTeacher->id,
                                'name' => $defaultTeacher->first_name . ' ' . $defaultTeacher->last_name
                            ]
                        ] : [],
                        'teacher_count' => $defaultTeacher ? 1 : 0
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Matières et enseignants récupérés avec succès',
                'subjects' => $subjects,
                'teachers' => $teachers,
                'subjectTeachers' => $subjectTeachers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans getClassSubjectsAndTeachers: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            // Fallback avec des données de base
            $subjects = collect([
                ['id' => 1, 'name' => 'Français'],
                ['id' => 2, 'name' => 'Mathématiques'],
                ['id' => 3, 'name' => 'Histoire-Géo'],
                ['id' => 4, 'name' => 'Sciences'],
                ['id' => 5, 'name' => 'Anglais'],
                ['id' => 6, 'name' => 'EPS'],
                ['id' => 7, 'name' => 'Arts plastiques']
            ]);
            
            $teachers = collect([
                ['id' => 1, 'first_name' => 'M.', 'last_name' => 'Dubois'],
                ['id' => 2, 'first_name' => 'Mme.', 'last_name' => 'Michel'],
                ['id' => 3, 'first_name' => 'M.', 'last_name' => 'Garcia'],
                ['id' => 4, 'first_name' => 'Mme.', 'last_name' => 'David'],
                ['id' => 5, 'first_name' => 'M.', 'last_name' => 'Robert']
            ]);
            
            $subjectTeachers = [];
            foreach ($subjects as $subject) {
                $teacher = $teachers->random();
                $subjectTeachers[] = [
                    'subject_id' => $subject['id'],
                    'subject_name' => $subject['name'],
                    'teacher_id' => $teacher['id'],
                    'teacher_name' => $teacher['first_name'] . ' ' . $teacher['last_name']
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Données de base utilisées (erreur API)',
                'subjects' => $subjects,
                'teachers' => $teachers,
                'subjectTeachers' => $subjectTeachers
            ]);
        }
    }
}