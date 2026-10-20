<?php

namespace App\Support;

use App\Models\Examen;
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
     * L'établissement de démonstration, et ceux qui y vivent.
     */
    private static function lePremierEtablissement(): array
    {
        $ecole = School::withoutGlobalScopes()->orderBy('id')->first();

        $comptes = self::parRoles(['admin', 'secretary', 'teacher', 'parent'], [
            'admin' => 'Élèves, classes, finances, paramètres de l’établissement.',
            'secretary' => 'La caisse : encaisser, et valider ou refuser une déclaration de paiement mobile.',
            'teacher' => 'Ses classes, ses notes, ses bulletins.',
            'parent' => 'Le portail des parents : bulletins, paiement mobile, décision d’orientation.',
        ]);

        /*
         * Un élève, toujours.
         *
         * Les cartes d'examen et d'orientation désignent des élèves précis, et
         * elles disparaissent quand aucun des élèves concernés n'a de compte —
         * ce qui arrive, puisque seuls les lycéens en ont. L'espace de l'élève
         * resterait alors introuvable depuis cette page, alors que c'est l'un
         * des cinq profils de l'application.
         */
        $eleve = self::unEleveDe($ecole?->id);

        if ($eleve) {
            $comptes[] = $eleve;
        }

        return [
            'titre' => $ecole->name ?? 'L’établissement',
            'sous_titre' => 'Ceux qui y travaillent et ceux qui y étudient',
            'comptes' => $comptes,
        ];
    }

    /**
     * Un lycéen de cet établissement, de préférence un dont les écrans sont pleins.
     *
     * Trois préférences, dans cet ordre, et chacune tient à ce qu'il y aura à
     * voir en se connectant :
     *
     * 1. un candidat **admis** à une session proclamée. C'est lui qui a tout :
     *    son relevé de notes d'examen, son attestation de réussite, et le
     *    reste de son dossier scolaire ;
     * 2. à défaut, n'importe quel candidat d'une session proclamée ;
     * 3. à défaut, un élève qui a des notes — sans elles, son espace, ses
     *    bulletins et ses moyennes n'ont rien à montrer.
     *
     * Et n'importe quel élève plutôt que pas de carte du tout : son espace
     * reste l'un des cinq profils de l'application.
     */
    private static function unEleveDe(?int $ecoleId): ?array
    {
        $requete = fn () => Student::withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->when($ecoleId, fn ($q) => $q->where('school_id', $ecoleId));

        $candidat = fn (?string $statut) => ExamenCandidat::withoutGlobalScopes()
            ->whereNotNull('student_id')
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->whereHas('examen', fn ($q) => $q->where('statut', 'proclame'))
            ->pluck('student_id');

        $eleve = (clone $requete())->whereIn('id', $candidat('admis'))->first()
            ?? (clone $requete())->whereIn('id', $candidat(null))->first()
            ?? (clone $requete())->whereHas('grades')->first()
            ?? $requete()->first();

        if (! $eleve) {
            return null;
        }

        $compte = self::compteDeLEleve($eleve->id, 'Élève');

        if (! $compte) {
            return null;
        }

        $classe = $eleve->enrollments()
            ->where('status', 'active')
            ->with('schoolClass:id,name')
            ->first()?->schoolClass?->name;

        // Ce qu'il y a de notable dans SON dossier : la candidature, quand il y
        // en a une, vaut mieux que la description générique d'un espace élève.
        $candidature = ExamenCandidat::withoutGlobalScopes()
            ->where('student_id', $eleve->id)
            ->whereHas('examen', fn ($q) => $q->where('statut', 'proclame'))
            ->with('examen:id,nom,type,annee,serie')
            ->first();

        $compte['cas'] = $candidature
            ? 'Son '.($candidature->examen?->intitule_complet ?? 'examen')
                .' : '.mb_strtolower($candidature->libelle_statut)
                .($candidature->mention ? ', mention '.$candidature->mention : '')
                .' — relevé de notes et attestation de réussite'
                .($classe ? '. Classe de '.$classe : '').'.'
            : 'Son espace : ses notes, son emploi du temps, ses absences, son orientation'
                .($classe ? ' — '.$classe : '')
                .'. Identifiant et mot de passe initial : son matricule.';

        return $compte;
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

        // Un élève de ce second établissement : c'est de son côté que le
        // cloisonnement se vérifie le mieux, puisqu'il ne doit rien voir de
        // l'autre école.
        foreach ($comptes as $compte) {
            $utilisateur = User::where('email', $compte['email'])->first();
            $eleve = $utilisateur ? self::unEleveDe($utilisateur->school_id) : null;

            if ($eleve) {
                $comptes[] = $eleve;
            }

            break;
        }

        return [
            'titre' => 'Un second établissement',
            'sous_titre' => 'La même application, une autre école',
            'comptes' => $comptes,
        ];
    }

    /**
     * Les examens nationaux, du côté de ceux qui les font.
     *
     * Le candidat et sa famille lisent déjà leur résultat depuis leur propre
     * espace : ces deux vues sont couvertes ailleurs. Ce qui manquait ici, ce
     * sont les trois rôles qui font tourner une session et qu'on ne trouve
     * nulle part ailleurs — celui qui la conduit et la signe, celui qui corrige
     * sous anonymat, et l'établissement qui suit ses élèves.
     */
    private static function lesExamens(): array
    {
        $comptes = array_filter([
            self::lOrganisateur(),
            self::leCorrecteur(),
            self::leProviseurDUnCentre(),
            self::lienDuResultatPublic(),
        ]);

        return [
            'titre' => 'Les examens nationaux',
            'sous_titre' => 'Ceux qui font la session',
            'comptes' => array_values($comptes),
        ];
    }

    /**
     * Celui qui conduit la session : convocation, délibération, signature.
     */
    private static function lOrganisateur(): ?array
    {
        $compte = collect(config('demo.comptes', []))->firstWhere('role', 'superadmin');

        if (! $compte || ! User::where('email', $compte['email'])->exists()) {
            return null;
        }

        $sessions = Examen::withoutGlobalScopes()->count();

        if ($sessions === 0) {
            return null;
        }

        $enCours = Examen::withoutGlobalScopes()
            ->whereIn('statut', ['epreuves', 'correction', 'deliberation'])
            ->count();

        return [
            'libelle' => 'Conduire la session',
            'email' => $compte['email'],
            'mot_de_passe' => $compte['mot_de_passe'],
            'cas' => $sessions.' session(s)'
                .($enCours > 0 ? ', dont '.$enCours.' en cours' : '')
                .' : candidats, centres, convocations, délibération, procès-verbal et signature.',
        ];
    }

    /**
     * L'enseignant convoqué : son paquet de copies, sous anonymat.
     *
     * Le libellé nomme l'épreuve et dit ce qui reste, parce que c'est ce qui
     * décide si l'écran a quelque chose à montrer : une session proclamée ferme
     * la saisie, et le panel n'y est plus qu'une archive.
     */
    private static function leCorrecteur(): ?array
    {
        $compte = collect(config('demo.comptes', []))->firstWhere('role', 'teacher');

        if (! $compte) {
            return null;
        }

        $utilisateur = User::where('email', $compte['email'])->first();

        if (! $utilisateur) {
            return null;
        }

        $convocations = ExamenCorrecteur::withoutGlobalScopes()
            ->where('user_id', $utilisateur->id)
            ->whereNotNull('epreuve_id')
            ->with(['epreuve:id,intitule', 'examen:id,statut'])
            ->get();

        if ($convocations->isEmpty()) {
            return null;
        }

        // Celle qui reste à corriger d'abord : c'est l'écran utile.
        $ouverte = $convocations->first(fn ($c) => in_array($c->examen?->statut, ['epreuves', 'correction'], true));
        $convocation = $ouverte ?? $convocations->first();

        $restantes = $ouverte
            ? ExamenCandidat::withoutGlobalScopes()
                ->where('examen_id', $convocation->examen_id)
                ->whereNotNull('numero_anonymat')
                ->whereDoesntHave('notes', fn ($q) => $q->where('epreuve_id', $convocation->epreuve_id))
                ->count()
            : 0;

        return [
            'libelle' => 'Corriger les copies',
            'email' => $compte['email'],
            'mot_de_passe' => $compte['mot_de_passe'],
            'cas' => $convocations->count().' convocation(s). '
                .($restantes > 0
                    ? $restantes.' copie(s) de '.($convocation->epreuve?->intitule ?? 'son épreuve').' à noter, sous numéro d’anonymat.'
                    : 'Paquets terminés : les notes se relisent, la saisie est fermée.'),
        ];
    }

    /**
     * Un lien direct vers la fiche publique d'un candidat.
     *
     * La page accepte le numéro et la date de naissance en paramètres : le
     * lien est donc un essai complet, et il ne touche pas la base — il lit la
     * fiche écrite à la proclamation.
     */
    private static function lienDuResultatPublic(): ?array
    {
        $candidat = ExamenCandidat::withoutGlobalScopes()
            ->where('statut', 'admis')
            ->whereNotNull('date_naissance')
            ->whereHas('examen', fn ($q) => $q->where('statut', 'proclame'))
            ->with('examen:id')
            ->first();

        if (! $candidat) {
            return null;
        }

        return [
            'libelle' => 'Résultat public, sans compte',
            'email' => $candidat->numero_candidat,
            'mot_de_passe' => '',
            'cas' => 'La page où arrive tout le pays le jour de la proclamation. Numéro et date de naissance déjà posés.',
            'lien' => route('resultats.chercher', [
                'examen' => $candidat->examen_id,
                'numero' => $candidat->numero_candidat,
                'naissance' => $candidat->date_naissance instanceof \DateTimeInterface
                    ? $candidat->date_naissance->format('Y-m-d')
                    : $candidat->date_naissance,
            ]),
        ];
    }

    /**
     * Le chef d'un établissement qui a des candidats dans la session.
     */
    private static function leProviseurDUnCentre(): ?array
    {
        foreach (config('demo.comptes_etablissements', []) as $compte) {
            $utilisateur = User::where('email', $compte['email'])->first();

            if (! $utilisateur || ! $utilisateur->school_id) {
                continue;
            }

            $candidats = ExamenCandidat::withoutGlobalScopes()
                ->where('school_id', $utilisateur->school_id)
                ->whereHas('examen', fn ($q) => $q->where('statut', 'proclame'))
                ->count();

            if ($candidats === 0) {
                continue;
            }

            return [
                'libelle' => 'L’établissement, côté examen',
                'email' => $compte['email'],
                'mot_de_passe' => $compte['mot_de_passe'],
                'cas' => 'Ses '.$candidats.' candidat(s) : leurs résultats, leur centre d’examen, et si ses enseignants ont corrigé.',
            ];
        }

        return null;
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

}
