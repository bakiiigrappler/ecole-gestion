<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Series;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Les séries du lycée gabonais.
 *
 * Le rattachement au niveau se fait par `level_id`. Ce seeder écrivait encore
 * une colonne `level` — une chaîne devant correspondre au nom du niveau — que
 * la migration `relier_les_series_aux_niveaux` a supprimée : le peuplement
 * d'une base neuve s'arrêtait là, sur les deux moteurs.
 */
class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            // 2nde
            ['code' => '2NDE-S',  'name' => 'Scientifique',      'niveau' => '2nde', 'order' => 1, 'description' => 'Série scientifique générale'],
            ['code' => '2NDE-LE', 'name' => 'Lettres modernes',  'niveau' => '2nde', 'order' => 2, 'description' => 'Série lettres et langues modernes'],

            // 1ère
            ['code' => '1ERE-S',  'name' => 'Scientifique',                     'niveau' => '1ère', 'order' => 1, 'description' => 'Série scientifique générale'],
            ['code' => '1ERE-A1', 'name' => 'Lettres-Langues anciennes',        'niveau' => '1ère', 'order' => 2, 'description' => 'Série lettres et langues anciennes'],
            ['code' => '1ERE-A2', 'name' => 'Lettres-Langues vivantes',         'niveau' => '1ère', 'order' => 3, 'description' => 'Série lettres et langues vivantes'],
            ['code' => '1ERE-B',  'name' => 'Sciences économiques et sociales', 'niveau' => '1ère', 'order' => 4, 'description' => 'Série sciences économiques et sociales'],

            // Terminale
            ['code' => 'TERM-S',  'name' => 'Scientifique',                      'niveau' => 'Terminal', 'order' => 1,  'description' => 'Série scientifique générale'],
            ['code' => 'TERM-A1', 'name' => 'Lettres-Langues anciennes',         'niveau' => 'Terminal', 'order' => 2,  'description' => 'Série lettres et langues anciennes'],
            ['code' => 'TERM-A2', 'name' => 'Lettres-Langues vivantes',          'niveau' => 'Terminal', 'order' => 3,  'description' => 'Série lettres et langues vivantes'],
            ['code' => 'TERM-B',  'name' => 'Sciences économiques et sociales',  'niveau' => 'Terminal', 'order' => 4,  'description' => 'Série sciences économiques et sociales'],
            ['code' => 'TERM-C',  'name' => 'Mathématiques-Sciences physiques',  'niveau' => 'Terminal', 'order' => 5,  'description' => 'Série mathématiques et sciences physiques'],
            ['code' => 'TERM-D',  'name' => 'Sciences naturelles',               'niveau' => 'Terminal', 'order' => 6,  'description' => 'Série sciences naturelles'],
            ['code' => 'TERM-E',  'name' => 'Techniques industrielles',          'niveau' => 'Terminal', 'order' => 7,  'description' => 'Série techniques industrielles'],
            ['code' => 'TERM-F1', 'name' => 'Mécanique générale',                'niveau' => 'Terminal', 'order' => 8,  'description' => 'Série mécanique générale'],
            ['code' => 'TERM-F2', 'name' => 'Électronique',                      'niveau' => 'Terminal', 'order' => 9,  'description' => 'Série électronique'],
            ['code' => 'TERM-F3', 'name' => 'Électrotechnique',                  'niveau' => 'Terminal', 'order' => 10, 'description' => 'Série électrotechnique'],
            ['code' => 'TERM-F4', 'name' => 'Génie civil',                       'niveau' => 'Terminal', 'order' => 11, 'description' => 'Série génie civil'],
            ['code' => 'TERM-G1', 'name' => 'Secrétariat',                       'niveau' => 'Terminal', 'order' => 12, 'description' => 'Série secrétariat'],
            ['code' => 'TERM-G2', 'name' => 'Comptabilité',                      'niveau' => 'Terminal', 'order' => 13, 'description' => 'Série comptabilité'],
            ['code' => 'TERM-G3', 'name' => 'Commerce',                          'niveau' => 'Terminal', 'order' => 14, 'description' => 'Série commerce'],
        ];

        /*
         * Les niveaux sont retrouvés sur un nom normalisé : « Terminal » et
         * « Terminale » ont coexisté selon les jeux de données, et un accent
         * de différence suffisait à ne rattacher aucune série.
         */
        $niveaux = Level::where('cycle', 'lycee')
            ->get(['id', 'name'])
            ->keyBy(fn ($niveau) => Str::slug($niveau->name));

        $orphelines = [];

        foreach ($series as $serie) {
            $cle = Str::slug($serie['niveau']);
            $niveau = $niveaux[$cle] ?? $niveaux[Str::slug($serie['niveau'].'e')] ?? null;

            if (! $niveau) {
                $orphelines[] = $serie['code'];

                continue;
            }

            Series::updateOrCreate(
                ['code' => $serie['code']],
                [
                    'name' => $serie['name'],
                    'level_id' => $niveau->id,
                    'order' => $serie['order'],
                    'description' => $serie['description'],
                    'is_active' => true,
                ]
            );
        }

        $creees = count($series) - count($orphelines);
        $this->command->info("Séries du lycée : {$creees} créée(s).");

        if ($orphelines) {
            $this->command->warn(
                'Sans niveau correspondant, donc ignorée(s) : '.implode(', ', $orphelines)
            );
        }
    }
}
