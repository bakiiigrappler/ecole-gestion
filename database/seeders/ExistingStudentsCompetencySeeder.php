<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;
use App\Models\Enrollment;

class ExistingStudentsCompetencySeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Création d\'évaluations pour les élèves existants...');

        // Récupérer l'année académique
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique actuelle trouvée.');
            return;
        }

        // Récupérer une classe primaire
        $class = SchoolClass::whereHas('level', function($query) {
            $query->where('cycle', 'primaire');
        })->first();

        if (!$class) {
            $this->command->error('Aucune classe primaire trouvée.');
            return;
        }

        // Récupérer les élèves de cette classe
        $students = Student::whereHas('enrollments', function($query) use ($class, $academicYear) {
            $query->where('class_id', $class->id)
                  ->where('academic_year_id', $academicYear->id)
                  ->where('status', 'active');
        })->limit(8)->get();

        if ($students->isEmpty()) {
            $this->command->error('Aucun élève trouvé dans la classe ' . $class->name);
            return;
        }

        $this->command->info("📊 Classe: {$class->name} - {$students->count()} élèves");

        // Récupérer les compétences
        $competencies = Competency::active()->get();
        if ($competencies->isEmpty()) {
            $this->command->error('Aucune compétence trouvée. Exécutez d\'abord CompetencySeeder.');
            return;
        }

        $this->command->info("📚 Compétences: {$competencies->count()}");

        // Créer des évaluations complètes
        $evaluationsCreated = 0;
        $studentProfiles = ['excellent', 'bon', 'moyen', 'difficultes'];

        foreach ($students as $index => $student) {
            $profile = $studentProfiles[$index % count($studentProfiles)];
            $this->command->info("👤 {$student->first_name} {$student->last_name} (profil: {$profile})");

            foreach ([1, 2, 3, 4, 5] as $palier) {
                foreach ($competencies as $competency) {
                    // Générer des données selon le profil
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

                    $evaluationsCreated++;
                }
            }
        }

        $this->command->info('✅ Évaluations créées avec succès !');
        $this->command->info("📊 Total: {$evaluationsCreated} évaluations");
        $this->command->info("🎯 Vous pouvez maintenant voir les bulletins complets !");
    }

    private function generateEvaluationData($competency, $profile, $palier)
    {
        // Points maximum selon les critères
        $maxPoints = $this->getMaxPointsForCompetency($competency);
        
        // Modificateur selon le profil
        $profileModifier = $this->getProfileModifier($profile);
        
        // Progression selon le palier
        $palierProgression = $palier * 0.08;

        $c1Points = $this->calculatePoints($maxPoints['c1'], $profileModifier + $palierProgression);
        $c2Points = $this->calculatePoints($maxPoints['c2'], $profileModifier + $palierProgression);
        $c3Points = $this->calculatePoints($maxPoints['c3'], $profileModifier + $palierProgression);
        $c4Points = $this->calculatePoints($maxPoints['c4'], $profileModifier + $palierProgression);

        $totalObtained = $c1Points + $c2Points + $c3Points + $c4Points;
        $totalMax = $maxPoints['c1'] + $maxPoints['c2'] + $maxPoints['c3'] + $maxPoints['c4'];
        $percentage = ($totalObtained / $totalMax) * 100;

        $mastery = $this->calculateMasteryLevel($percentage);
        $comment = $this->generateComment($competency->name, $mastery, $palier);

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
            'excellent' => 0.9,  // 90% des points
            'bon' => 0.75,       // 75% des points
            'moyen' => 0.6,      // 60% des points
            'difficultes' => 0.4, // 40% des points
            default => 0.6
        };
    }

    private function calculatePoints($maxPoints, $modifier)
    {
        $targetPoints = $maxPoints * $modifier;
        $variation = $maxPoints * 0.15; // 15% de variation
        
        return max(0, min($maxPoints, round($targetPoints + rand(-$variation, $variation))));
    }

    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }

    private function generateComment($competencyName, $mastery, $palier)
    {
        $comments = [
            'maximale' => [
                "🌟 Excellent travail en {$competencyName} ! Maîtrise parfaite au palier {$palier}.",
                "⭐ Performance remarquable en {$competencyName}. Continue ainsi !",
                "🎯 Très bonne maîtrise de {$competencyName} au palier {$palier}."
            ],
            'minimale' => [
                "✅ Bon niveau en {$competencyName}. Quelques efforts supplémentaires nécessaires.",
                "👍 Compétence acquise en {$competencyName} avec quelques lacunes mineures.",
                "📚 Bonne base en {$competencyName}, révisions recommandées pour le palier suivant."
            ],
            'partielle' => [
                "⚠️ Compétence partiellement acquise en {$competencyName}. Plus d'entraînement nécessaire.",
                "🔄 Progression en cours en {$competencyName}. Continuer les efforts.",
                "📖 Niveau partiel en {$competencyName}. Accompagnement nécessaire."
            ],
            'non_maitrise' => [
                "❌ Compétence non acquise en {$competencyName}. Accompagnement renforcé requis.",
                "🔍 Difficultés importantes en {$competencyName}. Révision approfondie nécessaire.",
                "🆘 Besoin d'un soutien particulier en {$competencyName} pour le palier {$palier}."
            ]
        ];

        $commentList = $comments[$mastery];
        return $commentList[array_rand($commentList)];
    }
}
