<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompetencyEvaluationController extends Controller
{
    /**
     * Afficher la liste des classes pour l'évaluation par compétences
     */
    public function index()
    {
        $classes = SchoolClass::with(['level'])
            ->whereHas('level', fn ($q) => $q->where('cycle', 'primaire'))
            ->active()
            ->orderBy('name')
            ->get();

        $anneeCourante = \App\Models\AcademicYear::where('is_current', true)->value('id');

        // Effectif de chaque classe : la page servait des cartes sans indiquer
        // combien d'eleves attendaient d'etre evalues.
        $effectifs = DB::table('enrollments')
            ->whereIn('class_id', $classes->pluck('id'))
            ->where('status', 'active')
            ->when($anneeCourante, fn ($q) => $q->where('academic_year_id', $anneeCourante))
            ->selectRaw('class_id, count(*) as n')
            ->groupBy('class_id')
            ->pluck('n', 'class_id');

        // Evaluations deja saisies, par classe.
        $evaluations = DB::table('student_competency_evaluations')
            ->whereIn('class_id', $classes->pluck('id'))
            ->selectRaw('class_id, count(*) as n, count(distinct student_id) as eleves')
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        return view('competency-evaluations.index', [
            'classes' => $classes,
            'effectifs' => $effectifs,
            'evaluations' => $evaluations,
            // Sans referentiel de competences, rien ne peut etre evalue :
            // la page doit le dire au lieu de laisser cliquer dans le vide.
            'nombreCompetences' => DB::table('competencies')->count(),
        ]);
    }

    /**
     * Afficher le formulaire d'évaluation pour une classe
     */
    public function create(Request $request)
    {
        $classId = $request->get('class_id');
        $palier = $request->get('palier', 1);
        
        if (!$classId) {
            return redirect()->route('competency-evaluations.index')
                ->with('error', 'Veuillez sélectionner une classe.');
        }

        $class = SchoolClass::with(['level', 'students'])->findOrFail($classId);
        
        // Vérifier que c'est une classe primaire
        if ($class->level->cycle !== 'primaire') {
            return redirect()->route('competency-evaluations.index')
                ->with('error', 'Ce système est réservé aux classes primaires.');
        }

        // Récupérer les compétences par domaine
        $competencies = Competency::active()
            ->with(['criteria' => function($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->orderBy('subject_area')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('subject_area');

        // Récupérer les élèves de la classe
        $students = $class->students()
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->get();

        // Récupérer les évaluations existantes pour ce palier
        $existingEvaluations = StudentCompetencyEvaluation::where('class_id', $classId)
            ->where('palier', $palier)
            ->where('academic_year_id', AcademicYear::where('is_current', true)->first()->id)
            ->get()
            ->keyBy(function($evaluation) {
                return $evaluation->student_id . '_' . $evaluation->competency_id;
            });

        return view('competency-evaluations.create', compact(
            'class', 'competencies', 'students', 'palier', 'existingEvaluations'
        ));
    }

    /**
     * Enregistrer les évaluations de compétences
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'palier' => 'required|integer|min:1|max:5',
            'student_id' => 'required|exists:students,id',
            'evaluations' => 'required|array',
            'evaluations.*.student_id' => 'required|exists:students,id',
            'evaluations.*.competency_id' => 'required|exists:competencies,id',
            'evaluations.*.c1_points' => 'nullable|integer|min:0',
            'evaluations.*.c2_points' => 'nullable|integer|min:0',
            'evaluations.*.c3_points' => 'nullable|integer|min:0',
            'evaluations.*.c1_max_points' => 'required|integer|min:1',
            'evaluations.*.c2_max_points' => 'required|integer|min:1',
            'evaluations.*.c3_max_points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $classId = $request->class_id;
            $palier = $request->palier;
            $academicYearId = AcademicYear::where('is_current', true)->first()?->id ?? 1;
            $teacherId = auth()->id() ?? 1; // L'enseignant connecté ou admin par défaut

            foreach ($request->evaluations as $evaluationData) {
                // Calculer les totaux
                $totalPointsObtained = ($evaluationData['c1_points'] ?? 0) + 
                                     ($evaluationData['c2_points'] ?? 0) + 
                                     ($evaluationData['c3_points'] ?? 0);
                
                $totalPointsMax = $evaluationData['c1_max_points'] + 
                                 $evaluationData['c2_max_points'] + 
                                 $evaluationData['c3_max_points'];

                // Calculer le niveau de maîtrise de la compétence (basé sur les points absolus)
                $masteryLevel = $this->calculateMasteryLevel($totalPointsObtained);

                // Vérifier si une évaluation existe déjà
                $existingEvaluation = StudentCompetencyEvaluation::where([
                    'student_id' => $evaluationData['student_id'],
                    'competency_id' => $evaluationData['competency_id'],
                    'class_id' => $classId,
                    'palier' => $palier,
                    'academic_year_id' => $academicYearId
                ])->first();

                $evaluationDataToSave = [
                    'student_id' => $evaluationData['student_id'],
                    'competency_id' => $evaluationData['competency_id'],
                    'class_id' => $classId,
                    'academic_year_id' => $academicYearId,
                    'teacher_id' => $teacherId,
                    'palier' => $palier,
                    'evaluation_date' => now(),
                    'c1_points' => $evaluationData['c1_points'] ?? 0,
                    'c2_points' => $evaluationData['c2_points'] ?? 0,
                    'c3_points' => $evaluationData['c3_points'] ?? 0,
                    'c4_points' => 0, // Toujours 0 car on n'utilise que 3 critères
                    'c1_max_points' => $evaluationData['c1_max_points'],
                    'c2_max_points' => $evaluationData['c2_max_points'],
                    'c3_max_points' => $evaluationData['c3_max_points'],
                    'c4_max_points' => 0, // Toujours 0 car on n'utilise que 3 critères
                    'total_points_obtained' => $totalPointsObtained,
                    'total_points_max' => $totalPointsMax,
                    'competency_mastery' => $masteryLevel,
                    'subject_mastery' => null, // Sera calculé après toutes les évaluations
                    'palier_mastery' => null, // Sera calculé après toutes les évaluations
                    'comments' => $evaluationData['comments'] ?? null,
                ];

                if ($existingEvaluation) {
                    $existingEvaluation->update($evaluationDataToSave);
                } else {
                    StudentCompetencyEvaluation::create($evaluationDataToSave);
                }
            }

            // Calculer les maîtrises de matière et de palier pour tous les élèves concernés
            $affectedStudents = collect($request->evaluations)->pluck('student_id')->unique();
            foreach ($affectedStudents as $studentId) {
                // Récupérer les compétences de l'élève pour ce palier
                $studentEvaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                    ->where('palier', $palier)
                    ->where('academic_year_id', $academicYearId)
                    ->where('class_id', $classId)
                    ->with('competency')
                    ->get();

                // Calculer et mettre à jour la maîtrise de chaque matière
                foreach ($studentEvaluations->groupBy('competency.subject_area') as $subjectArea => $evaluations) {
                    $subjectMastery = $this->calculateSubjectMastery($studentId, $subjectArea, $palier, $academicYearId, $classId);
                    
                    // Mettre à jour toutes les évaluations de cette matière
                    foreach ($evaluations as $evaluation) {
                        $evaluation->update(['subject_mastery' => $subjectMastery]);
                    }
                }

                // Calculer et mettre à jour la maîtrise du palier
                $palierMastery = $this->calculatePalierMastery($studentId, $palier, $academicYearId, $classId);
                StudentCompetencyEvaluation::where('student_id', $studentId)
                    ->where('palier', $palier)
                    ->where('academic_year_id', $academicYearId)
                    ->where('class_id', $classId)
                    ->update(['palier_mastery' => $palierMastery]);
            }

            DB::commit();

            Log::info('Évaluations de compétences enregistrées', [
                'class_id' => $classId,
                'palier' => $palier,
                'evaluations_count' => count($request->evaluations)
            ]);

            return redirect()->route('competency-evaluations.index')
                ->with('success', 'Les évaluations de compétences ont été enregistrées avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement des évaluations de compétences', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
        }
    }

    /**
     * Enregistrer les évaluations d'un seul élève pour un palier spécifique
     */
    public function storeSingleStudent(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'palier' => 'required|integer|min:1|max:5',
            'student_id' => 'required|exists:students,id',
            'evaluations' => 'required|array',
            'evaluations.*.competency_id' => 'required|exists:competencies,id',
            'evaluations.*.c1_points' => 'nullable|integer|min:0',
            'evaluations.*.c2_points' => 'nullable|integer|min:0',
            'evaluations.*.c3_points' => 'nullable|integer|min:0',
            'evaluations.*.c1_max_points' => 'required|integer|min:1',
            'evaluations.*.c2_max_points' => 'required|integer|min:1',
            'evaluations.*.c3_max_points' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $classId = $request->class_id;
            $palier = $request->palier;
            $studentId = $request->student_id;
            $academicYearId = AcademicYear::where('is_current', true)->first()?->id ?? 1;
            $teacherId = auth()->id() ?? 1;

            foreach ($request->evaluations as $evaluationData) {
                // Calculer les totaux
                $totalPointsObtained = ($evaluationData['c1_points'] ?? 0) + 
                                     ($evaluationData['c2_points'] ?? 0) + 
                                     ($evaluationData['c3_points'] ?? 0);
                
                $totalPointsMax = $evaluationData['c1_max_points'] + 
                                 $evaluationData['c2_max_points'] + 
                                 $evaluationData['c3_max_points'];

                // Calculer le niveau de maîtrise de la compétence (basé sur les points absolus)
                $masteryLevel = $this->calculateMasteryLevel($totalPointsObtained);

                // Vérifier si une évaluation existe déjà
                $existingEvaluation = StudentCompetencyEvaluation::where([
                    'student_id' => $studentId,
                    'competency_id' => $evaluationData['competency_id'],
                    'class_id' => $classId,
                    'palier' => $palier,
                    'academic_year_id' => $academicYearId
                ])->first();

                $evaluationDataToSave = [
                    'student_id' => $studentId,
                    'competency_id' => $evaluationData['competency_id'],
                    'class_id' => $classId,
                    'academic_year_id' => $academicYearId,
                    'teacher_id' => $teacherId,
                    'palier' => $palier,
                    'evaluation_date' => now(),
                    'c1_points' => $evaluationData['c1_points'] ?? 0,
                    'c2_points' => $evaluationData['c2_points'] ?? 0,
                    'c3_points' => $evaluationData['c3_points'] ?? 0,
                    'c4_points' => 0, // Toujours 0 car on n'utilise que 3 critères
                    'c1_max_points' => $evaluationData['c1_max_points'],
                    'c2_max_points' => $evaluationData['c2_max_points'],
                    'c3_max_points' => $evaluationData['c3_max_points'],
                    'c4_max_points' => 0, // Toujours 0 car on n'utilise que 3 critères
                    'total_points_obtained' => $totalPointsObtained,
                    'total_points_max' => $totalPointsMax,
                    'competency_mastery' => $masteryLevel,
                    'subject_mastery' => null, // Sera calculé après toutes les évaluations
                    'palier_mastery' => null, // Sera calculé après toutes les évaluations
                    'comments' => $evaluationData['comments'] ?? null,
                ];

                if ($existingEvaluation) {
                    $existingEvaluation->update($evaluationDataToSave);
                } else {
                    StudentCompetencyEvaluation::create($evaluationDataToSave);
                }
            }

            // Calculer les maîtrises de matière et de palier pour l'élève
            $studentEvaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                ->where('palier', $palier)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->with('competency')
                ->get();

            // Calculer et mettre à jour la maîtrise de chaque matière
            foreach ($studentEvaluations->groupBy('competency.subject_area') as $subjectArea => $evaluations) {
                $subjectMastery = $this->calculateSubjectMastery($studentId, $subjectArea, $palier, $academicYearId, $classId);
                
                // Mettre à jour toutes les évaluations de cette matière
                foreach ($evaluations as $evaluation) {
                    $evaluation->update(['subject_mastery' => $subjectMastery]);
                }
            }

            // Calculer et mettre à jour la maîtrise du palier
            $palierMastery = $this->calculatePalierMastery($studentId, $palier, $academicYearId, $classId);
            StudentCompetencyEvaluation::where('student_id', $studentId)
                ->where('palier', $palier)
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->update(['palier_mastery' => $palierMastery]);

            DB::commit();

            Log::info('Évaluations de compétences enregistrées pour un élève', [
                'class_id' => $classId,
                'student_id' => $studentId,
                'palier' => $palier,
                'evaluations_count' => count($request->evaluations)
            ]);

            return redirect()->route('competency-evaluations.create', [
                'class_id' => $classId,
                'palier' => $palier
            ])->with('success', 'Les évaluations de compétences ont été enregistrées avec succès pour cet élève.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement des évaluations de compétences', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
        }
    }

    /**
     * Afficher les bulletins de compétences pour une classe
     */
    public function showBulletins($classId, $palier = null)
    {
        $class = SchoolClass::with(['level'])->findOrFail($classId);
        
        if ($class->level->cycle !== 'primaire') {
            return redirect()->route('competency-evaluations.index')
                ->with('error', 'Ce système est réservé aux classes primaires.');
        }

        $students = $class->students()
            ->wherePivot('status', 'active')
            ->orderBy('first_name')
            ->get();

        // Si un palier spécifique est demandé, on l'utilise, sinon on prend le dernier palier évalué
        if (!$palier) {
            $lastEvaluation = StudentCompetencyEvaluation::where('class_id', $classId)
                ->where('academic_year_id', AcademicYear::where('is_current', true)->first()->id)
                ->max('palier');
            $palier = $lastEvaluation ?: 1;
        }

        // Récupérer les évaluations pour ce palier
        $evaluations = StudentCompetencyEvaluation::where('class_id', $classId)
            ->where('palier', $palier)
            ->where('academic_year_id', AcademicYear::where('is_current', true)->first()->id)
            ->with(['student', 'competency'])
            ->get()
            ->groupBy('student_id');

        // Récupérer les compétences pour l'affichage
        $competencies = Competency::active()
            ->orderBy('subject_area')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('subject_area');

        $academicYear = AcademicYear::where('is_current', true)->first();
        
        return view('competency-evaluations.bulletins', compact(
            'class', 'students', 'evaluations', 'competencies', 'palier', 'academicYear'
        ));
    }

    /**
     * Générer le bulletin PDF pour un élève
     */
    public function generateStudentBulletin($studentId, $palier = null)
    {
        $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
        $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
        
        if (!$currentEnrollment || $currentEnrollment->schoolClass->level->cycle !== 'primaire') {
            return redirect()->route('competency-evaluations.index')
                ->with('error', 'Élève non trouvé ou classe non primaire.');
        }

        $academicYear = AcademicYear::where('is_current', true)->first();
        $request = request();
        
        // Vérifier si c'est un bulletin annuel
        $isAnnual = $request->has('annual') || $request->get('annual') === 'true';
        $allPaliersData = null; // Initialiser la variable
        
        // Debug
        Log::info('Bulletin annuel - Debug', [
            'has_annual' => $request->has('annual'),
            'get_annual' => $request->get('annual'),
            'isAnnual' => $isAnnual,
            'all_params' => $request->all()
        ]);
        
        if ($isAnnual) {
            // BULLETIN ANNUEL : Récupérer TOUS les paliers (1-5) pour faire un bilan complet
            $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                ->where('academic_year_id', $academicYear->id)
                ->where('class_id', $currentEnrollment->schoolClass->id)
                ->with(['competency'])
                ->get()
                ->groupBy('palier');
            
            // PAS de palier spécifique pour l'annuel, c'est un assemblage de TOUS les paliers
            $palier = null;
            
            // Structure pour le bulletin annuel : TOUS les paliers (1-5) + profil de sortie
            $allPaliersData = [];
            for ($p = 1; $p <= 5; $p++) {
                $palierEvaluations = $evaluations->get($p, collect());
                $allPaliersData[] = [
                    'palier' => $p,
                    'evaluations' => $palierEvaluations,
                    'hasData' => $palierEvaluations->count() > 0
                ];
            }
        } else {
            // Si un palier spécifique est demandé via query string, on l'utilise
            if ($request->has('palier')) {
                $palier = $request->get('palier');
            } elseif (!$palier) {
                // Sinon on prend le dernier palier évalué
                $lastEvaluation = StudentCompetencyEvaluation::where('student_id', $studentId)
                    ->where('academic_year_id', $academicYear->id)
                    ->max('palier');
                $palier = $lastEvaluation ?: 1;
            }

            $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                ->where('palier', $palier)
                ->where('academic_year_id', $academicYear->id)
                ->with(['competency'])
                ->get()
                ->groupBy('competency.subject_area');
        }

        $competencies = Competency::active()
            ->orderBy('subject_area')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('subject_area');

        // Obtenir le nom de l'établissement selon le niveau de l'élève
        $schoolName = \App\Helpers\SchoolHelper::getSchoolNameByLevel($currentEnrollment->schoolClass->level);
        
        // Charger les paramètres de l'établissement
        $schoolSettings = \App\Models\SchoolSettings::getSettings();

        // Générer le PDF
        $viewData = compact(
            'student', 'currentEnrollment', 'evaluations', 'competencies', 'palier', 'isAnnual', 'academicYear', 'schoolName', 'schoolSettings'
        );
        
        // Ajouter les données des paliers pour le bulletin annuel
        if ($isAnnual && isset($allPaliersData)) {
            $viewData['allPaliersData'] = $allPaliersData;
        }
        
        return view('competency-evaluations.student-bulletin-pdf-document-exact', $viewData);
    }

    /**
     * Calculer le niveau de maîtrise d'une compétence basé sur les points absolus
     * Selon le document officiel:
     * - Maxi (maximale): 8 à 9 points en critères minimaux
     * - Mini (minimale): 5 à 7 points en critères minimaux
     * - Part (partielle): 3 à 4 points en critères minimaux
     * - N M (non maîtrise): 0 à 2 points en critères minimaux
     */
    private function calculateMasteryLevel($totalPoints)
    {
        if ($totalPoints >= 8) return 'maximale';
        if ($totalPoints >= 5) return 'minimale';
        if ($totalPoints >= 3) return 'partielle';
        return 'non_maitrise';
    }

    /**
     * Calculer le niveau de maîtrise d'une matière selon le nombre de compétences réussies
     * Selon le document officiel:
     * 
     * Pour les matières ayant 3 compétences (EDM&EAS):
     * - Maîtrise maximale: 3 compétences réussies sur 3
     * - Maîtrise minimale: 2 compétences réussies sur 3
     * - Maîtrise partielle: 1 compétence réussie sur 3
     * - Non maîtrise: 0 compétence réussie sur 3
     * 
     * Pour les matières ayant 2 compétences (Français, Mathématiques):
     * - Maîtrise maximale: 2 compétences réussies sur 2
     * - Maîtrise minimale: 1 compétence réussie sur 2
     * - Non maîtrise: 0 compétence réussie sur 2
     * 
     * Une compétence est réussie si elle a au moins une maîtrise minimale (5+ points)
     */
    private function calculateSubjectMastery($studentId, $subjectArea, $palier, $academicYearId, $classId)
    {
        // Récupérer toutes les compétences de la matière pour cet élève et ce palier
        $competencyIds = Competency::where('subject_area', $subjectArea)->pluck('id');
        
        $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
            ->where('palier', $palier)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->whereIn('competency_id', $competencyIds)
            ->get();
        
        $totalCompetencies = $competencyIds->count();
        $successfulCompetencies = $evaluations->filter(function($eval) {
            // Une compétence est réussie si elle a une maîtrise minimale ou maximale
            return in_array($eval->competency_mastery, ['minimale', 'maximale']);
        })->count();
        
        // Calcul selon le nombre de compétences de la matière
        if ($totalCompetencies == 3) {
            // Pour EDM&EAS (3 compétences)
            if ($successfulCompetencies == 3) return 'maximale';
            if ($successfulCompetencies == 2) return 'minimale';
            if ($successfulCompetencies == 1) return 'partielle';
            return 'non_maitrise';
        } else if ($totalCompetencies == 2) {
            // Pour Français et Mathématiques (2 compétences)
            if ($successfulCompetencies == 2) return 'maximale';
            if ($successfulCompetencies == 1) return 'minimale';
            return 'non_maitrise';
        }
        
        return 'non_maitrise';
    }

    /**
     * Calculer le niveau de maîtrise d'un palier selon le nombre de matières réussies
     * Selon le document officiel:
     * - Maîtrise maximale: 3 matières réussies sur 3
     * - Maîtrise minimale: 2 matières réussies sur 3
     * - Maîtrise partielle: 1 matière réussie sur 3
     * - Non maîtrise: 0 matière réussie sur 3
     * 
     * Une matière est réussie si elle a au moins une maîtrise minimale
     */
    private function calculatePalierMastery($studentId, $palier, $academicYearId, $classId)
    {
        // Calculer la maîtrise de chaque matière
        $subjects = ['EDM & EAS', 'Français', 'Mathématiques'];
        $successfulSubjects = 0;
        
        foreach ($subjects as $subject) {
            $subjectMastery = $this->calculateSubjectMastery($studentId, $subject, $palier, $academicYearId, $classId);
            if (in_array($subjectMastery, ['minimale', 'maximale'])) {
                $successfulSubjects++;
            }
        }
        
        // Calcul du niveau de maîtrise du palier
        if ($successfulSubjects == 3) return 'maximale';
        if ($successfulSubjects == 2) return 'minimale';
        if ($successfulSubjects == 1) return 'partielle';
        return 'non_maitrise';
    }

    /**
     * Vérifier les conditions de passage en classe de 5e année
     * Selon le document officiel:
     * - Être en situation de réussite dans les 5 paliers
     * - Avoir au moins une maîtrise minimale dans les 3 derniers paliers
     * - Avoir au moins une maîtrise minimale dans les paliers 4 et 5 et une maîtrise partielle au palier 3
     */
    private function checkPassageConditions($studentId, $academicYearId, $classId)
    {
        $palierMasteries = [];
        
        for ($palier = 1; $palier <= 5; $palier++) {
            $palierMasteries[$palier] = $this->calculatePalierMastery($studentId, $palier, $academicYearId, $classId);
        }
        
        // Condition 1: Situation de réussite (minimale ou maximale) dans les 5 paliers
        $condition1 = true;
        for ($palier = 1; $palier <= 5; $palier++) {
            if (!in_array($palierMasteries[$palier], ['minimale', 'maximale'])) {
                $condition1 = false;
                break;
            }
        }
        
        // Condition 2: Maîtrise minimale dans les 3 derniers paliers (3, 4, 5)
        $condition2 = in_array($palierMasteries[3], ['minimale', 'maximale']) &&
                      in_array($palierMasteries[4], ['minimale', 'maximale']) &&
                      in_array($palierMasteries[5], ['minimale', 'maximale']);
        
        // Condition 3: Maîtrise minimale dans paliers 4 et 5 + maîtrise partielle au minimum au palier 3
        $condition3 = in_array($palierMasteries[4], ['minimale', 'maximale']) &&
                      in_array($palierMasteries[5], ['minimale', 'maximale']) &&
                      in_array($palierMasteries[3], ['partielle', 'minimale', 'maximale']);
        
        return [
            'can_pass' => $condition1 || $condition2 || $condition3,
            'conditions' => [
                'all_paliers_successful' => $condition1,
                'last_three_paliers_minimal' => $condition2,
                'paliers_4_5_minimal_3_partial' => $condition3
            ],
            'palier_masteries' => $palierMasteries
        ];
    }

    /**
     * Vérifier les conditions de passage pour un élève (API publique)
     */
    public function checkStudentPassageConditions($studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass'])->findOrFail($studentId);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élève non inscrit dans une classe active'
                ], 404);
            }

            $academicYearId = AcademicYear::where('is_current', true)->first()?->id ?? 1;
            $classId = $currentEnrollment->schoolClass->id;

            $passageConditions = $this->checkPassageConditions($studentId, $academicYearId, $classId);
            
            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'class' => $currentEnrollment->schoolClass->name
                ],
                'passage_conditions' => $passageConditions
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification des conditions de passage', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API pour obtenir les compétences par domaine
     */
    public function getCompetenciesBySubjectArea()
    {
        $competencies = Competency::active()
            ->with(['criteria' => function($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->orderBy('subject_area')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('subject_area');

        return response()->json($competencies);
    }

    /**
     * API pour obtenir les données d'un élève pour la génération PDF
     */
    public function getStudentCompetencyData($studentId, $palier)
    {
        // Debug: Log de l'appel
        Log::info('API getStudentCompetencyData appelée', [
            'student_id' => $studentId,
            'palier' => $palier,
            'request_params' => request()->all()
        ]);
        
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
            $request = request();
            $isAnnual = $request->has('annual') || $palier === 'annual';

            // Récupérer les évaluations selon le type de bulletin
            if ($isAnnual) {
                // Bulletin annuel : tous les paliers
                $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('class_id', $currentEnrollment->schoolClass->id)
                    ->with(['competency'])
                    ->get()
                    ->groupBy('palier');
                    
                // S'assurer qu'on a les 5 paliers même s'ils sont vides
                $allPaliersEvaluations = collect();
                for ($p = 1; $p <= 5; $p++) {
                    $allPaliersEvaluations->put($p, $evaluations->get($p, collect()));
                }
                $evaluations = $allPaliersEvaluations;
            } else {
                // Bulletin d'un palier spécifique
                $evaluations = StudentCompetencyEvaluation::where('student_id', $studentId)
                    ->where('palier', $palier)
                    ->where('academic_year_id', $academicYear->id)
                    ->with(['competency'])
                    ->get();
            }

            // Récupérer les compétences organisées par domaine
            $competencies = Competency::active()
                ->orderBy('subject_area')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('subject_area')
                ->map(function($competencies, $subjectArea) {
                    return [
                        'name' => $subjectArea,
                        'competencies' => $competencies
                    ];
                });

            // Récupérer les paramètres de l'école
            $schoolSettings = null;
            try {
                $schoolSettings = \App\Models\SchoolSettings::first();
            } catch (\Exception $e) {
                // Les paramètres de l'école ne sont pas disponibles
            }
            
            // Obtenir le nom de l'établissement selon le niveau de l'élève
            $schoolName = \App\Helpers\SchoolHelper::getSchoolNameByLevel($currentEnrollment->schoolClass->level);

            // Récupérer les informations complètes de l'élève
            $student->load(['parents', 'enrollments.schoolClass.level']);
            
            // Récupérer l'enseignant de la classe
            $teacher = null;
            try {
                $teacher = $currentEnrollment->schoolClass->teachers()->first();
            } catch (\Exception $e) {
                // Pas d'enseignant assigné
            }

            $responseData = [
                'success' => true,
                'studentData' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'student_id' => $student->student_id,
                    'date_of_birth' => $student->date_of_birth,
                    'place_of_birth' => $student->place_of_birth,
                    'gender' => $student->gender,
                    'nationality' => $student->nationality,
                    'photo_path' => $student->photo,
                    'class_name' => $currentEnrollment->schoolClass->name,
                    'academic_year' => $academicYear->name,
                    'palier' => $isAnnual ? 'annual' : (int)$palier,
                    'parents' => $student->parents->map(function($parent) {
                        return [
                            'name' => $parent->first_name . ' ' . $parent->last_name,
                            'phone' => $parent->phone,
                            'email' => $parent->email
                        ];
                    })->toArray(),
                    'teacher' => $teacher ? [
                        'name' => $teacher->first_name . ' ' . $teacher->last_name,
                        'grade' => $teacher->grade ?? 'Enseignant'
                    ] : null
                ],
                'competencies' => $competencies->values()->toArray(),
                'schoolSettings' => $schoolSettings ? [
                    'name' => $schoolName, // Utiliser le nom selon le niveau
                    'address' => $schoolSettings->school_address,
                    'phone' => $schoolSettings->school_phone,
                    'email' => $schoolSettings->school_email,
                    'logo_path' => $schoolSettings->logo_url,
                    'seal_path' => $schoolSettings->seal_url,
                    'bp' => $schoolSettings->school_bp,
                    'motto' => $schoolSettings->school_motto,
                    'principal_name' => $schoolSettings->principal_name,
                    'principal_title' => $schoolSettings->principal_title
                ] : null
            ];

            if ($isAnnual) {
                // BULLETIN ANNUEL : Structure les données par palier (1-5) + profil de sortie
                $allPaliersData = [];
                for ($p = 1; $p <= 5; $p++) {
                    $palierEvaluations = $evaluations->get($p, collect());
                    // Créer les données même si pas d'évaluations pour avoir la structure complète
                    $allPaliersData[] = [
                        'palier' => $p,
                        'evaluations' => $palierEvaluations->toArray(),
                        'hasData' => $palierEvaluations->count() > 0,
                        'studentData' => $responseData['studentData'],
                        'competencies' => $responseData['competencies'],
                        'schoolSettings' => $responseData['schoolSettings']
                    ];
                }
                $responseData['allPaliersData'] = $allPaliersData;
                $responseData['isAnnual'] = true;
                
                // PAS de palier spécifique pour l'annuel
                $responseData['studentData']['palier'] = null;
                
                // Debug : Log des données
                Log::info('Bulletin annuel - Données récupérées', [
                    'student_id' => $studentId,
                    'paliers_count' => count($allPaliersData),
                    'evaluations_total' => $evaluations->flatten()->count()
                ]);
            } else {
                // Pour un palier spécifique
                $responseData['evaluations'] = $evaluations->toArray();
            }

            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des données PDF', [
                'student_id' => $studentId,
                'palier' => $palier,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
            ], 500);
        }
    }
}
