<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Competency;
use App\Models\StudentCompetencyEvaluation;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\ParentModel;
use App\Models\Enrollment;

class CompleteCompetencyDataSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Création de données complètes pour les compétences...');

        // 1. Créer l'année académique
        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            [
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_current' => true
            ]
        );

        // 2. Créer un niveau primaire CE1
        $level = Level::firstOrCreate(
            ['name' => 'CE1'],
            [
                'code' => 'CE1',
                'cycle' => 'primaire',
                'order' => 2,
                'is_active' => true
            ]
        );

        // 3. Créer une classe CE1 A
        $class = SchoolClass::firstOrCreate(
            ['name' => 'CE1 A', 'level_id' => $level->id],
            [
                'description' => 'Classe CE1 A - Évaluation par compétences',
                'capacity' => 30,
                'is_active' => true,
            ]
        );

        // 4. Créer des élèves avec des profils différents
        $students = $this->createStudents($class, $academicYear);

        // 5. Créer des évaluations complètes pour tous les paliers
        $this->createCompleteEvaluations($students, $class, $academicYear);

        $this->command->info('✅ Données créées avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->info('- ' . $students->count() . ' élèves créés');
        $this->command->info('- 5 paliers d\'évaluation');
        $this->command->info('- 7 compétences par élève');
        $this->command->info('- Total : ' . ($students->count() * 5 * 7) . ' évaluations');
    }

    private function createStudents($class, $academicYear)
    {
        $this->command->info('👥 Création des élèves...');

        $studentsData = [
            ['first_name' => 'Marie', 'last_name' => 'Dupont', 'profile' => 'excellent'],
            ['first_name' => 'Jean', 'last_name' => 'Martin', 'profile' => 'bon'],
            ['first_name' => 'Sophie', 'last_name' => 'Bernard', 'profile' => 'moyen'],
            ['first_name' => 'Pierre', 'last_name' => 'Petit', 'profile' => 'difficultes'],
            ['first_name' => 'Emma', 'last_name' => 'Moreau', 'profile' => 'excellent'],
            ['first_name' => 'Lucas', 'last_name' => 'Simon', 'profile' => 'bon'],
            ['first_name' => 'Chloé', 'last_name' => 'Laurent', 'profile' => 'moyen'],
            ['first_name' => 'Thomas', 'last_name' => 'Lefebvre', 'profile' => 'difficultes']
        ];

        $students = collect();

        foreach ($studentsData as $index => $studentData) {
            // Créer l'élève
            $student = Student::create([
                'student_id' => 'STU' . date('Y') . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'first_name' => $studentData['first_name'],
                'last_name' => $studentData['last_name'],
                'date_of_birth' => now()->subYears(7)->subDays($index * 15),
                'gender' => $index % 2 === 0 ? 'female' : 'male',
                'place_of_birth' => 'Libreville',
                'address' => 'Quartier ' . ($index + 1) . ', Libreville',
                'emergency_contact' => '077000000' . ($index + 1),
                'enrollment_date' => now()->subMonths(6),
                'status' => 'active'
            ]);

            // Créer un parent
            $parent = ParentModel::create([
                'first_name' => 'Parent' . ($index + 1),
                'last_name' => $studentData['last_name'],
                'phone' => '077000000' . ($index + 1),
                'email' => 'parent' . ($index + 1) . '@test.com',
                'address' => 'Adresse Parent ' . ($index + 1),
                'profession' => 'Fonctionnaire',
                'workplace' => 'Ministère de l\'Éducation'
            ]);

            // Associer l'élève au parent
            $student->parents()->attach($parent);

            // Créer l'inscription
            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now()->subMonths(6),
                'status' => 'active'
            ]);

            $students->push([
                'student' => $student,
                'profile' => $studentData['profile']
            ]);
        }

        return $students;
    }

    private function createCompleteEvaluations($students, $class, $academicYear)
    {
        $this->command->info('📝 Création des évaluations...');

        $competencies = Competency::active()->get();

        foreach ($students as $studentData) {
            $student = $studentData['student'];
            $profile = $studentData['profile'];

            foreach ([1, 2, 3, 4, 5] as $palier) {
                foreach ($competencies as $competency) {
                    // Générer des données selon le profil de l'élève
                    $evaluationData = $this->generateEvaluationData($competency, $profile, $palier);

                    StudentCompetencyEvaluation::create([
                        'student_id' => $student->id,
                        'competency_id' => $competency->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $academicYear->id,
                        'teacher_id' => 1, // Admin
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
                }
            }
        }
    }

    private function generateEvaluationData($competency, $profile, $palier)
    {
        // Points maximum selon les critères définis dans CompetencySeeder
        $maxPoints = $this->getMaxPointsForCompetency($competency);
        
        // Modificateur selon le profil de l'élève
        $profileModifier = $this->getProfileModifier($profile);
        
        // Progression selon le palier (les élèves s'améliorent avec le temps)
        $palierProgression = $palier * 0.1;

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
        // Points maximum selon les critères définis dans CompetencySeeder
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
        $variation = $maxPoints * 0.1; // 10% de variation
        
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
                "Excellent travail en {$competencyName} ! Maîtrise parfaite.",
                "Performance remarquable en {$competencyName}. Continue ainsi !",
                "Très bonne maîtrise de {$competencyName} au palier {$palier}."
            ],
            'minimale' => [
                "Bon niveau en {$competencyName}. Quelques efforts supplémentaires.",
                "Compétence acquise en {$competencyName} avec quelques lacunes mineures.",
                "Bonne base en {$competencyName}, révisions recommandées."
            ],
            'partielle' => [
                "Compétence partiellement acquise en {$competencyName}. Plus d'entraînement nécessaire.",
                "Progression en cours en {$competencyName}. Continuer les efforts.",
                "Niveau partiel en {$competencyName}. Accompagnement nécessaire."
            ],
            'non_maitrise' => [
                "Compétence non acquise en {$competencyName}. Accompagnement renforcé requis.",
                "Difficultés importantes en {$competencyName}. Révision approfondie nécessaire.",
                "Besoin d'un soutien particulier en {$competencyName}."
            ]
        ];

        $commentList = $comments[$mastery];
        return $commentList[array_rand($commentList)];
    }
}
