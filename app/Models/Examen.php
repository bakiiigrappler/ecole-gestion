<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Une session d'examen national : CEP, BEPC, baccalauréat.
 *
 * Elle n'appartient à aucun établissement — ce sont les établissements qui y
 * envoient leurs candidats et leurs correcteurs. D'où l'absence du filtre
 * global : le cloisonnement se fait sur les candidats, pas sur la session.
 *
 * Le statut n'est pas décoratif : il commande ce qui est permis. On ne saisit
 * pas une note avant la correction, on ne délibère pas sur des copies
 * incomplètes, et on ne proclame pas un résultat que personne n'a signé. Un
 * examen dont les étapes ne s'enchaînent pas est un examen attaquable.
 */
class Examen extends Model
{
    use SoftDeletes;

    protected $table = 'examens';

    protected $fillable = [
        'nom', 'type', 'annee', 'serie', 'statut',
        'debut_epreuves', 'fin_epreuves', 'proclamation',
        'moyenne_admission', 'moyenne_second_tour', 'notes',
    ];

    protected $casts = [
        'debut_epreuves' => 'date',
        'fin_epreuves' => 'date',
        'proclamation' => 'date',
        'moyenne_admission' => 'float',
        'moyenne_second_tour' => 'float',
    ];

    public const TYPES = [
        'cep' => 'Certificat d’études primaires',
        'bepc' => 'Brevet d’études du premier cycle',
        'bac_general' => 'Baccalauréat général',
        'bac_technique' => 'Baccalauréat technique',
    ];

    /** Le fil de la session, dans l'ordre. */
    public const ETAPES = [
        'preparation' => 'Préparation',
        'inscriptions' => 'Inscriptions ouvertes',
        'epreuves' => 'Épreuves en cours',
        'correction' => 'Correction',
        'deliberation' => 'Délibération',
        'signe' => 'Procès-verbal signé',
        'proclame' => 'Résultats proclamés',
    ];

    public function epreuves(): HasMany
    {
        return $this->hasMany(ExamenEpreuve::class, 'examen_id')->orderBy('ordre')->orderBy('id');
    }

    public function centres(): HasMany
    {
        return $this->hasMany(ExamenCentre::class, 'examen_id')->orderBy('nom');
    }

    public function candidats(): HasMany
    {
        return $this->hasMany(ExamenCandidat::class, 'examen_id');
    }

    public function correcteurs(): HasMany
    {
        return $this->hasMany(ExamenCorrecteur::class, 'examen_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ExamenNote::class, 'examen_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExamenDocument::class, 'examen_id');
    }

    public function scopeOuverts(Builder $requete): Builder
    {
        return $requete->whereNotIn('statut', ['proclame']);
    }

    public function getLibelleTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getLibelleStatutAttribute(): string
    {
        return self::ETAPES[$this->statut] ?? $this->statut;
    }

    public function getIntituleCompletAttribute(): string
    {
        return $this->nom.($this->serie ? ' — série '.$this->serie : '');
    }

    /** Le rang de l'étape courante, pour comparer deux statuts. */
    public function rangDeLEtape(?string $etape = null): int
    {
        return array_search($etape ?? $this->statut, array_keys(self::ETAPES), true) ?: 0;
    }

    public function auMoins(string $etape): bool
    {
        return $this->rangDeLEtape() >= $this->rangDeLEtape($etape);
    }

    public function estProclame(): bool
    {
        return $this->statut === 'proclame';
    }

    /** La correction est-elle ouverte à la saisie ? */
    public function accepteLesNotes(): bool
    {
        return in_array($this->statut, ['correction', 'deliberation'], true);
    }

    /**
     * Le total des coefficients, hors épreuves facultatives.
     *
     * Une épreuve facultative ne pèse pas dans le diviseur : seuls ses points
     * au-dessus de la moyenne s'ajoutent, et les compter deux fois ferait
     * baisser la moyenne de qui l'a choisie.
     */
    public function totalDesCoefficients(): float
    {
        return (float) $this->epreuves->where('facultative', false)->sum('coefficient');
    }
}
