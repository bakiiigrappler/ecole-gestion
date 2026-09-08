<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;

class SimpleTestDataSeeder extends Seeder
{
    public function run()
    {
        // Récupérer l'année académique
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->warn('Aucune année académique actuelle trouvée.');
            return;
        }

        // Récupérer la première classe disponible
        $class = SchoolClass::first();
        if (!$class) {
            $this->command->warn('Aucune classe trouvée.');
            return;
        }

        // Récupérer les 5 premiers élèves
        $students = Student::limit(5)->get();
        if ($students->isEmpty()) {
            $this->command->warn('Aucun élève trouvé.');
            return;
        }

        // Récupérer les compétences
        $competencies = Competency::active()->get();
        if ($competencies->isEmpty()) {
            $this->command->warn('Aucune compétence trouvée. Exécutez d\'abord CompetencySeeder.');
            return;
        }

        $this->command->info('Création des données de test...');
        $this->command->info('Classe: ' . $class->name);
        $this->command->info('Élèves: ' . $students->count());
        $this->command->info('Compétences: ' . $competencies->count());

        // Créer des évaluations pour le palier 1
        $evaluationsCreated = 0;
        foreach ($students as $student) {
            foreach ($competencies as $competency) {
                // Données de test réalistes
                $c1Max = 4; $c1Points = rand(2, 4);
                $c2Max = 4; $c2Points = rand(1, 4);
                $c3Max = 1; $c3Points = rand(0, 1);
                $c4Max = 3; $c4Points = rand(1, 3);

                $totalObtained = $c1Points + $c2Points + $c3Points + $c4Points;
                $totalMax = $c1Max + $c2Max + $c3Max + $c4Max;
                $percentage = ($totalObtained / $totalMax) * 100;

                $mastery = $this->calculateMasteryLevel($percentage);

                StudentCompetencyEvaluation::create([
                    'student_id' => $student->id,
                    'competency_id' => $competency->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'teacher_id' => 1,
                    'palier' => 1,
                    'evaluation_date' => now(),
                    'c1_points' => $c1Points,
                    'c2_points' => $c2Points,
                    'c3_points' => $c3Points,
                    'c4_points' => $c4Points,
                    'c1_max_points' => $c1Max,
                    'c2_max_points' => $c2Max,
                    'c3_max_points' => $c3Max,
                    'c4_max_points' => $c4Max,
                    'total_points_obtained' => $totalObtained,
                    'total_points_max' => $totalMax,
                    'competency_mastery' => $mastery,
                    'subject_mastery' => $mastery,
                    'palier_mastery' => $mastery,
                    'comments' => 'Évaluation de test - ' . ucfirst($mastery)
                ]);

                $evaluationsCreated++;
            }
        }

        $this->command->info('✅ ' . $evaluationsCreated . ' évaluations créées avec succès !');
    }

    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }
}
