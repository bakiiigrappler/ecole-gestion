<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un enseignant convoqué pour corriger, et ce qu'il corrige.
 *
 * La convocation porte l'établissement d'origine : c'est ainsi qu'un chef
 * d'établissement sait si l'un de ses professeurs a été appelé — question qu'il
 * pose chaque année, et à laquelle personne ne savait répondre sans téléphoner
 * à la direction des examens.
 */
class ExamenCorrecteur extends Model
{
    protected $table = 'examen_correcteurs';

    protected $fillable = [
        'examen_id', 'epreuve_id', 'centre_id', 'teacher_id', 'user_id', 'school_id',
        'nom', 'role', 'statut', 'convoque_le', 'termine_le',
    ];

    protected $casts = [
        'convoque_le' => 'datetime',
        'termine_le' => 'datetime',
    ];

    public const ROLES = [
        'correcteur' => 'Correcteur',
        'president_jury' => 'Président de jury',
        'secretaire' => 'Secrétaire de jury',
    ];

    public const STATUTS = [
        'convoque' => 'Convoqué',
        'accepte' => 'A accepté',
        'excuse' => 'Excusé',
        'termine' => 'Correction terminée',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function epreuve(): BelongsTo
    {
        return $this->belongsTo(ExamenEpreuve::class, 'epreuve_id');
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ExamenCentre::class, 'centre_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ExamenNote::class, 'correcteur_id');
    }

    public function getLibelleRoleAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getLibelleStatutAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getNomAfficheAttribute(): string
    {
        return $this->nom
            ?: trim(($this->teacher->first_name ?? '').' '.($this->teacher->last_name ?? ''))
            ?: ($this->user->name ?? 'Correcteur');
    }
}
