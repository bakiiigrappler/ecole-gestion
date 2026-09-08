<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;
use App\Models\StudentCompetencyEvaluation;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\Teacher;

class CompetencyExamplesSeeder extends Seeder
{
    public function run()
    {
        // Créer des compétences complètes pour les bulletins
        $this->createCompetencies();
        
        // Créer des critères pour chaque compétence
        $this->createCriteria();
        
        // Créer des exemples d'évaluations
        $this->createEvaluationExamples();
    }

    private function createCompetencies()
    {
        // Compétences EDM & EAS (Éducation au Développement et à l'Environnement & Éducation Artistique et Sportive)
        $competencies = [
            // EDM & EAS
            [
                'name' => 'Histoire, géographie, citoyenneté',
                'code' => 'COMP_EDM_1',
                'description' => 'Maîtrise des connaissances en histoire, géographie et éducation à la citoyenneté',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 1
            ],
            [
                'name' => 'Sciences, Technologie et informatique',
                'code' => 'COMP_EDM_2',
                'description' => 'Maîtrise des sciences, de la technologie et de l\'informatique',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 2
            ],
            [
                'name' => 'EPS et art',
                'code' => 'COMP_EDM_3',
                'description' => 'Maîtrise de l\'éducation physique et sportive et des arts',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 3
            ],

            // Français
            [
                'name' => 'Compréhension orale et langage',
                'code' => 'COMP_FR_1',
                'description' => 'Maîtrise de la compréhension orale et du langage',
                'subject_area' => 'Français',
                'sort_order' => 1
            ],
            [
                'name' => 'Lecture, écriture et production écrite',
                'code' => 'COMP_FR_2',
                'description' => 'Maîtrise de la lecture, de l\'écriture et de la production écrite',
                'subject_area' => 'Français',
                'sort_order' => 2
            ],
            [
                'name' => 'Grammaire et vocabulaire',
                'code' => 'COMP_FR_3',
                'description' => 'Maîtrise de la grammaire et du vocabulaire',
                'subject_area' => 'Français',
                'sort_order' => 3
            ],

            // Mathématiques
            [
                'name' => 'Nombres et calculs',
                'code' => 'COMP_MATH_1',
                'description' => 'Maîtrise des nombres et des calculs',
                'subject_area' => 'Mathématiques',
                'sort_order' => 1
            ],
            [
                'name' => 'Géométrie et mesures',
                'code' => 'COMP_MATH_2',
                'description' => 'Maîtrise de la géométrie et des mesures',
                'subject_area' => 'Mathématiques',
                'sort_order' => 2
            ],
            [
                'name' => 'Résolution de problèmes',
                'code' => 'COMP_MATH_3',
                'description' => 'Maîtrise de la résolution de problèmes',
                'subject_area' => 'Mathématiques',
                'sort_order' => 3
            ],

            // Compétences transversales
            [
                'name' => 'Autonomie et initiative',
                'code' => 'COMP_TRANS_1',
                'description' => 'Développement de l\'autonomie et de l\'initiative',
                'subject_area' => 'Compétences transversales',
                'sort_order' => 1
            ],
            [
                'name' => 'Travail en équipe',
                'code' => 'COMP_TRANS_2',
                'description' => 'Capacité à travailler en équipe',
                'subject_area' => 'Compétences transversales',
                'sort_order' => 2
            ],
            [
                'name' => 'Communication',
                'code' => 'COMP_TRANS_3',
                'description' => 'Maîtrise de la communication orale et écrite',
                'subject_area' => 'Compétences transversales',
                'sort_order' => 3
            ]
        ];

        foreach ($competencies as $competency) {
            Competency::updateOrCreate(
                ['code' => $competency['code']],
                $competency
            );
        }
    }

    private function createCriteria()
    {
        // Critères pour Histoire, géographie, citoyenneté
        $historyCompetency = Competency::where('code', 'COMP_EDM_1')->first();
        if ($historyCompetency) {
            $criteria = [
                [
                    'code' => 'C1',
                    'name' => 'Connaissances historiques',
                    'description' => 'Maîtrise des connaissances en histoire',
                    'max_points' => 25,
                    'sort_order' => 1
                ],
                [
                    'code' => 'C2',
                    'name' => 'Connaissances géographiques',
                    'description' => 'Maîtrise des connaissances en géographie',
                    'max_points' => 25,
                    'sort_order' => 2
                ],
                [
                    'code' => 'C3',
                    'name' => 'Citoyenneté et valeurs',
                    'description' => 'Compréhension des valeurs citoyennes',
                    'max_points' => 25,
                    'sort_order' => 3
                ],
                [
                    'code' => 'C4',
                    'name' => 'Méthodes de travail',
                    'description' => 'Maîtrise des méthodes de recherche et d\'analyse',
                    'max_points' => 25,
                    'sort_order' => 4
                ]
            ];

            foreach ($criteria as $criterion) {
                CompetencyCriteria::updateOrCreate(
                    [
                        'competency_id' => $historyCompetency->id,
                        'name' => $criterion['name']
                    ],
                    $criterion
                );
            }
        }

        // Critères pour Français - Compréhension orale
        $frenchCompetency = Competency::where('code', 'COMP_FR_1')->first();
        if ($frenchCompetency) {
            $criteria = [
                [
                    'code' => 'C1',
                    'name' => 'Écoute attentive',
                    'description' => 'Capacité à écouter attentivement',
                    'max_points' => 20,
                    'sort_order' => 1
                ],
                [
                    'code' => 'C2',
                    'name' => 'Compréhension du message',
                    'description' => 'Compréhension du message oral',
                    'max_points' => 30,
                    'sort_order' => 2
                ],
                [
                    'code' => 'C3',
                    'name' => 'Expression orale',
                    'description' => 'Qualité de l\'expression orale',
                    'max_points' => 30,
                    'sort_order' => 3
                ],
                [
                    'code' => 'C4',
                    'name' => 'Vocabulaire et syntaxe',
                    'description' => 'Maîtrise du vocabulaire et de la syntaxe',
                    'max_points' => 20,
                    'sort_order' => 4
                ]
            ];

            foreach ($criteria as $criterion) {
                CompetencyCriteria::updateOrCreate(
                    [
                        'competency_id' => $frenchCompetency->id,
                        'name' => $criterion['name']
                    ],
                    $criterion
                );
            }
        }

        // Critères pour Mathématiques - Nombres et calculs
        $mathCompetency = Competency::where('code', 'COMP_MATH_1')->first();
        if ($mathCompetency) {
            $criteria = [
                [
                    'code' => 'C1',
                    'name' => 'Connaissance des nombres',
                    'description' => 'Maîtrise de la numération',
                    'max_points' => 25,
                    'sort_order' => 1
                ],
                [
                    'code' => 'C2',
                    'name' => 'Calculs de base',
                    'description' => 'Maîtrise des opérations de base',
                    'max_points' => 30,
                    'sort_order' => 2
                ],
                [
                    'code' => 'C3',
                    'name' => 'Calcul mental',
                    'description' => 'Capacité de calcul mental',
                    'max_points' => 20,
                    'sort_order' => 3
                ],
                [
                    'code' => 'C4',
                    'name' => 'Résolution de calculs',
                    'description' => 'Résolution de problèmes de calcul',
                    'max_points' => 25,
                    'sort_order' => 4
                ]
            ];

            foreach ($criteria as $criterion) {
                CompetencyCriteria::updateOrCreate(
                    [
                        'competency_id' => $mathCompetency->id,
                        'name' => $criterion['name']
                    ],
                    $criterion
                );
            }
        }
    }

    private function createEvaluationExamples()
    {
        // Récupérer des données existantes
        $student = Student::first();
        $class = SchoolClass::first();
        $academicYear = AcademicYear::where('is_current', true)->first();
        $teacher = Teacher::first();

        if (!$student || !$class || !$academicYear || !$teacher) {
            $this->command->warn('Données manquantes pour créer les exemples d\'évaluations');
            return;
        }

        // Créer des exemples d'évaluations pour différents paliers
        $competencies = Competency::all();
        
        foreach ($competencies as $competency) {
            // Évaluation du 1er palier
            StudentCompetencyEvaluation::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'competency_id' => $competency->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'teacher_id' => $teacher->id,
                    'palier' => 1
                ],
                [
                    'evaluation_date' => now()->subDays(30),
                    'c1_points' => rand(15, 25),
                    'c2_points' => rand(15, 25),
                    'c3_points' => rand(15, 25),
                    'c4_points' => rand(15, 25),
                    'c1_max_points' => 25,
                    'c2_max_points' => 25,
                    'c3_max_points' => 25,
                    'c4_max_points' => 25,
                    'total_points_obtained' => rand(60, 100),
                    'total_points_max' => 100,
                    'competency_mastery' => $this->getRandomMastery(),
                    'subject_mastery' => $this->getRandomMastery(),
                    'palier_mastery' => $this->getRandomMastery(),
                    'is_exit_profile' => false,
                    'comments' => 'Évaluation du premier palier - Bonne progression générale.'
                ]
            );

            // Évaluation du 2ème palier
            StudentCompetencyEvaluation::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'competency_id' => $competency->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'teacher_id' => $teacher->id,
                    'palier' => 2
                ],
                [
                    'evaluation_date' => now()->subDays(15),
                    'c1_points' => rand(18, 25),
                    'c2_points' => rand(18, 25),
                    'c3_points' => rand(18, 25),
                    'c4_points' => rand(18, 25),
                    'c1_max_points' => 25,
                    'c2_max_points' => 25,
                    'c3_max_points' => 25,
                    'c4_max_points' => 25,
                    'total_points_obtained' => rand(70, 100),
                    'total_points_max' => 100,
                    'competency_mastery' => $this->getRandomMastery(),
                    'subject_mastery' => $this->getRandomMastery(),
                    'palier_mastery' => $this->getRandomMastery(),
                    'is_exit_profile' => false,
                    'comments' => 'Évaluation du deuxième palier - Progression notable.'
                ]
            );

            // Évaluation du 3ème palier (fin d'année)
            StudentCompetencyEvaluation::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'competency_id' => $competency->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'teacher_id' => $teacher->id,
                    'palier' => 3
                ],
                [
                    'evaluation_date' => now(),
                    'c1_points' => rand(20, 25),
                    'c2_points' => rand(20, 25),
                    'c3_points' => rand(20, 25),
                    'c4_points' => rand(20, 25),
                    'c1_max_points' => 25,
                    'c2_max_points' => 25,
                    'c3_max_points' => 25,
                    'c4_max_points' => 25,
                    'total_points_obtained' => rand(80, 100),
                    'total_points_max' => 100,
                    'competency_mastery' => $this->getRandomMastery(),
                    'subject_mastery' => $this->getRandomMastery(),
                    'palier_mastery' => $this->getRandomMastery(),
                    'is_exit_profile' => true,
                    'exit_profile' => $this->getRandomExitProfile(),
                    'comments' => 'Évaluation finale - Compétence maîtrisée avec succès.'
                ]
            );
        }
    }

    private function getRandomMastery()
    {
        $masteries = ['maximale', 'minimale', 'partielle', 'non_maitrise'];
        return $masteries[array_rand($masteries)];
    }

    private function getRandomExitProfile()
    {
        $profiles = ['maximale', 'minimale', 'partielle', 'non_maitrise'];
        return $profiles[array_rand($profiles)];
    }
}
