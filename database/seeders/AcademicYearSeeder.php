<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Créer les années scolaires selon le calendrier gabonais
     * Au Gabon, l'année scolaire commence en septembre et se termine en juin
     */
    public function run(): void
    {
        // Mettre à jour l'année scolaire actuelle
        AcademicYear::updateCurrentAcademicYear();
        
        // Générer les 3 années précédentes et 3 années suivantes
        AcademicYear::generateYears(3, 3);

        $this->command->info('Années académiques créées avec succès selon le calendrier gabonais !');
        $this->command->info('Année scolaire actuelle : ' . \App\Helpers\SchoolHelper::getCurrentAcademicYearName());
    }
}
