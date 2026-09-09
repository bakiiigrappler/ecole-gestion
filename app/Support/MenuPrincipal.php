<?php

namespace App\Support;

use App\Models\SchoolSettings;


/**
 * Le menu de l'application, décrit en données.
 *
 * Il vivait dans le gabarit de la barre latérale. Depuis que la disposition se
 * choisit — barre latérale ou barre d'en-tête — les deux rendus doivent lire le
 * même menu : le déclarer deux fois, c'est le voir diverger à la première
 * rubrique ajoutée.
 *
 * Chaque profil ne voit que ses rubriques, et l'état actif se déduit du motif
 * de route. Les rubriques vides ne sont pas rendues.
 */
class MenuPrincipal
{
    /** Mémorisé par requête : le menu se rend deux fois sur certaines pages. */
    private static ?int $versementsAVerifier = null;

    /**
     * Combien de versements déclarés attendent une vérification.
     *
     * Compté ici parce que le menu est le seul endroit vu depuis n'importe
     * quelle page. La requête est bornée à l'établissement courant par le
     * filtre global du modèle.
     */
    private static function versementsAVerifier(): int
    {
        if (self::$versementsAVerifier !== null) {
            return self::$versementsAVerifier;
        }

        return self::$versementsAVerifier = \App\Models\Payment::query()
            ->whereIn('payment_method', array_keys(MobileMoney::OPERATEURS))
            ->whereIn('status', ['pending', 'processing'])
            ->count();
    }

    /**
     * Les rubriques visibles par le compte connecté, liens déjà filtrés.
     *
     * @return array<int, array{titre: string, liens: array<int, array{libelle: string, route: string, motif: string}>}>
     */
    public static function rubriques(): array
    {
        $role = auth()->user()?->role;

        $estEnseignant = $role === 'teacher';
        $estSecretaire = $role === 'secretary';
        $estAdmin = Roles::estDeDirection($role);
        $estSuperAdmin = $role === 'superadmin';
        $estEleve = $role === 'student';
        $estParent = $role === 'parent';

        /*
         * Le superadmin surplombe les établissements : tant qu'il ne s'est
         * placé dans aucun, les modules de scolarité n'ont pas d'objet. Ils
         * réapparaissent dès qu'il entre dans un établissement.
         */
        $dansUnEtablissement = ! $estSuperAdmin || EcoleCourante::id() !== null;

        // Le secondaire et le primaire n'ont pas le même système d'évaluation :
        // les rubriques suivent la configuration de l'établissement.
        $parametres = $dansUnEtablissement ? SchoolSettings::getSettings() : null;
        $aPreprimaire = $parametres?->has_preprimary ?? true;
        $aPrimaire = $parametres?->has_primary ?? true;
        $aSecondaire = $parametres?->has_secondary ?? true;

        $rubriques = [
            [
                'titre' => 'Mon espace',
                'visible' => $estEleve,
                'liens' => [
                    ['libelle' => 'Tableau de bord', 'route' => 'mon-espace', 'motif' => 'mon-espace'],
                    ['libelle' => 'Mes notes', 'route' => 'mon-espace.notes', 'motif' => 'mon-espace.notes'],
                    ['libelle' => 'Mon emploi du temps', 'route' => 'mon-espace.emploi-du-temps', 'motif' => 'mon-espace.emploi-du-temps'],
                    ['libelle' => 'Mes absences', 'route' => 'mon-espace.absences', 'motif' => 'mon-espace.absences'],
                    ['libelle' => 'Ma fiche élève', 'route' => 'mon-espace.fiche', 'motif' => 'mon-espace.fiche'],
                ],
            ],
            [
                'titre' => 'Mon espace parent',
                'visible' => $estParent,
                'liens' => [
                    ['libelle' => 'Tableau de bord', 'route' => 'parent-portal.dashboard', 'motif' => 'parent-portal.dashboard'],
                    ['libelle' => 'Mes enfants', 'route' => 'parent-portal.children', 'motif' => 'parent-portal.children'],
                    ['libelle' => 'Mes paiements', 'route' => 'parent-portal.payment-history', 'motif' => 'parent-portal.payment-history'],
                    ['libelle' => 'Régler la scolarité', 'route' => 'parent-portal.paiement', 'motif' => 'parent-portal.paiement'],
                    ['libelle' => 'Mon profil', 'route' => 'parent-portal.profile', 'motif' => 'parent-portal.profile'],
                ],
            ],
            [
                'titre' => 'Scolarité',
                'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Élèves', 'route' => 'students.index', 'motif' => 'students.*'],
                    ['libelle' => 'Enseignants', 'route' => 'teachers.index', 'motif' => 'teachers.*'],
                    ['libelle' => 'Parents', 'route' => 'parents.index', 'motif' => 'parents.*'],
                    ['libelle' => 'Classes', 'route' => 'classes.index', 'motif' => 'classes.*'],
                    ['libelle' => 'Matières', 'route' => 'subjects.index', 'motif' => 'subjects.*', 'visible' => $estAdmin],
                    ['libelle' => 'Séries (lycée)', 'route' => 'series.index', 'motif' => 'series.*', 'visible' => $estAdmin && $aSecondaire],
                ],
            ],
            [
                'titre' => 'Mes classes',
                'visible' => $estEnseignant && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Classes', 'route' => 'classes.index', 'motif' => 'classes.*'],
                    ['libelle' => 'Matières', 'route' => 'subjects.index', 'motif' => 'subjects.*'],
                    ['libelle' => 'Emplois du temps', 'route' => 'schedules.index', 'motif' => 'schedules.*'],
                    ['libelle' => 'Liste des élèves', 'route' => 'students.index', 'motif' => 'students.*'],
                ],
            ],
            [
                'titre' => 'Inscriptions',
                'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Inscriptions', 'route' => 'enrollments.index', 'motif' => 'enrollments.index'],
                    ['libelle' => 'En attente', 'route' => 'enrollments.pending-students', 'motif' => 'enrollments.pending-students'],
                ],
            ],
            [
                'titre' => 'Évaluations',
                'visible' => ($estAdmin || $estEnseignant) && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Notes (secondaire)', 'route' => 'grades.index', 'motif' => 'grades.*', 'visible' => $aSecondaire],
                    ['libelle' => 'Compétences (primaire)', 'route' => 'competency-evaluations.index', 'motif' => 'competency-evaluations.*', 'visible' => $aPrimaire && $estAdmin],
                    ['libelle' => 'Notes préprimaire', 'route' => 'pre-primary-evaluations.index', 'motif' => 'pre-primary-evaluations.*', 'visible' => $aPreprimaire && $estAdmin],
                    ['libelle' => 'Bulletins', 'route' => 'bulletins.index', 'motif' => 'bulletins*'],
                    ['libelle' => 'Présences', 'route' => 'attendances.index', 'motif' => 'attendances.*'],
                    ['libelle' => 'Emplois du temps', 'route' => 'schedules.index', 'motif' => 'schedules.*', 'visible' => $estAdmin],
                ],
            ],
            [
                'titre' => 'Finances',
                // Le censeur seconde sur la scolarite, pas sur la caisse.
                'visible' => Roles::voitLesFinances($role) && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Frais scolaires', 'route' => 'fees.index', 'motif' => 'fees.*'],
                    ['libelle' => 'Paiements', 'route' => 'payments.index', 'motif' => 'payments.index'],
                    [
                        'libelle' => 'Versements déclarés',
                        'route' => 'payments.declarations',
                        'motif' => 'payments.declarations',
                        // Le nombre qui attend : un versement oublie, c'est un
                        // parent qui a paye et dont le dossier ne le dit pas.
                        'pastille' => self::versementsAVerifier(),
                    ],
                ],
            ],
            [
                'titre' => 'Rapports',
                'visible' => ($estAdmin || $estSecretaire) && $dansUnEtablissement,
                'liens' => [
                    ['libelle' => 'Statistiques', 'route' => 'statistics', 'motif' => 'statistics*'],
                ],
            ],
            [
                'titre' => 'Plateforme',
                'visible' => $estSuperAdmin,
                'liens' => [
                    ['libelle' => 'Établissements', 'route' => 'admin.schools.index', 'motif' => 'admin.schools.*'],
                    ['libelle' => 'Personnalisation', 'route' => 'admin.plateforme.personnalisation', 'motif' => 'admin.plateforme.personnalisation*'],
                    ['libelle' => 'Paramètres', 'route' => 'admin.plateforme.parametres', 'motif' => 'admin.plateforme.parametres*'],
                    ['libelle' => 'Corbeille', 'route' => 'admin.plateforme.corbeille', 'motif' => 'admin.plateforme.corbeille*'],
                ],
            ],
            [
                'titre' => 'Administration',
                'visible' => $estAdmin,
                'liens' => [
                    /*
                     * Administration d'une école : elle appartient à son propre
                     * administrateur. Le superadmin n'y accède qu'après s'être
                     * placé dans un établissement — hors de là, ces pages ne
                     * désignent aucune école en particulier.
                     */
                    ['libelle' => 'Paramètres généraux', 'route' => 'admin.settings.index', 'motif' => 'admin.settings.*', 'visible' => $dansUnEtablissement],
                    ['libelle' => 'Paramètres établissement', 'route' => 'admin.school-settings.index', 'motif' => 'admin.school-settings.*', 'visible' => $dansUnEtablissement],
                    ['libelle' => 'Comptes utilisateurs', 'route' => 'admin.users.index', 'motif' => 'admin.users.*', 'visible' => $dansUnEtablissement],

                    // Serveur : commun à tous les établissements.
                    ['libelle' => 'Informations système', 'route' => 'admin.system-info', 'motif' => 'admin.system-info'],
                    ['libelle' => 'Sécurité', 'route' => 'admin.security', 'motif' => 'admin.security'],
                    ['libelle' => 'Maintenance', 'route' => 'admin.maintenance', 'motif' => 'admin.maintenance'],
                ],
            ],
        ];

        // Ne remonte que ce qui se rend : rubriques visibles, liens visibles,
        // et jamais une rubrique dont tous les liens sont tombés.
        $retenues = [];

        foreach ($rubriques as $rubrique) {
            if (! ($rubrique['visible'] ?? true)) {
                continue;
            }

            $liens = array_values(array_filter(
                $rubrique['liens'],
                fn ($lien) => $lien['visible'] ?? true
            ));

            if ($liens === []) {
                continue;
            }

            $retenues[] = ['titre' => $rubrique['titre'], 'liens' => $liens];
        }

        return $retenues;
    }

    /**
     * Le lien générique « Tableau de bord » a-t-il lieu d'être ?
     *
     * L'élève et le parent ont le leur, dans leur rubrique : ce lien ne ferait
     * que les y renvoyer par une redirection, en doublonnant leur entrée.
     */
    public static function afficherLeTableauDeBord(): bool
    {
        return ! in_array(auth()->user()?->role, ['student', 'parent'], true);
    }

    /**
     * La disposition en cours : barre latérale ou barre d'en-tête.
     *
     * Un super administrateur peut la prévisualiser sans l'enregistrer, en
     * posant `?apercu_nav=header` sur n'importe quelle page.
     */
    public static function disposition(): string
    {
        $choisie = Marque::get('nav_layout') === 'header' ? 'header' : 'sidebar';

        $apercu = request()->query('apercu_nav');

        if (in_array($apercu, ['sidebar', 'header'], true)
            && auth()->user()?->role === 'superadmin') {
            return $apercu;
        }

        return $choisie;
    }

    /** L'affichage courant vient-il d'un aperçu, et non du réglage ? */
    public static function estUnApercu(): bool
    {
        return in_array(request()->query('apercu_nav'), ['sidebar', 'header'], true)
            && auth()->user()?->role === 'superadmin';
    }
}
