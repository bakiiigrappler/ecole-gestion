<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'code',
        'name',
        'description',
        'level_id',
        'is_active',
        'order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    // Relation avec les classes
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'series_id');
    }

    // Niveau du lycee auquel la serie appartient
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    /**
     * Le niveau s'ecrivait en clair dans une colonne texte devant correspondre
     * exactement a `levels.name`. Les vues l'affichent toujours ainsi : cet
     * accesseur le lit desormais dans la relation.
     */
    public function getLevelAttribute()
    {
        return $this->niveau?->name;
    }

    // Scope pour les séries actives
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour filtrer par niveau, par identifiant ou par nom
    public function scopeByLevel($query, $level)
    {
        if (is_numeric($level)) {
            return $query->where('level_id', $level);
        }

        return $query->whereHas('niveau', fn ($q) => $q->where('name', $level));
    }

    // Accesseur pour le nom complet avec niveau
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . $this->level . ')';
    }

    /** Prefixe de code par niveau, tel qu'employe dans le referentiel. */
    private const PREFIXES_NIVEAU = [
        '2nde' => '2NDE',
        '1ère' => '1ERE',
        'Terminale' => 'TERM',
    ];

    /**
     * Lettres du systeme gabonais, dans l'ordre ou on les ouvre habituellement.
     * Sert a proposer la premiere encore libre sur un niveau.
     */
    private const LETTRES = ['S', 'A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3', 'LE'];

    /** Lettre de serie portee par un code : c'est le suffixe apres le tiret. */
    public static function lettreDuCode(?string $code): string
    {
        $code = (string) $code;

        return str_contains($code, '-') ? substr($code, strpos($code, '-') + 1) : $code;
    }

    /** Premiere lettre encore disponible sur un niveau. */
    public static function prochaineLettre(?string $niveau): ?string
    {
        if (! $niveau) {
            return null;
        }

        $prises = self::byLevel($niveau)->pluck('code')
            ->map(fn ($c) => mb_strtoupper(self::lettreDuCode($c)))
            ->all();

        foreach (self::LETTRES as $lettre) {
            if (! in_array($lettre, $prises, true)) {
                return $lettre;
            }
        }

        return null;
    }

    /**
     * Code propose pour une serie. Il se compose du prefixe du niveau et de la
     * lettre de serie ; sans lettre fournie, la premiere libre du niveau est
     * retenue. Un suffixe numerique est ajoute si le code existe deja.
     */
    public static function genererCode($niveau, ?string $lettre = null, ?int $ignorerId = null): string
    {
        $nomDuNiveau = is_numeric($niveau) ? Level::find($niveau)?->name : $niveau;
        $lettre = mb_strtoupper(trim((string) ($lettre ?: self::prochaineLettre($niveau))));
        $lettre = preg_replace('/[^A-Z0-9]/', '', $lettre);

        if ($lettre === '') {
            return '';
        }

        $prefixe = self::PREFIXES_NIVEAU[$nomDuNiveau] ?? null;
        $candidat = mb_substr($prefixe ? $prefixe.'-'.$lettre : $lettre, 0, 10);

        $existe = fn ($code) => self::where('code', $code)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();

        if (! $existe($candidat)) {
            return $candidat;
        }

        for ($n = 2; $n <= 9; $n++) {
            $essai = mb_substr($candidat, 0, 8).'-'.$n;
            if (! $existe($essai)) {
                return $essai;
            }
        }

        return $candidat;
    }
}
