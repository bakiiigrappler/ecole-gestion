<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Referentiel de l'etablissement, requis par le reste de la chaine
            SchoolSettingsSeeder::class,

            // Comptes : un par role, pour parcourir chaque profil
            ComptesDemoSeeder::class,

            LevelSeeder::class,
            SeriesSeeder::class,
            // SubjectSeeder est obsolete : il insere subjects.level_id, colonne supprimee
            // par la migration 2025_08_27_122409 au profit de cycle/series.
            GabonSubjectsSeeder::class,
            AcademicYearSeeder::class,
            ClassSeeder::class,
            TeacherSeeder::class,
            ParentSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
            FeeSeeder::class,
            PaymentGatewaySeeder::class,

            // Rattachement enseignant/matiere, emplois du temps puis appel
            // quotidien : dans cet ordre, les absences du bulletin peuvent
            // etre rattachees a une discipline via l'emploi du temps.
            EnseignantMatiereSeeder::class,
            EmploiDuTempsSeeder::class,
            PresenceSeeder::class,

            // Referentiels de competences puis evaluations : le primaire et le
            // preprimaire s'evaluent ainsi, pas par notes chiffrees.
            CompetencySeeder::class,
            PrePrimaryCompetencySeeder::class,
            EvaluationsCompetencesSeeder::class,
            EvaluationsPreprimaireSeeder::class,

            // Liens parent-eleve, frais chiffres, paiements et notes : sans eux
            // le tableau de bord et les statistiques restent a zero.
            DonneesDemoSeeder::class,

            /*
             * Comptes des lyceens : leur matricule leur sert d'identifiant.
             * Apres les inscriptions, forcement — il n'y a de compte que pour
             * un eleve inscrit au lycee.
             */
            ComptesElevesSeeder::class,

            /*
             * Un second etablissement, pour que la vue du super administrateur
             * ait quelque chose a montrer : une plateforme multi-etablissements
             * qui n'en heberge qu'un ne demontre rien.
             */
            EtablissementLeonMbaSeeder::class,
        ]);
    }
}
