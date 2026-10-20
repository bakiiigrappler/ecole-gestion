<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Une épreuve de la session : une matière, un coefficient, un barème.
 *
 * Le coefficient est porté ici et non sur la matière de l'établissement : au
 * baccalauréat, les mathématiques ne pèsent pas le même poids en série C et en
 * série A2, et c'est la session qui en décide, pas l'école.
 */
class ExamenEpreuve extends Model
{
    protected $table = 'examen_epreuves';

    protected $fillable = [
        'examen_id', 'intitule', 'subject_id', 'coefficient', 'bareme',
        'duree_minutes', 'date_epreuve', 'heure_debut', 'facultative', 'ordre',
    ];

    protected $casts = [
        'date_epreuve' => 'date',
        'coefficient' => 'float',
        'facultative' => 'boolean',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ExamenNote::class, 'epreuve_id');
    }

    public function correcteurs(): HasMany
    {
        return $this->hasMany(ExamenCorrecteur::class, 'epreuve_id');
    }

    /** La note ramenée sur 20, quel que soit le barème de l'épreuve. */
    public function sur20(?float $note): ?float
    {
        if ($note === null || ! $this->bareme) {
            return null;
        }

        return round($note / $this->bareme * 20, 2);
    }

    public function getLibelleDureeAttribute(): ?string
    {
        if (! $this->duree_minutes) {
            return null;
        }

        $heures = intdiv($this->duree_minutes, 60);
        $minutes = $this->duree_minutes % 60;

        return trim(($heures ? $heures.' h ' : '').($minutes ? $minutes.' min' : ''));
    }
}
