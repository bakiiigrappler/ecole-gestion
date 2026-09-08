<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class SimpleGradesController extends Controller
{
    /**
     * Afficher la liste simplifiée des notes
     */
    public function index()
    {
        try {
            // Statistiques simples
            $stats = [
                'totalGrades' => StudentGrade::count(),
                'averageGrade' => StudentGrade::avg(DB::raw('(score / max_score) * 20')) ?? 0,
                'pendingGrades' => 0,
                'completionRate' => 85
            ];
            
            // Classes disponibles
            $classes = SchoolClass::where('is_active', true)->get();
            
            // Récupérer les élèves avec leurs notes de manière simplifiée
            $students = Student::with(['enrollments.schoolClass.level'])
                ->whereHas('enrollments', function($query) {
                    $query->where('status', 'active');
                })
                ->limit(50) // Limiter à 50 élèves pour éviter les timeouts
                ->get();
            
            $studentsWithGrades = [];
            
            foreach ($students as $student) {
                $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
                
                if ($currentEnrollment && $currentEnrollment->schoolClass) {
                    $class = $currentEnrollment->schoolClass;
                    
                    // Calculer la moyenne générale
                    $grades = StudentGrade::where('student_id', $student->id)
                        ->where('class_id', $class->id)
                        ->get();
                    
                    $cumulativeScore = '--';
                    $cumulativePercentage = 0;
                    $cumulativeGradeColor = 'secondary';
                    
                    if ($grades->count() > 0) {
                        $totalScore = $grades->sum('score');
                        $totalMaxScore = $grades->sum('max_score');
                        $cumulativeScore = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : 0;
                        $cumulativePercentage = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 1) : 0;
                        
                        // Déterminer la couleur de la note
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
                        } else {
                            $cumulativeGradeColor = 'danger';
                        }
                    }
                    
                    // Données des trimestres (version simplifiée)
                    $trimesterData = [
                        '1er trimestre' => ['is_available' => false, 'average' => 0, 'status' => 'Non disponible'],
                        '2ème trimestre' => ['is_available' => false, 'average' => 0, 'status' => 'Non disponible'],
                        '3ème trimestre' => ['is_available' => false, 'average' => 0, 'status' => 'Non disponible']
                    ];
                    
                    // Vérifier rapidement les trimestres disponibles
                    $trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
                    foreach ($trimesters as $trimester) {
                        $trimesterGrades = $grades->where('term', $trimester);
                        if ($trimesterGrades->count() > 0) {
                            $totalScore = $trimesterGrades->sum('score');
                            $totalMaxScore = $trimesterGrades->sum('max_score');
                            $average = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : 0;
                            
                            $trimesterData[$trimester] = [
                                'is_available' => true,
                                'average' => $average,
                                'total_notes' => $trimesterGrades->count(),
                                'status' => 'Disponible'
                            ];
                        }
                    }
                    
                    $studentsWithGrades[] = [
                        'student' => $student,
                        'cumulative_score' => $cumulativeScore,
                        'cumulative_percentage' => $cumulativePercentage,
                        'cumulative_grade_color' => $cumulativeGradeColor,
                        'total_grades' => $grades->count(),
                        'class' => $class,
                        'trimester_data' => $trimesterData
                    ];
                }
            }
            
            return view('grades.index', [
                'stats' => $stats,
                'classes' => $classes,
                'grades' => collect($studentsWithGrades)
            ]);
            
        } catch (\Exception $e) {
            return view('grades.index', [
                'stats' => ['totalGrades' => 0, 'averageGrade' => 0, 'pendingGrades' => 0, 'completionRate' => 0],
                'classes' => collect([]),
                'grades' => collect([])
            ]);
        }
    }
}
