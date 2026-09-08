<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competency;
use App\Models\CompetencyCriteria;
use Illuminate\Support\Facades\DB;

class CompetencyStructureSeeder extends Seeder
{
    /**
     * Créer les compétences selon la structure exacte des images
     */
    public function run()
    {
        DB::beginTransaction();

        try {
            // Supprimer les compétences existantes (gérer les contraintes FK)
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            CompetencyCriteria::truncate();
            Competency::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 1. COMPÉTENCES TRANSVERSALES
            $transversalCompetencies = [
                [
                    'name' => 'Autonomie et Initiative',
                    'code' => 'COMP_TRANS_1',
                    'subject_area' => 'Compétences transversales',
                    'description' => 'Développer l\'autonomie et l\'initiative de l\'élève',
                    'sort_order' => 1
                ],
                [
                    'name' => 'Travail en équipe',
                    'code' => 'COMP_TRANS_2',
                    'subject_area' => 'Compétences transversales',
                    'description' => 'Apprendre à travailler en équipe',
                    'sort_order' => 2
                ],
                [
                    'name' => 'Communication',
                    'code' => 'COMP_TRANS_3',
                    'subject_area' => 'Compétences transversales',
                    'description' => 'Développer les compétences de communication',
                    'sort_order' => 3
                ]
            ];

            // 2. EDM & EA8 (Éducation au Développement Durable et à l'Environnement & Éducation Artistique)
            $edmCompetencies = [
                [
                    'name' => 'Histoire, géographie, citoyenneté',
                    'code' => 'COMP_EDM_1',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences en histoire, géographie et citoyenneté',
                    'sort_order' => 1
                ],
                [
                    'name' => 'Histoire, géographie, citoyenneté',
                    'code' => 'COMP_EDM_2',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences avancées en histoire, géographie et citoyenneté',
                    'sort_order' => 2
                ],
                [
                    'name' => 'Sciences, Technologie et Informatique',
                    'code' => 'COMP_EDM_3',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences en sciences, technologie et informatique',
                    'sort_order' => 3
                ],
                [
                    'name' => 'Sciences, Technologie et Informatique',
                    'code' => 'COMP_EDM_4',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences avancées en sciences, technologie et informatique',
                    'sort_order' => 4
                ],
                [
                    'name' => 'EPS et art',
                    'code' => 'COMP_EDM_5',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences en éducation physique et sportive et art',
                    'sort_order' => 5
                ],
                [
                    'name' => 'EPS et art',
                    'code' => 'COMP_EDM_6',
                    'subject_area' => 'EDM & EA8',
                    'description' => 'Compétences avancées en éducation physique et sportive et art',
                    'sort_order' => 6
                ]
            ];

            // 3. FRANÇAIS
            $francaisCompetencies = [
                [
                    'name' => 'Compréhension orale et langage',
                    'subject_area' => 'Français',
                    'description' => 'Compétences en compréhension orale et langage',
                    'sort_order' => 1
                ],
                [
                    'name' => 'Compréhension orale et langage',
                    'subject_area' => 'Français',
                    'description' => 'Compétences avancées en compréhension orale et langage',
                    'sort_order' => 2
                ],
                [
                    'name' => 'Lecture, écriture et production écrite',
                    'subject_area' => 'Français',
                    'description' => 'Compétences en lecture, écriture et production écrite',
                    'sort_order' => 3
                ],
                [
                    'name' => 'Lecture, écriture et production écrite',
                    'subject_area' => 'Français',
                    'description' => 'Compétences avancées en lecture, écriture et production écrite',
                    'sort_order' => 4
                ],
                [
                    'name' => 'Grammaire et vocabulaire',
                    'subject_area' => 'Français',
                    'description' => 'Compétences en grammaire et vocabulaire',
                    'sort_order' => 5
                ],
                [
                    'name' => 'Grammaire et vocabulaire',
                    'subject_area' => 'Français',
                    'description' => 'Compétences avancées en grammaire et vocabulaire',
                    'sort_order' => 6
                ]
            ];

            // 4. MATHÉMATIQUES
            $mathCompetencies = [
                [
                    'name' => 'Nombres et opérations et Résolution des problèmes',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences en nombres, opérations et résolution de problèmes',
                    'sort_order' => 1
                ],
                [
                    'name' => 'Nombres et calculs',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences en nombres et calculs',
                    'sort_order' => 2
                ],
                [
                    'name' => 'Géométrie et Mesure',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences en géométrie et mesure',
                    'sort_order' => 3
                ],
                [
                    'name' => 'Géométrie et Mesure',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences avancées en géométrie et mesure',
                    'sort_order' => 4
                ],
                [
                    'name' => 'Résolution de problèmes',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences en résolution de problèmes',
                    'sort_order' => 5
                ],
                [
                    'name' => 'Résolution de problèmes',
                    'subject_area' => 'Mathématiques',
                    'description' => 'Compétences avancées en résolution de problèmes',
                    'sort_order' => 6
                ]
            ];

            // Créer toutes les compétences
            $allCompetencies = array_merge(
                $transversalCompetencies,
                $edmCompetencies,
                $francaisCompetencies,
                $mathCompetencies
            );

            foreach ($allCompetencies as $competencyData) {
                $competency = Competency::create([
                    'name' => $competencyData['name'],
                    'subject_area' => $competencyData['subject_area'],
                    'description' => $competencyData['description'],
                    'sort_order' => $competencyData['sort_order'],
                    'is_active' => true
                ]);

                // Créer les critères C1, C2, C3, C4 pour chaque compétence
                for ($i = 1; $i <= 4; $i++) {
                    CompetencyCriteria::create([
                        'competency_id' => $competency->id,
                        'code' => "C{$i}",
                        'description' => "Critère {$i} pour {$competency->name}",
                        'max_points' => 5,
                        'sort_order' => $i,
                        'is_active' => true
                    ]);
                }
            }

            DB::commit();
            
            $this->command->info('✅ Structure des compétences créée avec succès !');
            $this->command->info("📊 Statistiques :");
            $this->command->info("   - Compétences transversales : " . count($transversalCompetencies));
            $this->command->info("   - EDM & EA8 : " . count($edmCompetencies));
            $this->command->info("   - Français : " . count($francaisCompetencies));
            $this->command->info("   - Mathématiques : " . count($mathCompetencies));
            $this->command->info("   - Total compétences : " . count($allCompetencies));
            $this->command->info("   - Total critères : " . (count($allCompetencies) * 4));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erreur lors de la création de la structure des compétences : ' . $e->getMessage());
        }
    }
}
