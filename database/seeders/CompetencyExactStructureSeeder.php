<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;
use Illuminate\Support\Facades\DB;

class CompetencyExactStructureSeeder extends Seeder
{
    /**
     * Créer les compétences selon la structure EXACTE des images
     */
    public function run()
    {
        // Supprimer les compétences existantes (gérer les contraintes FK)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CompetencyCriteria::truncate();
        Competency::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Créer les compétences avec la structure EXACTE des images
        $competencies = [
            // Compétences transversales (3 compétences)
            ['name' => 'Autonomie et Initiative', 'code' => 'COMP_TRANS_1', 'subject_area' => 'Compétences transversales', 'sort_order' => 1],
            ['name' => 'Travail en équipe', 'code' => 'COMP_TRANS_2', 'subject_area' => 'Compétences transversales', 'sort_order' => 2],
            ['name' => 'Communication', 'code' => 'COMP_TRANS_3', 'subject_area' => 'Compétences transversales', 'sort_order' => 3],
            
            // EDM & EA8 (3 compétences principales, pas de doublons)
            ['name' => 'Histoire, géographie, citoyenneté', 'code' => 'COMP_EDM_1', 'subject_area' => 'EDM & EA8', 'sort_order' => 1],
            ['name' => 'Sciences, Technologie et Informatique', 'code' => 'COMP_EDM_2', 'subject_area' => 'EDM & EA8', 'sort_order' => 2],
            ['name' => 'EPS et art', 'code' => 'COMP_EDM_3', 'subject_area' => 'EDM & EA8', 'sort_order' => 3],
            
            // Français (3 compétences principales, pas de doublons)
            ['name' => 'Compréhension orale et langage', 'code' => 'COMP_FR_1', 'subject_area' => 'Français', 'sort_order' => 1],
            ['name' => 'Lecture, écriture et production écrite', 'code' => 'COMP_FR_2', 'subject_area' => 'Français', 'sort_order' => 2],
            ['name' => 'Grammaire et vocabulaire', 'code' => 'COMP_FR_3', 'subject_area' => 'Français', 'sort_order' => 3],
            
            // Mathématiques (3 compétences principales, pas de doublons)
            ['name' => 'Nombres et opérations et Résolution des problèmes', 'code' => 'COMP_MATH_1', 'subject_area' => 'Mathématiques', 'sort_order' => 1],
            ['name' => 'Géométrie et Mesure', 'code' => 'COMP_MATH_2', 'subject_area' => 'Mathématiques', 'sort_order' => 2],
            ['name' => 'Résolution de problèmes', 'code' => 'COMP_MATH_3', 'subject_area' => 'Mathématiques', 'sort_order' => 3],
        ];

        foreach ($competencies as $competencyData) {
            $competency = Competency::create([
                'name' => $competencyData['name'],
                'code' => $competencyData['code'],
                'subject_area' => $competencyData['subject_area'],
                'description' => 'Compétence ' . $competencyData['name'],
                'sort_order' => $competencyData['sort_order'],
                'is_active' => true
            ]);

            // Créer les critères C1, C2, C3, C4 pour chaque compétence
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
        
        $this->command->info('✅ Structure des compétences EXACTE créée avec succès !');
        $this->command->info("📊 Statistiques :");
        $this->command->info("   - Compétences transversales : 3");
        $this->command->info("   - EDM & EA8 : 3");
        $this->command->info("   - Français : 3");
        $this->command->info("   - Mathématiques : 3");
        $this->command->info("   - Total compétences : " . count($competencies));
        $this->command->info("   - Total critères : " . (count($competencies) * 4));
    }
}
