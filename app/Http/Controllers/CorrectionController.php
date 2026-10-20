<?php

namespace App\Http\Controllers;

use App\Models\Examen;
use App\Models\ExamenCandidat;
use App\Models\ExamenCorrecteur;
use App\Models\ExamenNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Le panel du correcteur convoqué.
 *
 * Un enseignant appelé à corriger ne voit ici que ce qu'il doit voir : les
 * copies de son épreuve, désignées par leur **numéro d'anonymat** — jamais par
 * le nom du candidat. C'est la règle de toute correction d'examen, et une
 * application qui la contournerait rendrait la session attaquable : il suffit
 * qu'un correcteur reconnaisse un nom pour que la note soit contestable.
 *
 * Il saisit, il corrige ses propres saisies tant que la délibération n'est pas
 * prononcée, et il déclare sa correction terminée. Rien d'autre : ni la
 * moyenne générale du candidat, ni les notes des autres épreuves, ni la
 * décision finale, qui ne le regardent pas.
 */
class CorrectionController extends Controller
{
    /**
     * Mes convocations : une ligne par épreuve à corriger.
     */
    public function index()
    {
        $convocations = $this->mesConvocations()
            ->load(['examen:id,nom,type,annee,serie,statut', 'epreuve:id,intitule,bareme,coefficient', 'centre:id,nom,ville']);

        return view('examens.correction.index', [
            // Ce qui reste à corriger d'abord : un correcteur qui arrive ici a
            // un paquet en attente, pas une archive à relire.
            'convocations' => $convocations
                ->map(fn ($c) => [
                    'convocation' => $c,
                    'avancement' => $this->avancement($c),
                ])
                ->sortByDesc(fn ($ligne) => $ligne['avancement']['reste'])
                ->values(),
        ]);
    }

    /**
     * Les copies d'une épreuve, sous anonymat.
     */
    public function copies(Request $request, ExamenCorrecteur $convocation)
    {
        $this->seulementLeSien($convocation);

        $examen = $convocation->examen;
        $epreuve = $convocation->epreuve;

        abort_unless($epreuve, 404, 'Cette convocation ne porte sur aucune épreuve.');

        // Les copies du centre du correcteur, s'il en a un : on ne corrige pas
        // les paquets d'un autre jury.
        $copies = ExamenCandidat::where('examen_id', $examen->id)
            ->whereNotNull('numero_anonymat')
            ->whereNotIn('statut', ['exclu'])
            ->when($convocation->centre_id, fn ($q, $centre) => $q->where('centre_id', $centre))
            ->when($request->input('reste'), fn ($q) => $q->whereDoesntHave(
                'notes',
                fn ($n) => $n->where('epreuve_id', $epreuve->id)
            ))
            // Par numéro d'anonymat : c'est l'ordre du paquet de copies.
            ->orderBy('numero_anonymat')
            ->get(['id', 'numero_anonymat']);

        $notes = ExamenNote::where('epreuve_id', $epreuve->id)
            ->whereIn('candidat_id', $copies->pluck('id'))
            ->get()
            ->keyBy('candidat_id');

        return view('examens.correction.copies', [
            'convocation' => $convocation,
            'examen' => $examen,
            'epreuve' => $epreuve,
            'copies' => $copies,
            'notes' => $notes,
            'avancement' => $this->avancement($convocation),
            'resteSeulement' => (bool) $request->input('reste'),
        ]);
    }

    /**
     * Enregistrer les notes saisies.
     *
     * La saisie se fait par paquet — c'est ainsi qu'on corrige : on remplit une
     * colonne de numéros, puis on enregistre. Enregistrer copie par copie
     * ferait trente allers-retours pour un paquet de trente.
     */
    public function enregistrer(Request $request, ExamenCorrecteur $convocation)
    {
        $this->seulementLeSien($convocation);

        $examen = $convocation->examen;
        $epreuve = $convocation->epreuve;

        if (! $examen->accepteLesNotes()) {
            return back()->with('error', 'La session n’est pas en correction : la saisie est fermée.');
        }

        $donnees = $request->validate([
            'notes' => 'array',
            'notes.*' => 'nullable|numeric|min:0|max:'.$epreuve->bareme,
            'absents' => 'array',
            'absents.*' => 'nullable|boolean',
        ], [
            'notes.*.max' => 'Une note dépasse le barème de l’épreuve ('.$epreuve->bareme.').',
        ]);

        $enregistrees = 0;

        DB::transaction(function () use ($donnees, $request, $convocation, $examen, $epreuve, &$enregistrees) {
            foreach ($donnees['notes'] ?? [] as $candidatId => $note) {
                $absent = (bool) ($request->input('absents.'.$candidatId));

                if ($note === null && ! $absent) {
                    continue;
                }

                // La copie appartient-elle bien à ce paquet ? Un identifiant
                // vient d'un formulaire, et un formulaire se modifie.
                $candidat = ExamenCandidat::where('examen_id', $examen->id)
                    ->when($convocation->centre_id, fn ($q, $centre) => $q->where('centre_id', $centre))
                    ->find($candidatId);

                if (! $candidat) {
                    continue;
                }

                ExamenNote::updateOrCreate(
                    ['epreuve_id' => $epreuve->id, 'candidat_id' => $candidat->id],
                    [
                        'examen_id' => $examen->id,
                        'correcteur_id' => $convocation->id,
                        'note' => $absent ? null : $note,
                        'absent' => $absent,
                        'saisie_par' => auth()->id(),
                        'saisie_le' => now(),
                    ]
                );

                $enregistrees++;
            }
        });

        return back()->with('success', $enregistrees
            ? $enregistrees.' note(s) enregistrée(s).'
            : 'Aucune note à enregistrer.');
    }

    /**
     * Déclarer sa correction terminée.
     */
    public function terminer(ExamenCorrecteur $convocation)
    {
        $this->seulementLeSien($convocation);

        $avancement = $this->avancement($convocation);

        if ($avancement['reste'] > 0) {
            return back()->with('error', $avancement['reste'].' copie(s) sans note : la correction n’est pas terminée.');
        }

        $convocation->forceFill(['statut' => 'termine', 'termine_le' => now()])->save();

        return back()->with('success', 'Correction déclarée terminée. Merci.');
    }

    /* ==================================================================
       Les rouages
       ================================================================== */

    /**
     * Les convocations du compte connecté.
     *
     * Le lien passe par `user_id`, posé à la convocation depuis la fiche de
     * l'enseignant. Un enseignant sans compte ne peut pas saisir — la direction
     * des examens doit alors saisir pour lui.
     */
    private function mesConvocations()
    {
        $convocations = ExamenCorrecteur::where('user_id', auth()->id())
            ->whereHas('examen', fn ($q) => $q->whereNotIn('statut', ['preparation', 'inscriptions']))
            ->get();

        abort_if($convocations->isEmpty() && ! auth()->user()?->isSuperAdmin(), 403,
            'Vous n’êtes convoqué à aucune correction.');

        return $convocations;
    }

    private function seulementLeSien(ExamenCorrecteur $convocation): void
    {
        abort_unless(
            $convocation->user_id === auth()->id() || auth()->user()?->isSuperAdmin(),
            403,
            'Cette convocation n’est pas la vôtre.'
        );
    }

    /**
     * Où en est ce correcteur : combien de copies, combien notées.
     *
     * @return array{attendues: int, notees: int, reste: int, part: int}
     */
    private function avancement(ExamenCorrecteur $convocation): array
    {
        if (! $convocation->epreuve_id) {
            return ['attendues' => 0, 'notees' => 0, 'reste' => 0, 'part' => 0];
        }

        $attendues = ExamenCandidat::where('examen_id', $convocation->examen_id)
            ->whereNotNull('numero_anonymat')
            ->whereNotIn('statut', ['exclu'])
            ->when($convocation->centre_id, fn ($q, $centre) => $q->where('centre_id', $centre))
            ->count();

        $notees = ExamenNote::where('epreuve_id', $convocation->epreuve_id)
            ->when($convocation->centre_id, fn ($q, $centre) => $q->whereHas(
                'candidat',
                fn ($c) => $c->where('centre_id', $centre)
            ))
            ->count();

        return [
            'attendues' => $attendues,
            'notees' => $notees,
            'reste' => max(0, $attendues - $notees),
            'part' => $attendues > 0 ? (int) round($notees / $attendues * 100) : 0,
        ];
    }
}
