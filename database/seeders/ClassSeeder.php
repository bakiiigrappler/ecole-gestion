<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Level;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = Level::where('is_active', true)->orderBy('order')->get();
        
        foreach ($levels as $level) {
            // Créer 2 à 3 classes par niveau selon le cycle
            $classCount = in_array($level->cycle, ['preprimaire', 'primaire']) ? 2 : 3;

            /*
             * Au lycee, une classe sans serie n'existe pas : on est en
             * terminale C ou en terminale A2, pas en « terminale ». Le bulletin
             * la porte, et le service d'orientation la lit pour savoir ce que
             * le superieur ouvre a l'eleve — sans elle, il ne peut rien
             * proposer.
             */
            $series = $level->cycle === 'lycee'
                ? \App\Models\Series::where('level_id', $level->id)->orderBy('order')->get()
                : collect();

            for ($i = 1; $i <= $classCount; $i++) {
                $serie = $series->isNotEmpty() ? $series[($i - 1) % $series->count()] : null;

                SchoolClass::create([
                    'name' => $level->name . ' ' . $i,
                    'level_id' => $level->id, // Relation correcte avec la table levels
                    'series_id' => $serie?->id,
                    'capacity' => $this->getCapacityByLevel($level->cycle),
                    'description' => 'Classe ' . $level->name . ' section ' . $i,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Classes créées avec succès !');
        $this->command->info('Niveaux actifs traités : ' . $levels->count());
        $this->command->info('Classes par niveau : Préprimaire/Primaire = 2, Collège = 3');
    }
    
    /**
     * Définir la capacité selon le niveau
     */
    private function getCapacityByLevel($cycle)
    {
        return match($cycle) {
            'preprimaire' => rand(15, 20), // Plus petit pour les tout-petits
            'primaire' => rand(20, 30),
            'college' => rand(25, 35),
            'lycee' => rand(30, 40),
            default => rand(20, 30)
        };
    }
}
