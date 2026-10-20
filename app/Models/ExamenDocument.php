<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Le procès-verbal d'une session, scellé ou signé à la main.
 *
 * Un résultat d'examen n'existe que porté par un document signé. Deux manières
 * de le faire, et l'application accepte les deux parce que les usages diffèrent
 * d'un centre à l'autre :
 *
 *   — **le scellement électronique** : l'empreinte du contenu est calculée au
 *     moment de la signature et conservée avec l'identité du signataire et
 *     l'heure. Qui modifie une note ensuite casse l'empreinte, et la
 *     vérification le dit ;
 *   — **la signature manuscrite** : le président de jury imprime, signe,
 *     cachette et dépose le scan. Le document fait foi de la même façon, et
 *     l'empreinte est alors celle du fichier déposé.
 *
 * Dans les deux cas, un jeton de vérification permet à qui tient le papier de
 * confronter ce qu'il lit à ce que la base contient.
 */
class ExamenDocument extends Model
{
    protected $table = 'examen_documents';

    protected $fillable = [
        'examen_id', 'centre_id', 'type', 'intitule',
        'empreinte', 'signe_par', 'signataire_nom', 'signataire_qualite', 'signe_le',
        'fichier', 'mode_signature', 'jeton_verification',
    ];

    protected $casts = [
        'signe_le' => 'datetime',
    ];

    public const TYPES = [
        'proces_verbal' => 'Procès-verbal de délibération',
        'releve_centre' => 'Relevé des résultats du centre',
        'attestation' => 'Attestation de réussite',
    ];

    public const MODES = [
        'electronique' => 'Signature électronique',
        'manuscrite' => 'Signature manuscrite déposée',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ExamenCentre::class, 'centre_id');
    }

    public function signataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signe_par');
    }

    public function estSigne(): bool
    {
        return $this->signe_le !== null;
    }

    public function getLibelleTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getLibelleModeAttribute(): ?string
    {
        return self::MODES[$this->mode_signature] ?? null;
    }

    /**
     * L'empreinte du contenu : ce qui rend le scellement vérifiable.
     *
     * On n'empreinte pas le PDF — il se régénère et son octet change — mais ce
     * qu'il dit : la session, le centre, et chaque candidat avec sa moyenne et
     * sa décision. C'est cela qu'un fraudeur voudrait changer.
     *
     * **Et ses notes par épreuve.** Elles ne figurent pas au procès-verbal, mais
     * le relevé de notes les publie — et il les publie sous cette signature,
     * en portant la mention « Document scellé ». Tant qu'elles restaient hors
     * de l'empreinte, une note pouvait être modifiée après la signature :
     * l'application continuait d'annoncer un document intact, et de délivrer
     * l'attestation de réussite qui en dépend. Vérifié : la moyenne ne bougeant
     * qu'à la délibération, le sceau ne s'en apercevait pas.
     *
     * Conséquence voulue : toucher à une note après la signature rompt le
     * sceau. C'est le comportement juste — on délibère et l'on signe à
     * nouveau — mais il rend « non intacts » les documents signés avant ce
     * changement, dont l'empreinte a été calculée autrement.
     */
    public static function empreinteDu(Examen $examen, ?ExamenCentre $centre = null): string
    {
        $candidats = ExamenCandidat::where('examen_id', $examen->id)
            ->when($centre, fn ($q) => $q->where('centre_id', $centre->id))
            ->orderBy('numero_candidat')
            ->with(['notes' => fn ($q) => $q->orderBy('epreuve_id')])
            ->get(['id', 'numero_candidat', 'nom', 'prenom', 'moyenne', 'statut', 'mention']);

        $lignes = $candidats
            ->map(fn ($c) => implode('|', [
                $c->numero_candidat, $c->nom, $c->prenom,
                $c->moyenne === null ? '' : number_format($c->moyenne, 2, '.', ''),
                $c->statut, $c->mention,
                // Les notes dans l'ordre des épreuves : l'absence compte autant
                // qu'un zéro, et elle doit donc entrer dans l'empreinte.
                $c->notes->map(fn ($n) => $n->epreuve_id.':'
                    .($n->absent ? 'abs' : ($n->note === null ? '' : number_format($n->note, 2, '.', ''))))
                    ->join(','),
            ]))
            ->join("\n");

        return hash('sha256', $examen->id.'::'.($centre->id ?? 'tous')."::\n".$lignes);
    }

    public static function jeton(): string
    {
        return strtoupper(Str::random(12));
    }

    /**
     * Le document dit-il encore ce qu'il disait au moment de la signature ?
     */
    public function estIntact(): bool
    {
        if (! $this->empreinte || $this->mode_signature === 'manuscrite') {
            return true;
        }

        return hash_equals($this->empreinte, self::empreinteDu($this->examen, $this->centre));
    }
}
