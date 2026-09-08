<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolSettings;
use App\Helpers\SchoolHelper;

class MigrateSchoolLevelsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:migrate-levels {--force : Force la mise à jour même si les champs sont déjà remplis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrer les données des établissements vers le nouveau système de niveaux';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏫 Migration des données des établissements...');
        $this->newLine();

        try {
            $settings = SchoolSettings::first();
            
            if (!$settings) {
                $this->error('❌ Aucun paramètre d\'établissement trouvé.');
                $this->info('💡 Créez d\'abord les paramètres via l\'interface web.');
                return Command::FAILURE;
            }

            $this->info("📋 Paramètres actuels (ID: {$settings->id}):");
            $this->table(
                ['Champ', 'Valeur'],
                [
                    ['school_name', $settings->school_name ?? 'null'],
                    ['primary_school_name', $settings->primary_school_name ?? 'null'],
                    ['secondary_school_name', $settings->secondary_school_name ?? 'null'],
                    ['academic_year', $settings->academic_year ?? 'null'],
                ]
            );
            $this->newLine();

            $updates = [];
            $force = $this->option('force');

            // Migrer primary_school_name
            if (!$settings->primary_school_name || $force) {
                $updates['primary_school_name'] = $settings->school_name ?: 'Complexe Scolaire';
                $this->line("➜ Mise à jour de primary_school_name : {$updates['primary_school_name']}");
            }

            // Migrer secondary_school_name
            if (!$settings->secondary_school_name || $force) {
                $updates['secondary_school_name'] = $settings->school_name ?: 'Lycée';
                $this->line("➜ Mise à jour de secondary_school_name : {$updates['secondary_school_name']}");
            }

            // Mettre à jour l'année scolaire si nécessaire
            $currentYear = SchoolHelper::getCurrentAcademicYearName();
            if (!$settings->academic_year || $settings->academic_year !== $currentYear || $force) {
                $updates['academic_year'] = $currentYear;
                $this->line("➜ Mise à jour de academic_year : {$updates['academic_year']}");
            }

            if (!empty($updates)) {
                $settings->update($updates);
                $this->newLine();
                $this->info('✅ Paramètres mis à jour avec succès !');
                
                $this->newLine();
                $this->info('📋 Nouveaux paramètres :');
                $settings->refresh();
                $this->table(
                    ['Champ', 'Valeur'],
                    [
                        ['primary_school_name', $settings->primary_school_name],
                        ['secondary_school_name', $settings->secondary_school_name],
                        ['academic_year', $settings->academic_year],
                        ['has_primary', $settings->has_primary ? 'Oui' : 'Non'],
                        ['has_secondary', $settings->has_secondary ? 'Oui' : 'Non'],
                    ]
                );
            } else {
                $this->info('✅ Tous les champs sont déjà remplis, aucune mise à jour nécessaire.');
            }

            $this->newLine();
            $this->info('🎉 Migration terminée avec succès !');
            $this->newLine();
            $this->comment('💡 Vous pouvez maintenant accéder aux paramètres pour personnaliser les noms.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la migration : ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
