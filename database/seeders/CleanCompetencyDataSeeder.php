<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;
use App\Models\StudentCompetencyEvaluation;
use Illuminate\Support\Facades\DB;

class CleanCompetencyDataSeeder extends Seeder
{
    /**
     * Nettoyer complètement et recréer les données avec la structure exacte
     */
    public function run()
    {
        $this->command->info('🧹 Nettoyage complet de la base de données...');
        
        // Supprimer TOUTES les données existantes
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Supprimer dans l'ordre pour respecter les contraintes FK
        StudentCompetencyEvaluation::truncate();
        CompetencyCriteria::truncate();
        Competency::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Base de données nettoyée !');
        
        // Créer les compétences avec la structure EXACTE de l'image
        $this->command->info('📚 Création des compétences avec la structure exacte...');
        
        $competencies = [
            // EDM & EAS (3 compétences)
            ['name' => 'Histoire, géographie, citoyenneté', 'code' => 'COMP_EDM_1', 'subject_area' => 'EDM & EAS', 'sort_order' => 1],
            ['name' => 'Sciences, Technologie et informatique', 'code' => 'COMP_EDM_2', 'subject_area' => 'EDM & EAS', 'sort_order' => 2],
            ['name' => 'EPS et art', 'code' => 'COMP_EDM_3', 'subject_area' => 'EDM & EAS', 'sort_order' => 3],
            
            // Français (3 compétences)
            ['name' => 'Compréhension orale et langage', 'code' => 'COMP_FR_1', 'subject_area' => 'Français', 'sort_order' => 1],
            ['name' => 'Lecture, écriture et production écrite', 'code' => 'COMP_FR_2', 'subject_area' => 'Français', 'sort_order' => 2],
            ['name' => 'Grammaire et vocabulaire', 'code' => 'COMP_FR_3', 'subject_area' => 'Français', 'sort_order' => 3],
            
            // Mathématiques (3 compétences)
            ['name' => 'Nombres et opérations et Résolution des problèmes', 'code' => 'COMP_MATH_1', 'subject_area' => 'Mathématiques', 'sort_order' => 1],
            ['name' => 'Géométrie et Mesure', 'code' => 'COMP_MATH_2', 'subject_area' => 'Mathématiques', 'sort_order' => 2],
            ['name' => 'Résolution des problèmes', 'code' => 'COMP_MATH_3', 'subject_area' => 'Mathématiques', 'sort_order' => 3],
        ];

        $createdCompetencies = [];
        
        foreach ($competencies as $competencyData) {
            $competency = Competency::create([
                'name' => $competencyData['name'],
                'code' => $competencyData['code'],
                'subject_area' => $competencyData['subject_area'],
                'description' => 'Compétence ' . $competencyData['name'],
                'sort_order' => $competencyData['sort_order'],
                'is_active' => true
            ]);
            
            $createdCompetencies[] = $competency;

            // Créer EXACTEMENT 3 critères (C1, C2, C3) pour chaque compétence
            for ($i = 1; $i <= 3; $i++) {
                CompetencyCriteria::create([
                    'competency_id' => $competency->id,
                    'code' => "C{$i}",
                    'name' => "Critère {$i}",
                    'description' => "Critère {$i} pour {$competency->name}",
                    'max_points' => 5,
                    'sort_order' => $i,
                    'is_active' => true
                ]);
            }
        }
        
        $this->command->info('✅ Compétences créées avec succès !');
        $this->command->info("📊 Statistiques :");
        $this->command->info("   - EDM & EAS : 3 compétences (3 critères chacune)");
        $this->command->info("   - Français : 3 compétences (3 critères chacune)");
        $this->command->info("   - Mathématiques : 3 compétences (3 critères chacune)");
        $this->command->info("   - Total compétences : " . count($competencies));
        $this->command->info("   - Total critères : " . (count($competencies) * 3));
        $this->command->info("   - Structure : Chaque compétence = 3 critères (C1, C2, C3) + somme");
        
        // Créer des évaluations d'exemple pour la classe 2
        $this->command->info('📝 Création des évaluations d\'exemple...');
        
        $this->createExampleEvaluations($createdCompetencies);
        
        $this->command->info('🎉 Données créées avec succès !');
        $this->command->info('📋 Structure exacte selon l\'image :');
        $this->command->info('   - 9 compétences principales (3 par domaine)');
        $this->command->info('   - 3 critères par compétence (C1, C2, C3)');
        $this->command->info('   - Pas de colonnes supplémentaires');
        $this->command->info('   - Structure PDF conforme à l\'image');
    }
    
    private function createExampleEvaluations($competencies)
    {
        // Récupérer la classe 2
        $class = \App\Models\SchoolClass::find(2);
        if (!$class) {
            $this->command->error('❌ Classe 2 non trouvée. Veuillez créer la classe 2 d\'abord.');
            return;
        }
        
        // Récupérer l'année académique courante
        $academicYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if (!$academicYear) {
            $academicYear = \App\Models\AcademicYear::create([
                'name' => '2024-2025',
                'is_current' => true
            ]);
        }
        
        // Récupérer un enseignant
        $teacher = \App\Models\Teacher::first();
        if (!$teacher) {
            $teacher = \App\Models\Teacher::create([
                'first_name' => 'Enseignant',
                'last_name' => 'Test',
                'email' => 'enseignant@test.com',
                'phone' => '0123456789'
            ]);
        }
        
        // Récupérer ou créer un élève pour la classe 2
        $student = $class->students()->wherePivot('status', 'active')->first();
        if (!$student) {
            $student = \App\Models\Student::create([
                'first_name' => 'Sophie',
                'last_name' => 'DIFFERENT',
                'student_id' => 'STU2024TEST004',
                'date_of_birth' => '2016-11-25',
                'email' => 'sophie@test.com',
                'phone' => '0123456789'
            ]);
            
            // Inscrire l'élève à la classe
            $student->enrollments()->create([
                'class_id' => $class->id,
                'academic_year_id' => $academicYear->id,
                'enrollment_date' => now(),
                'status' => 'active'
            ]);
        }
        
        // Créer des évaluations pour les paliers 1 et 2
        foreach ([1, 2] as $palier) {
            foreach ($competencies as $competency) {
                // Générer des points aléatoires pour les 3 critères
                $c1_points = rand(0, 5);
                $c2_points = rand(0, 5);
                $c3_points = rand(0, 5);
                
                $totalPointsObtained = $c1_points + $c2_points + $c3_points;
                $totalPointsMax = 15; // 3 critères × 5 points max
                $percentage = $totalPointsMax > 0 ? ($totalPointsObtained / $totalPointsMax) * 100 : 0;
                
                // Calculer le niveau de maîtrise
                $masteryLevel = $this->calculateMasteryLevel($percentage);
                
                StudentCompetencyEvaluation::create([
                    'student_id' => $student->id,
                    'competency_id' => $competency->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'teacher_id' => $teacher->id,
                    'palier' => $palier,
                    'evaluation_date' => now(),
                    'c1_points' => $c1_points,
                    'c2_points' => $c2_points,
                    'c3_points' => $c3_points,
                    'c1_max_points' => 5,
                    'c2_max_points' => 5,
                    'c3_max_points' => 5,
                    'total_points_obtained' => $totalPointsObtained,
                    'total_points_max' => $totalPointsMax,
                    'competency_mastery' => $masteryLevel,
                    'subject_mastery' => $masteryLevel,
                    'palier_mastery' => $masteryLevel,
                    'comments' => "Évaluation pour {$competency->name} au palier {$palier}",
                ]);
            }
        }
        
        $this->command->info("✅ Évaluations créées pour l'élève {$student->first_name} {$student->last_name}");
        $this->command->info("   - Classe : {$class->name}");
        $this->command->info("   - Paliers : 1 et 2");
        $this->command->info("   - Compétences : " . count($competencies));
        $this->command->info("   - Total évaluations : " . (count($competencies) * 2));
    }
    
    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }
}
