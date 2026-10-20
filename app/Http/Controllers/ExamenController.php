<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use App\Models\ExamenCentre;
use App\Models\ExamenCorrecteur;
use App\Models\ExamenDocument;
use App\Models\ExamenEpreuve;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\EcoleCourante;
use App\Support\Examens\Deliberation;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Les examens nationaux, côté organisateur.
 *
 * Une session se conduit par étapes, et chacune n'ouvre que ce que la
 * précédente a rendu possible : on inscrit, on compose, on corrige, on
 * délibère, on signe, on proclame. L'écran suit ce fil — il n'offre jamais
 * l'action d'après tant que celle d'avant n'est pas faite, parce qu'un examen
 * dont les étapes se chevauchent est un examen contestable.
 *
 * L'organisateur est le super administrateur de la plateforme : la direction
 * des examens, en somme. Les établissements, eux, ne font qu'inscrire leurs
 * élèves et lire leurs résultats — c'est `ExamenEtablissementController`.
 */
class ExamenController extends Controller
{
    /* ==================================================================
       Les sessions
       ================================================================== */

    public function index()
    {
        $this->seulementLOrganisateur();

        $examens = Examen::withCount(['candidats', 'centres', 'correcteurs'])
            ->orderByDesc('annee')
            ->orderBy('type')
            ->paginate(20);

        return view('examens.index', ['examens' => $examens]);
    }

    public function create()
    {
        $this->seulementLOrganisateur();

        return view('examens.create', ['examen' => new Examen(['annee' => now()->year])]);
    }

    public function store(Request $request)
    {
        $this->seulementLOrganisateur();

        $donnees = $this->valider($request);

        $examen = Examen::create($donnees + ['statut' => 'preparation']);

        return redirect()->route('examens.show', $examen)
            ->with('success', 'Session ouverte. Déclarez les épreuves, puis les centres.');
    }

    public function show(Examen $examen)
    {
        $this->seulementLOrganisateur();

        $examen->load(['epreuves', 'centres']);

        return view('examens.show', [
            'examen' => $examen,
            'avancement' => Deliberation::avancement($examen),
            'chiffres' => $this->chiffres($examen),
            'documents' => $examen->documents()->with('signataire:id,name')->latest()->get(),
        ]);
    }

    public function update(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $examen->update($this->valider($request, $examen));

        return back()->with('success', 'Session mise à jour.');
    }

    /* ==================================================================
       Les épreuves et les centres
       ================================================================== */

    public function ajouterEpreuve(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $donnees = $request->validate([
            'intitule' => 'required|string|max:255',
            'subject_id' => 'nullable|integer',
            'coefficient' => 'required|numeric|min:0.5|max:20',
            'bareme' => 'required|integer|min:1|max:100',
            'duree_minutes' => 'nullable|integer|min:15|max:480',
            'date_epreuve' => 'nullable|date',
            'facultative' => 'nullable|boolean',
        ]);

        $examen->epreuves()->create($donnees + [
            'facultative' => $request->boolean('facultative'),
            'ordre' => ($examen->epreuves()->max('ordre') ?? 0) + 1,
        ]);

        return back()->with('success', 'Épreuve ajoutée.');
    }

    public function supprimerEpreuve(Examen $examen, ExamenEpreuve $epreuve)
    {
        $this->seulementLOrganisateur();

        // Une épreuve déjà corrigée ne se retire pas : les notes qui la
        // portent disparaîtraient avec elle.
        if ($epreuve->notes()->exists()) {
            return back()->with('error', 'Cette épreuve porte déjà des notes : elle ne peut plus être retirée.');
        }

        $epreuve->delete();

        return back()->with('success', 'Épreuve retirée.');
    }

    public function ajouterCentre(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $donnees = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'ville' => 'nullable|string|max:255',
            'school_id' => 'nullable|integer',
            'capacite' => 'nullable|integer|min:1',
            'president_jury' => 'nullable|string|max:255',
        ]);

        $examen->centres()->create($donnees);

        return back()->with('success', 'Centre ajouté.');
    }

    /* ==================================================================
       Les candidats
       ================================================================== */

    public function candidats(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $candidats = $examen->candidats()
            ->with(['centre:id,nom', 'school:id,name'])
            ->when($request->input('centre'), fn ($q, $c) => $q->where('centre_id', $c))
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when(trim((string) $request->input('recherche')), function ($q, $terme) {
                $motif = '%'.mb_strtolower($terme).'%';
                $q->where(fn ($r) => $r->whereRaw('LOWER(nom) LIKE ?', [$motif])
                    ->orWhereRaw('LOWER(prenom) LIKE ?', [$motif])
                    ->orWhereRaw('LOWER(numero_candidat) LIKE ?', [$motif]));
            })
            ->orderBy('nom')
            ->paginate(50)
            ->withQueryString();

        return view('examens.candidats', [
            'examen' => $examen,
            'candidats' => $candidats,
            'centres' => $examen->centres,
            'ecoles' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Inscrire en bloc les élèves d'un établissement.
     *
     * Les candidats viennent des inscriptions de l'année : personne ne ressaisit
     * trois cents noms, et la fiche de l'élève reste la source — numéro de
     * candidat mis à part, qui naît ici.
     */
    public function inscrireUneEcole(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $donnees = $request->validate([
            'school_id' => 'required|integer',
            'niveau' => 'required|string|max:40',
            'centre_id' => 'nullable|integer',
        ]);

        if ($examen->auMoins('correction')) {
            return back()->with('error', 'La correction est commencée : les inscriptions sont closes.');
        }

        $eleves = Student::withoutGlobalScopes()
            ->where('school_id', $donnees['school_id'])
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($n) => $n->where('name', 'like', '%'.$donnees['niveau'].'%')))
            ->with(['enrollments.schoolClass.serie'])
            ->get();

        $ecole = School::find($donnees['school_id']);
        $inscrits = 0;

        foreach ($eleves as $eleve) {
            if ($examen->candidats()->where('student_id', $eleve->id)->exists()) {
                continue;
            }

            $examen->candidats()->create([
                'student_id' => $eleve->id,
                'school_id' => $eleve->school_id,
                'centre_id' => $donnees['centre_id'] ?? null,
                'numero_candidat' => $this->numeroDeCandidat($examen),
                'nom' => $eleve->last_name,
                'prenom' => $eleve->first_name,
                'date_naissance' => $eleve->date_of_birth,
                'lieu_naissance' => $eleve->place_of_birth ?? null,
                'sexe' => $eleve->gender,
                'serie' => $eleve->enrollments->firstWhere('status', 'active')?->schoolClass?->serie?->code,
                'etablissement_origine' => $ecole->name ?? null,
                'statut' => 'inscrit',
            ]);

            $inscrits++;
        }

        return back()->with('success', $inscrits
            ? $inscrits.' candidat(s) inscrit(s) pour '.($ecole->name ?? 'cet établissement').'.'
            : 'Aucun nouvel élève à inscrire : ils y sont déjà.');
    }

    /**
     * Tirer les numéros d'anonymat.
     *
     * Ils se tirent une fois, à l'ouverture des épreuves, et ne se retirent
     * plus : une copie déjà corrigée sous un numéro doit le garder, sinon la
     * note ne retrouve plus son candidat.
     */
    public function tirerLAnonymat(Examen $examen)
    {
        $this->seulementLOrganisateur();

        $sansNumero = $examen->candidats()->whereNull('numero_anonymat')->get();

        if ($sansNumero->isEmpty()) {
            return back()->with('error', 'Tous les candidats ont déjà un numéro d’anonymat.');
        }

        // Un tirage, pas une suite : deux candidats voisins par le nom ne
        // doivent pas se retrouver voisins par le numéro.
        $numeros = collect(range(1, $sansNumero->count()))->shuffle();

        DB::transaction(function () use ($sansNumero, $numeros, $examen) {
            $deja = $examen->candidats()->whereNotNull('numero_anonymat')->count();

            foreach ($sansNumero->values() as $rang => $candidat) {
                $candidat->forceFill([
                    'numero_anonymat' => 'A'.str_pad((string) ($deja + $numeros[$rang]), 5, '0', STR_PAD_LEFT),
                ])->save();
            }
        });

        return back()->with('success', $sansNumero->count().' numéro(s) d’anonymat tirés.');
    }

    /* ==================================================================
       Les correcteurs
       ================================================================== */

    public function correcteurs(Examen $examen)
    {
        $this->seulementLOrganisateur();

        return view('examens.correcteurs', [
            'examen' => $examen->load('epreuves', 'centres'),
            'correcteurs' => $examen->correcteurs()
                ->with(['epreuve:id,intitule', 'centre:id,nom', 'teacher:id,first_name,last_name', 'school:id,name'])
                ->orderBy('role')
                ->get(),
            'enseignants' => Teacher::withoutGlobalScopes()
                ->with('school:id,name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'school_id', 'user_id']),
        ]);
    }

    public function convoquer(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $donnees = $request->validate([
            'teacher_id' => 'required|integer',
            'epreuve_id' => 'nullable|integer',
            'centre_id' => 'nullable|integer',
            'role' => 'required|in:correcteur,president_jury,secretaire',
        ]);

        $enseignant = Teacher::withoutGlobalScopes()->find($donnees['teacher_id']);

        abort_unless($enseignant, 404, 'Enseignant introuvable.');

        $examen->correcteurs()->updateOrCreate(
            [
                'teacher_id' => $enseignant->id,
                'epreuve_id' => $donnees['epreuve_id'] ?? null,
            ],
            [
                'centre_id' => $donnees['centre_id'] ?? null,
                // Le compte sert à la saisie : sans lui, le correcteur ne peut
                // pas entrer ses notes lui-même.
                'user_id' => $enseignant->user_id,
                'school_id' => $enseignant->school_id,
                'nom' => trim($enseignant->first_name.' '.$enseignant->last_name),
                'role' => $donnees['role'],
                'statut' => 'convoque',
                'convoque_le' => now(),
            ]
        );

        return back()->with('success', 'Convocation enregistrée.');
    }

    public function retirerLaConvocation(Examen $examen, ExamenCorrecteur $correcteur)
    {
        $this->seulementLOrganisateur();

        if ($correcteur->notes()->exists()) {
            return back()->with('error', 'Ce correcteur a déjà saisi des notes : sa convocation ne se retire plus.');
        }

        $correcteur->delete();

        return back()->with('success', 'Convocation retirée.');
    }

    /* ==================================================================
       Les étapes, la délibération, la signature
       ================================================================== */

    public function changerDEtape(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $donnees = $request->validate([
            'statut' => 'required|in:'.implode(',', array_keys(Examen::ETAPES)),
        ]);

        // La proclamation a sa propre porte : elle exige un document signé.
        if ($donnees['statut'] === 'proclame') {
            return back()->with('error', 'La proclamation passe par le procès-verbal signé.');
        }

        $examen->forceFill(['statut' => $donnees['statut']])->save();

        return back()->with('success', 'Session passée en « '.Examen::ETAPES[$donnees['statut']].' ».');
    }

    public function deliberer(Examen $examen)
    {
        $this->seulementLOrganisateur();

        if (! $examen->auMoins('correction')) {
            return back()->with('error', 'La correction n’est pas commencée : il n’y a rien à délibérer.');
        }

        $bilan = Deliberation::prononcer($examen);

        if ($examen->statut === 'correction') {
            $examen->forceFill(['statut' => 'deliberation'])->save();
        }

        return back()->with('success', sprintf(
            'Délibération prononcée : %d admis, %d au second tour, %d ajournés%s.',
            $bilan['admis'],
            $bilan['second_tour'],
            $bilan['ajournes'],
            $bilan['incomplets'] ? ' — '.$bilan['incomplets'].' dossier(s) incomplet(s)' : ''
        ));
    }

    /**
     * Le procès-verbal, tel que le signataire va le lire.
     *
     * On ne signe pas un enregistrement en base : on signe un document. Celui
     * qui engage sa responsabilité doit voir la liste nominative qu'il arrête,
     * le bilan qu'il arrête, et pouvoir l'imprimer avant de décider.
     */
    public function procesVerbal(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        $centre = $request->integer('centre')
            ? ExamenCentre::where('examen_id', $examen->id)->find($request->integer('centre'))
            : null;

        $candidats = $examen->candidats()
            ->when($centre, fn ($q) => $q->where('centre_id', $centre->id))
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return view('examens.proces-verbal', [
            'examen' => $examen,
            'centre' => $centre,
            'candidats' => $candidats,
            'document' => $examen->documents()
                ->where('type', 'proces_verbal')
                ->when($centre, fn ($q) => $q->where('centre_id', $centre->id), fn ($q) => $q->whereNull('centre_id'))
                ->whereNotNull('signe_le')
                ->latest('signe_le')
                ->first(),
        ]);
    }

    /**
     * Sceller le procès-verbal, ou déposer le scan signé à la main.
     */
    public function signer(Request $request, Examen $examen)
    {
        $this->seulementLOrganisateur();

        if (! $examen->auMoins('deliberation')) {
            return back()->with('error', 'Rien à signer : la délibération n’a pas eu lieu.');
        }

        $donnees = $request->validate([
            'centre_id' => 'nullable|integer',
            'mode_signature' => 'required|in:electronique,manuscrite',
            'signataire_nom' => 'required|string|max:255',
            'signataire_qualite' => 'required|string|max:255',
            'fichier' => 'required_if:mode_signature,manuscrite|nullable|file|mimes:pdf,jpg,jpeg,png|max:8192',
        ], [
            'fichier.required_if' => 'Déposez le procès-verbal signé et cacheté.',
        ]);

        $centre = $donnees['centre_id'] ? ExamenCentre::find($donnees['centre_id']) : null;

        $document = new ExamenDocument([
            'examen_id' => $examen->id,
            'centre_id' => $centre?->id,
            'type' => 'proces_verbal',
            'intitule' => 'Procès-verbal de délibération — '.$examen->intitule_complet
                .($centre ? ' — '.$centre->nom : ''),
            'mode_signature' => $donnees['mode_signature'],
            'signataire_nom' => $donnees['signataire_nom'],
            'signataire_qualite' => $donnees['signataire_qualite'],
            'signe_par' => auth()->id(),
            'signe_le' => now(),
            'jeton_verification' => ExamenDocument::jeton(),
        ]);

        if ($donnees['mode_signature'] === 'manuscrite' && $request->hasFile('fichier')) {
            $chemin = $request->file('fichier')->store('examens/proces-verbaux', 'public');

            $document->fichier = $chemin;
            // L'empreinte du fichier déposé : c'est lui qui fait foi.
            $document->empreinte = hash_file('sha256', storage_path('app/public/'.$chemin));
        } else {
            // Le scellement porte sur ce que le document dit, pas sur le PDF :
            // un PDF se régénère, les résultats ne doivent pas bouger.
            $document->empreinte = ExamenDocument::empreinteDu($examen, $centre);
        }

        $document->save();

        if ($examen->statut === 'deliberation') {
            $examen->forceFill(['statut' => 'signe'])->save();
        }

        return back()->with('success', 'Procès-verbal signé. La proclamation est désormais possible.');
    }

    public function proclamer(Examen $examen)
    {
        $this->seulementLOrganisateur();

        if (! $examen->documents()->whereNotNull('signe_le')->exists()) {
            return back()->with('error', 'Aucun procès-verbal signé : la proclamation attendra.');
        }

        $examen->forceFill([
            'statut' => 'proclame',
            'proclamation' => now(),
        ])->save();

        // Les résultats deviennent consultables : la page publique les sert
        // depuis un cache reconstruit ici, pas depuis la base à chaque visite.
        \App\Support\Examens\ResultatsPublies::reconstruire($examen);

        return back()->with('success', 'Résultats proclamés. Ils sont consultables par numéro de candidat.');
    }

    /* ==================================================================
       Les rouages
       ================================================================== */

    private function seulementLOrganisateur(): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin(),
            403,
            'L’organisation des examens nationaux relève de la direction des examens.'
        );
    }

    private function valider(Request $request, ?Examen $examen = null): array
    {
        return $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(Examen::TYPES)),
            'annee' => 'required|integer|min:2000|max:2100',
            'serie' => 'nullable|string|max:20',
            'debut_epreuves' => 'nullable|date',
            'fin_epreuves' => 'nullable|date|after_or_equal:debut_epreuves',
            'moyenne_admission' => 'required|numeric|min:0|max:20',
            'moyenne_second_tour' => 'nullable|numeric|min:0|max:20|lt:moyenne_admission',
            'notes' => 'nullable|string|max:2000',
        ], [
            'moyenne_second_tour.lt' => 'Le seuil du second tour doit être inférieur à la moyenne d’admission.',
        ]);
    }

    /**
     * Un numéro de candidat, unique dans la session.
     *
     * Forme : type, année, puis cinq chiffres — « BAC26-00417 ». C'est ce que
     * le candidat saisira pour lire son résultat ; il doit être court, lisible
     * au téléphone et sans caractère qui prête à confusion.
     */
    private function numeroDeCandidat(Examen $examen): string
    {
        $prefixe = strtoupper(Str::of($examen->type)->replace('_', '')->substr(0, 3))
            .substr((string) $examen->annee, -2).'-';

        $dernier = (int) Str::after(
            $examen->candidats()->where('numero_candidat', 'like', $prefixe.'%')->max('numero_candidat') ?: $prefixe.'0',
            $prefixe
        );

        return $prefixe.str_pad((string) ($dernier + 1), 5, '0', STR_PAD_LEFT);
    }

    /** @return array<string, int> */
    private function chiffres(Examen $examen): array
    {
        $candidats = $examen->candidats();

        return [
            'candidats' => (clone $candidats)->count(),
            'centres' => $examen->centres()->count(),
            'correcteurs' => $examen->correcteurs()->count(),
            'admis' => (clone $candidats)->where('statut', 'admis')->count(),
            'second_tour' => (clone $candidats)->where('statut', 'second_tour')->count(),
            'ajournes' => (clone $candidats)->where('statut', 'ajourne')->count(),
            'sans_anonymat' => (clone $candidats)->whereNull('numero_anonymat')->count(),
        ];
    }
}
