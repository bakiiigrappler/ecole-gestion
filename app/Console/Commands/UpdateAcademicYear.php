<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Helpers\SchoolHelper;

class UpdateAcademicYear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'academic-year:update {--generate : Générer aussi les années passées et futures}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour l\'année scolaire actuelle selon le calendrier gabonais (septembre à juin)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎓 Mise à jour de l\'année scolaire...');
        $this->newLine();

        try {
            // Mettre à jour l'année scolaire actuelle
            $academicYear = AcademicYear::updateCurrentAcademicYear();
            
            $this->info('✅ Année scolaire mise à jour avec succès !');
            $this->table(
                ['Champ', 'Valeur'],
                [
                    ['Nom', $academicYear->name],
                    ['Date de début', $academicYear->start_date->format('d/m/Y')],
                    ['Date de fin', $academicYear->end_date->format('d/m/Y')],
                    ['Statut', $academicYear->status],
                    ['Est actuelle', $academicYear->is_current ? 'Oui' : 'Non'],
                ]
            );

            // Générer les années si l'option est activée
            if ($this->option('generate')) {
                $this->newLine();
                $this->info('📅 Génération des années scolaires passées et futures...');
                
                AcademicYear::generateYears(3, 3);
                
                $totalYears = AcademicYear::count();
                $this->info("✅ {$totalYears} années scolaires disponibles dans la base de données");
                
                $this->newLine();
                $this->info('Liste des années :');
                $years = AcademicYear::orderBy('start_date', 'desc')->get();
                
                $yearsList = $years->map(function ($year) {
                    return [
                        $year->name,
                        $year->start_date->format('d/m/Y'),
                        $year->end_date->format('d/m/Y'),
                        $year->is_current ? '✓ Actuelle' : ($year->status === 'active' ? 'Active' : 'Inactive'),
                    ];
                });
                
                $this->table(
                    ['Année', 'Début', 'Fin', 'Statut'],
                    $yearsList
                );
            }

            $this->newLine();
            $this->info('🎉 Opération terminée avec succès !');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la mise à jour : ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
