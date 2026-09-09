<?php

namespace Database\Seeders;

use App\Models\School;
use App\Support\EcoleCourante;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * Tout ce qui suit appartient au premier etablissement.
         *
         * Le rattachement se fait d'ordinaire tout seul : le trait
         * `AppartientAUnEtablissement` pose `school_id` a la creation, d'apres
         * l'etablissement de l'utilisateur connecte. Un seeder n'a pas
         * d'utilisateur connecte — et sur une base neuve, ou la migration qui a
         * cree ETB001 n'avait aucune donnee a rattacher, tout naissait sans
         * etablissement : 730 eleves, 50 parents, 609 paiements et 167 comptes
         * n'appartenaient a personne. Le portail parent repondait 404, et le
         * cloisonnement multi-etablissements ne cloisonnait rien.
         */
        EcoleCourante::forcer($this->premierEtablissement());

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
             * Le compte parent et le compte enseignant recoivent leur fiche :
             * leur portail part d'elle, et sans elle il n'a rien a afficher.
             * Apres les liens parent-eleve, donc.
             */
            RattacherLesComptesDemoSeeder::class,
        ]);

        /*
         * Le second etablissement se rattache lui-meme : il force le sien au
         * debut de son seeder. On sort donc du premier avant de l'appeler,
         * sans quoi ses donnees naitraient dans l'autre ecole.
         */
        EcoleCourante::oublier();

        $this->call([
            /*
             * Un second etablissement, pour que la vue du super administrateur
             * ait quelque chose a montrer : une plateforme multi-etablissements
             * qui n'en heberge qu'un ne demontre rien.
             */
            EtablissementLeonMbaSeeder::class,
        ]);

        EcoleCourante::oublier();
    }

    /**
     * L'etablissement d'accueil des donnees de demonstration.
     *
     * La migration qui a ouvert le multi-etablissements en cree un depuis les
     * parametres existants. Sur une base neuve il est bien la, mais vide : on
     * le retrouve par son code plutot que d'en creer un second.
     */
    private function premierEtablissement(): int
    {
        $ecole = School::withoutGlobalScopes()->where('code', 'ETB001')->first()
            ?? School::withoutGlobalScopes()->orderBy('id')->first();

        if ($ecole) {
            return $ecole->id;
        }

        return School::create([
            'name' => 'Etablissement scolaire',
            'code' => 'ETB001',
            'has_preprimaire' => true,
            'has_primaire' => true,
            'has_college' => true,
            'has_lycee' => true,
            'is_active' => true,
            'country' => 'Gabon',
        ])->id;
    }
}
