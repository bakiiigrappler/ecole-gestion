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
            
            for ($i = 1; $i <= $classCount; $i++) {
                SchoolClass::create([
                    'name' => $level->name . ' ' . $i,
                    'level_id' => $level->id, // Relation correcte avec la table levels
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
