<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::query()
            ->with([
                // Inscription en cours : une seule, avec sa classe et son niveau.
                'enrollments' => function ($q) {
                    $q->where('status', 'active')
                      ->with(['schoolClass.level', 'academicYear'])
                      ->latest()
                      ->limit(1);
                },
                'parents:id,first_name,last_name',
            ]);

        /*
         * Un enseignant ne voit que les élèves de ses classes, et il les lit
         * classe par classe : une liste alphabétique de 724 noms, ou même de
         * 111, ne lui sert à rien. Sa page est donc une autre page.
         */
        if (\App\Support\PerimetreEnseignant::estEnseignant()) {
            return $this->mesEleves($request);
        }

        // --- Recherche textuelle : nom, prénom ou matricule -----------------
        if ($terme = trim((string) $request->input('search'))) {
            $motif = '%'.mb_strtolower($terme).'%';

            $query->where(function ($q) use ($motif) {
                $q->whereRaw('LOWER(first_name) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(last_name) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(student_id) LIKE ?', [$motif])
                  // Retrouver un eleve par son numero ou son courriel.
                  ->orWhereRaw('LOWER(phone) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$motif]);
            });
        }

        // --- Filtres portés par l'élève lui-même ---------------------------
        if ($statut = $request->input('status')) {
            $query->where('status', $statut);
        }

        if ($statutActuel = $request->input('current_status')) {
            $query->where('current_status', $statutActuel);
        }

        // --- Filtres portés par l'inscription de l'année en cours ----------
        $inscriptionActive = fn ($q) => $q->where('status', 'active');

        if ($cycle = $request->input('cycle')) {
            $query->whereHas('enrollments', function ($q) use ($cycle, $inscriptionActive) {
                $inscriptionActive($q)->whereHas('schoolClass.level', fn ($n) => $n->where('cycle', $cycle));
            });
        }

        if ($niveau = $request->input('level')) {
            $query->whereHas('enrollments', function ($q) use ($niveau, $inscriptionActive) {
                $inscriptionActive($q)->whereHas('schoolClass', fn ($c) => $c->where('level_id', $niveau));
            });
        }

        if ($classe = $request->input('class')) {
            $query->whereHas('enrollments', function ($q) use ($classe, $inscriptionActive) {
                $inscriptionActive($q)->where('class_id', $classe);
            });
        }

        if ($typeInscription = $request->input('student_status')) {
            $query->whereHas('enrollments', function ($q) use ($typeInscription, $inscriptionActive) {
                $inscriptionActive($q)->where('student_status', $typeInscription);
            });
        }

        // Inscrit ou non pour l'année scolaire en cours.
        if ($etatInscription = $request->input('enrollment_status')) {
            $anneeCourante = AcademicYear::where('is_current', true)->value('id');

            $surAnneeCourante = function ($q) use ($anneeCourante) {
                $q->where('status', 'active');

                if ($anneeCourante) {
                    $q->where('academic_year_id', $anneeCourante);
                }
            };

            $etatInscription === 'enrolled'
                ? $query->whereHas('enrollments', $surAnneeCourante)
                : $query->whereDoesntHave('enrollments', $surAnneeCourante);
        }

        // --- Pagination : 10 lignes par page, filtres conservés -------------
        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $students = $query->orderBy('created_at', 'desc')
            ->paginate($parPage)
            ->withQueryString();

        // --- Données des listes déroulantes --------------------------------
        $levels = Level::select('id', 'name', 'code', 'cycle')->active()->orderBy('order')->get();
        $classes = SchoolClass::select('id', 'name', 'level_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // --- Statistiques ---------------------------------------------------
        $studentsByCurrentStatus = [
            'actifs' => Student::where('status', 'active')->count(),
            'anciens' => Student::where('status', 'inactive')->count(),
            'avec_redoublement' => Student::where('total_redoublements', '>', 0)->count(),
        ];

        return view('students.index', compact(
            'students',
            'levels',
            'classes',
            'studentsByCurrentStatus'
        ));
    }

    /**
     * « Mes élèves » : la liste d'un enseignant, groupée par classe.
     *
     * Un enseignant ne cherche pas un élève dans un annuaire : il ouvre une
     * classe. La page présente donc un onglet par classe, l'effectif en
     * regard, et sur chaque onglet la liste nominative de cette classe-là.
     */
    private function mesEleves(Request $request)
    {
        $perimetre = \App\Support\PerimetreEnseignant::class;
        $enseignant = $perimetre::enseignant();
        $sesClasses = $perimetre::classes();

        $classes = SchoolClass::with('level')
            ->whereIn('id', $sesClasses ?: [0])
            ->orderBy('name')
            ->get();

        // La classe ouverte : celle demandée, sinon la première.
        $ouverte = $request->input('class');
        $ouverte = $classes->contains('id', (int) $ouverte) ? (int) $ouverte : $classes->first()?->id;

        $terme = trim((string) $request->input('search'));

        $eleves = Student::query()
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereIn('class_id', $sesClasses ?: [0]))
            ->with([
                'enrollments' => fn ($q) => $q
                    ->where('status', 'active')
                    ->whereIn('class_id', $sesClasses ?: [0])
                    ->latest()
                    ->limit(1),
                'parents:id,first_name,last_name,phone',
            ])
            ->when($terme !== '', function ($q) use ($terme) {
                $motif = '%'.mb_strtolower($terme).'%';

                $q->where(function ($w) use ($motif) {
                    $w->whereRaw('LOWER(first_name) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(student_id) LIKE ?', [$motif]);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->groupBy(fn ($eleve) => $eleve->enrollments->first()?->class_id);

        return view('students.mes-eleves', [
            'enseignant' => $enseignant,
            'classes' => $classes,
            'eleves' => $eleves,
            'ouverte' => $ouverte,
            'terme' => $terme,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $levels = Level::active()->orderBy('order')->get();
        $classes = SchoolClass::with('level')->where('is_active', true)->get();
        // Toutes les annees, la plus recente d'abord : le formulaire met en
        // avant l'annee en cours mais permet d'inscrire sur une autre.
        $academicYears = AcademicYear::orderByDesc('is_current')->orderByDesc('name')->get();
        
        // Apercu du matricule a venir : lecture seule, sans effet de bord.
        $prochainMatricule = Student::generateStudentId();

        // Responsables deja connus : on rattache plutot que de ressaisir.
        $parents = \App\Models\ParentModel::withCount('students')
            ->orderBy('last_name')->orderBy('first_name')->get();

        return view('students.create', compact(
            'levels', 'classes', 'academicYears', 'prochainMatricule', 'parents'
        ));
    }

    /**
     * Rattache les responsables legaux saisis a la creation d'un eleve.
     *
     * Chaque ligne designe soit un parent deja connu, soit une fiche a creer.
     * Le lien de parente et les autorisations sont portes par le pivot : ils
     * decrivent la relation a CET enfant, pas la personne.
     */
    private function rattacherLesResponsables(Student $student, array $responsables): void
    {
        $liens = [];

        foreach ($responsables as $responsable) {
            if (($responsable['mode'] ?? null) === 'nouveau') {
                $parent = \App\Models\ParentModel::create([
                    'first_name' => $responsable['first_name'],
                    'last_name' => $responsable['last_name'],
                    'gender' => $responsable['gender'],
                    'phone' => $responsable['phone'],
                    'address' => $student->address,
                ]);

                $parentId = $parent->id;
            } else {
                $parentId = $responsable['parent_id'] ?? null;
            }

            if (! $parentId) {
                continue;
            }

            $liens[$parentId] = [
                'relationship_type' => $responsable['relationship_type'],
                'is_primary_contact' => (bool) ($responsable['is_primary_contact'] ?? false),
                'lives_with_student' => (bool) ($responsable['lives_with_student'] ?? false),
                'can_pickup' => (bool) ($responsable['can_pickup'] ?? false),
            ];
        }

        if ($liens) {
            $student->parents()->sync($liens);
        }
    }

    /**
     * Display the specified resource (student details page)
     */
    public function show(Student $student)
    {
        $student->load([
            'parents',
            'enrollments.schoolClass.level',
            'enrollments.academicYear'
        ]);
        
        return view('students.show', compact('student'));
    }

    /**
     * Get student details for modal view (AJAX)
     */
    public function getStudentDetails($id)
    {
        $student = Student::with([
            'parents',
            'enrollments' => function($query) {
                $query->where('status', 'active')->with(['schoolClass.level', 'academicYear']);
            }
        ])->findOrFail($id);
        
        // Forcer le rechargement des relations
        $student->refresh();
        $student->load([
            'enrollments' => function($query) {
                $query->where('status', 'active')->with(['schoolClass.level', 'academicYear']);
            }
        ]);

        // Récupérer l'inscription active
        $activeEnrollment = $student->enrollments->first();
        
        // Debug logging
        Log::info('Student details request', [
            'student_id' => $id,
            'enrollments_count' => $student->enrollments->count(),
            'active_enrollment' => $activeEnrollment ? 'exists' : 'null',
            'class_name' => $activeEnrollment ? $activeEnrollment->schoolClass->name ?? 'null' : 'no_enrollment',
            'level_name' => $activeEnrollment && $activeEnrollment->schoolClass ? $activeEnrollment->schoolClass->level->name ?? 'null' : 'no_class'
        ]);

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth->format('d/m/Y'),
                'age' => $student->age,
                'gender' => $student->gender,
                'place_of_birth' => $student->place_of_birth,
                'address' => $student->address,
                'emergency_contact' => $student->emergency_contact,
                'medical_conditions' => $student->medical_conditions,
                'enrollment_date' => $student->enrollment_date->format('d/m/Y'),
                'status' => $student->status,
                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                'parents' => $student->parents->map(function($parent) {
                    return [
                        'first_name' => $parent->first_name,
                        'last_name' => $parent->last_name,
                        'phone' => $parent->phone,
                        'email' => $parent->email,
                        'address' => $parent->address,
                        'profession' => $parent->profession
                    ];
                }),
                'current_enrollment' => $activeEnrollment ? [
                    'class_name' => $activeEnrollment->schoolClass->name ?? null,
                    'level_name' => $activeEnrollment->schoolClass->level->name ?? null,
                    'academic_year' => $activeEnrollment->academicYear->name ?? null
                ] : null
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log des données reçues
        Log::info('Store method called', [
            'all_data' => $request->all(),
            'has_file' => $request->hasFile('photo'),
            'create_enrollment' => $request->input('create_enrollment')
        ]);
        
        $validated = $request->validate([
            // Le matricule est toujours généré automatiquement, pas de validation nécessaire
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'place_of_birth' => 'nullable|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'medical_conditions' => 'nullable|string',
            // Une inaptitude sans motif n'est pas exploitable par l'equipe :
            // la base le refuse aussi, par contrainte.
            'fitness_status' => 'nullable|in:apte,inapte',
            'unfitness_reason' => 'nullable|required_if:fitness_status,inapte|string|max:1000',
            'enrollment_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:active,inactive,graduated,transferred',
            
            // Inscription facultative : si la case est cochee, l'annee et la
            // classe deviennent obligatoires. Sans required_if, un champ non
            // transmis creait l'eleve sans inscription et sans le signaler.
            'create_enrollment' => 'nullable|string|in:on',
            'academic_year_id' => 'required_if:create_enrollment,on|nullable|exists:academic_years,id',
            'class_id' => 'required_if:create_enrollment,on|nullable|exists:classes,id',
            // Responsables legaux, facultatifs : soit un parent deja connu,
            // soit une fiche creee au vol.
            'responsables' => 'nullable|array',
            'responsables.*.mode' => 'required|in:existant,nouveau',
            'responsables.*.parent_id' => 'required_if:responsables.*.mode,existant|nullable|exists:parents,id',
            'responsables.*.first_name' => 'required_if:responsables.*.mode,nouveau|nullable|string|max:255',
            'responsables.*.last_name' => 'required_if:responsables.*.mode,nouveau|nullable|string|max:255',
            'responsables.*.gender' => 'required_if:responsables.*.mode,nouveau|nullable|in:male,female',
            'responsables.*.phone' => 'required_if:responsables.*.mode,nouveau|nullable|string|max:255',
            'responsables.*.relationship_type' => 'required|in:father,mother,guardian,other',
            'responsables.*.is_primary_contact' => 'nullable|boolean',
            'responsables.*.lives_with_student' => 'nullable|boolean',
            'responsables.*.can_pickup' => 'nullable|boolean',
        ], [
            'academic_year_id.required_if' => 'Choisissez une année scolaire pour inscrire l’élève.',
            'class_id.required_if' => 'Choisissez une classe pour inscrire l’élève.',
            'responsables.*.parent_id.required_if' => 'Choisissez un responsable dans la liste.',
            'responsables.*.first_name.required_if' => 'Le prénom du responsable est obligatoire.',
            'responsables.*.last_name.required_if' => 'Le nom du responsable est obligatoire.',
            'responsables.*.gender.required_if' => 'Le sexe du responsable est obligatoire.',
            'responsables.*.phone.required_if' => 'Le téléphone du responsable est obligatoire.',
        ]);

        // Générer automatiquement le matricule - toujours obligatoire
        $validated['student_id'] = Student::generateStudentId();

        // Définir le statut par défaut
        $validated['status'] = $validated['status'] ?? 'active';

        // Handle photo upload avec débogage
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {
                $validated['photo'] = $file->store('students/photos', 'public');
                Log::info('Photo uploadée avec succès: ' . $validated['photo']);
            } else {
                Log::error('Fichier photo invalide');
            }
        } else {
            Log::info('Aucun fichier photo reçu');
        }

        DB::beginTransaction();
        try {
            // Créer l'élève
            $student = Student::create($validated);

            // Rattacher les responsables légaux saisis dans le formulaire.
            $this->rattacherLesResponsables($student, $validated['responsables'] ?? []);

            // Créer l'inscription si demandée (checkbox cochée = "on")
            $hasEnrollment = isset($validated['create_enrollment']) && $validated['create_enrollment'] === 'on' && !empty($validated['academic_year_id']) && !empty($validated['class_id']);
            
            if ($hasEnrollment) {
                $enrollmentData = [
                    'student_id' => $student->id,
                    'academic_year_id' => $validated['academic_year_id'],
                    'class_id' => $validated['class_id'],
                    'enrollment_date' => $validated['enrollment_date'],
                    'enrollment_status' => 'active',
                    'status' => 'active',
                    'is_new_enrollment' => true,
                ];

                \App\Models\Enrollment::create($enrollmentData);
            }

            DB::commit();

            $successMessage = 'Élève ajouté avec succès!' . 
                           ($hasEnrollment ? ' Inscription créée.' : '') .
                           ' Matricule généré: ' . $student->student_id;

            Log::info('Élève créé avec succès', [
                'student_id' => $student->id,
                'matricule' => $student->student_id,
                'name' => $student->full_name
            ]);

            // Si c'est une requête AJAX, retourner JSON
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'student' => [
                        'id' => $student->id,
                        'student_id' => $student->student_id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'full_name' => $student->full_name,
                        'date_of_birth' => $student->date_of_birth->format('d/m/Y'),
                        'age' => $student->age,
                        'gender' => $student->gender,
                        'enrollment_created' => $hasEnrollment ?? false
                    ]
                ]);
            }

            return redirect()->route('students.index')->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors de la création de l\'élève', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si c'est une requête AJAX, retourner JSON
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de l\'élève: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la création de l\'élève: ' . $e->getMessage()]);
        }
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        $classes = SchoolClass::where('is_active', true)->get();
        $academicYears = AcademicYear::where('status', 'active')->get();
        
        return view('students.edit', compact('student', 'classes', 'academicYears'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'student_id' => 'required|unique:students,student_id,' . $student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'place_of_birth' => 'nullable|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'emergency_contact' => 'nullable|string|max:255',
            'medical_conditions' => 'nullable|string',
            // Une inaptitude sans motif n'est pas exploitable par l'equipe :
            // la base le refuse aussi, par contrainte.
            'fitness_status' => 'nullable|in:apte,inapte',
            'unfitness_reason' => 'nullable|required_if:fitness_status,inapte|string|max:1000',
            'enrollment_date' => 'required|date',
            'status' => 'required|in:active,inactive,graduated,transferred',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'unfitness_reason.required_if' => 'Précisez le motif de l’inaptitude.',
        ]);

        // Repasser « apte » doit effacer le motif, sinon il survit a l'ecran.
        if (($validated['fitness_status'] ?? 'apte') !== 'inapte') {
            $validated['unfitness_reason'] = null;
        }

        try {
            // Handle photo upload avec débogage
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($file->isValid()) {
                    // Supprimer l'ancienne photo si elle existe
                    if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                        Storage::disk('public')->delete($student->photo);
                        Log::info('Ancienne photo supprimée: ' . $student->photo);
                    }
                    $validated['photo'] = $file->store('students/photos', 'public');
                    Log::info('Nouvelle photo uploadée: ' . $validated['photo']);
                } else {
                    Log::error('Fichier photo invalide lors de la modification');
                }
            } else {
                Log::info('Aucun fichier photo reçu lors de la modification');
            }

            $student->update($validated);

            // Retourner avec un message de succès
            return redirect()->route('students.index')->with('success', 'Élève modifié avec succès!');
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner avec un message d'erreur
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Une erreur est survenue lors de la modification : ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Student $student)
    {
        try {
            /*
             * Mise en corbeille, pas destruction : la fiche reste restaurable
             * depuis la plateforme. La photo n'est donc pas effacee ici — une
             * fiche restauree reviendrait sans elle. Elle part avec la
             * suppression definitive, dans le modele.
             */
            $student->delete();

            // La liste supprime via un formulaire classique ; l'API et les
            // anciens appels fetch attendent toujours du JSON.
            if (! $request->expectsJson()) {
                return redirect()->route('students.index')
                    ->with('success', 'L’élève est placé en corbeille. Il reste restaurable.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Élève supprimé avec succès!'
            ]);
            
        } catch (\Exception $e) {
            if (! $request->expectsJson()) {
                return redirect()->route('students.index')
                    ->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search students
     */
    public function search(Request $request)
    {
        $query = Student::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        // Recherche par paramètre 'q' pour la recherche par matricule (API pour parents)
        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('student_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('class') && $request->class) {
            $query->whereHas('enrollments.schoolClass', function($q) use ($request) {
                $q->where('name', $request->class);
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Pour l'API de recherche parent, limiter les résultats et les champs
        if ($request->has('q')) {
            $students = $query->active()
                             ->select('id', 'student_id', 'first_name', 'last_name', 'date_of_birth')
                             ->orderBy('student_id')
                             ->limit(10)
                             ->get();
        } else {
            $students = $query->with(['enrollments.schoolClass', 'parents'])->get();
        }

        return response()->json($students);
    }

    /**
     * Get classes by level for AJAX
     */
    public function getClassesByLevel(Request $request)
    {
        $levelId = $request->level_id;
        $academicYearId = $request->academic_year_id;
        
        // Si pas d'année académique spécifiée, utiliser l'année en cours
        if (!$academicYearId) {
            $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear ? $currentYear->id : null;
        }
        
        $classes = SchoolClass::where('is_active', true)
            ->where('level_id', $levelId)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);
        
        // Ajouter les informations de capacité pour chaque classe
        $classesWithCapacity = $classes->map(function($class) use ($academicYearId) {
            $enrolledCount = $class->getEnrolledStudentsCount($academicYearId);
            $availablePlaces = $class->getAvailablePlaces($academicYearId);
            $occupationPercentage = $class->getOccupationPercentage($academicYearId);
            
            return [
                'id' => $class->id,
                'name' => $class->name,
                'capacity' => $class->capacity,
                'enrolled_count' => $enrolledCount,
                'available_places' => $availablePlaces,
                'occupation_percentage' => $occupationPercentage,
                'is_full' => $availablePlaces <= 0,
                'display_name' => $class->name . ' (' . $availablePlaces . '/' . $class->capacity . ' places)'
            ];
        });
        
        return response()->json($classesWithCapacity);
    }

    /**
     * Get classes by cycle for AJAX
     */
    public function getClassesByCycle(Request $request)
    {
        $cycle = $request->cycle;
        $classes = SchoolClass::where('is_active', true)
            ->whereHas('level', function($query) use ($cycle) {
                $query->where('cycle', $cycle);
            })
            ->with('level')
            ->get();
        
        return response()->json($classes);
    }

    /**
     * Vérifier le matricule d'un élève pour la réinscription
     */
    public function checkMatricule(Request $request)
    {
        $matricule = $request->input('matricule');
        
        if (!$matricule) {
            return response()->json([
                'success' => false,
                'message' => 'Matricule requis'
            ], 400);
        }

        // Rechercher l'élève par matricule
        $student = Student::where('student_id', $matricule)->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Matricule non trouvé'
            ]);
        }

        // Vérifier si l'élève est déjà inscrit pour l'année scolaire en cours
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        
        if ($currentAcademicYear) {
            $currentEnrollment = $student->enrollments()
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('status', 'active')
                ->with(['schoolClass.level'])
                ->first();
                
            if ($currentEnrollment) {
                return response()->json([
                    'success' => true,
                    'already_enrolled' => true,
                    'student' => $student,
                    'current_class' => $currentEnrollment->schoolClass->name ?? 'N/A',
                    'current_level' => $currentEnrollment->schoolClass->level->name ?? 'N/A'
                ]);
            }
        }

        // Obtenir la dernière classe de l'élève
        $lastEnrollment = $student->enrollments()
            ->with(['schoolClass.level'])
            ->orderBy('created_at', 'desc')
            ->first();
            
        $lastClass = $lastEnrollment ? $lastEnrollment->schoolClass->name : 'Aucune';

        return response()->json([
            'success' => true,
            'already_enrolled' => false,
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
                'gender' => $student->gender
            ],
            'last_class' => $lastClass
        ]);
    }
}
