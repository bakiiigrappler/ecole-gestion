<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;

class UniversalCompetencySeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Création d\'évaluations universelles...');

        // Récupérer l'année académique
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique actuelle trouvée.');
            return;
        }

        // Récupérer toutes les classes
        $classes = SchoolClass::with('level')->get();
        if ($classes->isEmpty()) {
            $this->command->error('Aucune classe trouvée.');
            return;
        }

        $this->command->info("📊 Classes trouvées: {$classes->count()}");

        // Récupérer les compétences
        $competencies = Competency::active()->get();
        if ($competencies->isEmpty()) {
            $this->command->error('Aucune compétence trouvée. Exécutez d\'abord CompetencySeeder.');
            return;
        }

        $totalEvaluations = 0;

        foreach ($classes as $class) {
            $this->command->info("📚 Traitement de la classe: {$class->name} ({$class->level->cycle})");

            // Récupérer les élèves de cette classe
            $students = Student::whereHas('enrollments', function($query) use ($class, $academicYear) {
                $query->where('class_id', $class->id)
                      ->where('academic_year_id', $academicYear->id)
                      ->where('status', 'active');
            })->get();

            if ($students->isEmpty()) {
                $this->command->warn("  ⚠️ Aucun élève dans {$class->name}");
                continue;
            }

            $this->command->info("  👥 {$students->count()} élèves trouvés");

            // Créer des évaluations pour tous les élèves
            foreach ($students as $index => $student) {
                $profile = $this->getStudentProfile($index, $student);
                
                foreach ([1, 2, 3, 4, 5] as $palier) {
                    foreach ($competencies as $competency) {
                        // Vérifier si l'évaluation existe déjà
                        $existing = StudentCompetencyEvaluation::where([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'palier' => $palier,
                            'academic_year_id' => $academicYear->id
                        ])->first();

                        if ($existing) {
                            continue; // Skip si existe déjà
                        }

                        // Générer des données
                        $evaluationData = $this->generateEvaluationData($competency, $profile, $palier);

                        StudentCompetencyEvaluation::create([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $academicYear->id,
                            'teacher_id' => 1,
                            'palier' => $palier,
                            'evaluation_date' => now()->subDays(rand(1, 30)),
                            'c1_points' => $evaluationData['c1_points'],
                            'c2_points' => $evaluationData['c2_points'],
                            'c3_points' => $evaluationData['c3_points'],
                            'c4_points' => $evaluationData['c4_points'],
                            'c1_max_points' => $evaluationData['c1_max_points'],
                            'c2_max_points' => $evaluationData['c2_max_points'],
                            'c3_max_points' => $evaluationData['c3_max_points'],
                            'c4_max_points' => $evaluationData['c4_max_points'],
                            'total_points_obtained' => $evaluationData['total_points_obtained'],
                            'total_points_max' => $evaluationData['total_points_max'],
                            'competency_mastery' => $evaluationData['mastery'],
                            'subject_mastery' => $evaluationData['mastery'],
                            'palier_mastery' => $evaluationData['mastery'],
                            'is_exit_profile' => $palier === 5,
                            'exit_profile' => $palier === 5 ? $evaluationData['mastery'] : null,
                            'comments' => $evaluationData['comment']
                        ]);

                        $totalEvaluations++;
                    }
                }
            }
        }

        $this->command->info('✅ Évaluations créées avec succès !');
        $this->command->info("📊 Total: {$totalEvaluations} évaluations");
        $this->command->info("🎯 Vous pouvez maintenant voir les bulletins complets !");
        $this->command->info("🌐 Accédez à: Menu → Évaluations → Compétences (Primaire)");
    }

    private function getStudentProfile($index, $student)
    {
        // Assigner des profils selon l'index pour avoir de la diversité
        $profiles = ['excellent', 'bon', 'moyen', 'difficultes'];
        return $profiles[$index % count($profiles)];
    }

    private function generateEvaluationData($competency, $profile, $palier)
    {
        // Points maximum selon les critères
        $maxPoints = $this->getMaxPointsForCompetency($competency);
        
        // Modificateur selon le profil
        $profileModifier = $this->getProfileModifier($profile);
        
        // Progression selon le palier (amélioration avec le temps)
        $palierProgression = $palier * 0.05;

        $c1Points = $this->calculatePoints($maxPoints['c1'], $profileModifier + $palierProgression);
        $c2Points = $this->calculatePoints($maxPoints['c2'], $profileModifier + $palierProgression);
        $c3Points = $this->calculatePoints($maxPoints['c3'], $profileModifier + $palierProgression);
        $c4Points = $this->calculatePoints($maxPoints['c4'], $profileModifier + $palierProgression);

        $totalObtained = $c1Points + $c2Points + $c3Points + $c4Points;
        $totalMax = $maxPoints['c1'] + $maxPoints['c2'] + $maxPoints['c3'] + $maxPoints['c4'];
        $percentage = ($totalObtained / $totalMax) * 100;

        $mastery = $this->calculateMasteryLevel($percentage);
        $comment = $this->generateComment($competency->name, $mastery, $palier, $profile);

        return [
            'c1_points' => $c1Points,
            'c2_points' => $c2Points,
            'c3_points' => $c3Points,
            'c4_points' => $c4Points,
            'c1_max_points' => $maxPoints['c1'],
            'c2_max_points' => $maxPoints['c2'],
            'c3_max_points' => $maxPoints['c3'],
            'c4_max_points' => $maxPoints['c4'],
            'total_points_obtained' => $totalObtained,
            'total_points_max' => $totalMax,
            'mastery' => $mastery,
            'comment' => $comment
        ];
    }

    private function getMaxPointsForCompetency($competency)
    {
        $criteria = $competency->criteria;
        return [
            'c1' => $criteria->where('code', 'C1')->first()->max_points ?? 4,
            'c2' => $criteria->where('code', 'C2')->first()->max_points ?? 4,
            'c3' => $criteria->where('code', 'C3')->first()->max_points ?? 1,
            'c4' => $criteria->where('code', 'C4')->first()->max_points ?? 3
        ];
    }

    private function getProfileModifier($profile)
    {
        return match($profile) {
            'excellent' => 0.85,  // 85% des points
            'bon' => 0.70,        // 70% des points
            'moyen' => 0.55,      // 55% des points
            'difficultes' => 0.35, // 35% des points
            default => 0.55
        };
    }

    private function calculatePoints($maxPoints, $modifier)
    {
        $targetPoints = $maxPoints * $modifier;
        $variation = $maxPoints * 0.2; // 20% de variation pour plus de réalisme
        
        return max(0, min($maxPoints, round($targetPoints + rand(-$variation, $variation))));
    }

    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }

    private function generateComment($competencyName, $mastery, $palier, $profile)
    {
        $profileEmojis = [
            'excellent' => '🌟',
            'bon' => '✅',
            'moyen' => '📚',
            'difficultes' => '⚠️'
        ];

        $emoji = $profileEmojis[$profile] ?? '📖';

        $comments = [
            'maximale' => [
                "{$emoji} Excellent travail en {$competencyName} ! Maîtrise parfaite au palier {$palier}.",
                "{$emoji} Performance remarquable en {$competencyName}. Continue ainsi !",
                "{$emoji} Très bonne maîtrise de {$competencyName} au palier {$palier}."
            ],
            'minimale' => [
                "{$emoji} Bon niveau en {$competencyName}. Quelques efforts supplémentaires nécessaires.",
                "{$emoji} Compétence acquise en {$competencyName} avec quelques lacunes mineures.",
                "{$emoji} Bonne base en {$competencyName}, révisions recommandées."
            ],
            'partielle' => [
                "{$emoji} Compétence partiellement acquise en {$competencyName}. Plus d'entraînement nécessaire.",
                "{$emoji} Progression en cours en {$competencyName}. Continuer les efforts.",
                "{$emoji} Niveau partiel en {$competencyName}. Accompagnement nécessaire."
            ],
            'non_maitrise' => [
                "{$emoji} Compétence non acquise en {$competencyName}. Accompagnement renforcé requis.",
                "{$emoji} Difficultés importantes en {$competencyName}. Révision approfondie nécessaire.",
                "{$emoji} Besoin d'un soutien particulier en {$competencyName}."
            ]
        ];

        $commentList = $comments[$mastery];
        return $commentList[array_rand($commentList)];
    }
}
