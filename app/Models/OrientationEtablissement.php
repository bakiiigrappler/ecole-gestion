<?php

namespace App\Models;

use App\Support\EcoleCourante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un établissement vers lequel on oriente : lycée, université, grande école,
 * centre de formation professionnelle.
 *
 * Le répertoire est d'abord national — les lycées et les universités du Gabon
 * ne changent pas d'une école à l'autre, et chaque établissement n'a pas à
 * ressaisir la liste. Un `school_id` nul désigne cette part commune ;
 * renseigné, il s'agit d'un établissement ajouté par une école pour ses seuls
 * élèves.
 *
 * D'où l'absence du filtre global habituel : il masquerait le répertoire
 * national. Le cloisonnement passe par `visiblesPar()`, qui laisse voir le
 * commun et le sien, et par rien d'autre.
 */
class OrientationEtablissement extends Model
{
    use SoftDeletes;

    protected $table = 'orientation_etablissements';

    protected $fillable = [
        'school_id', 'nom', 'sigle', 'type', 'statut', 'ville', 'quartier',
        'latitude', 'longitude', 'capacite', 'inscrits', 'age_min', 'age_max',
        'filieres', 'description', 'telephone', 'site', 'is_active',
    ];

    protected $casts = [
        'filieres' => 'array',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public const TYPES = [
        'lycee' => 'Lycée',
        'centre_professionnel' => 'Centre de formation professionnelle',
        'universite' => 'Université',
        'ecole_superieure' => 'Grande école',
    ];

    public const STATUTS = [
        'public' => 'Public',
        'prive' => 'Privé',
        'confessionnel' => 'Confessionnel',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Le répertoire nationalet celui de l'établissement courant.
     */
    public function scopeVisiblesPar(Builder $requete, ?int $ecole = null): Builder
    {
        $ecole ??= EcoleCourante::id();

        return $requete->where(function ($q) use ($ecole) {
            $q->whereNull('school_id');

            if ($ecole) {
                $q->orWhere('school_id', $ecole);
            }
        });
    }

    public function scopeDuType(Builder $requete, string|array $type): Builder
    {
        return $requete->whereIn('type', (array) $type);
    }

    public function getLibelleTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getNomCompletAttribute(): string
    {
        return $this->sigle ? $this->nom.' ('.$this->sigle.')' : $this->nom;
    }

    /** Places encore libres, ou null si l'établissement ne les déclare pas. */
    public function getPlacesLibresAttribute(): ?int
    {
        if ($this->capacite === null) {
            return null;
        }

        return max(0, $this->capacite - (int) $this->inscrits);
    }

    public function getEstCompletAttribute(): bool
    {
        return $this->capacite !== null && (int) $this->inscrits >= $this->capacite;
    }

    /**
     * Distance à vol d'oiseau, en kilomètres, ou null si l'un des deux points
     * manque. Formule de haversine : la Terre n'est pas plate, et Libreville
     * s'étend assez pour que cela se voie.
     */
    public function distanceDepuis(?float $latitude, ?float $longitude): ?float
    {
        if ($latitude === null || $longitude === null || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        $rayon = 6371;
        $dLat = deg2rad($this->latitude - $latitude);
        $dLon = deg2rad($this->longitude - $longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) * sin($dLon / 2) ** 2;

        return round($rayon * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
