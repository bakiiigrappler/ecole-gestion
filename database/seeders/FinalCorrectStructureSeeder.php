<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;
use App\Models\StudentCompetencyEvaluation;
use Illuminate\Support\Facades\DB;

class FinalCorrectStructureSeeder extends Seeder
{
    /**
     * Créer la structure EXACTE selon le document
     */
    public function run()
    {
        $this->command->info('🧹 Nettoyage complet et recréation de la structure exacte...');
        
        // Supprimer TOUTES les données existantes
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        StudentCompetencyEvaluation::truncate();
        CompetencyCriteria::truncate();
        Competency::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Base de données nettoyée !');
        
        // Créer les compétences avec la structure EXACTE du document
        $competencies = [
            // EDM & EAS (3 compétences)
            ['name' => 'Histoire, géographie, citoyenneté', 'code' => 'COMP_EDM_1', 'subject_area' => 'EDM & EAS', 'sort_order' => 1],
            ['name' => 'Sciences, Technologie et Informatique', 'code' => 'COMP_EDM_2', 'subject_area' => 'EDM & EAS', 'sort_order' => 2],
            ['name' => 'EPS et art', 'code' => 'COMP_EDM_3', 'subject_area' => 'EDM & EAS', 'sort_order' => 3],
            
            // Français (2 compétences selon le document)
            ['name' => 'Compréhension orale et langage', 'code' => 'COMP_FR_1', 'subject_area' => 'Français', 'sort_order' => 1],
            ['name' => 'Lecture, écriture et production écrite', 'code' => 'COMP_FR_2', 'subject_area' => 'Français', 'sort_order' => 2],
            
            // Mathématiques (2 compétences selon le document)
            ['name' => 'Nombres et opérations et Résolution des problèmes', 'code' => 'COMP_MATH_1', 'subject_area' => 'Mathématiques', 'sort_order' => 1],
            ['name' => 'Géométrie et Mesure', 'code' => 'COMP_MATH_2', 'subject_area' => 'Mathématiques', 'sort_order' => 2],
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

            // Créer EXACTEMENT 4 critères (C1, C2, C3, C4) pour chaque compétence selon le document
            for ($i = 1; $i <= 4; $i++) {
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
        $this->command->info("   - EDM & EAS : 3 compétences (4 critères chacune)");
        $this->command->info("   - Français : 2 compétences (4 critères chacune)");
        $this->command->info("   - Mathématiques : 2 compétences (4 critères chacune)");
        $this->command->info("   - Total compétences : " . count($competencies));
        $this->command->info("   - Total critères : " . (count($competencies) * 4));
        
        // Créer des évaluations d'exemple avec les calculs corrects
        $this->command->info('📝 Création des évaluations d\'exemple avec calculs corrects...');
        
        $this->createExampleEvaluationsWithCorrectCalculations($createdCompetencies);
        
        $this->command->info('🎉 Données créées avec succès !');
        $this->command->info('📋 Structure exacte selon le document :');
        $this->command->info('   - 7 compétences principales');
        $this->command->info('   - 4 critères par compétence (C1, C2, C3, C4)');
        $this->command->info('   - Calculs corrects : C1+C2+C3+C4 = Note de la compétence');
        $this->command->info('   - Structure PDF conforme au document');
    }
    
    private function createExampleEvaluationsWithCorrectCalculations($competencies)
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
                'first_name' => 'Aïcha',
                'last_name' => 'Nguema',
                'student_id' => 'STU20250100',
                'date_of_birth' => '2019-10-05',
                'email' => 'aicha@test.com',
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
        
        // Créer des évaluations pour le palier 5 avec les calculs corrects
        $palier = 5;
        
        // Données exactes du document
        $evaluationData = [
            'Histoire, géographie, citoyenneté' => ['c1' => 4, 'c2' => 4, 'c3' => 1, 'c4' => 3, 'total' => 12],
            'Sciences, Technologie et Informatique' => ['c1' => 5, 'c2' => 3, 'c3' => 2, 'c4' => 2, 'total' => 12],
            'EPS et art' => ['c1' => 4, 'c2' => 3, 'c3' => 2, 'c4' => 3, 'total' => 12],
            'Compréhension orale et langage' => ['c1' => 2, 'c2' => 2, 'c3' => 2, 'c4' => 2, 'total' => 8],
            'Lecture, écriture et production écrite' => ['c1' => 3, 'c2' => 2, 'c3' => 2, 'c4' => 1, 'total' => 8],
            'Nombres et opérations et Résolution des problèmes' => ['c1' => 3, 'c2' => 1, 'c3' => 0, 'c4' => 2, 'total' => 6],
            'Géométrie et Mesure' => ['c1' => 1, 'c2' => 1, 'c3' => 1, 'c4' => 1, 'total' => 4],
        ];
        
        foreach ($competencies as $competency) {
            $data = $evaluationData[$competency->name] ?? ['c1' => 0, 'c2' => 0, 'c3' => 0, 'c4' => 0, 'total' => 0];
            
            // Calculer le niveau de maîtrise basé sur le total
            $totalPoints = $data['total'];
            $maxPoints = 20; // 4 critères × 5 points max
            $percentage = $maxPoints > 0 ? ($totalPoints / $maxPoints) * 100 : 0;
            
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
                'c1_points' => $data['c1'],
                'c2_points' => $data['c2'],
                'c3_points' => $data['c3'],
                'c4_points' => $data['c4'],
                'c1_max_points' => 5,
                'c2_max_points' => 5,
                'c3_max_points' => 5,
                'c4_max_points' => 5,
                'total_points_obtained' => $data['total'],
                'total_points_max' => $maxPoints,
                'competency_mastery' => $masteryLevel,
                'subject_mastery' => $masteryLevel,
                'palier_mastery' => $masteryLevel,
                'comments' => "Évaluation pour {$competency->name} au palier {$palier}",
            ]);
        }
        
        $this->command->info("✅ Évaluations créées pour l'élève {$student->first_name} {$student->last_name}");
        $this->command->info("   - Classe : {$class->name}");
        $this->command->info("   - Palier : {$palier}");
        $this->command->info("   - Compétences : " . count($competencies));
        $this->command->info("   - Total évaluations : " . count($competencies));
        $this->command->info("   - Calculs corrects : C1+C2+C3+C4 = Note de la compétence");
    }
    
    private function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }
}
