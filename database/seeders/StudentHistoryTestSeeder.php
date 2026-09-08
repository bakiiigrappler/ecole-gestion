<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class StudentHistoryTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🎓 Création d'historiques pour les élèves existants...\n\n";

        // Récupérer ou créer les années scolaires
        $years = [
            '2021-2022' => AcademicYear::firstOrCreate(
                ['name' => '2021-2022'],
                [
                    'start_date' => '2021-09-01',
                    'end_date' => '2022-06-30',
                    'status' => 'inactive',
                    'is_current' => false
                ]
            ),
            '2022-2023' => AcademicYear::firstOrCreate(
                ['name' => '2022-2023'],
                [
                    'start_date' => '2022-09-01',
                    'end_date' => '2023-06-30',
                    'status' => 'inactive',
                    'is_current' => false
                ]
            ),
            '2023-2024' => AcademicYear::firstOrCreate(
                ['name' => '2023-2024'],
                [
                    'start_date' => '2023-09-01',
                    'end_date' => '2024-06-30',
                    'status' => 'inactive',
                    'is_current' => false
                ]
            ),
            '2024-2025' => AcademicYear::firstOrCreate(
                ['name' => '2024-2025'],
                [
                    'start_date' => '2024-09-01',
                    'end_date' => '2025-06-30',
                    'status' => 'active',
                    'is_current' => true
                ]
            ),
        ];

        // Récupérer les niveaux et classes
        $levels = Level::with('classes')->get()->keyBy('code');
        
        // Récupérer un enseignant pour les notes
        $teacher = Teacher::first();
        if (!$teacher) {
            $teacher = Teacher::create([
                'employee_id' => 'TEACH001',
                'first_name' => 'Jean',
                'last_name' => 'PROFESSEUR',
                'date_of_birth' => '1980-01-01',
                'gender' => 'male',
                'phone' => '06 00 00 00 00',
                'email' => 'prof@test.com',
                'address' => 'Libreville',
                'hire_date' => '2020-09-01',
                'status' => 'active'
            ]);
        }

        // Récupérer les matières
        $subjects = Subject::all();
        if ($subjects->isEmpty()) {
            $subjects = collect([
                Subject::create(['name' => 'Français', 'code' => 'FR', 'is_active' => true]),
                Subject::create(['name' => 'Mathématiques', 'code' => 'MATH', 'is_active' => true]),
                Subject::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]),
            ]);
        }

        // Récupérer les élèves existants (limiter à 10 pour les tests)
        $students = Student::limit(10)->get();

        if ($students->isEmpty()) {
            echo "❌ Aucun élève trouvé dans la base de données.\n";
            echo "💡 Veuillez d'abord créer des élèves.\n";
            return;
        }

        echo "📊 Nombre d'élèves à traiter : " . $students->count() . "\n\n";

        // Supprimer toutes les inscriptions existantes pour ces élèves
        echo "🗑️  Suppression des inscriptions existantes...\n";
        $studentIds = $students->pluck('id');
        Enrollment::whereIn('student_id', $studentIds)->delete();
        StudentGrade::whereIn('student_id', $studentIds)->delete();
        echo "✅ Inscriptions et notes supprimées\n\n";

        $scenarios = [
            'excellent_student' => [
                'description' => 'Élève excellent qui passe toujours',
                'years' => [
                    ['level' => 'PS', 'result' => 'admis', 'average' => 16.5],
                    ['level' => 'MS', 'result' => 'admis', 'average' => 17.2],
                    ['level' => 'GS', 'result' => 'admis', 'average' => 16.8],
                    ['level' => 'CP', 'result' => 'admis', 'average' => 15.5],
                ]
            ],
            'good_student' => [
                'description' => 'Bon élève avec progression régulière',
                'years' => [
                    ['level' => 'PS', 'result' => 'admis', 'average' => 12.5],
                    ['level' => 'MS', 'result' => 'admis', 'average' => 13.2],
                    ['level' => 'GS', 'result' => 'admis', 'average' => 14.0],
                    ['level' => 'CP', 'result' => 'admis', 'average' => 13.8],
                ]
            ],
            'repeating_student' => [
                'description' => 'Élève avec un redoublement',
                'years' => [
                    ['level' => 'PS', 'result' => 'admis', 'average' => 11.0],
                    ['level' => 'MS', 'result' => 'redouble', 'average' => 8.5],
                    ['level' => 'MS', 'result' => 'admis', 'average' => 12.0],
                    ['level' => 'GS', 'result' => 'admis', 'average' => 11.5],
                ]
            ],
            'struggling_student' => [
                'description' => 'Élève en difficulté avec deux redoublements',
                'years' => [
                    ['level' => 'PS', 'result' => 'admis', 'average' => 10.5],
                    ['level' => 'MS', 'result' => 'redouble', 'average' => 7.8],
                    ['level' => 'MS', 'result' => 'redouble', 'average' => 8.9],
                    ['level' => 'MS', 'result' => 'admis', 'average' => 10.2],
                ]
            ],
            'college_student' => [
                'description' => 'Élève au collège avec bon parcours',
                'years' => [
                    ['level' => 'CM2', 'result' => 'admis', 'average' => 13.5],
                    ['level' => '6EME', 'result' => 'admis', 'average' => 12.8],
                    ['level' => '5EME', 'result' => 'admis', 'average' => 13.2],
                    ['level' => '4EME', 'result' => 'admis', 'average' => 12.5],
                ]
            ],
            'lycee_student' => [
                'description' => 'Élève au lycée',
                'years' => [
                    ['level' => '3EME', 'result' => 'admis', 'average' => 12.0],
                    ['level' => '2NDE', 'result' => 'admis', 'average' => 11.5],
                    ['level' => '1ERE', 'result' => 'admis', 'average' => 12.3],
                    ['level' => 'TERM', 'result' => 'admis', 'average' => 13.0],
                ]
            ],
        ];

        $scenarioKeys = array_keys($scenarios);
        $studentIndex = 0;

        foreach ($students as $student) {
            $studentIndex++;
            
            // Assigner un scénario de manière cyclique
            $scenarioKey = $scenarioKeys[($studentIndex - 1) % count($scenarioKeys)];
            $scenario = $scenarios[$scenarioKey];

            echo "👤 Élève #{$studentIndex}: {$student->full_name} ({$student->student_id})\n";
            echo "   📋 Scénario: {$scenario['description']}\n";

            $yearKeys = array_keys($years);
            $previousClass = null;
            $previousYear = null;

            foreach ($scenario['years'] as $index => $yearData) {
                if ($index >= count($yearKeys)) break;

                $yearKey = $yearKeys[$index];
                $academicYear = $years[$yearKey];
                $levelCode = $yearData['level'];

                // Trouver le niveau et une classe
                $level = $levels->get($levelCode);
                if (!$level) {
                    echo "   ⚠️  Niveau {$levelCode} non trouvé, passage au suivant\n";
                    continue;
                }

                $class = $level->classes->first();
                if (!$class) {
                    // Créer une classe si elle n'existe pas
                    $class = SchoolClass::create([
                        'name' => $level->name . ' A',
                        'level_id' => $level->id,
                        'capacity' => 30,
                        'is_active' => true,
                    ]);
                }

                // Déterminer le statut de l'élève
                $studentStatus = 'nouveau';
                if ($previousClass && $previousYear) {
                    if ($yearData['result'] === 'redouble') {
                        $studentStatus = 'redoublant';
                    } else {
                        $studentStatus = 'passant';
                    }
                }

                // Créer l'inscription
                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'class_id' => $class->id,
                    'enrollment_date' => $academicYear->start_date,
                    'status' => $index === count($scenario['years']) - 1 ? 'active' : 'completed',
                    'is_reinscription' => $index > 0,
                    'student_status' => $studentStatus,
                    'previous_class_id' => $previousClass ? $previousClass->id : null,
                    'previous_academic_year_id' => $previousYear ? $previousYear->id : null,
                    'previous_year_result' => $previousClass ? $yearData['result'] : 'non_applicable',
                    'previous_year_average' => $previousClass ? $yearData['average'] : null,
                    'status_comments' => $this->generateComment($studentStatus, $yearData['result'], $yearData['average']),
                ]);

                // Créer des notes pour cette année (sauf pour l'année en cours)
                if ($index < count($scenario['years']) - 1) {
                    $this->createGradesForEnrollment($enrollment, $yearData['average'], $subjects, $teacher);
                }

                echo "   ✅ {$academicYear->name}: {$class->name} - {$studentStatus} - Moyenne: {$yearData['average']}/20\n";

                $previousClass = $class;
                $previousYear = $academicYear;
            }

            // Mettre à jour les statistiques de l'élève
            $student->updateEnrollmentStats();
            
            echo "   📊 Stats: {$student->total_enrollments} inscriptions, {$student->total_redoublements} redoublements\n";
            echo "   🎯 Statut: {$student->current_status}\n\n";
        }

        echo "\n✅ Historiques créés avec succès!\n";
        echo "\n📋 Résumé des scénarios:\n";
        foreach ($scenarios as $key => $scenario) {
            echo "   • {$scenario['description']}\n";
        }
        echo "\n💡 Vous pouvez maintenant consulter les fiches élèves pour voir l'historique complet!\n";
    }

    /**
     * Créer des notes pour une inscription
     */
    private function createGradesForEnrollment($enrollment, $targetAverage, $subjects, $teacher)
    {
        $trimestres = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
        
        foreach ($subjects as $subject) {
            foreach ($trimestres as $trimestre) {
                // Générer une note autour de la moyenne cible
                $variation = rand(-20, 20) / 10; // Variation de -2 à +2
                $grade = max(0, min(20, $targetAverage + $variation));

                StudentGrade::create([
                    'student_id' => $enrollment->student_id,
                    'subject_id' => $subject->id,
                    'class_id' => $enrollment->class_id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $enrollment->academic_year_id,
                    'term' => $trimestre,
                    'score' => round($grade, 2),
                    'max_score' => 20,
                    'comments' => $this->generateGradeComment($grade),
                ]);
            }
        }
    }

    /**
     * Générer un commentaire pour le statut
     */
    private function generateComment($status, $result, $average)
    {
        $comments = [
            'nouveau' => "Première inscription dans l'établissement.",
            'passant' => "Élève admis en classe supérieure avec une moyenne de {$average}/20.",
            'redoublant' => "Redoublement suite à des difficultés (moyenne: {$average}/20).",
        ];

        return $comments[$status] ?? '';
    }

    /**
     * Générer un commentaire pour une note
     */
    private function generateGradeComment($grade)
    {
        if ($grade >= 16) return "Excellent travail";
        if ($grade >= 14) return "Très bien";
        if ($grade >= 12) return "Bien";
        if ($grade >= 10) return "Assez bien";
        if ($grade >= 8) return "Passable";
        return "Insuffisant";
    }
}
