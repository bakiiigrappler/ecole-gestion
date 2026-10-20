<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un centre d'examen : le lieu où l'on compose, et où l'on corrige.
 *
 * Il peut être hébergé par un établissement de la plateforme — un lycée prête
 * ses salles — ou n'exister que pour la session. Le candidat doit savoir où il
 * compose, et l'établissement d'origine où sont partis ses élèves : c'est la
 * première question qu'un parent pose.
 */
class ExamenCentre extends Model
{
    protected $table = 'examen_centres';

    protected $fillable = [
        'examen_id', 'nom', 'code', 'ville', 'school_id', 'capacite', 'president_jury',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function candidats(): HasMany
    {
        return $this->hasMany(ExamenCandidat::class, 'centre_id');
    }

    public function correcteurs(): HasMany
    {
        return $this->hasMany(ExamenCorrecteur::class, 'centre_id');
    }

    public function getPlacesLibresAttribute(): ?int
    {
        return $this->capacite === null
            ? null
            : max(0, $this->capacite - $this->candidats()->count());
    }
}
