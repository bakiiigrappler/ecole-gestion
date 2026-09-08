<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LevelFee;
use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Support\Facades\DB;

class SchoolFeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n💰 Création des frais scolaires...\n\n";

        // Supprimer tous les frais existants
        echo "🗑️  Suppression des frais existants...\n";
        
        // Désactiver temporairement les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Supprimer les tables liées
        DB::table('enrollment_fees')->truncate();
        DB::table('class_fees')->truncate();
        DB::table('level_fees')->truncate();
        
        // Réactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "✅ Frais existants supprimés\n\n";

        // Récupérer l'année scolaire en cours
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            echo "❌ Aucune année scolaire en cours trouvée.\n";
            echo "💡 Veuillez d'abord créer une année scolaire active.\n";
            return;
        }

        echo "📅 Année scolaire : {$currentYear->name}\n\n";

        // ========================================
        // FRAIS PAR CYCLE
        // ========================================
        
        echo "📚 Création des frais par cycle...\n\n";

        $cyclesFees = [
            'preprimaire' => [
                [
                    'name' => 'Frais de scolarité - Préprimaire',
                    'fee_type' => 'tuition',
                    'amount' => 155000,
                    'description' => 'Frais de scolarité de base (inclut inscription et matériel pédagogique)',
                    'is_mandatory' => true,
                    'is_base_tuition' => true,
                    'sort_order' => 1
                ],
            ],
            'primaire' => [
                [
                    'name' => 'Frais de scolarité - Primaire',
                    'fee_type' => 'tuition',
                    'amount' => 205000,
                    'description' => 'Frais de scolarité de base (inclut inscription et manuels scolaires)',
                    'is_mandatory' => true,
                    'is_base_tuition' => true,
                    'sort_order' => 1
                ],
            ],
            'college' => [
                [
                    'name' => 'Frais de scolarité - Collège',
                    'fee_type' => 'tuition',
                    'amount' => 285000,
                    'description' => 'Frais de scolarité de base (inclut inscription, manuels et laboratoire)',
                    'is_mandatory' => true,
                    'is_base_tuition' => true,
                    'sort_order' => 1
                ],
            ],
            'lycee' => [
                [
                    'name' => 'Frais de scolarité - Lycée',
                    'fee_type' => 'tuition',
                    'amount' => 355000,
                    'description' => 'Frais de scolarité de base (inclut inscription, manuels, laboratoire et informatique)',
                    'is_mandatory' => true,
                    'is_base_tuition' => true,
                    'sort_order' => 1
                ],
            ],
        ];

        foreach ($cyclesFees as $cycle => $fees) {
            echo "  📌 Cycle : " . ucfirst($cycle) . "\n";
            
            foreach ($fees as $fee) {
                LevelFee::create([
                    'level_id' => null,
                    'cycle' => $cycle,
                    'is_general' => false,
                    'academic_year_id' => $currentYear->id,
                    'fee_type' => $fee['fee_type'],
                    'name' => $fee['name'],
                    'description' => $fee['description'],
                    'amount' => $fee['amount'],
                    'frequency' => 'yearly',
                    'is_mandatory' => $fee['is_mandatory'],
                    'is_active' => true,
                    'sort_order' => $fee['sort_order'],
                    'is_base_tuition' => $fee['is_base_tuition'] ?? false
                ]);
                
                $badge = ($fee['is_base_tuition'] ?? false) ? '💰 SCOLARITÉ DE BASE' : ($fee['is_mandatory'] ? '🔴 Obligatoire' : '🟢 Optionnel');
                echo "     ✅ {$fee['name']} - " . number_format($fee['amount'], 0, ',', ' ') . " FCFA {$badge}\n";
            }
            
            echo "\n";
        }

        // ========================================
        // FRAIS GÉNÉRAUX (non liés au cycle)
        // ========================================
        
        echo "🌐 Création des frais généraux (tous cycles)...\n\n";

        $generalFees = [
            [
                'name' => 'Uniforme complet',
                'fee_type' => 'uniform',
                'amount' => 25000,
                'description' => 'Tenue complète (chemise, pantalon/jupe, chaussures)',
                'is_mandatory' => false,
                'sort_order' => 1
            ],
            [
                'name' => 'Tenue de sport',
                'fee_type' => 'uniform',
                'amount' => 15000,
                'description' => 'Survêtement et chaussures de sport',
                'is_mandatory' => false,
                'sort_order' => 2
            ],
            [
                'name' => 'Transport scolaire',
                'fee_type' => 'transport',
                'amount' => 30000,
                'description' => 'Abonnement mensuel au transport scolaire',
                'is_mandatory' => false,
                'sort_order' => 3,
                'frequency' => 'monthly'
            ],
            [
                'name' => 'Cantine scolaire',
                'fee_type' => 'meal',
                'amount' => 25000,
                'description' => 'Repas du midi (abonnement mensuel)',
                'is_mandatory' => false,
                'sort_order' => 4,
                'frequency' => 'monthly'
            ],
            [
                'name' => 'Assurance scolaire',
                'fee_type' => 'other',
                'amount' => 10000,
                'description' => 'Assurance accidents scolaires (recommandée)',
                'is_mandatory' => false,
                'sort_order' => 5
            ],
            [
                'name' => 'Carte d\'étudiant',
                'fee_type' => 'other',
                'amount' => 2000,
                'description' => 'Carte d\'identité scolaire avec photo',
                'is_mandatory' => false,
                'sort_order' => 6
            ],
        ];

        // ========================================
        // FRAIS OPTIONNELS SPÉCIFIQUES AU COLLÈGE
        // ========================================
        
        echo "🏫 Création des frais optionnels spécifiques au collège...\n\n";

        $collegeSpecificFees = [
            [
                'name' => 'Matériel de laboratoire',
                'fee_type' => 'other',
                'amount' => 35000,
                'description' => 'Matériel scientifique pour les TP (Sciences Physiques, SVT)',
                'is_mandatory' => false,
                'sort_order' => 1
            ],
            [
                'name' => 'Calculatrice scientifique',
                'fee_type' => 'other',
                'amount' => 25000,
                'description' => 'Calculatrice graphique pour les mathématiques',
                'is_mandatory' => false,
                'sort_order' => 2
            ],
            [
                'name' => 'Atlas géographique',
                'fee_type' => 'books',
                'amount' => 15000,
                'description' => 'Atlas complet pour la géographie',
                'is_mandatory' => false,
                'sort_order' => 3
            ],
            [
                'name' => 'Dictionnaire bilingue',
                'fee_type' => 'books',
                'amount' => 20000,
                'description' => 'Dictionnaire français-anglais/espagnol',
                'is_mandatory' => false,
                'sort_order' => 4
            ],
            [
                'name' => 'Sortie éducative',
                'fee_type' => 'activities',
                'amount' => 30000,
                'description' => 'Sortie pédagogique annuelle (musée, site historique)',
                'is_mandatory' => false,
                'sort_order' => 5
            ],
            [
                'name' => 'Club scientifique',
                'fee_type' => 'activities',
                'amount' => 15000,
                'description' => 'Participation aux activités du club de sciences',
                'is_mandatory' => false,
                'sort_order' => 6
            ],
            [
                'name' => 'Cours de soutien',
                'fee_type' => 'other',
                'amount' => 40000,
                'description' => 'Cours de soutien en mathématiques et français',
                'is_mandatory' => false,
                'sort_order' => 7,
                'frequency' => 'monthly'
            ],
        ];

        foreach ($collegeSpecificFees as $fee) {
            LevelFee::create([
                'level_id' => null,
                'cycle' => 'college',
                'is_general' => false,
                'academic_year_id' => $currentYear->id,
                'fee_type' => $fee['fee_type'],
                'name' => $fee['name'],
                'description' => $fee['description'],
                'amount' => $fee['amount'],
                'frequency' => $fee['frequency'] ?? 'yearly',
                'is_mandatory' => $fee['is_mandatory'],
                'is_active' => true,
                'sort_order' => $fee['sort_order']
            ]);
            
            $status = $fee['is_mandatory'] ? '🔴 Obligatoire' : '🟢 Optionnel';
            $frequency = $fee['frequency'] ?? 'yearly';
            $frequencyLabel = $frequency === 'monthly' ? '(mensuel)' : '(annuel)';
            
            echo "  ✅ {$fee['name']} - " . number_format($fee['amount'], 0, ',', ' ') . " FCFA {$status} {$frequencyLabel}\n";
        }

        echo "\n";

        foreach ($generalFees as $fee) {
            LevelFee::create([
                'level_id' => null,
                'cycle' => null,
                'is_general' => true,
                'academic_year_id' => $currentYear->id,
                'fee_type' => $fee['fee_type'],
                'name' => $fee['name'],
                'description' => $fee['description'],
                'amount' => $fee['amount'],
                'frequency' => $fee['frequency'] ?? 'yearly',
                'is_mandatory' => $fee['is_mandatory'],
                'is_active' => true,
                'sort_order' => $fee['sort_order']
            ]);
            
            $status = $fee['is_mandatory'] ? '🔴 Obligatoire' : '🟢 Optionnel';
            $frequency = $fee['frequency'] ?? 'yearly';
            $frequencyLabel = $frequency === 'monthly' ? '(mensuel)' : '(annuel)';
            
            echo "  ✅ {$fee['name']} - " . number_format($fee['amount'], 0, ',', ' ') . " FCFA {$status} {$frequencyLabel}\n";
        }

        echo "\n✅ Frais scolaires créés avec succès!\n\n";

        // Afficher un résumé
        echo "📊 Résumé:\n";
        echo "  • Frais de scolarité de base : " . LevelFee::where('is_base_tuition', true)->count() . " (4 cycles)\n";
        echo "  • Frais généraux optionnels : " . LevelFee::where('is_general', true)->count() . "\n";
        echo "  • Frais spécifiques au collège : " . LevelFee::where('cycle', 'college')->where('is_general', false)->where('is_base_tuition', false)->count() . "\n";
        echo "  • Total : " . LevelFee::count() . " frais\n\n";

        echo "💰 Montants de scolarité de base par cycle:\n";
        $baseFees = LevelFee::where('is_base_tuition', true)->get();
        foreach ($baseFees as $fee) {
            echo "  • " . ucfirst($fee->cycle) . " : " . number_format($fee->amount, 0, ',', ' ') . " FCFA\n";
        }

        echo "\n💡 Vous pouvez maintenant créer des inscriptions et les frais seront chargés automatiquement!\n";
    }
}
