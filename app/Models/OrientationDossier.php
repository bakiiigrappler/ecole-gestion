<?php

namespace App\Models;

use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Le dossier d'orientation d'un élève, pour une année.
 *
 * Il porte trois choses que personne ne gardait : le profil tel qu'il était au
 * moment du vœu — moyennes figées, car un dossier se relit des années plus tard
 * et les notes, elles, continuent de changer ; les vœux de l'élève, classés ;
 * et la décision motivée de l'établissement.
 */
class OrientationDossier extends Model
{
    use AppartientAUnEtablissement, SoftDeletes;

    protected $table = 'orientation_dossiers';

    protected $fillable = [
        'school_id', 'student_id', 'academic_year_id', 'niveau', 'serie_actuelle',
        'profil', 'moyennes', 'voie', 'filiere', 'mode_admission', 'voeux',
        'statut', 'commentaire_eleve', 'motif_code', 'motif_precision',
        'decide_par', 'decide_le', 'soumis_le', 'decision_vue_le',
    ];

    protected $casts = [
        'moyennes' => 'array',
        'voeux' => 'array',
        'decide_le' => 'datetime',
        'soumis_le' => 'datetime',
        'decision_vue_le' => 'datetime',
    ];

    public const NIVEAUX = [
        'troisieme' => 'Troisième — vers le lycée',
        'terminale' => 'Terminale — vers le supérieur',
    ];

    /*
     * Le vocabulaire du service d'orientation : on accorde un voeu, ou l'on
     * marque son desaccord, motif a l'appui. « Refuse » laisserait croire a une
     * sanction, quand il s'agit d'un avis sur un voeu.
     */
    public const STATUTS = [
        'brouillon' => 'En cours',
        'soumis' => 'En attente',
        'accorde' => 'Accord',
        'refuse' => 'Désaccord',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decide_par');
    }

    public function scopeAEtudier(Builder $requete): Builder
    {
        return $requete->where('statut', 'soumis');
    }

    public function scopeDecides(Builder $requete): Builder
    {
        return $requete->whereIn('statut', ['accorde', 'refuse']);
    }

    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estSoumis(): bool
    {
        return $this->statut === 'soumis';
    }

    public function estDecide(): bool
    {
        return in_array($this->statut, ['accorde', 'refuse'], true);
    }

    public function getLibelleStatutAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /**
     * Les vœux, dans l'ordre, avec l'établissement rattaché.
     *
     * Les vœux sont rangés en JSON plutôt qu'en table : ils ne valent que par
     * le dossier, ne se requêtent jamais seuls, et le jour où un établissement
     * disparaît du répertoire, le vœu doit rester lisible tel qu'il a été fait.
     *
     * @return array<int, array{rang: int, etablissement: ?OrientationEtablissement, nom: string, filiere: ?string}>
     */
    public function voeuxDetailles(): array
    {
        $voeux = collect($this->voeux ?? [])->sortBy('rang')->values();

        if ($voeux->isEmpty()) {
            return [];
        }

        $etablissements = OrientationEtablissement::withTrashed()
            ->whereIn('id', $voeux->pluck('etablissement_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $voeux->map(fn ($voeu, $rang) => [
            'rang' => $voeu['rang'] ?? $rang + 1,
            'etablissement' => $etablissements->get($voeu['etablissement_id'] ?? null),
            'nom' => $voeu['nom'] ?? ($etablissements->get($voeu['etablissement_id'] ?? null)->nom ?? 'Établissement supprimé'),
            'filiere' => $voeu['filiere'] ?? null,
        ])->all();
    }
}
