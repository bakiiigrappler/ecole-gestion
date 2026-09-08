<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Niveaux du préprimaire (actifs)
        $preprimaireLevels = [
            ['name' => 'Petite Section', 'code' => 'PS', 'cycle' => 'preprimaire', 'order' => 1, 'description' => 'Petite Section du préprimaire'],
            ['name' => 'Moyenne Section', 'code' => 'MS', 'cycle' => 'preprimaire', 'order' => 2, 'description' => 'Moyenne Section du préprimaire'],
            ['name' => 'Grande Section', 'code' => 'GS', 'cycle' => 'preprimaire', 'order' => 3, 'description' => 'Grande Section du préprimaire'],
        ];

        // Niveaux du primaire (actifs)
        $primaireLevels = [
            ['name' => 'CP', 'code' => 'CP', 'cycle' => 'primaire', 'order' => 4, 'description' => 'Cours Préparatoire'],
            ['name' => 'CE1', 'code' => 'CE1', 'cycle' => 'primaire', 'order' => 5, 'description' => 'Cours Élémentaire 1'],
            ['name' => 'CE2', 'code' => 'CE2', 'cycle' => 'primaire', 'order' => 6, 'description' => 'Cours Élémentaire 2'],
            ['name' => 'CM1', 'code' => 'CM1', 'cycle' => 'primaire', 'order' => 7, 'description' => 'Cours Moyen 1'],
            ['name' => 'CM2', 'code' => 'CM2', 'cycle' => 'primaire', 'order' => 8, 'description' => 'Cours Moyen 2'],
        ];

        // Niveaux du collège (actifs)
        $collegeLevels = [
            ['name' => '6ème', 'code' => '6EME', 'cycle' => 'college', 'order' => 9, 'description' => 'Sixième du collège'],
            ['name' => '5ème', 'code' => '5EME', 'cycle' => 'college', 'order' => 10, 'description' => 'Cinquième du collège'],
            ['name' => '4ème', 'code' => '4EME', 'cycle' => 'college', 'order' => 11, 'description' => 'Quatrième du collège'],
            ['name' => '3ème', 'code' => '3EME', 'cycle' => 'college', 'order' => 12, 'description' => 'Troisième du collège'],
        ];

        // Niveaux du lycée (actifs)
        $lyceeLevels = [
            ['name' => '2nde', 'code' => '2NDE', 'cycle' => 'lycee', 'order' => 13, 'description' => 'Seconde du lycée', 'is_active' => true],
            ['name' => '1ère', 'code' => '1ERE', 'cycle' => 'lycee', 'order' => 14, 'description' => 'Première du lycée', 'is_active' => true],
            ['name' => 'Terminal', 'code' => 'TERMINAL', 'cycle' => 'lycee', 'order' => 15, 'description' => 'Terminale du lycée', 'is_active' => true],
        ];

        // Créer les niveaux du préprimaire
        foreach ($preprimaireLevels as $level) {
            Level::create(array_merge($level, ['is_active' => true]));
        }

        // Créer les niveaux du primaire
        foreach ($primaireLevels as $level) {
            Level::create(array_merge($level, ['is_active' => true]));
        }

        // Créer les niveaux du collège
        foreach ($collegeLevels as $level) {
            Level::create(array_merge($level, ['is_active' => true]));
        }

        // Créer les niveaux du lycée (actifs)
        foreach ($lyceeLevels as $level) {
            Level::create(array_merge($level, ['is_active' => true]));
        }

        $this->command->info('Niveaux créés avec succès !');
        $this->command->info('Préprimaire : PS, MS, GS');
        $this->command->info('Primaire : CP, CE1, CE2, CM1, CM2');
        $this->command->info('Collège : 6ème, 5ème, 4ème, 3ème');
        $this->command->info('Lycée : 2nde, 1ère, Terminal (actifs)');
    }
}
