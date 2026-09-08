<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\AcademicYear;

class SimpleGradeController extends Controller
{
    /**
     * Afficher le bulletin simplifié d'un élève
     */
    public function showBulletin(string $studentId)
    {
        try {
            $student = Student::with(['enrollments.schoolClass.level'])->findOrFail($studentId);
            $currentEnrollment = $student->enrollments()->where('status', 'active')->first();
            
            if (!$currentEnrollment || !$currentEnrollment->schoolClass) {
                return redirect()->route('grades.index')->with('error', 'L\'élève n\'est pas inscrit dans une classe active.');
            }
            
            $class = $currentEnrollment->schoolClass;
            $academicYear = AcademicYear::where('is_current', true)->first();
            
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
                $trimesterGrades = StudentGrade::with(['subject', 'teacher'])
                    ->where('student_id', $studentId)
                    ->where('class_id', $class->id)
                    ->where('term', $trimester)
                    ->get();
                
                if ($trimesterGrades->count() > 0) {
                    $subjects = [];
                    $totalScore = 0;
                    $totalMaxScore = 0;
                    
                    foreach ($trimesterGrades as $grade) {
                        if ($grade->subject) {
                            $subjects[] = [
                                'name' => $grade->subject->name,
                                'average' => round(($grade->score / $grade->max_score) * 20, 2),
                                'coefficient' => 1,
                                'rank' => 'N/C',
                                'appreciation' => $this->getAppreciation(($grade->score / $grade->max_score) * 20)
                            ];
                            
                            $totalScore += $grade->score;
                            $totalMaxScore += $grade->max_score;
                        }
                    }
                    
                    $cumulativeScore = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 20, 2) : 0;
                    
                    $trimesterData[$trimester] = [
                        'subjects' => $subjects,
                        'cumulative_score' => $cumulativeScore,
                        'total_subjects' => count($subjects),
                        'rank' => 'N/C',
                        'is_available' => true,
                        'status' => 'Disponible'
                    ];
                }
            }
            
            return view('grades.bulletin-simple', compact(
                'student', 
                'class', 
                'academicYear',
                'trimesterData'
            ));
            
        } catch (\Exception $e) {
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
}
