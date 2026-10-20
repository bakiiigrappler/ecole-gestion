<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un candidat à une session d'examen.
 *
 * Il porte deux numéros, et la distinction est tout le sujet. Le **numéro de
 * candidat** l'identifie publiquement : c'est celui qu'il saisit pour consulter
 * son résultat, celui qui figure sur sa convocation. Le **numéro d'anonymat**
 * n'existe que le temps de la correction : il est le seul lien entre la copie
 * et lui, et le correcteur ne voit que celui-là. Une application qui montrerait
 * le nom au correcteur rendrait toute la session contestable.
 *
 * Un candidat n'est pas forcément un élève de la plateforme : le candidat libre
 * existe, et sa fiche ne tient qu'à ce qu'il a déclaré.
 */
class ExamenCandidat extends Model
{
    use SoftDeletes;

    protected $table = 'examen_candidats';

    protected $fillable = [
        'examen_id', 'student_id', 'school_id', 'centre_id',
        'numero_candidat', 'numero_anonymat',
        'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'sexe',
        'serie', 'etablissement_origine', 'statut',
        'moyenne', 'total_points', 'mention', 'observation',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'moyenne' => 'float',
        'total_points' => 'float',
    ];

    public const STATUTS = [
        'inscrit' => 'Inscrit',
        'present' => 'Présent',
        'absent' => 'Absent',
        'exclu' => 'Exclu',
        'admis' => 'Admis',
        'second_tour' => 'Second tour',
        'ajourne' => 'Ajourné',
    ];

    /**
     * Les mentions, telles qu'elles se disent au Gabon.
     */
    public const MENTIONS = [
        16 => 'Très bien',
        14 => 'Bien',
        12 => 'Assez bien',
        10 => 'Passable',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(ExamenCentre::class, 'centre_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ExamenNote::class, 'candidat_id');
    }

    public function scopeDeLEtablissement(Builder $requete, ?int $ecole): Builder
    {
        return $ecole ? $requete->where('school_id', $ecole) : $requete;
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom.' '.$this->prenom);
    }

    public function getLibelleStatutAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function estDecide(): bool
    {
        return in_array($this->statut, ['admis', 'second_tour', 'ajourne'], true);
    }

    /**
     * La mention qui revient à une moyenne.
     *
     * Rien en dessous de 10 : on ne mentionne pas un échec.
     */
    public static function mentionPour(?float $moyenne): ?string
    {
        if ($moyenne === null) {
            return null;
        }

        foreach (self::MENTIONS as $seuil => $mention) {
            if ($moyenne >= $seuil) {
                return $mention;
            }
        }

        return null;
    }
}
