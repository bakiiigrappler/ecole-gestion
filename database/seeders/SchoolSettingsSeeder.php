<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolSettings;

class SchoolSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Le logo de l'etablissement et le sceau de la Republique figurent en
        // tete des bulletins : sans ces deux colonnes, l'en-tete restait nu.
        $images = $this->deposerLesImages();

        // Vérifier si des paramètres existent déjà
        if (SchoolSettings::count() > 0) {
            $this->command->info('⚠️  Des paramètres d\'école existent déjà. Mise à jour...');
            
            $settings = SchoolSettings::first();
            $settings->update([
                'primary_school_name' => $settings->primary_school_name ?? 'ÉCOLE PRIVÉE',
                'secondary_school_name' => $settings->secondary_school_name ?? 'LYCÉE',
                'school_phone' => $settings->school_phone ?? '06037499',
                'school_bp' => $settings->school_bp ?? 'BP: 6',
                'school_motto' => $settings->school_motto ?? 'Travail - Rigueur - Discipline',
                'academic_year' => $settings->academic_year ?? '2024-2025',
                'city' => $settings->city ?? 'Libreville',
                'country' => $settings->country ?? 'Gabon',
                // Une image deja televersee par l'etablissement prime.
                'school_logo' => $settings->school_logo ?: ($images['logo'] ?? null),
                'school_seal' => $settings->school_seal ?: ($images['sceau'] ?? null),
                'is_active' => true,
            ]);
            
            $this->command->info('✅ Paramètres mis à jour avec succès.');
            return;
        }
        
        // Créer les paramètres par défaut de l'établissement
        SchoolSettings::create([
            'school_name' => 'Établissement Scolaire',
            'primary_school_name' => 'ÉCOLE PRIVÉE',
            'secondary_school_name' => 'LYCÉE',
            'school_address' => 'Libreville, Gabon',
            'school_phone' => '06037499',
            'school_email' => 'contact@ecole.ga',
            'school_website' => 'https://ecole.ga',
            'school_bp' => 'BP: 6',
            'school_logo' => $images['logo'] ?? null,
            'school_seal' => $images['sceau'] ?? null,
            'school_motto' => 'Travail - Rigueur - Discipline',
            'school_description' => 'Établissement d\'enseignement de qualité',
            'principal_name' => 'Directeur de l\'établissement',
            'principal_title' => 'Le Proviseur',
            'academic_year' => '2024-2025',
            'school_type' => 'Établissement Scolaire',
            'school_level' => 'Tous niveaux',
            'has_preprimary' => true,
            'has_primary' => true,
            'has_secondary' => true,
            'country' => 'Gabon',
            'city' => 'Libreville',
            'timezone' => 'Africa/Libreville',
            'currency' => 'FCFA',
            'language' => 'fr',
            'is_active' => true,
        ]);
        
        $this->command->info('✅ Paramètres de l\'école créés avec succès.');
    }

    /**
     * Depose le logo et le sceau fournis avec le depot dans le stockage
     * public, et renvoie leurs chemins relatifs.
     *
     * `SchoolSettings::getLogoUrlAttribute()` prefixe par `storage/` : les
     * chemins rendus sont donc relatifs a `storage/app/public`.
     */
    private function deposerLesImages(): array
    {
        $sources = [
            'logo' => ['images/logo-ecole.svg', 'school/logo-etablissement.svg'],
            'sceau' => ['images/sceau-221128112237.png', 'school/sceau-republique.png'],
        ];

        $dossier = storage_path('app/public/school');

        if (! is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        $deposees = [];

        foreach ($sources as $role => [$source, $destination]) {
            $chemin = storage_path('app/public/' . $destination);

            if (! file_exists($chemin)) {
                $origine = public_path($source);

                if (! file_exists($origine)) {
                    continue;
                }

                copy($origine, $chemin);
            }

            $deposees[$role] = $destination;
        }

        return $deposees;
    }
}
