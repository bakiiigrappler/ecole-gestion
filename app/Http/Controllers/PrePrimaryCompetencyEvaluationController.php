<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\PrePrimaryCompetency;
use App\Models\PrePrimaryCompetencyEvaluation;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrePrimaryCompetencyEvaluationController extends Controller
{
    /** Codes du livret, tels que la base les accepte. */
    public const CODES = [
        'MAX' => 'Maîtrise maximale',
        'MIN' => 'Maîtrise minimale',
        'PART' => 'Maîtrise partielle',
        'NM' => 'Non maîtrisée',
    ];

    /**
     * Afficher la liste des classes préprimaires
     */
    public function index()
    {
        $classes = SchoolClass::with(['level'])
            ->whereHas('level', fn ($q) => $q->where('cycle', 'preprimaire'))
            ->active()
            ->orderBy('name')
            ->get();

        $anneeCourante = \App\Models\AcademicYear::where('is_current', true)->value('id');

        // Effectif de chaque classe : la page listait les classes sans dire
        // combien d'enfants attendaient d'etre evalues.
        $effectifs = \Illuminate\Support\Facades\DB::table('enrollments')
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('status', 'active')
            ->when($anneeCourante, fn ($q) => $q->where('academic_year_id', $anneeCourante))
            ->selectRaw('class_id, count(*) as n')
            ->groupBy('class_id')
            ->pluck('n', 'class_id');

        // Avancement par classe et par trimestre : un code renseigne vaut
        // evaluation faite pour ce trimestre.
        $avancement = \Illuminate\Support\Facades\DB::table('pre_primary_competency_evaluations')
            ->whereIn('class_id', $classes->pluck('id'))
            ->selectRaw('
                class_id,
                count(distinct student_id) filter (where trimester_1_code is not null) as t1,
                count(distinct student_id) filter (where trimester_2_code is not null) as t2,
                count(distinct student_id) filter (where trimester_3_code is not null) as t3
            ')
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        return view('pre-primary-evaluations.index', [
            'classes' => $classes,
            'effectifs' => $effectifs,
            'avancement' => $avancement,
            // Sans referentiel, aucun formulaire d'evaluation n'a de contenu.
            'nombreCompetences' => \Illuminate\Support\Facades\DB::table('pre_primary_competencies')->count(),
        ]);
    }

    /**
     * Afficher le formulaire d'évaluation pour une classe et un trimestre
     */
    public function create(Request $request)
    {
        $classId = $request->get('class_id');
        $trimester = $request->get('trimester', 1);
        
        if (!$classId) {
            return redirect()->route('pre-primary-evaluations.index')
                ->with('error', 'Veuillez sélectionner une classe.');
        }

        $class = SchoolClass::with(['level', 'students'])->findOrFail($classId);
        
        // Vérifier que c'est une classe de maternelle
        if ($class->level->cycle !== 'preprimaire') {
            return redirect()->route('pre-primary-evaluations.index')
                ->with('error', 'Ce système est réservé aux classes de maternelle.');
        }

        // Récupérer les compétences par domaine
        $competencies = PrePrimaryCompetency::active()
            ->ordered()
            ->get()
            ->groupBy('domain');

        // Récupérer les élèves de la classe
        $students = $class->students()
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->get();

        $academicYearId = AcademicYear::where('is_current', true)->first()?->id;

        // Récupérer les évaluations existantes pour ce trimestre
        $existingEvaluations = PrePrimaryCompetencyEvaluation::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->keyBy(function($evaluation) {
                return $evaluation->student_id . '_' . $evaluation->pre_primary_competency_id;
            });

        // Codes d'évaluation disponibles
        /*
         * Codes du livret. Le formulaire proposait « A / AR / AB / NA », que la
         * contrainte CHECK de PostgreSQL refuse : seuls MAX, MIN, PART et NM
         * sont acceptes. Aucune evaluation n'aurait pu etre enregistree.
         */
        $codes = self::CODES;

        return view('pre-primary-evaluations.create', compact(
            'class', 'competencies', 'students', 'trimester', 'existingEvaluations', 'codes'
        ));
    }

    /**
     * Enregistrer les évaluations
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'trimester' => 'required|integer|min:1|max:3',
            'evaluations' => 'required|array',
            'evaluations.*.student_id' => 'required|exists:students,id',
            'evaluations.*.competency_id' => 'required|exists:pre_primary_competencies,id',
            'evaluations.*.code' => 'required|in:A,AR,AB,NA',
        ]);

        DB::beginTransaction();

        try {
            $classId = $request->class_id;
            $trimester = $request->trimester;
            $academicYearId = AcademicYear::where('is_current', true)->first()?->id ?? 1;
            $teacherId = auth()->id() ?? 1;

            foreach ($request->evaluations as $evaluationData) {
                // Vérifier si une évaluation existe déjà
                $existingEvaluation = PrePrimaryCompetencyEvaluation::where([
                    'student_id' => $evaluationData['student_id'],
                    'pre_primary_competency_id' => $evaluationData['competency_id'],
                    'class_id' => $classId,
                    'academic_year_id' => $academicYearId
                ])->first();

                $codeField = "trimester_{$trimester}_code";
                $commentField = "trimester_{$trimester}_comment";

                if ($existingEvaluation) {
                    // Mettre à jour l'évaluation existante
                    $existingEvaluation->update([
                        $codeField => $evaluationData['code'],
                        $commentField => $evaluationData['comment'] ?? null,
                        'teacher_id' => $teacherId,
                    ]);
                } else {
                    // Créer une nouvelle évaluation
                    $dataToSave = [
                        'student_id' => $evaluationData['student_id'],
                        'pre_primary_competency_id' => $evaluationData['competency_id'],
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'teacher_id' => $teacherId,
                        $codeField => $evaluationData['code'],
                        $commentField => $evaluationData['comment'] ?? null,
                    ];

                    PrePrimaryCompetencyEvaluation::create($dataToSave);
                }
            }

            DB::commit();

            Log::info('Évaluations préprimaire enregistrées', [
                'class_id' => $classId,
                'trimester' => $trimester,
                'evaluations_count' => count($request->evaluations)
            ]);

            return redirect()->route('pre-primary-evaluations.create', [
                'class_id' => $classId,
                'trimester' => $trimester
            ])->with('success', 'Les évaluations ont été enregistrées avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement des évaluations préprimaire', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
        }
    }

    /**
     * Afficher les bulletins pour une classe
     */
    public function showBulletins($classId)
    {
        $class = SchoolClass::with(['level'])->findOrFail($classId);
        
        if ($class->level->cycle !== 'preprimaire') {
            return redirect()->route('pre-primary-evaluations.index')
                ->with('error', 'Ce système est réservé aux classes de maternelle.');
        }

        $students = $class->students()
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->get();

        $academicYearId = AcademicYear::where('is_current', true)->first()?->id;

        // Récupérer toutes les évaluations pour cette classe
        $evaluations = PrePrimaryCompetencyEvaluation::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->with(['student', 'competency'])
            ->get()
            ->groupBy('student_id');

        // Récupérer les compétences pour l'affichage
        $competencies = PrePrimaryCompetency::active()
            ->ordered()
            ->get()
            ->groupBy('domain');

        $academicYear = AcademicYear::where('is_current', true)->first();
        
        return view('pre-primary-evaluations.bulletins', compact(
            'class', 'students', 'evaluations', 'competencies', 'academicYear'
        ));
    }

    /**
     * Générer le bulletin PDF pour un élève
     */
    public function generateStudentBulletin($studentId)
    {
        $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
        $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
        
        if (!$currentEnrollment || $currentEnrollment->schoolClass->level->cycle !== 'preprimaire') {
            return redirect()->route('pre-primary-evaluations.index')
                ->with('error', 'Élève non trouvé ou classe non maternelle.');
        }

        $academicYear = AcademicYear::where('is_current', true)->first();
        $classId = $currentEnrollment->schoolClass->id;

        // Récupérer toutes les évaluations de l'élève
        $evaluations = PrePrimaryCompetencyEvaluation::where('student_id', $studentId)
            ->where('academic_year_id', $academicYear->id)
            ->where('class_id', $classId)
            ->with(['competency'])
            ->get()
            ->keyBy('pre_primary_competency_id');

        $competencies = PrePrimaryCompetency::active()
            ->ordered()
            ->get()
            ->groupBy('domain');

        // Obtenir le nom de l'établissement
        $schoolName = \App\Helpers\SchoolHelper::getSchoolNameByLevel($currentEnrollment->schoolClass->level);
        
        // Charger les paramètres de l'établissement
        $schoolSettings = \App\Models\SchoolSettings::getSettings();

        return view('pre-primary-evaluations.student-bulletin-pdf', compact(
            'student', 'currentEnrollment', 'evaluations', 'competencies', 
            'academicYear', 'schoolName', 'schoolSettings'
        ));
    }

    /**
     * API pour obtenir les données d'un élève pour la génération PDF
     */
    public function getStudentData($studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élève non inscrit dans une classe active'
                ]);
            }

            $academicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$academicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active trouvée'
                ]);
            }

            // Récupérer les évaluations
            $evaluations = PrePrimaryCompetencyEvaluation::where('student_id', $studentId)
                ->where('academic_year_id', $academicYear->id)
                ->where('class_id', $currentEnrollment->school_class_id ?? $currentEnrollment->class_id)
                ->get();

            // Récupérer les compétences organisées par domaine
            $competencies = PrePrimaryCompetency::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('domain')
                ->map(function($comps, $domain) {
                    return [
                        'name' => $domain,
                        'competencies' => $comps->toArray()
                    ];
                });

            // Récupérer l'enseignant de la classe
            $teacher = $currentEnrollment->schoolClass->allTeachers()->first();

            // Récupérer les paramètres de l'école
            $schoolSettings = \App\Models\SchoolSettings::getSettings();

            // Construire l'URL du logo manuellement pour éviter les problèmes
            $logoUrl = null;
            if ($schoolSettings && $schoolSettings->school_logo) {
                $logoUrl = url('storage/' . $schoolSettings->school_logo);
            }

            $responseData = [
                'success' => true,
                'studentData' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_id' => $student->student_id,
                    'date_of_birth' => $student->date_of_birth ?? '',
                    'place_of_birth' => $student->place_of_birth ?? '',
                    'gender' => $student->gender ?? '',
                    'photo_path' => $student->photo ?? '',
                    'class_name' => $currentEnrollment->schoolClass->name ?? 'Non défini',
                    'academic_year' => $academicYear->name ?? '',
                    'teacher' => $teacher ? [
                        'name' => ($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? ''),
                        'grade' => 'Enseignant'
                    ] : null
                ],
                'schoolSettings' => [
                    'primary_school_name' => $schoolSettings->primary_school_name ?? 'ECOLE PRIVEE',
                    'school_phone' => $schoolSettings->school_phone ?? '',
                    'school_address' => $schoolSettings->school_address ?? '',
                    'school_bp' => $schoolSettings->school_bp ?? '',
                    'city' => $schoolSettings->city ?? '',
                    'school_motto' => $schoolSettings->school_motto ?? 'Travail - Rigueur - Discipline',
                    'school_logo' => $logoUrl,
                ],
                'evaluations' => $evaluations->toArray(),
                'competencies' => $competencies->values()->toArray()
            ];

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('Erreur API getStudentData', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques pour une classe
     */
    public function getClassStatistics($classId)
    {
        $academicYearId = AcademicYear::where('is_current', true)->first()?->id;
        
        $evaluations = PrePrimaryCompetencyEvaluation::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        // Les compteurs portaient eux aussi sur les anciens codes : ils
        // renvoyaient zero pour les quatre valeurs, quelles que soient les
        // evaluations enregistrees.
        $stats = [
            'total_students' => $evaluations->pluck('student_id')->unique()->count(),
            'total_competencies' => PrePrimaryCompetency::active()->count(),
        ];

        foreach ([1, 2, 3] as $numero) {
            $stats['trimester_'.$numero] = collect(array_keys(self::CODES))
                ->mapWithKeys(fn ($code) => [
                    $code => $evaluations->where('trimester_'.$numero.'_code', $code)->count(),
                ])
                ->all();
        }

        return response()->json($stats);
    }

    /**
     * Supprimer une évaluation
     */
    public function destroy($id)
    {
        try {
            $evaluation = PrePrimaryCompetencyEvaluation::findOrFail($id);
            $classId = $evaluation->class_id;
            $evaluation->delete();

            Log::info('Évaluation préprimaire supprimée', [
                'evaluation_id' => $id,
                'student_id' => $evaluation->student_id
            ]);

            return redirect()->route('pre-primary-evaluations.bulletins', $classId)
                ->with('success', 'Évaluation supprimée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression d\'une évaluation préprimaire', [
                'error' => $e->getMessage(),
                'evaluation_id' => $id
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Supprimer toutes les évaluations d'un élève pour une classe
     */
    public function destroyStudentEvaluations(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        try {
            $academicYearId = AcademicYear::where('is_current', true)->first()?->id;
            
            $deleted = PrePrimaryCompetencyEvaluation::where('student_id', $request->student_id)
                ->where('class_id', $request->class_id)
                ->where('academic_year_id', $academicYearId)
                ->delete();

            Log::info('Évaluations d\'un élève supprimées', [
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'count' => $deleted
            ]);

            return redirect()->route('pre-primary-evaluations.bulletins', $request->class_id)
                ->with('success', "Toutes les évaluations de l'élève ont été supprimées ($deleted évaluations).");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des évaluations d\'un élève', [
                'error' => $e->getMessage(),
                'student_id' => $request->student_id
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Réinitialiser les évaluations d'un trimestre pour une classe
     */
    public function resetTrimester(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'trimester' => 'required|integer|min:1|max:3',
        ]);

        try {
            $academicYearId = AcademicYear::where('is_current', true)->first()?->id;
            $trimester = $request->trimester;
            $codeField = "trimester_{$trimester}_code";
            $commentField = "trimester_{$trimester}_comment";
            
            $updated = PrePrimaryCompetencyEvaluation::where('class_id', $request->class_id)
                ->where('academic_year_id', $academicYearId)
                ->update([
                    $codeField => null,
                    $commentField => null
                ]);

            Log::info('Trimestre réinitialisé', [
                'class_id' => $request->class_id,
                'trimester' => $trimester,
                'count' => $updated
            ]);

            return redirect()->route('pre-primary-evaluations.create', [
                'class_id' => $request->class_id,
                'trimester' => $trimester
            ])->with('success', "Le trimestre $trimester a été réinitialisé ($updated évaluations).");

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation du trimestre', [
                'error' => $e->getMessage(),
                'class_id' => $request->class_id
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la réinitialisation.');
        }
    }
}
