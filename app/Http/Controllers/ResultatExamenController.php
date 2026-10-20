<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use App\Models\ExamenCorrecteur;
use App\Support\AccesEleve;
use App\Support\EcoleCourante;
use App\Support\Examens\ResultatsPublies;
use App\Support\Roles;
use Illuminate\Http\Request;

/**
 * La consultation des résultats : le public, l'établissement, la famille.
 *
 * Trois lectures d'une même proclamation, et trois exigences différentes.
 *
 * **Le public** arrive en masse, à la minute de la proclamation, et pose
 * toujours la même question. Sa page ne touche pas la base : elle lit la fiche
 * écrite une fois pour toutes à la proclamation. C'est ce qui fait tenir
 * vingt mille visiteurs là où une requête par visite en fait tomber trois
 * mille.
 *
 * **L'établissement** veut ses propres élèves : combien d'admis, dans quels
 * centres ils ont composé, et si l'un de ses professeurs a été convoqué pour
 * corriger. Ces trois questions reviennent chaque année et n'avaient pas de
 * réponse sans téléphoner.
 *
 * **La famille** veut le relevé détaillé, note par épreuve. Il n'est pas
 * public : il se lit depuis le compte de l'élève ou de son parent.
 */
class ResultatExamenController extends Controller
{
    /* ==================================================================
       Le public
       ================================================================== */

    /**
     * La page d'accueil des résultats : les sessions proclamées.
     */
    public function accueil()
    {
        $examens = Examen::where('statut', 'proclame')
            ->orderByDesc('annee')
            ->orderBy('type')
            ->get();

        return view('examens.resultats.accueil', [
            'examens' => $examens->map(fn ($e) => [
                'examen' => $e,
                'resume' => ResultatsPublies::resume($e),
            ]),
        ]);
    }

    /**
     * Chercher un résultat : numéro de candidat et date de naissance.
     *
     * La date n'est pas une formalité : sans elle, n'importe qui lirait le
     * résultat de n'importe qui en incrémentant un numéro.
     */
    public function chercher(Request $request, Examen $examen)
    {
        abort_unless($examen->estProclame(), 404, 'Les résultats de cette session ne sont pas proclamés.');

        $donnees = $request->validate([
            'numero' => 'required|string|max:30',
            'naissance' => 'nullable|date',
        ], [
            'numero.required' => 'Entrez votre numéro de candidat.',
        ]);

        $fiche = ResultatsPublies::lire($examen, $donnees['numero']);

        if (! $fiche) {
            return back()->withInput()->withErrors([
                'numero' => 'Aucun candidat ne porte ce numéro dans cette session.',
            ]);
        }

        if (! ResultatsPublies::naissanceConcorde($fiche, $donnees['naissance'] ?? null)) {
            return back()->withInput()->withErrors([
                'naissance' => 'La date de naissance ne correspond pas à ce numéro.',
            ]);
        }

        return view('examens.resultats.fiche', [
            'examen' => $examen,
            'fiche' => $fiche,
        ]);
    }

    /**
     * Retrouver son numéro à partir de son nom.
     *
     * C'est la requête la plus coûteuse d'un portail de résultats, et celle que
     * tout le monde fait le jour J parce que la convocation est restée à la
     * maison. Elle lit l'annuaire préparé à la proclamation, jamais la base.
     */
    public function retrouverLeNumero(Request $request, Examen $examen)
    {
        abort_unless($examen->estProclame(), 404);

        $terme = trim((string) $request->input('nom'));

        return view('examens.resultats.numero', [
            'examen' => $examen,
            'terme' => $terme,
            'trouves' => mb_strlen($terme) >= 3
                ? ResultatsPublies::chercherParNom($examen, $terme)
                : [],
        ]);
    }

    /* ==================================================================
       L'établissement
       ================================================================== */

    /**
     * Les résultats des élèves de l'établissement courant.
     */
    public function parEtablissement(Request $request, Examen $examen)
    {
        $this->seulementLEtablissement();

        $ecole = EcoleCourante::id();

        $candidats = $examen->candidats()
            ->deLEtablissement($ecole)
            ->with('centre:id,nom,ville')
            ->orderBy('statut')
            ->orderBy('nom')
            ->get();

        // Les professeurs de la maison appelés à corriger : la question se pose
        // chaque année, et personne ne savait y répondre.
        $correcteurs = ExamenCorrecteur::where('examen_id', $examen->id)
            ->when($ecole, fn ($q) => $q->where('school_id', $ecole))
            ->with(['epreuve:id,intitule', 'centre:id,nom', 'teacher:id,first_name,last_name'])
            ->get();

        $parCentre = $candidats->groupBy(fn ($c) => $c->centre?->nom ?? 'Centre non affecté');

        return view('examens.resultats.etablissement', [
            'examen' => $examen,
            'candidats' => $candidats,
            'parCentre' => $parCentre,
            'correcteurs' => $correcteurs,
            'chiffres' => [
                'candidats' => $candidats->count(),
                'admis' => $candidats->where('statut', 'admis')->count(),
                'second_tour' => $candidats->where('statut', 'second_tour')->count(),
                'ajournes' => $candidats->where('statut', 'ajourne')->count(),
                'absents' => $candidats->where('statut', 'absent')->count(),
            ],
        ]);
    }

    /**
     * La liste des sessions où l'établissement a des candidats.
     */
    public function sessionsDeLEtablissement()
    {
        $this->seulementLEtablissement();

        $ecole = EcoleCourante::id();

        $examens = Examen::whereHas('candidats', fn ($q) => $q->when($ecole, fn ($r) => $r->where('school_id', $ecole)))
            ->withCount(['candidats as mes_candidats' => fn ($q) => $q->when($ecole, fn ($r) => $r->where('school_id', $ecole))])
            ->orderByDesc('annee')
            ->get();

        return view('examens.resultats.sessions', ['examens' => $examens]);
    }

    /* ==================================================================
       La famille
       ================================================================== */

    /**
     * Le relevé détaillé d'un candidat : note par épreuve.
     *
     * Il n'est pas public — le résultat l'est, le détail ne l'est pas — et ne
     * s'ouvre qu'une fois la session proclamée : un relevé lu avant la
     * proclamation serait une fuite.
     */
    public function releve(ExamenCandidat $candidat)
    {
        $examen = $candidat->examen;

        abort_unless($examen->estProclame(), 404, 'Les résultats ne sont pas encore proclamés.');

        $this->seulementLesSiens($candidat);

        $candidat->load(['centre', 'notes.epreuve', 'school:id,name']);

        $epreuves = $examen->epreuves;
        $notes = $candidat->notes->keyBy('epreuve_id');

        return view('examens.resultats.releve', [
            'examen' => $examen,
            'candidat' => $candidat,
            'epreuves' => $epreuves,
            'notes' => $notes,
            'diviseur' => $examen->totalDesCoefficients(),
            'document' => $examen->documents()->whereNotNull('signe_le')->latest('signe_le')->first(),
            /*
             * L'attestation s'offre-t-elle ? La vue s'en sert dans son en-tete,
             * qui est rendu avant le corps : la calculer la-bas laissait la
             * variable indefinie. Et c'est de toute facon au controleur de dire
             * ce qui est permis.
             */
            'attestation' => $candidat->statut === 'admis' && self::sceauDe($examen) !== null,
        ]);
    }

    /**
     * L'attestation de réussite, et sa condition : le sceau.
     *
     * Une attestation n'est pas un relevé. Le relevé rend compte de notes ;
     * l'attestation **affirme un succès**, et c'est avec elle qu'on s'inscrit
     * à l'université ou qu'on postule. Elle ne peut donc exister que si le
     * procès-verbal est signé et scellé : sans signature, l'établissement
     * délivrerait un document qui engage le ministère sur des résultats que
     * personne n'a arrêtés.
     *
     * Trois verrous, donc, et chacun répond à une façon de se tromper :
     *
     * - le candidat doit être **admis**. On n'atteste pas d'un ajournement ;
     * - un procès-verbal doit être **signé** ;
     * - son empreinte doit être **intacte**. Si une note a changé depuis la
     *   signature, le document signé ne dit plus ce que la base dit, et c'est
     *   exactement le cas ou une attestation deviendrait un faux.
     */
    public function attestation(ExamenCandidat $candidat)
    {
        $examen = $candidat->examen;

        abort_unless($examen->estProclame(), 404, 'Les résultats ne sont pas encore proclamés.');

        $this->seulementLesSiens($candidat);

        abort_unless(
            $candidat->statut === 'admis',
            404,
            'L’attestation de réussite ne se délivre qu’aux candidats admis.'
        );

        $document = self::sceauDe($examen);

        abort_unless(
            $document,
            404,
            'Le procès-verbal de cette session n’est pas signé : aucune attestation ne peut être délivrée.'
        );

        // La fiche de l'eleve sert de recours pour l'accord du document quand le
        // sexe n'a pas ete recopie sur le candidat.
        $candidat->load(['centre', 'school:id,name', 'student:id,gender']);

        return view('examens.resultats.attestation', [
            'examen' => $examen,
            'candidat' => $candidat,
            'document' => $document,
        ]);
    }

    /**
     * Le procès-verbal signé d'une session, s'il tient encore.
     *
     * Rendu public et statique parce que les vues s'en servent pour décider
     * d'afficher ou non le bouton de l'attestation : il ne faut pas proposer un
     * lien qui mènera à un refus.
     */
    public static function sceauDe(Examen $examen): ?\App\Models\ExamenDocument
    {
        $document = $examen->documents()
            ->whereNotNull('signe_le')
            ->latest('signe_le')
            ->first();

        return $document && $document->estIntact() ? $document : null;
    }

    /**
     * Mes examens, ou ceux de mes enfants.
     */
    public function mesExamens(Request $request)
    {
        $utilisateur = auth()->user();

        $eleves = match ($utilisateur?->role) {
            'student' => \App\Models\Student::where('user_id', $utilisateur->id)->get(),
            'parent' => \App\Models\ParentModel::where('user_id', $utilisateur->id)->first()?->students()->get() ?? collect(),
            default => collect(),
        };

        abort_if($eleves->isEmpty(), 403, 'Cet écran est celui de l’élève et de sa famille.');

        $candidatures = ExamenCandidat::whereIn('student_id', $eleves->pluck('id'))
            ->with(['examen', 'centre:id,nom,ville'])
            ->orderByDesc('id')
            ->get();

        return view('examens.resultats.mes-examens', [
            'candidatures' => $candidatures,
            'eleves' => $eleves,
        ]);
    }

    /* ==================================================================
       Les rouages
       ================================================================== */

    private function seulementLEtablissement(): void
    {
        $role = auth()->user()?->role;

        abort_unless(
            Roles::estDeDirection($role) || $role === 'secretary',
            403,
            'Les résultats d’examen de l’établissement sont réservés à la direction et au secrétariat.'
        );
    }

    /**
     * Le relevé d'un candidat ne se lit que par lui, sa famille, son
     * établissement ou l'organisateur.
     */
    private function seulementLesSiens(ExamenCandidat $candidat): void
    {
        $utilisateur = auth()->user();

        abort_unless($utilisateur, 403);

        if ($utilisateur->isSuperAdmin()) {
            return;
        }

        if ($candidat->student) {
            AccesEleve::verifier($candidat->student);

            return;
        }

        // Candidat libre : seul l'établissement qui l'a inscrit peut le lire.
        abort_unless(
            $candidat->school_id && $candidat->school_id === EcoleCourante::id()
                && (Roles::estDeDirection($utilisateur->role) || $utilisateur->role === 'secretary'),
            403,
            'Ce relevé ne relève pas de votre compte.'
        );
    }
}
