<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\StudentCompetencyEvaluation;
use App\Models\Competency;
use App\Models\AcademicYear;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class CompetencyEvaluationExamplesSeeder extends Seeder
{
    /**
     * Créer des évaluations d'exemple pour la classe 2
     */
    public function run()
    {
        // Vérifier que la classe 2 existe
        $class = SchoolClass::find(2);
        if (!$class) {
            $this->command->error('Classe 2 non trouvée. Veuillez d\'abord créer la classe 2.');
            return;
        }

        // Récupérer l'année académique courante
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->error('Aucune année académique courante trouvée.');
            return;
        }

        // Récupérer un enseignant (ou créer un enseignant par défaut)
        $teacher = Teacher::first();
        if (!$teacher) {
            $teacher = Teacher::create([
                'first_name' => 'Enseignant',
                'last_name' => 'Exemple',
                'email' => 'enseignant@exemple.com',
                'phone' => '0123456789',
                'speciality' => 'Enseignant généraliste',
                'grade' => 'Professeur des écoles'
            ]);
        }

        // Récupérer les élèves de la classe 2
        $students = Student::whereHas('enrollments', function($query) use ($class) {
            $query->where('class_id', $class->id)
                  ->where('status', 'active');
        })->get();

        if ($students->isEmpty()) {
            $this->command->error('Aucun élève trouvé dans la classe 2.');
            return;
        }

        // Récupérer les compétences
        $competencies = Competency::active()->with('criteria')->get();
        if ($competencies->isEmpty()) {
            $this->command->error('Aucune compétence trouvée.');
            return;
        }

        $this->command->info("Création des évaluations d'exemple pour {$students->count()} élèves...");

        DB::beginTransaction();

        try {
            // Créer des évaluations pour les paliers 1 et 2
            foreach ([1, 2] as $palier) {
                $this->command->info("Création des évaluations pour le palier {$palier}...");
                
                foreach ($students as $student) {
                    foreach ($competencies as $competency) {
                        // Vérifier si l'évaluation existe déjà
                        $existingEvaluation = StudentCompetencyEvaluation::where([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'class_id' => $class->id,
                            'palier' => $palier,
                            'academic_year_id' => $academicYear->id
                        ])->first();

                        if ($existingEvaluation) {
                            continue; // Passer à la compétence suivante
                        }

                        // Générer des notes aléatoires mais réalistes
                        $c1_points = rand(0, 5);
                        $c2_points = rand(0, 5);
                        $c3_points = rand(0, 5);
                        $c4_points = rand(0, 5);

                        // Points maximum (généralement 5 pour chaque critère)
                        $c1_max_points = 5;
                        $c2_max_points = 5;
                        $c3_max_points = 5;
                        $c4_max_points = 5;

                        $totalPointsObtained = $c1_points + $c2_points + $c3_points + $c4_points;
                        $totalPointsMax = $c1_max_points + $c2_max_points + $c3_max_points + $c4_max_points;

                        // Calculer le niveau de maîtrise
                        $percentage = $totalPointsMax > 0 ? ($totalPointsObtained / $totalPointsMax) * 100 : 0;
                        $masteryLevel = $this->calculateMasteryLevel($percentage);

                        // Créer l'évaluation
                        StudentCompetencyEvaluation::create([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $academicYear->id,
                            'teacher_id' => $teacher->id,
                            'palier' => $palier,
                            'evaluation_date' => now()->subDays(rand(1, 30)), // Date aléatoire dans les 30 derniers jours
                            'c1_points' => $c1_points,
                            'c2_points' => $c2_points,
                            'c3_points' => $c3_points,
                            'c4_points' => $c4_points,
                            'c1_max_points' => $c1_max_points,
                            'c2_max_points' => $c2_max_points,
                            'c3_max_points' => $c3_max_points,
                            'c4_max_points' => $c4_max_points,
                            'total_points_obtained' => $totalPointsObtained,
                            'total_points_max' => $totalPointsMax,
                            'competency_mastery' => $masteryLevel,
                            'subject_mastery' => $masteryLevel,
                            'palier_mastery' => $masteryLevel,
                            'comments' => $this->generateComment($masteryLevel, $palier)
                        ]);
                    }
                }
            }

            DB::commit();
            $this->command->info('✅ Évaluations d\'exemple créées avec succès !');
            $this->command->info("📊 Statistiques :");
            $this->command->info("   - Classe : {$class->name}");
            $this->command->info("   - Élèves : {$students->count()}");
            $this->command->info("   - Compétences : {$competencies->count()}");
            $this->command->info("   - Paliers : 1 et 2");
            $this->command->info("   - Total évaluations : " . ($students->count() * $competencies->count() * 2));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erreur lors de la création des évaluations : ' . $e->getMessage());
        }
    }

    /**
     * Calculer le niveau de maîtrise basé sur le pourcentage
     */
    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }

    /**
     * Générer un commentaire basé sur le niveau de maîtrise
     */
    private function generateComment($masteryLevel, $palier)
    {
        $comments = [
            'maximale' => [
                "Excellent travail au palier {$palier} ! L'élève maîtrise parfaitement cette compétence.",
                "Très bonne performance. Maîtrise complète de la compétence au palier {$palier}.",
                "Résultats remarquables au palier {$palier}. Compétence parfaitement acquise."
            ],
            'minimale' => [
                "Bon travail au palier {$palier}. L'élève maîtrise bien cette compétence.",
                "Satisfaisant au palier {$palier}. Compétence globalement acquise.",
                "Bon niveau de maîtrise au palier {$palier}. Quelques progrès encore possibles."
            ],
            'partielle' => [
                "Progrès en cours au palier {$palier}. Compétence partiellement acquise.",
                "En cours d'acquisition au palier {$palier}. Encore quelques efforts nécessaires.",
                "Maîtrise partielle au palier {$palier}. Continuer les efforts."
            ],
            'non_maitrise' => [
                "Difficultés rencontrées au palier {$palier}. Compétence non encore acquise.",
                "Besoin de soutien au palier {$palier}. Compétence à retravailler.",
                "En cours d'apprentissage au palier {$palier}. Accompagnement nécessaire."
            ]
        ];

        $levelComments = $comments[$masteryLevel] ?? $comments['partielle'];
        return $levelComments[array_rand($levelComments)];
    }
}
