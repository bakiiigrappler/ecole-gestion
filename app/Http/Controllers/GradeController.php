<?php

namespace App\Http\Controllers;

use App\Models\StudentGrade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /*
         * L'ancienne version chargeait 300 lignes de notes, les regroupait en
         * PHP, relancait une requete par eleve pour sa moyenne cumulee, puis
         * paginait le tableau en memoire. Avec 2880 notes, la plupart des eleves
         * n'apparaissaient jamais et le total affiche par la pagination etait
         * faux. Tout est desormais agrege en base, et la page pagine vraiment.
         */
        $classesDuSecondaire = SchoolClass::whereHas('level', fn ($q) => $q->whereIn('cycle', ['college', 'lycee']))
            ->orderBy('name')
            ->pluck('id');

        /*
         * Un enseignant lit ses notes classe par classe : la page lui ouvre un
         * onglet par classe, et se place d'office sur la premiere. Sans cela
         * il tombait sur un melange de toutes ses classes, trie par moyenne.
         */
        $estEnseignant = \App\Support\PerimetreEnseignant::estEnseignant();
        $mesClasses = collect();

        if ($estEnseignant) {
            $mesClasses = SchoolClass::with('level')
                ->whereIn('id', \App\Support\PerimetreEnseignant::classes() ?: [0])
                ->whereIn('id', $classesDuSecondaire)
                ->orderBy('name')
                ->get();

            $demandee = $request->input('class_id');

            if (! $mesClasses->contains('id', (int) $demandee)) {
                /*
                 * L'onglet ouvert d'office est celui d'une classe ou il a
                 * effectivement note : ouvrir sur une classe vide donnait un
                 * tableau vide, et laissait croire que le filtre etait casse.
                 */
                $laPlusFournie = StudentGrade::whereIn('class_id', $mesClasses->pluck('id'))
                    ->whereIn('subject_id', \App\Support\PerimetreEnseignant::matieres() ?: [0])
                    ->selectRaw('class_id, count(*) as notes')
                    ->groupBy('class_id')
                    ->orderByDesc('notes')
                    ->value('class_id');

                $request->merge(['class_id' => $laPlusFournie ?: $mesClasses->first()?->id]);
            }
        }

        $lignes = StudentGrade::query()
            ->whereIn('student_grades.class_id', $classesDuSecondaire)
            // Un enseignant ne note que ses classes, et seulement la matiere
            // qu'il y enseigne : les deux conditions valent ensemble.
            ->when(\App\Support\PerimetreEnseignant::estEnseignant(), fn ($q) => $q
                ->whereIn('student_grades.class_id', \App\Support\PerimetreEnseignant::classes() ?: [0])
                ->whereIn('subject_id', \App\Support\PerimetreEnseignant::matieres() ?: [0]))
            ->when($request->input('class_id'), fn ($q, $v) => $q->where('student_grades.class_id', $v))
            ->when($request->input('subject_id'), fn ($q, $v) => $q->where('subject_id', $v))
            ->when($request->input('term'), fn ($q, $v) => $q->where('term', $v))
            ->when($request->input('teacher_id'), fn ($q, $v) => $q->where('teacher_id', $v))
            ->when($request->input('recherche'), function ($q, $motif) {
                $q->whereHas('student', fn ($s) => $s
                    ->where('first_name', 'ilike', "%{$motif}%")
                    ->orWhere('last_name', 'ilike', "%{$motif}%")
                    ->orWhere('student_id', 'ilike', "%{$motif}%"));
            })
            // Une note peut etre sur 10, 20 ou 100 : on ramene tout sur 20 avant
            // de moyenner, sinon les matieres ne sont pas comparables.
            ->selectRaw("
                student_grades.student_id,
                student_grades.class_id,
                count(*) as total_notes,
                count(distinct subject_id) as matieres,
                avg(case when max_score > 0 then score / max_score * 20 end) as moyenne,
                avg(case when term = '1er trimestre'  and max_score > 0 then score / max_score * 20 end) as t1,
                avg(case when term = '2ème trimestre' and max_score > 0 then score / max_score * 20 end) as t2,
                avg(case when term = '3ème trimestre' and max_score > 0 then score / max_score * 20 end) as t3
            ")
            ->groupBy('student_grades.student_id', 'student_grades.class_id')
            ->orderByDesc('moyenne');

        // Taille de page : celle demandee si elle est permise, sinon celle
        // reglee pour la plateforme.
        $parPage = \App\Support\ParametresPlateforme::pagination($request->input('per_page'));

        $releves = $lignes->paginate($parPage)->withQueryString();

        // Les eleves et les classes de la page courante, en deux requetes.
        $eleves = Student::whereIn('id', $releves->pluck('student_id'))->get()->keyBy('id');
        $classes = SchoolClass::with('level')->whereIn('id', $releves->pluck('class_id'))->get()->keyBy('id');

        return view('grades.index', [
            'releves' => $releves,
            'eleves' => $eleves,
            'classesDesReleves' => $classes,
            // Les compteurs d'en-tete portent sur ce que la personne a le
            // droit de voir : donner a un enseignant la moyenne generale de
            // l'etablissement reviendrait a la lui ouvrir par la bande.
            'statistiques' => $this->chiffresDesNotes(
                $estEnseignant ? $mesClasses->pluck('id') : $classesDuSecondaire,
                $estEnseignant ? \App\Support\PerimetreEnseignant::matieres() : null
            ),
            'classes' => $estEnseignant
                ? $mesClasses
                : SchoolClass::with('level')->whereIn('id', $classesDuSecondaire)->orderBy('name')->get(),
            'subjects' => Subject::whereIn('cycle', ['college', 'lycee'])
                ->when($estEnseignant, fn ($q) => $q->whereIn('id', \App\Support\PerimetreEnseignant::matieres() ?: [0]))
                ->orderBy('name')->get(),
            'teachers' => Teacher::orderBy('last_name')->orderBy('first_name')->get(),
            'estEnseignant' => $estEnseignant,
            'mesClasses' => $mesClasses,
        ]);
    }

    /**
     * Chiffres de la liste des notes, en une requete groupee plutot qu'en une
     * dizaine de compteurs separes.
     */
    private function chiffresDesNotes($classes, ?array $matieres = null): array
    {
        $global = StudentGrade::whereIn('class_id', $classes)
            ->when($matieres !== null, fn ($q) => $q->whereIn('subject_id', $matieres ?: [0]))
            ->selectRaw("
                count(*) as notes,
                count(distinct student_id) as eleves,
                count(distinct subject_id) as matieres,
                avg(case when max_score > 0 then score / max_score * 20 end) as moyenne,
                count(*) filter (where max_score > 0 and score / max_score * 20 >= 10) as suffisantes
            ")
            ->first();

        $parTrimestre = StudentGrade::whereIn('class_id', $classes)
            ->when($matieres !== null, fn ($q) => $q->whereIn('subject_id', $matieres ?: [0]))
            ->selectRaw("term, count(*) as n, avg(case when max_score > 0 then score / max_score * 20 end) as moyenne")
            ->groupBy('term')
            ->orderBy('term')
            ->get();

        $notes = (int) ($global->notes ?? 0);

        return [
            'notes' => $notes,
            'eleves' => (int) ($global->eleves ?? 0),
            'matieres' => (int) ($global->matieres ?? 0),
            'moyenne' => $global->moyenne !== null ? round((float) $global->moyenne, 2) : null,
            'taux_reussite' => $notes > 0 ? (int) round($global->suffisantes / $notes * 100) : null,
            'par_trimestre' => $parTrimestre,
        ];
    }
    public function create(Request $request)
    {
        // Récupérer les niveaux pour la sélection hiérarchique
        $levels = \App\Models\Level::active()->orderBy('order')->get();
        
        // Si un student_id est fourni, récupérer l'élève
        $selectedStudent = null;
        if ($request->has('student_id')) {
            $selectedStudent = Student::find($request->student_id);
        }

        return view('grades.create', compact('levels', 'selectedStudent'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log pour debug
        Log::info('Store method appelée', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_grades' => $request->has('grades'),
            'grades_is_array' => is_array($request->input('grades')),
            'grades_count' => is_array($request->input('grades')) ? count($request->input('grades')) : 0,
            'request_data' => $request->all()
        ]);

        // Vérifier si c'est un envoi multiple (nouveau format)
        if ($request->has('grades') && is_array($request->input('grades'))) {
            return $this->storeMultipleGrades($request);
        }
        
        // Ancien format pour une seule note
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|in:devoir,composition,controle,oral',
            'term' => 'required|in:1er trimestre,2ème trimestre,3ème trimestre',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:1',
            'exam_date' => 'required|date',
            'comments' => 'nullable|string|max:500'
        ]);

        // Vérifier que la note ne dépasse pas le maximum
        if ($validated['score'] > $validated['max_score']) {
            return back()->withErrors(['score' => 'La note ne peut pas dépasser le maximum.'])->withInput();
        }

        // Récupérer l'année académique active
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            return back()->withErrors(['error' => 'Aucune année académique active trouvée.'])->withInput();
        }

        // Récupérer la classe de l'élève et le professeur de la matière
        $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
        $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
        
        if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
            return back()->withErrors(['error' => 'L\'élève n\'est pas inscrit dans une classe active.'])->withInput();
        }

        // Récupérer le professeur qui enseigne cette matière dans cette classe
        $teacher = Teacher::whereHas('subjects', function ($query) use ($validated) {
            $query->where('subjects.id', $validated['subject_id']);
        })->whereHas('classes', function ($query) use ($currentEnrollment) {
            $query->where('classes.id', $currentEnrollment->class_id);
        })->first();

        if (!$teacher) {
            return back()->withErrors(['error' => 'Aucun professeur trouvé pour cette matière dans cette classe.'])->withInput();
        }

        // Préparer les données pour la création
        $gradeData = [
            'student_id' => $validated['student_id'],
            'subject_id' => $validated['subject_id'],
            'class_id' => $currentEnrollment->class_id,
            'teacher_id' => $teacher->id,
            'exam_type' => $validated['exam_type'],
            'term' => $validated['term'],
            'score' => $validated['score'],
            'max_score' => $validated['max_score'],
            'exam_date' => $validated['exam_date'],
            'comments' => $validated['comments'],
            'academic_year_id' => $academicYear->id
        ];

        $grade = StudentGrade::create($gradeData);

        // Charger les relations pour une réponse JSON complète
        $grade->load(['student', 'subject', 'schoolClass', 'teacher']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Note enregistrée avec succès !',
                'grade' => $grade,
            ], 201);
        }

        return redirect()->route('grades.index')
            ->with('success', 'Note enregistrée avec succès !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // L'ID est toujours un ID d'élève pour afficher son bulletin
            $studentId = $id;
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            // Ce dossier est-il le sien, celui de son enfant, ou celui d'un
            // eleve de sa classe ? Sans cette verification, changer le chiffre
            // dans l'URL suffisait a lire le dossier de n'importe qui.
            \App\Support\AccesEleve::verifier($student);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            // Récupérer toutes les notes de l'élève
            $grades = StudentGrade::with(['subject', 'teacher'])
                ->where('student_id', $studentId)
                ->where('class_id', $currentEnrollment->class_id)
                ->orderBy('subject_id')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Calculer la moyenne cumulée
            if ($grades->count() > 0) {
                $totalScore = $grades->sum('score');
                $totalMaxScore = $grades->sum('max_score');
                $cumulativeScore = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : 0;
                $cumulativePercentage = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 1) : 0;
                
                // Déterminer la couleur de la note
                $cumulativeGradeColor = 'danger';
                if ($cumulativePercentage >= 80) {
                    $cumulativeGradeColor = 'success';
                } elseif ($cumulativePercentage >= 70) {
                    $cumulativeGradeColor = 'info';
                } elseif ($cumulativePercentage >= 60) {
                    $cumulativeGradeColor = 'primary';
                } elseif ($cumulativePercentage >= 50) {
                    $cumulativeGradeColor = 'warning';
                } elseif ($cumulativePercentage >= 40) {
                    $cumulativeGradeColor = 'secondary';
                }
            } else {
                $cumulativeScore = '--';
                $cumulativePercentage = 0;
                $cumulativeGradeColor = 'secondary';
            }
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            // Préparer les informations de l'étudiant
            $studentInfo = [
                'id' => $student->id,
                'matricule' => $student->student_id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'birth_date' => $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : 'N/C',
                'birth_place' => $student->place_of_birth ?? $student->birth_place ?? 'N/C',
                'gender' => $student->gender === 'male' ? 'M' : 'F',
                'nationality' => $student->nationality ?? 'Gabonaise',
                'address' => $student->address ?? 'N/C'
            ];
            
            // Calculer l'effectif de la classe
            $totalStudents = $class->enrollments()->where('status', 'active')->count();
            $maleStudents = $class->enrollments()->where('status', 'active')
                ->whereHas('student', function($q) {
                    $q->where('gender', 'male');
                })->count();
            $femaleStudents = $class->enrollments()->where('status', 'active')
                ->whereHas('student', function($q) {
                    $q->where('gender', 'female');
                })->count();
            
            // Récupérer le professeur principal
            $principalTeacher = $class->allTeachers()->wherePivot('role', 'principal')->first();
            $principalTeacherName = $principalTeacher ? $principalTeacher->first_name . ' ' . $principalTeacher->last_name : 'N/C';
            
            // Préparer les données pour le PDF
            $tableDataForPDF = [];
            $totalsRowForPDF = [];
            
            if ($grades->count() > 0) {
                $gradesBySubject = $grades->groupBy('subject_id');
                
                foreach ($gradesBySubject as $subjectId => $subjectGrades) {
                    $subject = $subjectGrades->first()->subject;
                    $teacher = $subjectGrades->first()->teacher;
                    
                    // Calculer la moyenne de la matière
                    $subjectTotalScore = $subjectGrades->sum('score');
                    $subjectTotalMaxScore = $subjectGrades->sum('max_score');
                    $subjectAverage = $subjectTotalMaxScore > 0 ? round(($subjectTotalScore / $subjectTotalMaxScore) * 20, 2) : 0;
                    $subjectPercentage = $subjectTotalMaxScore > 0 ? round(($subjectTotalScore / $subjectTotalMaxScore) * 100, 1) : 0;
                    
                    // Déterminer l'appréciation
                    $appreciation = 'Insuffisant';
                    if ($subjectPercentage >= 80) $appreciation = 'Excellent';
                    elseif ($subjectPercentage >= 70) $appreciation = 'Très bien';
                    elseif ($subjectPercentage >= 60) $appreciation = 'Bien';
                    elseif ($subjectPercentage >= 50) $appreciation = 'Assez bien';
                    elseif ($subjectPercentage >= 40) $appreciation = 'Passable';
                    
                    // Coefficient (par défaut 1)
                    $coefficient = 1;
                    $noteCoeff = $subjectAverage * $coefficient;
                    
                    $tableDataForPDF[] = [
                        $subject->name,
                        $subjectAverage . '/20',
                        $coefficient,
                        number_format($noteCoeff, 2),
                        '--',
                        '0h00',
                        $appreciation,
                        $teacher->first_name . ' ' . $teacher->last_name
                    ];
                }
                
                // Préparer la ligne des totaux
                $totalCoeff = $gradesBySubject->count();
                $totalNoteCoeff = $grades->sum(function($grade) {
                    return ($grade->score / $grade->max_score) * 20;
                });
                
                $totalsRowForPDF = [
                    'TOTAUX',
                    $cumulativeScore . '/20',
                    $totalCoeff,
                    number_format($totalNoteCoeff, 2),
                    '--',
                    '0h00',
                    $cumulativePercentage >= 50 ? "Admis" : "Non admis",
                    ''
                ];
            }
            
            return view('grades.show', compact(
                'student', 
                'grades', 
                'class', 
                'academicYear', 
                'cumulativeScore', 
                'cumulativePercentage', 
                'cumulativeGradeColor',
                'tableDataForPDF',
                'totalsRowForPDF',
                'studentInfo',
                'totalStudents',
                'maleStudents',
                'femaleStudents',
                'principalTeacherName'
            ));
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage du bulletin', [
                'student_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('grades.index')->with('error', 'Erreur lors de l\'affichage du bulletin.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $grade = StudentGrade::findOrFail($id);
        $students = Student::orderBy('first_name')->get();
        $subjects = Subject::orderBy('name')->get();
        $classes = SchoolClass::whereHas('level', function($q) {
            $q->where('cycle', 'lycee');
        })->orderBy('name')->get();
        $teachers = Teacher::orderBy('first_name')->get();

        return view('grades.edit', compact('grade', 'students', 'subjects', 'classes', 'teachers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grade = StudentGrade::findOrFail($id);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:teachers,id',
            'term' => 'required|in:1er trimestre,2ème trimestre,3ème trimestre',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:1',
            'comments' => 'nullable|string|max:500'
        ]);

        // Vérifier que la note ne dépasse pas le maximum
        if ($validated['score'] > $validated['max_score']) {
            return back()->withErrors(['score' => 'La note ne peut pas dépasser le maximum.'])->withInput();
        }

        $grade->update($validated);

        return redirect()->route('grades.manage-student', $grade->student_id)
            ->with('success', 'Note mise à jour avec succès !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $grade = StudentGrade::findOrFail($id);
        $studentId = $grade->student_id; // Sauvegarder l'ID de l'élève avant la suppression
        $grade->delete();

        return redirect()->route('grades.manage-student', $studentId)
            ->with('success', 'Note supprimée avec succès !');
    }

    /**
     * Gérer les notes d'un élève (vue pour modifier/supprimer des notes individuelles)
     */
    public function manageStudentGrades(string $studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            // Ce dossier est-il le sien, celui de son enfant, ou celui d'un
            // eleve de sa classe ? Sans cette verification, changer le chiffre
            // dans l'URL suffisait a lire le dossier de n'importe qui.
            \App\Support\AccesEleve::verifier($student);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            // Récupérer toutes les notes de l'élève
            $grades = StudentGrade::with(['subject', 'teacher'])
                ->where('student_id', $studentId)
                ->where('class_id', $currentEnrollment->class_id)
                ->orderBy('subject_id')
                ->orderBy('created_at', 'desc')
                ->get();
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            return view('grades.manage-student', compact(
                'student', 
                'grades', 
                'class', 
                'academicYear'
            ));
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la gestion des notes', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('grades.index')->with('error', 'Erreur lors de la gestion des notes.');
        }
    }

    /**
     * Supprimer toutes les notes d'un élève
     */
    public function deleteAllGradesForStudent(string $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $deletedCount = StudentGrade::where('student_id', $studentId)->delete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Toutes les notes de {$student->first_name} {$student->last_name} ont été supprimées avec succès.",
                    'deleted_count' => $deletedCount
                ]);
            }

            return redirect()->route('grades.index')
                ->with('success', "Toutes les notes de {$student->first_name} {$student->last_name} ont été supprimées avec succès.");
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des notes', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de la suppression des notes'
                ], 500);
            }

            return redirect()->route('grades.index')
                ->with('error', 'Erreur lors de la suppression des notes.');
        }
    }

    /**
     * Afficher le bulletin de notes d'un élève
     */
    public function showBulletin(string $studentId)
    {
        try {
            Log::info('showBulletin called for student: ' . $studentId);
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            // Ce dossier est-il le sien, celui de son enfant, ou celui d'un
            // eleve de sa classe ? Sans cette verification, changer le chiffre
            // dans l'URL suffisait a lire le dossier de n'importe qui.
            \App\Support\AccesEleve::verifier($student);
            Log::info('Student found: ' . $student->first_name . ' ' . $student->last_name);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            // Initialiser les variables par défaut
            // L'effectif etait fixe a zero : le bulletin annoncait « 0 eleves ».
            $roster = Student::whereHas('enrollments', fn ($q) => $q
                    ->where('class_id', $class->id)
                    ->where('status', 'active'))
                ->get(['id', 'gender']);

            $totalStudents = $roster->count();
            $maleStudents = $roster->where('gender', 'male')->count();
            $femaleStudents = $roster->where('gender', 'female')->count();
            
            // ==================== INFORMATIONS COMPLÈTES DE L'ÉLÈVE ====================
            $studentInfo = [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'matricule' => $student->student_id ?? 'STU' . str_pad($student->id, 6, '0', STR_PAD_LEFT),
                'birth_date' => $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d-m-Y') : 'N/C',
                'gender' => $student->gender ? ucfirst($student->gender) : 'N/C',
            ];
            
            // ==================== NOTES PAR TRIMESTRE ====================
            $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
            $trimesterData = [];
            
            // Initialiser tous les trimestres avec le statut "Non disponible"
            foreach ($trimesters as $trimester) {
                $trimesterData[$trimester] = [
                    'subjects' => [],
                    'cumulative_score' => 0,
                    'total_subjects' => 0,
                    'rank' => 'N/C',
                    'is_available' => false,
                    'status' => 'Non disponible',
                    'absences' => 0
                ];
            }

            // Heures manquees, relevees dans le module Presences.
            $absences = $this->absencesParTrimestre($studentId, $class->id, $academicYear);

            // Traiter les trimestres avec des données
            foreach ($trimesters as $trimester) {
                $trimesterGrades = StudentGrade::with(['subject', 'teacher'])
                    ->where('student_id', $studentId)
                    ->where('class_id', $class->id)
                    ->where('term', $trimester)
                    ->orderBy('subject_id')
                    ->get();
                
                if ($trimesterGrades->count() > 0) {
                    try {
                        $calculatedData = $this->calculateTrimesterGrades($trimesterGrades, $class, $studentId);
                        $chiffres = $this->chiffresDeLaClasse($class->id, $trimester, $class);

                        // Moyenne de la classe et rang, discipline par discipline :
                        // le bulletin officiel les affiche, ils valaient « N/C ».
                        $heures = $absences[$trimester];
                        $calculatedData['subjects'] = array_map(function ($matiere) use ($chiffres, $studentId, $heures) {
                            $id = $matiere['subject_id'];
                            $matiere['class_average'] = $chiffres['par_matiere'][$id] ?? null;
                            $matiere['rank'] = $chiffres['rangs_matiere'][$id][$studentId] ?? 'N/C';
                            $matiere['absences'] = $heures['par_matiere'][$id] ?? 0;

                            return $matiere;
                        }, $calculatedData['subjects']);

                        $trimesterData[$trimester] = array_merge($calculatedData, [
                            'is_available' => true,
                            'status' => 'Disponible',
                            'rank' => $chiffres['rangs_generaux'][$studentId] ?? 'N/C',
                            'class_average' => $chiffres['profil']['moyenne'],
                            'class_profile' => $chiffres['profil'],
                            'absences' => $absences[$trimester]['total'],
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur calcul trimestre: ' . $e->getMessage());
                        // Garder le statut "Non disponible" en cas d'erreur
                    }
                }
            }
            
            // ==================== PROFESSEUR PRINCIPAL DE LA CLASSE ====================
            $principalTeacher = $class->allTeachers()
                ->wherePivot('role', 'principal')
                ->first();
            
            $principalTeacherName = $principalTeacher ? 
                $principalTeacher->first_name . ' ' . $principalTeacher->last_name : 
                'N/C';
            
            // Calculer les moyennes de classe par trimestre
            $classAverages = $this->calculateClassAveragesByTrimester($class->id);

            // Le bulletin du 3e trimestre porte la decision de passage : il
            // annonce la classe dans laquelle l'eleve entrera.
            $niveauSuivant = $class->level
                ? Level::where('order', '>', $class->level->order)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->first()
                : null;

            $classeDePassage = $niveauSuivant?->name;
            
            // Variables simplifiées pour la vue
            $subjectRanks = [];
            $subjectCoefficients = [];
            $classProfile = ['moyenne_classe' => $classAverages['moyenne_generale'] ?? 'N/C'];
            $generalBalance = ['moyenne_generale' => $classAverages['moyenne_generale'] ?? 'N/C'];
            $tableDataForPDF = [];
            $totalsRowForPDF = [];
            $gradesDataForPDF = [];
            $cumulativeScore = 0;
            $cumulativePercentage = 0;
            $termLabel = '1er TRIMESTRE';
            $schoolName = 'Établissement Scolaire';
            $schoolSettings = null;
            
            // Calculer les coefficients des matières pour la vue
            if (!empty($trimesterData)) {
                $lastTrimester = array_key_last($trimesterData);
                $currentTrimesterGrades = $trimesterData[$lastTrimester];
                
                if (isset($currentTrimesterGrades['subjects'])) {
                    foreach ($currentTrimesterGrades['subjects'] as $subjectData) {
                        $subjectName = strtolower($subjectData['name']);
                        $subjectCoefficients[$subjectName] = $subjectData['coefficient'] ?? 1;
                    }
                }
            }
            
            Log::info('About to return view grades.show-trimesters');
            return view('grades.show-trimesters', compact(
                'student', 
                'class', 
                'academicYear',
                'totalStudents',
                'maleStudents',
                'femaleStudents',
                'studentInfo',
                'trimesterData',
                'subjectRanks',
                'classAverages',
                'subjectCoefficients',
                'classProfile',
                'generalBalance',
                'principalTeacherName',
                'tableDataForPDF',
                'totalsRowForPDF',
                'gradesDataForPDF',
                'cumulativeScore',
                'cumulativePercentage',
                'termLabel',
                'classeDePassage',
                'schoolName',
                'schoolSettings'
            ));
            
        } catch (\Exception $e) {
            Log::error('Exception in showBulletin: ' . $e->getMessage());
            Log::error('Erreur lors de l\'affichage du bulletin', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('grades.index')->with('error', 'Erreur lors de l\'affichage du bulletin.');
        }
    }

    /**
     * Obtenir l'appréciation basée sur la note
     */
    private function getAppreciation($score)
    {
        if ($score >= 16) return 'Excellent';
        if ($score >= 14) return 'Très bien';
        if ($score >= 12) return 'Bien';
        if ($score >= 10) return 'Assez bien';
        if ($score >= 8) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Obtenir les statistiques des notes
     */
    private function getGradeStats(Request $request)
    {
        $query = StudentGrade::query();

        // Appliquer les mêmes filtres que pour la liste
        if ($request->get('class_id')) {
            $query->byClass($request->get('class_id'));
        }
        if ($request->get('subject_id')) {
            $query->bySubject($request->get('subject_id'));
        }
        if ($request->get('term')) {
            $query->byTerm($request->get('term'));
        }

        $totalGrades = $query->count();
        
        if ($totalGrades > 0) {
            // Calculer la moyenne en pourcentage puis convertir sur 20
            $averageGrade = $query->avg(DB::raw('(score / max_score) * 20'));
            $pendingGrades = $this->getPendingGradesCount($request);
            $completionRate = $this->getCompletionRate($request);
        } else {
            $averageGrade = 0;
            $pendingGrades = 0;
            $completionRate = 0;
        }

        return [
            'totalGrades' => $totalGrades,
            'averageGrade' => round($averageGrade, 1),
            'pendingGrades' => $pendingGrades,
            'completionRate' => $completionRate
        ];
    }

    /**
     * Compter les notes en attente de saisie
     */
    private function getPendingGradesCount(Request $request)
    {
        // Logique pour déterminer les notes manquantes
        // Ceci est un exemple simplifié
        return 45; // À implémenter selon votre logique métier
    }

    /**
     * Calculer le taux de complétion
     */
    private function getCompletionRate(Request $request)
    {
        // Logique pour calculer le taux de complétion
        // Ceci est un exemple simplifié
        return 92; // À implémenter selon votre logique métier
    }

    /**
     * Export des notes
     */
    public function export(Request $request)
    {
        // Logique d'export à implémenter
        return response()->json(['message' => 'Export en cours de développement']);
    }

    /**
     * Notes par élève
     */
    public function byStudent(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);
        $grades = StudentGrade::where('student_id', $studentId)
            ->with(['subject', 'schoolClass', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('grades.by-student', compact('student', 'grades'));
    }

    /**
     * Notes par classe
     */
    public function byClass(Request $request, $classId)
    {
        $class = SchoolClass::findOrFail($classId);
        $grades = StudentGrade::where('class_id', $classId)
            ->with(['student', 'subject', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('grades.by-class', compact('class', 'grades'));
    }

    // ==================== MÉTHODES API POUR LE FORMULAIRE DYNAMIQUE ====================

    /**
     * Récupérer tous les élèves avec leurs classes
     */
    public function getStudentsWithClasses()
    {
        try {
            $students = Student::with(['enrollments.schoolClass.level'])
                ->orderBy('first_name')
                ->get()
                ->map(function ($student) {
                    $currentEnrollment = $student->enrollments()
                        ->where('status', 'active')
                        ->with('schoolClass.level')
                        ->first();
                    
                    return [
                        'id' => $student->id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'school_class_id' => $currentEnrollment ? $currentEnrollment->class_id : null,
                        'school_class' => $currentEnrollment && $currentEnrollment->schoolClass ? [
                            'id' => $currentEnrollment->schoolClass->id,
                            'name' => $currentEnrollment->schoolClass->name,
                            'level' => $currentEnrollment->schoolClass->level ? [
                                'id' => $currentEnrollment->schoolClass->level->id,
                                'name' => $currentEnrollment->schoolClass->level->name,
                                'cycle' => $currentEnrollment->schoolClass->level->cycle
                            ] : null
                        ] : null
                    ];
                });

            return response()->json($students);
        } catch (\Exception $e) {
            Log::error('Erreur dans getStudentsWithClasses: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors du chargement des élèves'], 500);
        }
    }

    /**
     * Récupérer toutes les matières avec leurs professeurs
     */
    public function getSubjectsWithTeachers()
    {
        $subjects = Subject::with(['teachers.classes'])
            ->orderBy('name')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'teachers' => $subject->teachers->map(function ($teacher) {
                        return [
                            'id' => $teacher->id,
                            'first_name' => $teacher->first_name,
                            'last_name' => $teacher->last_name,
                            'specialization' => $teacher->specialization,
                            'classes' => $teacher->classes->map(function ($class) {
                                return [
                                    'id' => $class->id,
                                    'name' => $class->name
                                ];
                            })
                        ];
                    })
                ];
            });

        return response()->json($subjects);
    }

    /**
     * Récupérer les informations détaillées d'un élève
     */
    public function getStudentInfo($studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass.levelData'])
                ->findOrFail($studentId);
            
            $currentEnrollment = $student->enrollments()
                ->where('status', 'active')
                ->with('schoolClass.levelData')
                ->first();

            return response()->json([
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'school_class' => $currentEnrollment && $currentEnrollment->schoolClass ? [
                    'id' => $currentEnrollment->schoolClass->id,
                    'name' => $currentEnrollment->schoolClass->name,
                    'level' => $currentEnrollment->schoolClass->levelData ? [
                        'id' => $currentEnrollment->schoolClass->levelData->id,
                        'name' => $currentEnrollment->schoolClass->levelData->name,
                        'cycle' => $currentEnrollment->schoolClass->levelData->cycle
                    ] : null
                ] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des informations de l\'élève', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération des informations de l\'élève',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les matières disponibles pour une classe
     */
    public function getClassSubjects($classId)
    {
        try {
            $class = SchoolClass::with(['levelData'])->findOrFail($classId);
            
            // Récupérer les enseignants assignés à cette classe
            $teachersInClass = \App\Models\Teacher::whereHas('classes', function ($query) use ($classId) {
                $query->where('classes.id', $classId);
            })->get();

            // Récupérer les spécialisations des enseignants
            $specializations = $teachersInClass->pluck('specialization')
                                              ->filter() // enlever les null
                                              ->unique()
                                              ->values();

            // Log pour debug
            Log::info('Enseignants et spécialisations trouvés', [
                'class_id' => $classId,
                'teachers' => $teachersInClass->pluck('full_name')->toArray(),
                'specializations' => $specializations->toArray()
            ]);

            // Récupérer les matières correspondant aux spécialisations
            $subjects = Subject::whereIn('name', $specializations)
                              ->orWhere(function($query) use ($specializations) {
                                  // Essayez aussi de matcher par le code ou une partie du nom
                                  foreach ($specializations as $spec) {
                                      $query->orWhere('name', 'LIKE', "%{$spec}%")
                                            ->orWhere('code', 'LIKE', "%{$spec}%");
                                  }
                              })
                              ->orderBy('name')
                              ->get();

            // Si aucune matière trouvée par spécialisation, récupérer les matières du cycle de la classe
            if ($subjects->isEmpty() && $class->levelData) {
                $subjects = Subject::where('cycle', $class->levelData->cycle)
                                  ->orderBy('name')
                                  ->get();
                
                Log::info('Aucune matière trouvée par spécialisation, utilisation du cycle', [
                    'cycle' => $class->levelData->cycle,
                    'subjects_count' => $subjects->count()
                ]);
            }

            // Log final
            Log::info('Matières finales pour la classe', [
                'class_id' => $classId,
                'subjects_count' => $subjects->count(),
                'subjects' => $subjects->pluck('name')->toArray()
            ]);

            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des matières', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la récupération des matières'], 500);
        }
    }

    /**
     * Récupérer le professeur d'une matière pour une classe spécifique
     */
    public function getSubjectTeacherForClass($subjectId, $classId)
    {
        // D'abord, essayer de trouver un professeur qui enseigne cette matière dans cette classe
        $teacher = Teacher::whereHas('subjects', function ($query) use ($subjectId) {
            $query->where('subjects.id', $subjectId);
        })->whereHas('classes', function ($query) use ($classId) {
            $query->where('classes.id', $classId);
        })->first();

        // Si aucun professeur trouvé, prendre le premier professeur de cette matière
        if (!$teacher) {
            $teacher = Teacher::whereHas('subjects', function ($query) use ($subjectId) {
                $query->where('subjects.id', $subjectId);
            })->first();
        }

        if (!$teacher) {
            return response()->json(['error' => 'Aucun professeur trouvé pour cette matière'], 404);
        }

        return response()->json([
            'id' => $teacher->id,
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'specialization' => $teacher->specialization
        ]);
    }

    /**
     * Récupérer les classes d'un niveau
     */
    public function getClassesForLevel($levelId)
    {
        $classes = SchoolClass::where('level_id', $levelId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    /**
     * Récupérer les élèves d'une classe
     */
    public function getStudentsForClass($classId)
    {
        $students = Student::whereHas('enrollments', function ($query) use ($classId) {
            $query->where('class_id', $classId)
                  ->where('status', 'active');
        })->orderBy('first_name')
          ->get(['id', 'first_name', 'last_name']);

        return response()->json($students);
    }

    /**
     * Enregistrer plusieurs notes à la fois pour un élève
     */
    public function storeMultipleGrades(Request $request)
    {
        try {
                    // Log des données reçues pour debug
        Log::info('Données brutes reçues pour l\'enregistrement multiple', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_data' => $request->all(),
            'json_data' => $request->json() ? $request->json()->all() : null,
            'input_data' => $request->input(),
            'is_json' => $request->isJson()
        ]);

            // Validation des données principales
            try {
                $validated = $request->validate([
                    'student_id' => 'required|exists:students,id',
                    'term' => 'required|in:1er trimestre,2ème trimestre,3ème trimestre',
                    'grades' => 'required|array|min:1',
                    'grades.*.subject_id' => 'required|exists:subjects,id',
                    'grades.*.score' => 'required|numeric|min:0',
                    'grades.*.max_score' => 'required|numeric|min:1',
                    'grades.*.comments' => 'nullable|string|max:500'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Erreur de validation lors de l\'enregistrement multiple', [
                    'errors' => $e->errors(),
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur de validation',
                    'validation_errors' => $e->errors()
                ], 422);
            }

            Log::info('Données reçues pour l\'enregistrement multiple', [
                'student_id' => $validated['student_id'],
                'term' => $validated['term'],
                'grades_count' => count($validated['grades'])
            ]);

            // Récupérer l'année académique active
            $academicYear = AcademicYear::where('is_current', true)->first();
            if (!$academicYear) {
                return response()->json(['error' => 'Aucune année académique active trouvée.'], 400);
            }

            // Récupérer l'élève et sa classe
            $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return response()->json(['error' => 'L\'élève n\'est pas inscrit dans une classe active.'], 400);
            }

            // Vérifier que l'enrollment a bien un class_id
            if (!$currentEnrollment->class_id) {
                Log::error('Enrollment sans class_id', [
                    'enrollment' => $currentEnrollment->toArray(),
                    'student_id' => $validated['student_id']
                ]);
                return response()->json(['error' => 'L\'inscription de l\'élève n\'a pas de classe associée.'], 400);
            }

            $createdGrades = [];
            $errors = [];

            // Traitement de chaque note
            foreach ($validated['grades'] as $index => $gradeData) {
                try {
                    // Vérifier que la note ne dépasse pas le maximum
                    if ($gradeData['score'] > $gradeData['max_score']) {
                        $errors[] = "Note #" . ($index + 1) . ": La note (" . $gradeData['score'] . ") ne peut pas dépasser le maximum (" . $gradeData['max_score'] . ").";
                        continue;
                    }

                    // Récupérer le professeur qui enseigne cette matière dans cette classe
                    $teacher = Teacher::whereHas('subjects', function ($query) use ($gradeData) {
                        $query->where('subjects.id', $gradeData['subject_id']);
                    })->whereHas('classes', function ($query) use ($currentEnrollment) {
                        $query->where('classes.id', $currentEnrollment->class_id);
                    })->first();

                    // Si pas de professeur trouvé, prendre le premier professeur de la matière
                    if (!$teacher) {
                        $teacher = Teacher::whereHas('subjects', function ($query) use ($gradeData) {
                            $query->where('subjects.id', $gradeData['subject_id']);
                        })->first();
                    }

                    if (!$teacher) {
                        $errors[] = "Note #" . ($index + 1) . ": Aucun professeur trouvé pour cette matière.";
                        continue;
                    }

                    // Préparer les données pour l'insertion
                    $gradeCreateData = [
                        'student_id' => $validated['student_id'],
                        'subject_id' => $gradeData['subject_id'],
                        'class_id' => $currentEnrollment->class_id,
                        'teacher_id' => $teacher->id,
                        'term' => $validated['term'],
                        'score' => $gradeData['score'],
                        'max_score' => $gradeData['max_score'],
                        'comments' => $gradeData['comments'] ?? '',
                        'academic_year_id' => $academicYear->id
                    ];

                    Log::info('Données avant insertion Grade', [
                        'grade_data' => $gradeCreateData,
                        'academic_year' => $academicYear ? $academicYear->toArray() : 'null'
                    ]);

                    // Créer la note
                    $grade = StudentGrade::create($gradeCreateData);

                    $createdGrades[] = $grade;

                } catch (\Exception $e) {
                    Log::error('Erreur lors de la création de la note', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'grade_data' => $gradeData
                    ]);
                    $errors[] = "Note #" . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // Réponse finale
            if (count($createdGrades) > 0) {
                $message = count($createdGrades) . ' note(s) enregistrée(s) avec succès';
                if (count($errors) > 0) {
                    $message .= ', ' . count($errors) . ' erreur(s) détectée(s)';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'created_count' => count($createdGrades),
                    'error_count' => count($errors),
                    'errors' => $errors
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucune note n\'a pu être enregistrée',
                    'errors' => $errors
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur générale lors de l\'enregistrement multiple', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'enregistrement des notes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déterminer le trimestre actuel basé sur la date
     */
    private function getCurrentTerm()
    {
        $currentMonth = date('n'); // 1-12
        
        if ($currentMonth >= 9 || $currentMonth <= 12) {
            return 1; // Premier trimestre (Septembre-Décembre)
        } elseif ($currentMonth >= 1 && $currentMonth <= 3) {
            return 2; // Deuxième trimestre (Janvier-Mars)
        } else {
            return 3; // Troisième trimestre (Avril-Juin)
        }
    }

    /**
     * Obtenir le label du trimestre
     */
    private function getTermLabel($term)
    {
        switch ($term) {
            case 1:
                return '1er TRIMESTRE';
            case 2:
                return '2ème TRIMESTRE';
            case 3:
                return '3ème TRIMESTRE';
            default:
                return '3ème TRIMESTRE';
        }
    }


    /**
     * Heures d'absence relevees dans le module Presences, trimestre par trimestre.
     *
     * Le pointage se fait par seance (une date, un creneau) et non par matiere :
     * chaque seance manquee est rattachee a sa discipline via l'emploi du temps
     * de la classe. Tant que celui-ci n'est pas saisi, seul le total du
     * trimestre est connu et les disciplines restent a zero.
     */
    private function absencesParTrimestre(int $studentId, int $classId, $academicYear): array
    {
        $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        $vide = array_fill_keys($trimestres, ['par_matiere' => [], 'total' => 0]);

        if (!$academicYear) {
            return $vide;
        }

        $debut = Carbon::parse($academicYear->start_date)->startOfDay();
        $fin = Carbon::parse($academicYear->end_date)->endOfDay();
        $an = $debut->year;

        // Memes bornes que le decoupage de l'annee scolaire retenu par
        // getCurrentTerm() : septembre-decembre, janvier-mars, avril-juin.
        $bornes = [
            '1er trimestre' => [$debut, Carbon::create($an, 12, 31)->endOfDay()],
            '2ème trimestre' => [Carbon::create($an + 1, 1, 1)->startOfDay(), Carbon::create($an + 1, 3, 31)->endOfDay()],
            '3ème trimestre' => [Carbon::create($an + 1, 4, 1)->startOfDay(), $fin],
        ];

        // 'absent' comme 'excused' comptent comme heures manquees ; un retard
        // ('late') n'est pas une absence.
        $seances = Attendance::where('student_id', $studentId)
            ->whereIn('status', ['absent', 'excused'])
            ->whereBetween('attendance_date', [$debut->toDateString(), $fin->toDateString()])
            ->get(['attendance_date', 'time_slot']);

        if ($seances->isEmpty()) {
            return $vide;
        }

        $emploiDuTemps = DB::table('schedules')
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYear->id)
            ->get(['subject_id', 'day_of_week', 'start_time', 'end_time']);

        $absences = $vide;

        foreach ($seances as $seance) {
            $jour = Carbon::parse($seance->attendance_date);

            foreach ($bornes as $trimestre => [$de, $a]) {
                if ($jour->lt($de) || $jour->gt($a)) {
                    continue;
                }

                foreach ($this->coursManques($emploiDuTemps, $jour, $seance->time_slot) as $cours) {
                    $absences[$trimestre]['total'] += $cours['minutes'];

                    if ($cours['subject_id']) {
                        $id = $cours['subject_id'];
                        $absences[$trimestre]['par_matiere'][$id] =
                            ($absences[$trimestre]['par_matiere'][$id] ?? 0) + $cours['minutes'];
                    }
                }

                break;
            }
        }

        return $absences;
    }

    /**
     * Les cours manques pour une seance pointee absente.
     *
     * Sans emploi du temps saisi, l'heure manquee ne peut etre rattachee a
     * aucune discipline : elle compte alors pour une heure au total du
     * trimestre, et pour rien dans les colonnes des matieres.
     */
    private function coursManques($emploiDuTemps, Carbon $jour, ?string $creneau): array
    {
        $duJour = $emploiDuTemps->where('day_of_week', $jour->dayOfWeekIso);

        if ($duJour->isEmpty()) {
            return [['subject_id' => null, 'minutes' => 60]];
        }

        $duree = function ($ligne) {
            $de = Carbon::parse($ligne->start_time);
            $a = Carbon::parse($ligne->end_time);

            return max(0, $de->diffInMinutes($a)) ?: 60;
        };

        // Pointage a la journee : toutes les heures du jour sont manquees.
        // La feuille d'appel enregistre ce cas sous le libelle « journee ».
        if (!$creneau || $creneau === 'journee') {
            return $duJour->map(fn ($ligne) => [
                'subject_id' => $ligne->subject_id,
                'minutes' => $duree($ligne),
            ])->values()->all();
        }

        $heure = substr($creneau, 0, 5);
        $cours = $duJour->first(fn ($ligne) => substr((string) $ligne->start_time, 0, 5) === $heure);

        return $cours
            ? [['subject_id' => $cours->subject_id, 'minutes' => $duree($cours)]]
            : [['subject_id' => null, 'minutes' => 60]];
    }

    /**
     * Calculer les notes d'un trimestre
     */

    /**
     * Chiffres de la classe pour un trimestre : moyenne de chaque discipline,
     * rang de chaque eleve par discipline, moyenne generale ponderee de chaque
     * eleve, et le profil de la classe (forte, faible et moyenne generale).
     *
     * Le bulletin officiel affiche ces colonnes ; elles n'etaient pas calculees
     * — le rang valait « N/C » partout et la moyenne de la classe par discipline
     * n'existait pas.
     */
    private function chiffresDeLaClasse(int $classId, string $trimestre, SchoolClass $class): array
    {
        $notes = StudentGrade::with('subject')
            ->where('class_id', $classId)
            ->where('term', $trimestre)
            ->get();

        if ($notes->isEmpty()) {
            return [
                'par_matiere' => [],
                'rangs_matiere' => [],
                'moyennes_eleves' => [],
                'rangs_generaux' => [],
                'profil' => ['forte' => null, 'faible' => null, 'moyenne' => null],
            ];
        }

        // Moyenne de chaque eleve dans chaque discipline, ramenee sur 20.
        $parMatiere = [];
        $rangsMatiere = [];

        foreach ($notes->groupBy('subject_id') as $subjectId => $lot) {
            $moyennes = $lot->groupBy('student_id')->map(function ($n) {
                $total = $n->sum('max_score');

                return $total > 0 ? round($n->sum('score') / $total * 20, 2) : null;
            })->filter();

            $parMatiere[$subjectId] = $moyennes->avg() !== null ? round($moyennes->avg(), 2) : null;

            // Rang : moyennes egales partagent le meme rang.
            $ordonnees = $moyennes->sortDesc()->values();
            foreach ($moyennes as $studentId => $moyenne) {
                $rangsMatiere[$subjectId][$studentId] = $ordonnees->search($moyenne) + 1;
            }
        }

        // Moyenne generale ponderee de chaque eleve, avec les memes coefficients
        // que ceux appliques a l'eleve affiche.
        $moyennesEleves = [];

        foreach ($notes->groupBy('student_id') as $studentId => $lot) {
            $points = 0;
            $coefficients = 0;

            foreach ($lot->groupBy('subject_id') as $subjectId => $sesNotes) {
                $matiere = $sesNotes->first()->subject ?? null;
                if (! $matiere) {
                    continue;
                }

                $total = $sesNotes->sum('max_score');
                if ($total <= 0) {
                    continue;
                }

                $coefficient = $this->getSubjectCoefficient($matiere, $class);
                $points += ($sesNotes->sum('score') / $total * 20) * $coefficient;
                $coefficients += $coefficient;
            }

            if ($coefficients > 0) {
                $moyennesEleves[$studentId] = round($points / $coefficients, 2);
            }
        }

        $classement = collect($moyennesEleves)->sortDesc();
        $ordonnees = $classement->values();

        $rangsGeneraux = [];
        foreach ($moyennesEleves as $studentId => $moyenne) {
            $rangsGeneraux[$studentId] = $ordonnees->search($moyenne) + 1;
        }

        return [
            'par_matiere' => $parMatiere,
            'rangs_matiere' => $rangsMatiere,
            'moyennes_eleves' => $moyennesEleves,
            'rangs_generaux' => $rangsGeneraux,
            'profil' => [
                'forte' => $classement->first(),
                'faible' => $classement->last(),
                'moyenne' => $classement->isNotEmpty() ? round($classement->avg(), 2) : null,
                'effectif' => count($moyennesEleves),
            ],
        ];
    }

    private function calculateTrimesterGrades($grades, $class, $studentId)
    {
        $subjects = [];
        $totalWeightedScore = 0;
        $totalCoefficient = 0;
        
        try {
            // Grouper par matière
            $gradesBySubject = $grades->groupBy('subject_id');
            
            foreach ($gradesBySubject as $subjectId => $subjectGrades) {
                $subject = $subjectGrades->first()->subject ?? null;
                $teacher = $subjectGrades->first()->teacher ?? null;
                
                if (!$subject) continue;
                
                // Calculer la moyenne de la matière
                $subjectTotalScore = $subjectGrades->sum('score');
                $subjectTotalMaxScore = $subjectGrades->sum('max_score');
                $subjectAverage = $subjectTotalMaxScore > 0 ? round(($subjectTotalScore / $subjectTotalMaxScore) * 20, 2) : 0;
                
                // Récupérer le coefficient de la matière
                $coefficient = $this->getSubjectCoefficient($subject, $class);
                
                $subjects[] = [
                    'subject_id' => $subjectId,
                    'name' => $subject->name,
                    'average' => $subjectAverage,
                    'teacher_name' => $teacher ? $teacher->first_name . ' ' . $teacher->last_name : 'N/A',
                    'coefficient' => $coefficient,
                    'rank' => 'N/C',
                    'appreciation' => $this->getAppreciation($subjectAverage)
                ];
                
                // Calculer la moyenne pondérée
                $totalWeightedScore += $subjectAverage * $coefficient;
                $totalCoefficient += $coefficient;
            }
            
            // Calculer la moyenne générale pondérée
            $cumulativeScore = $totalCoefficient > 0 ? round($totalWeightedScore / $totalCoefficient, 2) : 0;
            
            return [
                'subjects' => $subjects,
                'cumulative_score' => $cumulativeScore,
                'total_subjects' => count($subjects)
            ];
        } catch (\Exception $e) {
            Log::error('Erreur calculateTrimesterGrades: ' . $e->getMessage());
            return [
                'subjects' => [],
                'cumulative_score' => 0,
                'total_subjects' => 0
            ];
        }
    }
    
    /**
     * Calculer les rangs par matière
     */
    private function calculateSubjectRanks($classId, $studentId)
    {
        $ranks = [];
        
        // Récupérer toutes les matières qui ont des notes dans cette classe
        $subjectsWithGrades = StudentGrade::where('class_id', $classId)
            ->select('subject_id')
            ->distinct()
            ->get()
            ->pluck('subject_id');
        
        $subjects = Subject::whereIn('id', $subjectsWithGrades)->get();
        
        foreach ($subjects as $subject) {
            // Récupérer tous les élèves avec des notes dans cette matière
            $studentAverages = StudentGrade::where('class_id', $classId)
                ->where('subject_id', $subject->id)
                ->selectRaw('student_id, AVG((score / max_score) * 20) as average')
                ->groupBy('student_id')
                ->orderByDesc('average')
                ->get();
            
            if ($studentAverages->count() > 0) {
                // Trouver le rang de l'élève
                $rank = 1;
                foreach ($studentAverages as $avg) {
                    if ($avg->student_id == $studentId) {
                        $ranks[$subject->id] = $rank;
                        break;
                    }
                    $rank++;
                }
            }
        }
        
        return $ranks;
    }
    
    /**
     * Calculer les moyennes de classe par matière
     */
    private function calculateClassAverages($classId)
    {
        $averages = [];
        
        $subjectAverages = StudentGrade::where('class_id', $classId)
            ->selectRaw('subject_id, AVG((score / max_score) * 20) as class_average')
            ->groupBy('subject_id')
            ->get();
        
        foreach ($subjectAverages as $avg) {
            $averages[$avg->subject_id] = round($avg->class_average, 2);
        }
        
        return $averages;
    }
    
    /**
     * Calculer les moyennes de classe par trimestre
     */
    private function calculateClassAveragesByTrimester($classId)
    {
        $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        $trimesterAverages = [];
        $totalAverage = 0;
        $availableTrimesters = 0;
        
        foreach ($trimesters as $trimester) {
            // Calculer la moyenne de classe pour ce trimestre
            $grades = StudentGrade::where('class_id', $classId)
                ->where('term', $trimester)
                ->get();
            
            if ($grades->count() > 0) {
                // Calculer la moyenne pondérée par coefficient
                $totalWeightedScore = 0;
                $totalCoefficient = 0;
                
                // Grouper par matière pour calculer les moyennes par matière
                $gradesBySubject = $grades->groupBy('subject_id');
                
                foreach ($gradesBySubject as $subjectId => $subjectGrades) {
                    $subject = $subjectGrades->first()->subject;
                    $coefficient = $this->getSubjectCoefficient($subject, SchoolClass::find($classId));
                    
                    // Calculer la moyenne de la matière pour ce trimestre
                    $subjectTotalScore = $subjectGrades->sum('score');
                    $subjectTotalMaxScore = $subjectGrades->sum('max_score');
                    $subjectAverage = $subjectTotalMaxScore > 0 ? ($subjectTotalScore / $subjectTotalMaxScore) * 20 : 0;
                    
                    $totalWeightedScore += $subjectAverage * $coefficient;
                    $totalCoefficient += $coefficient;
                }
                
                $trimesterAverage = $totalCoefficient > 0 ? round($totalWeightedScore / $totalCoefficient, 2) : 0;
                $trimesterAverages[$trimester] = $trimesterAverage;
                $totalAverage += $trimesterAverage;
                $availableTrimesters++;
            } else {
                $trimesterAverages[$trimester] = 0;
            }
        }
        
        $moyenneGenerale = $availableTrimesters > 0 ? round($totalAverage / $availableTrimesters, 2) : 0;
        
        return [
            'trimestres' => $trimesterAverages,
            'moyenne_generale' => $moyenneGenerale,
            'trimestres_disponibles' => $availableTrimesters
        ];
    }
    
    /**
     * Obtenir les coefficients des matières
     */
    private function getSubjectCoefficients($levelId)
    {
        // Coefficients par défaut selon le niveau
        $coefficients = [
            // Matières principales
            'mathématiques' => 4,
            'français' => 4,
            'histoire' => 2,
            'géographie' => 2,
            'sciences' => 3,
            'anglais' => 2,
            'espagnol' => 2,
            'allemand' => 2,
            'arts plastiques' => 1,
            'musique' => 1,
            'éducation physique et sportive' => 1,
            'technologie' => 1,
            'sciences de la vie et de la terre' => 2,
            'physique-chimie' => 2,
            'physique' => 2,
            'chimie' => 2,
            'svt' => 2,
            'eps' => 1,
            'arts' => 1,
            'informatique' => 1,
            'philosophie' => 3,
            'littérature' => 3,
            'langues vivantes' => 2,
            'histoire-géographie' => 2,
        ];
        
        return $coefficients;
    }
    
    /**
     * Calculer le profil de la classe
     */
    private function calculateClassProfile($classId)
    {
        // Récupérer toutes les notes de la classe
        $allGrades = StudentGrade::where('class_id', $classId)->get();
        
        if ($allGrades->count() == 0) {
            return [
                'moyenne_classe' => 0,
                'meilleure_note' => 0,
                'plus_basse_note' => 0,
                'ecart_type' => 0
            ];
        }
        
        // Calculer les statistiques
        $grades = $allGrades->map(function ($grade) {
            return ($grade->score / $grade->max_score) * 20;
        });
        
        $moyenne = $grades->avg();
        $max = $grades->max();
        $min = $grades->min();
        
        // Calculer l'écart-type
        $variance = $grades->map(function ($grade) use ($moyenne) {
            return pow($grade - $moyenne, 2);
        })->avg();
        $ecartType = sqrt($variance);
        
        return [
            'moyenne_classe' => round($moyenne, 2),
            'meilleure_note' => round($max, 2),
            'plus_basse_note' => round($min, 2),
            'ecart_type' => round($ecartType, 2)
        ];
    }
    
    /**
     * Calculer le bilan général
     */
    private function calculateGeneralBalance($trimesterData)
    {
        if (empty($trimesterData)) {
            return [
                'trimestres_disponibles' => [],
                'moyenne_generale' => 0,
                'evolution' => 'N/C'
            ];
        }
        
        $trimestres = array_keys($trimesterData);
        $moyennes = array_column($trimesterData, 'cumulative_score');
        
        $moyenneGenerale = array_sum($moyennes) / count($moyennes);
        
        // Calculer l'évolution
        $evolution = 'N/C';
        if (count($moyennes) > 1) {
            $diff = end($moyennes) - reset($moyennes);
            if ($diff > 0) {
                $evolution = 'En progression';
            } elseif ($diff < 0) {
                $evolution = 'En régression';
            } else {
                $evolution = 'Stable';
            }
        }
        
        return [
            'trimestres_disponibles' => $trimestres,
            'moyenne_generale' => round($moyenneGenerale, 2),
            'evolution' => $evolution
        ];
    }
    
    /**
     * Générer un matricule unique pour le bulletin
     */
    private function generateBulletinMatricule($studentId, $academicYearId)
    {
        // Générer un matricule de 12 chiffres : YYYYSSSSTTTT
        // YYYY = année, SSSS = student ID paddé, TTTT = timestamp modifié
        $year = date('Y');
        $studentPart = str_pad($studentId, 4, '0', STR_PAD_LEFT);
        $timePart = str_pad(substr(time(), -4), 4, '0', STR_PAD_LEFT);
        
        return $year . $studentPart . $timePart;
    }
    
    /**
     * Générer et télécharger le PDF du bulletin d'un trimestre
     */
    public function downloadBulletinPDF(Request $request, string $studentId)
    {
        try {
            $trimester = $request->get('trimester', '1er trimestre');
            
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            // Ce dossier est-il le sien, celui de son enfant, ou celui d'un
            // eleve de sa classe ? Sans cette verification, changer le chiffre
            // dans l'URL suffisait a lire le dossier de n'importe qui.
            \App\Support\AccesEleve::verifier($student);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            // Récupérer les notes du trimestre spécifié
            $grades = StudentGrade::with(['subject', 'teacher'])
                ->where('student_id', $studentId)
                ->where('class_id', $class->id)
                ->where('term', $trimester)
                ->get();
            
            if ($grades->count() === 0) {
                return redirect()->back()->with('error', 'Aucune note disponible pour ce trimestre.');
            }
            
            // Calculer les données du trimestre
            $trimesterData = $this->calculateTrimesterGrades($grades, $class, $studentId);
            
            // Préparer les données pour le PDF
            $tableDataForPDF = [];
            $totalsRowForPDF = [];
            
            foreach ($trimesterData['subjects'] as $subject) {
                $tableDataForPDF[] = [
                    $subject['name'],
                    $subject['average'] . '/20',
                    $subject['coefficient'],
                    number_format($subject['average'] * $subject['coefficient'], 2),
                    $subject['rank'],
                    '0h00',
                    $subject['appreciation'],
                    $subject['teacher_name']
                ];
            }
            
            $totalsRowForPDF = [
                'TOTAUX',
                $trimesterData['cumulative_score'] . '/20',
                array_sum(array_column($trimesterData['subjects'], 'coefficient')),
                number_format(array_sum(array_column($trimesterData['subjects'], 'average')) * array_sum(array_column($trimesterData['subjects'], 'coefficient')), 2),
                'N/C',
                '0h00',
                $trimesterData['cumulative_score'] >= 10 ? "Admis" : "Non admis",
                ''
            ];
            
            // Données pour le PDF
            $pdfData = [
                'student' => $student,
                'class' => $class,
                'academicYear' => $academicYear,
                'trimester' => $trimester,
                'trimesterData' => $trimesterData,
                'tableDataForPDF' => $tableDataForPDF,
                'totalsRowForPDF' => $totalsRowForPDF,
                'schoolName' => 'Établissement Scolaire',
                'schoolSettings' => null
            ];
            
            // Générer le PDF
            $pdf = \PDF::loadView('grades.bulletin-pdf', $pdfData);
            
            $filename = 'Bulletin_' . $student->first_name . '_' . $student->last_name . '_' . $trimester . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du PDF du bulletin', [
                'student_id' => $studentId,
                'trimester' => $request->get('trimester'),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Erreur lors de la génération du PDF.');
        }
    }

    /**
     * Afficher le bulletin sous forme de carte
     */
    public function showBulletinCarte(string $studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            // Ce dossier est-il le sien, celui de son enfant, ou celui d'un
            // eleve de sa classe ? Sans cette verification, changer le chiffre
            // dans l'URL suffisait a lire le dossier de n'importe qui.
            \App\Support\AccesEleve::verifier($student);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
            // Récupérer le professeur principal
            $principalTeacher = $class->allTeachers()->wherePivot('role', 'principal')->first();
            $principalTeacherName = $principalTeacher ? $principalTeacher->first_name . ' ' . $principalTeacher->last_name : 'N/C';
            
            // Données simplifiées pour les trimestres
            $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
            $trimesterData = [];
            
            // Initialiser tous les trimestres avec le statut "Non disponible"
            foreach ($trimesters as $trimester) {
                $trimesterData[$trimester] = [
                    'subjects' => [],
                    'cumulative_score' => 0,
                    'total_subjects' => 0,
                    'rank' => 'N/C',
                    'is_available' => false,
                    'status' => 'Non disponible'
                ];
            }
            
            // Récupérer les notes pour chaque trimestre
            foreach ($trimesters as $trimester) {
                $grades = StudentGrade::with(['subject', 'teacher'])
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('term', $trimester)
                    ->get();
                
                if ($grades->count() > 0) {
                    $subjects = [];
                    $totalScore = 0;
                    $totalCoefficient = 0;
                    
                    foreach ($grades as $grade) {
                        // Calculer la moyenne sur 20
                        $average = $grade->max_score > 0 ? round(($grade->score / $grade->max_score) * 20, 2) : 0;
                        
                        $subjectData = [
                            'name' => $grade->subject->name,
                            'average' => $average,
                            'coefficient' => $grade->subject->coefficient ?? 1,
                            'rank' => 'N/C',
                            'teacher_name' => $grade->teacher ? $grade->teacher->first_name . ' ' . $grade->teacher->last_name : 'N/C',
                            'appreciation' => $this->getAppreciation($average)
                        ];
                        
                        $subjects[] = $subjectData;
                        $totalScore += $average * ($grade->subject->coefficient ?? 1);
                        $totalCoefficient += $grade->subject->coefficient ?? 1;
                    }
                    
                    $cumulativeScore = $totalCoefficient > 0 ? $totalScore / $totalCoefficient : 0;
                    
                    $trimesterData[$trimester] = [
                        'subjects' => $subjects,
                        'cumulative_score' => round($cumulativeScore, 2),
                        'total_subjects' => count($subjects),
                        'rank' => 'N/C',
                        'is_available' => true,
                        'status' => 'Disponible'
                    ];
                }
            }
            
            return view('grades.bulletin-carte', compact(
                'student', 
                'class', 
                'academicYear',
                'trimesterData',
                'principalTeacherName'
            ));
            
        } catch (\Exception $e) {
            return redirect()->route('grades.index')->with('error', 'Erreur lors de l\'affichage du bulletin.');
        }
    }

    /**
     * Récupérer l'état des trimestres d'un élève (API)
     */
    public function getStudentTrimesters($studentId)
    {
        try {
            // Vérifier que l'élève existe
            $student = Student::find($studentId);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élève non trouvé',
                    'error' => 'Student not found'
                ], 404);
            }
            
            // Récupérer l'année académique courante
            $academicYear = AcademicYear::where('is_current', true)->first();
            if (!$academicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année académique courante trouvée',
                    'error' => 'No current academic year'
                ], 500);
            }
            
            $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
            $trimesterStatus = [];
            
            foreach ($trimesters as $trimester) {
                $grades = StudentGrade::with('subject')
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('term', $trimester)
                    ->get();
                
                $trimesterStatus[$trimester] = [
                    'name' => $trimester,
                    'has_grades' => $grades->count() > 0,
                    'grades_count' => $grades->count(),
                    'subjects_with_grades' => $grades->pluck('subject.name')->unique()->values()->toArray(),
                    'average_score' => $grades->count() > 0 ? round($grades->avg('average_score'), 2) : 0,
                    'status' => $grades->count() > 0 ? 'completed' : 'pending',
                    'status_text' => $grades->count() > 0 ? 'Complété' : 'En attente',
                    'status_color' => $grades->count() > 0 ? 'success' : 'warning'
                ];
            }
            
            // Déterminer le prochain trimestre à traiter
            $nextTrimester = null;
            foreach ($trimesters as $trimester) {
                if (!$trimesterStatus[$trimester]['has_grades']) {
                    $nextTrimester = $trimester;
                    break;
                }
            }
            
            // Si tous les trimestres sont complétés
            $allCompleted = collect($trimesterStatus)->every(function ($status) {
                return $status['has_grades'];
            });
            
            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'student_id' => $student->student_id ?? 'N/A'
                ],
                'trimesters' => $trimesterStatus,
                'next_trimester' => $nextTrimester,
                'all_completed' => $allCompleted,
                'academic_year' => $academicYear->name
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur dans getStudentTrimesters', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les données des trimestres pour un élève (version simplifiée)
     */
    private function getStudentTrimesterData($studentId, $classId)
    {
        try {
            $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
            $trimesterData = [];
            
            // Récupérer la classe avec ses relations
            $class = SchoolClass::with('level')->find($classId);
            if (!$class) {
                throw new \Exception('Classe non trouvée');
            }
            
            // Récupérer toutes les notes de l'élève avec les matières
            $allGrades = StudentGrade::with('subject')
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->get();
            
            foreach ($trimesters as $trimester) {
                $grades = $allGrades->where('term', $trimester);
                
                if ($grades->count() > 0) {
                    // Calculer la moyenne pondérée avec les coefficients
                    $totalWeightedScore = 0;
                    $totalCoefficient = 0;
                    $subjects = [];
                    
                    // Grouper par matière pour calculer les moyennes pondérées
                    $gradesBySubject = $grades->groupBy('subject_id');
                    
                    foreach ($gradesBySubject as $subjectId => $subjectGrades) {
                        $subject = $subjectGrades->first()->subject ?? null;
                        if (!$subject) continue;
                        
                        // Calculer la moyenne de la matière
                        $subjectTotalScore = $subjectGrades->sum('score');
                        $subjectTotalMaxScore = $subjectGrades->sum('max_score');
                        $subjectAverage = $subjectTotalMaxScore > 0 ? round(($subjectTotalScore / $subjectTotalMaxScore) * 20, 2) : 0;
                        
                        // Récupérer le coefficient
                        $coefficient = $this->getSubjectCoefficient($subject, $class);
                        
                        // Ajouter les détails de la matière
                        $subjects[] = [
                            'subject_id' => $subjectId,
                            'subject_name' => $subject->name,
                            'average' => $subjectAverage,
                            'coefficient' => $coefficient,
                            'teacher_name' => 'N/A' // Pas de teacher dans cette version simplifiée
                        ];
                        
                        // Ajouter à la moyenne pondérée
                        $totalWeightedScore += $subjectAverage * $coefficient;
                        $totalCoefficient += $coefficient;
                    }
                    
                    $average = $totalCoefficient > 0 ? round($totalWeightedScore / $totalCoefficient, 2) : 0;
                    
                    $trimesterData[$trimester] = [
                        'is_available' => true,
                        'average' => $average,
                        'total_notes' => $grades->count(),
                        'status' => 'Disponible',
                        'subjects' => $subjects
                    ];
                } else {
                    $trimesterData[$trimester] = [
                        'is_available' => false,
                        'average' => 0,
                        'total_notes' => 0,
                        'status' => 'Non disponible'
                    ];
                }
            }
            
            return $trimesterData;
        } catch (\Exception $e) {
            Log::error('Erreur getStudentTrimesterData: ' . $e->getMessage());
            // En cas d'erreur, retourner des données par défaut
            $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
            $trimesterData = [];
            
            foreach ($trimesters as $trimester) {
                $trimesterData[$trimester] = [
                    'is_available' => false,
                    'average' => 0,
                    'total_notes' => 0,
                    'status' => 'Non disponible'
                ];
            }
            
            return $trimesterData;
        }
    }

    /**
     * Récupérer le coefficient d'une matière selon la classe et la série
     */
    private function getSubjectCoefficient($subject, $class)
    {
        // Si la classe a une série (lycée), utiliser les coefficients par série
        if ($class->series) {
            return $this->getCoefficientForSeries($class->series, $subject->name);
        }
        
        // Sinon, utiliser le coefficient de la matière ou 1 par défaut
        return $subject->coefficient ?? 1;
    }

    /**
     * Get coefficient for a subject based on series
     */
    private function getCoefficientForSeries($series, $subjectName)
    {
        $coefficients = [
            'S' => [
                'Mathématiques' => 7, 'Sciences physiques' => 6, 'Sciences de la Vie et de la Terre' => 6,
                'Français' => 4, 'Histoire-Géographie' => 3, 'Anglais' => 2, 'Philosophie' => 4,
                'Éducation physique et sportive' => 1
            ],
            'C' => [
                'Mathématiques' => 7, 'Sciences physiques' => 6, 'Sciences de la Vie et de la Terre' => 5,
                'Français' => 4, 'Histoire-Géographie' => 3, 'Anglais' => 2, 'Philosophie' => 4,
                'Éducation physique et sportive' => 1
            ],
            'D' => [
                'Sciences de la Vie et de la Terre' => 7, 'Mathématiques' => 5, 'Sciences physiques' => 5,
                'Français' => 4, 'Histoire-Géographie' => 3, 'Anglais' => 2, 'Philosophie' => 4,
                'Éducation physique et sportive' => 1
            ],
            'A1' => [
                'Français' => 6, 'Littérature' => 5, 'Latin' => 4, 'Histoire-Géographie' => 4,
                'Anglais' => 3, 'Mathématiques' => 2, 'Philosophie' => 4, 'Éducation physique et sportive' => 1
            ],
            'A2' => [
                'Français' => 6, 'Littérature' => 5, 'Espagnol' => 4, 'Histoire-Géographie' => 4,
                'Anglais' => 3, 'Mathématiques' => 2, 'Philosophie' => 4, 'Éducation physique et sportive' => 1
            ],
            'B' => [
                'Sciences économiques et sociales' => 7, 'Mathématiques' => 5, 'Français' => 4,
                'Histoire-Géographie' => 4, 'Anglais' => 3, 'Philosophie' => 4, 'Éducation physique et sportive' => 1
            ],
            'E' => [
                'Technologie industrielle' => 8, 'Mathématiques' => 6, 'Sciences physiques' => 5,
                'Français' => 3, 'Histoire-Géographie' => 2, 'Anglais' => 2, 'Philosophie' => 3,
                'Éducation physique et sportive' => 1
            ],
            'LE' => [
                'Français' => 6, 'Littérature' => 5, 'Histoire-Géographie' => 4, 'Anglais' => 3,
                'Mathématiques' => 2, 'Philosophie' => 4, 'Éducation physique et sportive' => 1
            ]
        ];

        if (!$series) {
            return 1; // Coefficient par défaut
        }

        // Extraire la série pure (ex: "2NDE-S" -> "S", "1ERE-C" -> "C")
        $pureSeries = preg_replace('/^.*?([A-Z]+)$/', '$1', $series);
        
        if (!isset($coefficients[$pureSeries])) {
            return 1; // Coefficient par défaut
        }

        return $coefficients[$pureSeries][$subjectName] ?? 1;
    }
}
