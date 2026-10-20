<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La note d'une copie : un candidat, une épreuve, un correcteur.
 *
 * Elle porte qui l'a saisie et quand. Une note d'examen se conteste, et une
 * contestation sans trace de saisie ne s'instruit pas.
 */
class ExamenNote extends Model
{
    protected $table = 'examen_notes';

    protected $fillable = [
        'examen_id', 'epreuve_id', 'candidat_id', 'correcteur_id',
        'note', 'absent', 'observation', 'saisie_par', 'saisie_le',
    ];

    protected $casts = [
        'note' => 'float',
        'absent' => 'boolean',
        'saisie_le' => 'datetime',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function epreuve(): BelongsTo
    {
        return $this->belongsTo(ExamenEpreuve::class, 'epreuve_id');
    }

    public function candidat(): BelongsTo
    {
        return $this->belongsTo(ExamenCandidat::class, 'candidat_id');
    }

    public function correcteur(): BelongsTo
    {
        return $this->belongsTo(ExamenCorrecteur::class, 'correcteur_id');
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisie_par');
    }
}
