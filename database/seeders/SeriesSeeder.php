<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Series;

class SeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Séries pour la 2nde
        $series2nde = [
            ['code' => '2NDE-S', 'name' => 'Scientifique', 'level' => '2nde', 'order' => 1, 'description' => 'Série scientifique générale'],
            ['code' => '2NDE-LE', 'name' => 'Lettres modernes', 'level' => '2nde', 'order' => 2, 'description' => 'Série lettres et langues modernes'],
        ];

        // Séries pour la 1ère
        $series1ere = [
            ['code' => '1ERE-S', 'name' => 'Scientifique', 'level' => '1ère', 'order' => 1, 'description' => 'Série scientifique générale'],
            ['code' => '1ERE-A1', 'name' => 'Lettres-Langues anciennes', 'level' => '1ère', 'order' => 2, 'description' => 'Série lettres et langues anciennes'],
            ['code' => '1ERE-A2', 'name' => 'Lettres-Langues vivantes', 'level' => '1ère', 'order' => 3, 'description' => 'Série lettres et langues vivantes'],
            ['code' => '1ERE-B', 'name' => 'Sciences économiques et sociales', 'level' => '1ère', 'order' => 4, 'description' => 'Série sciences économiques et sociales'],
        ];

        // Séries pour la Terminale
        $seriesTerminale = [
            ['code' => 'TERM-S', 'name' => 'Scientifique', 'level' => 'Terminale', 'order' => 1, 'description' => 'Série scientifique générale'],
            ['code' => 'TERM-A1', 'name' => 'Lettres-Langues anciennes', 'level' => 'Terminale', 'order' => 2, 'description' => 'Série lettres et langues anciennes'],
            ['code' => 'TERM-A2', 'name' => 'Lettres-Langues vivantes', 'level' => 'Terminale', 'order' => 3, 'description' => 'Série lettres et langues vivantes'],
            ['code' => 'TERM-B', 'name' => 'Sciences économiques et sociales', 'level' => 'Terminale', 'order' => 4, 'description' => 'Série sciences économiques et sociales'],
            ['code' => 'TERM-C', 'name' => 'Mathématiques-Sciences physiques', 'level' => 'Terminale', 'order' => 5, 'description' => 'Série mathématiques et sciences physiques'],
            ['code' => 'TERM-D', 'name' => 'Sciences naturelles', 'level' => 'Terminale', 'order' => 6, 'description' => 'Série sciences naturelles'],
            ['code' => 'TERM-E', 'name' => 'Techniques industrielles', 'level' => 'Terminale', 'order' => 7, 'description' => 'Série techniques industrielles'],
            ['code' => 'TERM-F1', 'name' => 'Mécanique générale', 'level' => 'Terminale', 'order' => 8, 'description' => 'Série mécanique générale'],
            ['code' => 'TERM-F2', 'name' => 'Électronique', 'level' => 'Terminale', 'order' => 9, 'description' => 'Série électronique'],
            ['code' => 'TERM-F3', 'name' => 'Électrotechnique', 'level' => 'Terminale', 'order' => 10, 'description' => 'Série électrotechnique'],
            ['code' => 'TERM-F4', 'name' => 'Génie civil', 'level' => 'Terminale', 'order' => 11, 'description' => 'Série génie civil'],
            ['code' => 'TERM-G1', 'name' => 'Secrétariat', 'level' => 'Terminale', 'order' => 12, 'description' => 'Série secrétariat'],
            ['code' => 'TERM-G2', 'name' => 'Comptabilité', 'level' => 'Terminale', 'order' => 13, 'description' => 'Série comptabilité'],
            ['code' => 'TERM-G3', 'name' => 'Commerce', 'level' => 'Terminale', 'order' => 14, 'description' => 'Série commerce'],
        ];

        // Créer les séries pour la 2nde
        foreach ($series2nde as $serie) {
            Series::create(array_merge($serie, ['is_active' => true]));
        }

        // Créer les séries pour la 1ère
        foreach ($series1ere as $serie) {
            Series::create(array_merge($serie, ['is_active' => true]));
        }

        // Créer les séries pour la Terminale
        foreach ($seriesTerminale as $serie) {
            Series::create(array_merge($serie, ['is_active' => true]));
        }

        $this->command->info('Séries créées avec succès !');
        $this->command->info('2nde : 2NDE-S, 2NDE-LE');
        $this->command->info('1ère : 1ERE-S, 1ERE-A1, 1ERE-A2, 1ERE-B');
        $this->command->info('Terminale : TERM-S, TERM-A1, TERM-A2, TERM-B, TERM-C, TERM-D, TERM-E, TERM-F1, TERM-F2, TERM-F3, TERM-F4, TERM-G1, TERM-G2, TERM-G3');
    }
}
