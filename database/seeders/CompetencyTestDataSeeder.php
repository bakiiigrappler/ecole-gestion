<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;

class CompetencyTestDataSeeder extends Seeder
{
    public function run()
    {
        // Récupérer l'année académique actuelle
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::create([
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_current' => true
            ]);
        }

        // Récupérer une classe primaire (exemple)
        $class = SchoolClass::whereHas('level', function($query) {
            $query->where('cycle', 'primaire');
        })->first();

        if (!$class) {
            $this->command->warn('Aucune classe primaire trouvée. Veuillez d\'abord créer une classe primaire.');
            return;
        }

        // Récupérer les élèves de la classe
        $students = $class->students()->wherePivot('status', 'active')->get();

        if ($students->isEmpty()) {
            $this->command->warn('Aucun élève trouvé dans la classe ' . $class->name);
            return;
        }

        // Récupérer toutes les compétences
        $competencies = Competency::active()->get();

        // Créer des évaluations de test pour chaque palier (1-5)
        foreach ([1, 2, 3, 4, 5] as $palier) {
            foreach ($students as $student) {
                foreach ($competencies as $competency) {
                    // Générer des données aléatoires mais réalistes
                    $c1Max = rand(3, 5);
                    $c2Max = rand(3, 5);
                    $c3Max = rand(1, 3);
                    $c4Max = rand(2, 4);

                    $c1Points = rand(0, $c1Max);
                    $c2Points = rand(0, $c2Max);
                    $c3Points = rand(0, $c3Max);
                    $c4Points = rand(0, $c4Max);

                    $totalObtained = $c1Points + $c2Points + $c3Points + $c4Points;
                    $totalMax = $c1Max + $c2Max + $c3Max + $c4Max;
                    $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;

                    // Déterminer le niveau de maîtrise
                    $mastery = $this->calculateMasteryLevel($percentage);

                    StudentCompetencyEvaluation::create([
                        'student_id' => $student->id,
                        'competency_id' => $competency->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $academicYear->id,
                        'teacher_id' => 1, // ID du premier utilisateur (admin)
                        'palier' => $palier,
                        'evaluation_date' => now()->subDays(rand(1, 30)),
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
                        'is_exit_profile' => $palier === 5, // Seul le palier 5 est un profil de sortie
                        'exit_profile' => $palier === 5 ? $mastery : null,
                        'comments' => $this->generateComment($mastery, $percentage)
                    ]);
                }
            }
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('- ' . $students->count() . ' élèves');
        $this->command->info('- ' . $competencies->count() . ' compétences');
        $this->command->info('- 5 paliers d\'évaluation');
        $this->command->info('- Total: ' . ($students->count() * $competencies->count() * 5) . ' évaluations');
    }

    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }

    private function generateComment($mastery, $percentage)
    {
        $comments = [
            'maximale' => [
                'Excellent travail ! Maîtrise parfaite de la compétence.',
                'Très bonne performance. L\'élève maîtrise parfaitement cette compétence.',
                'Performance remarquable. Continue ainsi !'
            ],
            'minimale' => [
                'Bon travail. Quelques efforts supplémentaires nécessaires.',
                'Compétence acquise avec quelques lacunes mineures.',
                'Bonne base, quelques révisions recommandées.'
            ],
            'partielle' => [
                'Compétence partiellement acquise. Nécessite plus d\'entraînement.',
                'Des efforts supplémentaires sont nécessaires pour maîtriser cette compétence.',
                'Progression en cours. Continuer les efforts.'
            ],
            'non_maitrise' => [
                'Compétence non acquise. Un accompagnement renforcé est nécessaire.',
                'Difficultés importantes. Révision approfondie requise.',
                'Besoin d\'un soutien particulier pour cette compétence.'
            ]
        ];

        $commentList = $comments[$mastery];
        return $commentList[array_rand($commentList)];
    }
}
