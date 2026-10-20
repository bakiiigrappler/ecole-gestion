<?php

namespace App\Support;

use App\Models\ExamenCandidat;
use App\Models\ExamenCorrecteur;
use App\Models\OrientationDossier;
use App\Models\ParentModel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;

/**
 * Les accès rapides de la page de connexion, groupés par cas.
 *
 * Un bouton par rôle ne suffit pas à faire le tour de l'application : le même
 * compte enseignant montre ses classes **et** son paquet de copies, le même
 * compte parent montre une facture **et** un désaccord d'orientation. Ce qu'on
 * veut atteindre en un clic, ce n'est donc pas un rôle, c'est un **cas** — et
 * certains cas ne tiennent qu'à des données : l'élève admis au baccalauréat,
 * celui dont le vœu a reçu un désaccord, le candidat d'un centre donné.
 *
 * Cette classe les cherche donc en base plutôt que de les figer en
 * configuration. Deux conséquences qui sont le but :
 *
 * - un cas qui n'existe pas dans les données n'apparaît pas. Un bouton qui
 *   mène à un écran vide ment sur l'état de l'application ;
 * - les mots de passe proposés sont ceux qu'on connaît vraiment. Pour les
 *   élèves, le matricule sert d'identifiant et de mot de passe initial ; un
 *   élève qui a changé le sien est écarté, faute de quoi le bouton échouerait.
 *
 * Confort de démonstration : `config('demo.acces_rapide')` coupe tout.
 */
class ComptesDeDemonstration
{
    /**
     * Combien de lignes parcourir avant d'abandonner un cas.
     *
     * Le premier dossier en désaccord n'est pas forcément celui d'un élève qui
     * a un compte : seuls les lycéens en ont, et l'orientation concerne aussi
     * les troisièmes. Prendre le premier et s'arrêter là faisait donc
     * disparaître le cas alors qu'il existait deux lignes plus bas.
     */
    private const PARCOURUS = 40;

    /**
     * Les groupes de cartes, prêts pour la vue.
     *
     * @return array<int, array{titre: string, sous_titre: string, comptes: array<int, array{libelle: string, email: string, mot_de_passe: string, cas: string}>}>
     */
    public static function groupes(): array
    {
        $groupes = [
            self::laPlateforme(),
            self::lePremierEtablissement(),
            self::leSecondEtablissement(),
            self::lesExamens(),
            self::lOrientation(),
        ];

        // Un groupe sans carte ne se dessine pas : il laisserait un titre seul.
        return array_values(array_filter($groupes, fn ($groupe) => ! empty($groupe['comptes'])));
    }

    /**
     * La vue d'ensemble : tous les établissements, et les examens nationaux.
     */
    private static function laPlateforme(): array
    {
        return [
            'titre' => 'La plateforme',
            'sous_titre' => 'Au-dessus des établissements',
            'comptes' => self::parRoles(['superadmin'], [
                'superadmin' => 'Les deux établissements, les sessions d’examen national, la signature du procès-verbal.',
            ]),
        ];
    }

    /**
     * L'établissement de démonstration, et ses quatre métiers.
     */
    private static function lePremierEtablissement(): array
    {
        $ecole = School::withoutGlobalScopes()->orderBy('id')->first();

        $correcteur = self::convocationDuCompte('enseignant@ecole.com');

        return [
            'titre' => $ecole->name ?? 'L’établissement',
            'sous_titre' => 'Les métiers d’une école',
            'comptes' => self::parRoles(['admin', 'secretary', 'teacher', 'parent'], [
                'admin' => 'Élèves, classes, finances, paramètres de l’établissement.',
                'secretary' => 'La caisse : encaisser, et valider ou refuser une déclaration de paiement mobile.',
                'teacher' => $correcteur
                    ? 'Ses classes et ses notes — et sa convocation à la correction : '.$correcteur
                    : 'Ses classes, ses notes, ses bulletins.',
                'parent' => 'Le portail des parents : bulletins, paiement mobile, décision d’orientation.',
            ]),
        ];
    }

    /**
     * Le second établissement : c'est lui qui prouve le cloisonnement.
     */
    private static function leSecondEtablissement(): array
    {
        $comptes = [];

        foreach (config('demo.comptes_etablissements', []) as $compte) {
            $utilisateur = User::where('email', $compte['email'])->first();

            if (! $utilisateur) {
                continue;
            }

            $ecole = $utilisateur->school_id
                ? School::withoutGlobalScopes()->find($utilisateur->school_id)
                : null;

            $comptes[] = [
                'libelle' => $compte['libelle'],
                'email' => $compte['email'],
                'mot_de_passe' => $compte['mot_de_passe'],
                'cas' => 'Ne voit que son établissement'
                    .($ecole ? ' — '.$ecole->name : '')
                    .' : c’est le cloisonnement qu’on vérifie ici.',
            ];
        }

        return [
            'titre' => 'Un second établissement',
            'sous_titre' => 'La même application, une autre école',
            'comptes' => $comptes,
        ];
    }

    /**
     * Les cas d'examen : une session proclamée se lit depuis plusieurs places.
     */
    private static function lesExamens(): array
    {
        $comptes = [];

        // Les trois décisions : la décision change tout l'écran, et c'est
        // précisément ce qu'il faut pouvoir comparer en deux clics.
        foreach ([
            'admis' => 'Admis au baccalauréat',
            'second_tour' => 'Au second tour',
            'ajourne' => 'Ajourné au baccalauréat',
        ] as $statut => $libelle) {
            $candidats = ExamenCandidat::withoutGlobalScopes()
                ->whereNotNull('student_id')
                ->where('statut', $statut)
                ->whereHas('examen', fn ($q) => $q->where('statut', 'proclame'))
                ->with('centre:id,nom')
                ->limit(self::PARCOURUS)
                ->get();

            foreach ($candidats as $candidat) {
                $compte = self::compteDeLEleve($candidat->student_id, $libelle);

                if (! $compte) {
                    continue;
                }

                $compte['cas'] = 'Son relevé d’examen'
                    .($candidat->numero_candidat ? ', n° '.$candidat->numero_candidat : '')
                    .($candidat->centre ? ' · '.$candidat->centre->nom : '')
                    .'.';

                $comptes[] = $compte;
                break;
            }
        }

        return [
            'titre' => 'Les examens nationaux',
            'sous_titre' => 'Du côté des candidats',
            'comptes' => $comptes,
        ];
    }

    /**
     * Les quatre états d'un dossier d'orientation.
     *
     * Les quatre, parce qu'ils ne se lisent pas pareil : un dossier en cours de
     * saisie, un dossier transmis qui attend, un vœu suivi, un vœu non suivi
     * avec son motif. C'est là tout le service, et un seul bouton n'en
     * montrerait qu'un quart.
     */
    private static function lOrientation(): array
    {
        $comptes = [];

        foreach ([
            'brouillon' => ['En cours de saisie', 'Un dossier commencé, pas encore transmis : l’élève compose ses vœux.'],
            'soumis' => ['En attente de décision', 'Transmis au service d’orientation : l’élève attend l’avis.'],
            'accorde' => ['Accord d’orientation', 'Un vœu suivi : la décision, et la suite du parcours.'],
            'refuse' => ['Désaccord d’orientation', 'Un vœu non suivi, motif à l’appui : c’est l’écran qui porte l’alerte.'],
        ] as $statut => [$libelle, $cas]) {
            $dossiers = OrientationDossier::withoutGlobalScopes()
                ->where('statut', $statut)
                ->whereNotNull('student_id')
                ->limit(self::PARCOURUS)
                ->get();

            $pose = false;

            foreach ($dossiers as $dossier) {
                $compte = self::compteDeLEleve($dossier->student_id, $libelle);

                if (! $compte) {
                    continue;
                }

                $compte['cas'] = $cas;
                $comptes[] = $compte;
                $pose = true;
                break;
            }

            if ($pose || $dossiers->isEmpty()) {
                continue;
            }

            /*
             * Le cas existe, mais aucun de ces élèves n'a de compte : seuls les
             * lycéens en ont, et l'orientation concerne aussi les troisièmes.
             * Il reste alors le portail des parents, qui porte la même
             * décision — et c'est de toute façon là qu'une famille la lit.
             */
            $parent = self::leParentDUnDossier($dossiers->pluck('student_id')->all());

            if ($parent) {
                $parent['libelle'] = $libelle.' — vu par le parent';
                $parent['cas'] = $cas.' L’élève concerné est en troisième : il n’a pas de compte, la famille lit la décision depuis son portail.';
                $comptes[] = $parent;
            }
        }

        return [
            'titre' => 'L’orientation',
            'sous_titre' => 'De la troisième au lycée, de la terminale au supérieur',
            'comptes' => $comptes,
        ];
    }

    /**
     * Le compte parent de démonstration, s'il a l'un de ces enfants à charge.
     *
     * Son mot de passe est le seul que nous connaissions parmi les parents :
     * proposer un autre parent donnerait un bouton qui échoue.
     *
     * @param  array<int, int>  $eleves
     */
    private static function leParentDUnDossier(array $eleves): ?array
    {
        $compte = collect(config('demo.comptes', []))->firstWhere('role', 'parent');

        if (! $compte) {
            return null;
        }

        $utilisateur = User::where('email', $compte['email'])->first();

        if (! $utilisateur) {
            return null;
        }

        $fiche = ParentModel::withoutGlobalScopes()->where('user_id', $utilisateur->id)->first();

        if (! $fiche || ! $fiche->students()->whereIn('students.id', $eleves)->exists()) {
            return null;
        }

        return [
            'libelle' => $compte['libelle'],
            'email' => $compte['email'],
            'mot_de_passe' => $compte['mot_de_passe'],
            'cas' => '',
        ];
    }

    /**
     * Les comptes fixés en configuration, dans l'ordre demandé.
     *
     * @param  array<int, string>  $roles
     * @param  array<string, string>  $cas
     */
    private static function parRoles(array $roles, array $cas): array
    {
        $catalogue = collect(config('demo.comptes', []))->keyBy('role');
        $comptes = [];

        foreach ($roles as $role) {
            $compte = $catalogue->get($role);

            // Un bouton qui ne connecte à rien vaut moins que pas de bouton.
            if (! $compte || ! User::where('email', $compte['email'])->exists()) {
                continue;
            }

            $comptes[] = [
                'libelle' => $compte['libelle'],
                'email' => $compte['email'],
                'mot_de_passe' => $compte['mot_de_passe'],
                'cas' => $cas[$role] ?? '',
            ];
        }

        return $comptes;
    }

    /**
     * Le compte d'un élève, si son mot de passe est encore son matricule.
     *
     * Le lycéen se connecte avec son matricule, mot de passe initial
     * identique. S'il l'a changé, nous ne le connaissons plus : proposer le
     * bouton quand même donnerait un échec de connexion sans explication.
     */
    private static function compteDeLEleve(int $studentId, string $libelle): ?array
    {
        $eleve = Student::withoutGlobalScopes()->find($studentId);

        if (! $eleve || ! $eleve->user_id) {
            return null;
        }

        $utilisateur = User::find($eleve->user_id);

        if (! $utilisateur || ! $utilisateur->matricule) {
            return null;
        }

        return [
            'libelle' => $libelle.' — '.trim($eleve->first_name.' '.$eleve->last_name),
            'email' => $utilisateur->matricule,
            'mot_de_passe' => $utilisateur->matricule,
            'cas' => '',
        ];
    }

    /**
     * Ce à quoi ce compte est convoqué, dit en une ligne.
     */
    private static function convocationDuCompte(string $courriel): ?string
    {
        $utilisateur = User::where('email', $courriel)->first();

        if (! $utilisateur) {
            return null;
        }

        $convocation = ExamenCorrecteur::withoutGlobalScopes()
            ->where('user_id', $utilisateur->id)
            ->whereNotNull('epreuve_id')
            ->whereHas('examen', fn ($q) => $q->whereIn('statut', ['epreuves', 'correction']))
            ->with('epreuve:id,intitule')
            ->first();

        return $convocation?->epreuve?->intitule
            ? 'un paquet de '.$convocation->epreuve->intitule.' à corriger.'
            : null;
    }
}
