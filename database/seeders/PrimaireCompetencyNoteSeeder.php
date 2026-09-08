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
use Illuminate\Support\Facades\DB;

class PrimaireCompetencyNoteSeeder extends Seeder
{
    /**
     * Créer les compétences et notes d'exemple pour le primaire
     */
    public function run()
    {
        $this->command->info('🎯 Création des compétences et notes de primaire...');
        
        // Créer les compétences de primaire
        $competencies = $this->createPrimaireCompetencies();
        
        // Créer les critères pour chaque compétence
        $this->createPrimaireCriteria($competencies);
        
        // Créer des exemples de notes/évaluations
        $this->createPrimaireNotes($competencies);
        
        $this->command->info('✅ Compétences et notes de primaire créées avec succès !');
    }

    /**
     * Créer les compétences de primaire
     */
    private function createPrimaireCompetencies()
    {
        $competencies = [
            // EDM & EAS (3 compétences)
            [
                'name' => 'Histoire, géographie, citoyenneté',
                'code' => 'COMP_PRIM_EDM_1',
                'description' => 'Maîtrise des connaissances en histoire, géographie et éducation à la citoyenneté',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 1
            ],
            [
                'name' => 'Sciences, Technologie et Informatique',
                'code' => 'COMP_PRIM_EDM_2',
                'description' => 'Maîtrise des sciences, de la technologie et de l\'informatique',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 2
            ],
            [
                'name' => 'EPS et art',
                'code' => 'COMP_PRIM_EDM_3',
                'description' => 'Maîtrise de l\'éducation physique et sportive et des arts',
                'subject_area' => 'EDM & EAS',
                'sort_order' => 3
            ],

            // Français (3 compétences)
            [
                'name' => 'Compréhension orale et langage',
                'code' => 'COMP_PRIM_FR_1',
                'description' => 'Maîtrise de la compréhension orale et du langage',
                'subject_area' => 'Français',
                'sort_order' => 1
            ],
            [
                'name' => 'Lecture, écriture et production écrite',
                'code' => 'COMP_PRIM_FR_2',
                'description' => 'Maîtrise de la lecture, de l\'écriture et de la production écrite',
                'subject_area' => 'Français',
                'sort_order' => 2
            ],
            [
                'name' => 'Grammaire et vocabulaire',
                'code' => 'COMP_PRIM_FR_3',
                'description' => 'Maîtrise de la grammaire et du vocabulaire',
                'subject_area' => 'Français',
                'sort_order' => 3
            ],

            // Mathématiques (3 compétences)
            [
                'name' => 'Nombres et opérations',
                'code' => 'COMP_PRIM_MATH_1',
                'description' => 'Maîtrise des nombres et des opérations',
                'subject_area' => 'Mathématiques',
                'sort_order' => 1
            ],
            [
                'name' => 'Géométrie et mesures',
                'code' => 'COMP_PRIM_MATH_2',
                'description' => 'Maîtrise de la géométrie et des mesures',
                'subject_area' => 'Mathématiques',
                'sort_order' => 2
            ],
            [
                'name' => 'Résolution de problèmes',
                'code' => 'COMP_PRIM_MATH_3',
                'description' => 'Maîtrise de la résolution de problèmes',
                'subject_area' => 'Mathématiques',
                'sort_order' => 3
            ],
        ];

        $createdCompetencies = [];
        
        foreach ($competencies as $competencyData) {
            $competency = Competency::updateOrCreate(
                ['code' => $competencyData['code']],
                array_merge($competencyData, [
                    'is_active' => true
                ])
            );
            
            $createdCompetencies[] = $competency;
        }

        $this->command->info("✅ " . count($createdCompetencies) . " compétences de primaire créées");
        
        return $createdCompetencies;
    }

    /**
     * Créer les critères pour chaque compétence (3 critères par compétence)
     */
    private function createPrimaireCriteria($competencies)
    {
        // Descriptions des critères par domaine
        $criteriaDescriptions = [
            // EDM & EAS
            'Histoire, géographie, citoyenneté' => [
                'C1' => ['name' => 'Connaissances historiques', 'description' => 'Maîtrise des connaissances en histoire'],
                'C2' => ['name' => 'Connaissances géographiques', 'description' => 'Maîtrise des connaissances en géographie'],
                'C3' => ['name' => 'Citoyenneté et valeurs', 'description' => 'Compréhension des valeurs citoyennes'],
            ],
            'Sciences, Technologie et Informatique' => [
                'C1' => ['name' => 'Observation et expérimentation', 'description' => 'Capacité à observer et expérimenter'],
                'C2' => ['name' => 'Connaissances scientifiques', 'description' => 'Maîtrise des connaissances scientifiques'],
                'C3' => ['name' => 'Utilisation de la technologie', 'description' => 'Maîtrise de l\'utilisation de la technologie'],
            ],
            'EPS et art' => [
                'C1' => ['name' => 'Compétences physiques', 'description' => 'Maîtrise des compétences physiques'],
                'C2' => ['name' => 'Créativité artistique', 'description' => 'Développement de la créativité artistique'],
                'C3' => ['name' => 'Coopération et respect', 'description' => 'Capacité à coopérer et respecter les autres'],
            ],
            
            // Français
            'Compréhension orale et langage' => [
                'C1' => ['name' => 'Écoute attentive', 'description' => 'Capacité à écouter attentivement'],
                'C2' => ['name' => 'Compréhension du message', 'description' => 'Compréhension du message oral'],
                'C3' => ['name' => 'Expression orale', 'description' => 'Qualité de l\'expression orale'],
            ],
            'Lecture, écriture et production écrite' => [
                'C1' => ['name' => 'Fluence de lecture', 'description' => 'Capacité à lire avec fluidité'],
                'C2' => ['name' => 'Compréhension écrite', 'description' => 'Compréhension des textes écrits'],
                'C3' => ['name' => 'Production écrite', 'description' => 'Qualité de la production écrite'],
            ],
            'Grammaire et vocabulaire' => [
                'C1' => ['name' => 'Vocabulaire', 'description' => 'Maîtrise du vocabulaire'],
                'C2' => ['name' => 'Grammaire', 'description' => 'Maîtrise des règles grammaticales'],
                'C3' => ['name' => 'Orthographe', 'description' => 'Maîtrise de l\'orthographe'],
            ],
            
            // Mathématiques
            'Nombres et opérations' => [
                'C1' => ['name' => 'Connaissance des nombres', 'description' => 'Maîtrise de la numération'],
                'C2' => ['name' => 'Calculs de base', 'description' => 'Maîtrise des opérations de base'],
                'C3' => ['name' => 'Calcul mental', 'description' => 'Capacité de calcul mental'],
            ],
            'Géométrie et mesures' => [
                'C1' => ['name' => 'Reconnaissance des formes', 'description' => 'Reconnaissance des formes géométriques'],
                'C2' => ['name' => 'Mesures', 'description' => 'Maîtrise des mesures'],
                'C3' => ['name' => 'Repérage dans l\'espace', 'description' => 'Capacité à se repérer dans l\'espace'],
            ],
            'Résolution de problèmes' => [
                'C1' => ['name' => 'Compréhension du problème', 'description' => 'Compréhension de l\'énoncé'],
                'C2' => ['name' => 'Stratégie de résolution', 'description' => 'Choix d\'une stratégie de résolution'],
                'C3' => ['name' => 'Vérification et présentation', 'description' => 'Vérification et présentation de la solution'],
            ],
        ];

        $totalCriteria = 0;
        
        foreach ($competencies as $competency) {
            $descriptions = $criteriaDescriptions[$competency->name] ?? [];
            
            // Créer 3 critères (C1, C2, C3) pour chaque compétence
            for ($i = 1; $i <= 3; $i++) {
                $code = "C{$i}";
                $criterionData = $descriptions[$code] ?? [
                    'name' => "Critère {$i}",
                    'description' => "Critère {$i} pour {$competency->name}"
                ];
                
                CompetencyCriteria::updateOrCreate(
                    [
                        'competency_id' => $competency->id,
                        'code' => $code
                    ],
                    [
                        'name' => $criterionData['name'],
                        'description' => $criterionData['description'],
                        'max_points' => 5, // Maximum 5 points par critère
                        'sort_order' => $i,
                        'is_active' => true
                    ]
                );
                
                $totalCriteria++;
            }
        }

        $this->command->info("✅ {$totalCriteria} critères créés (3 par compétence)");
    }

    /**
     * Créer des exemples de notes/évaluations pour les élèves de primaire
     */
    private function createPrimaireNotes($competencies)
    {
        // Récupérer ou créer les données nécessaires
        $academicYear = AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $this->command->warn('⚠️  Aucune année académique courante trouvée. Création d\'une année...');
            $academicYear = AcademicYear::create([
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear()->addYear(),
                'is_current' => true
            ]);
        }

        // Récupérer ou créer un enseignant
        $teacher = Teacher::first();
        if (!$teacher) {
            $this->command->warn('⚠️  Aucun enseignant trouvé. Création d\'un enseignant par défaut...');
            $teacher = Teacher::create([
                'first_name' => 'Enseignant',
                'last_name' => 'Primaire',
                'email' => 'enseignant.primaire@ecole.com',
                'phone' => '0123456789',
                'speciality' => 'Enseignant généraliste primaire',
                'grade' => 'Professeur des écoles'
            ]);
        }

        // Récupérer les classes de primaire
        $classes = SchoolClass::whereHas('level', function($query) {
            $query->where('name', 'like', '%Primaire%')
                  ->orWhere('name', 'like', '%CP%')
                  ->orWhere('name', 'like', '%CE%')
                  ->orWhere('name', 'like', '%CM%');
        })->orWhere('name', 'like', '%CP%')
          ->orWhere('name', 'like', '%CE%')
          ->orWhere('name', 'like', '%CM%')
          ->get();

        if ($classes->isEmpty()) {
            // Chercher n'importe quelle classe
            $classes = SchoolClass::limit(3)->get();
        }

        if ($classes->isEmpty()) {
            $this->command->error('❌ Aucune classe trouvée. Veuillez créer des classes d\'abord.');
            return;
        }

        $totalEvaluations = 0;
        
        foreach ($classes as $class) {
            // Récupérer les élèves de la classe
            $students = Student::whereHas('enrollments', function($query) use ($class, $academicYear) {
                $query->where('class_id', $class->id)
                      ->where('academic_year_id', $academicYear->id)
                      ->where('status', 'active');
            })->limit(5)->get(); // Limiter à 5 élèves pour les exemples

            if ($students->isEmpty()) {
                continue;
            }

            $this->command->info("📝 Création d'évaluations pour la classe : {$class->name} ({$students->count()} élèves)");

            // Créer des évaluations pour les paliers 1 et 2
            foreach ([1, 2] as $palier) {
                foreach ($students as $student) {
                    foreach ($competencies as $competency) {
                        // Vérifier si l'évaluation existe déjà
                        $existing = StudentCompetencyEvaluation::where([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $academicYear->id,
                            'palier' => $palier
                        ])->first();

                        if ($existing) {
                            continue;
                        }

                        // Générer des notes réalistes (sur 5 pour chaque critère)
                        $c1_points = $this->generateRealisticNote($palier);
                        $c2_points = $this->generateRealisticNote($palier);
                        $c3_points = $this->generateRealisticNote($palier);
                        
                        $totalPoints = $c1_points + $c2_points + $c3_points;
                        $maxPoints = 15; // 3 critères × 5 points
                        
                        // Calculer le niveau de maîtrise
                        $percentage = ($totalPoints / $maxPoints) * 100;
                        $masteryLevel = $this->calculateMasteryLevel($percentage);

                        // Créer l'évaluation
                        StudentCompetencyEvaluation::create([
                            'student_id' => $student->id,
                            'competency_id' => $competency->id,
                            'class_id' => $class->id,
                            'academic_year_id' => $academicYear->id,
                            'teacher_id' => $teacher->id,
                            'palier' => $palier,
                            'evaluation_date' => $palier == 1 
                                ? now()->subDays(rand(60, 90)) 
                                : now()->subDays(rand(15, 30)),
                            'c1_points' => $c1_points,
                            'c2_points' => $c2_points,
                            'c3_points' => $c3_points,
                            'c4_points' => 0, // Pas de C4 pour les compétences principales
                            'c1_max_points' => 5,
                            'c2_max_points' => 5,
                            'c3_max_points' => 5,
                            'c4_max_points' => 0,
                            'total_points_obtained' => $totalPoints,
                            'total_points_max' => $maxPoints,
                            'competency_mastery' => $masteryLevel,
                            'subject_mastery' => $masteryLevel,
                            'palier_mastery' => $masteryLevel,
                            'is_exit_profile' => false,
                            'comments' => $this->generateComment($competency->name, $palier, $masteryLevel)
                        ]);

                        $totalEvaluations++;
                    }
                }
            }
        }

        $this->command->info("✅ {$totalEvaluations} évaluations créées");
        $this->command->info("📊 Statistiques :");
        $this->command->info("   - Classes : {$classes->count()}");
        $this->command->info("   - Compétences : " . count($competencies));
        $this->command->info("   - Paliers : 1 et 2");
        $this->command->info("   - Total évaluations : {$totalEvaluations}");
    }

    /**
     * Générer une note réaliste selon le palier
     * Le palier 2 devrait avoir de meilleures notes en moyenne
     */
    private function generateRealisticNote($palier)
    {
        if ($palier == 1) {
            // Palier 1 : notes entre 2 et 5, moyenne autour de 3.5
            return rand(2, 5);
        } else {
            // Palier 2 : notes entre 3 et 5, moyenne autour de 4
            $weights = [3, 3, 4, 4, 4, 4, 4, 5, 5, 5];
            return $weights[array_rand($weights)];
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
     * Générer un commentaire selon le niveau de maîtrise
     */
    private function generateComment($competencyName, $palier, $masteryLevel)
    {
        $comments = [
            'maximale' => [
                "Excellent travail au palier {$palier} ! L'élève maîtrise parfaitement la compétence : {$competencyName}.",
                "Très bonne performance au palier {$palier}. Maîtrise complète de la compétence : {$competencyName}.",
                "Résultats remarquables au palier {$palier}. Compétence parfaitement acquise : {$competencyName}."
            ],
            'minimale' => [
                "Bon travail au palier {$palier}. L'élève maîtrise bien la compétence : {$competencyName}.",
                "Satisfaisant au palier {$palier}. Compétence globalement acquise : {$competencyName}.",
                "Bon niveau de maîtrise au palier {$palier}. Quelques progrès encore possibles : {$competencyName}."
            ],
            'partielle' => [
                "Progrès en cours au palier {$palier}. Compétence partiellement acquise : {$competencyName}.",
                "En cours d'acquisition au palier {$palier}. Encore quelques efforts nécessaires : {$competencyName}.",
                "Maîtrise partielle au palier {$palier}. Continuer les efforts : {$competencyName}."
            ],
            'non_maitrise' => [
                "Difficultés rencontrées au palier {$palier}. Compétence non encore acquise : {$competencyName}.",
                "Besoin de soutien au palier {$palier}. Compétence à retravailler : {$competencyName}.",
                "En cours d'apprentissage au palier {$palier}. Accompagnement nécessaire : {$competencyName}."
            ]
        ];

        $levelComments = $comments[$masteryLevel] ?? $comments['partielle'];
        return $levelComments[array_rand($levelComments)];
    }
}

