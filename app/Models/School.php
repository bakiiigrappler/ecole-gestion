<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Un établissement scolaire.
 *
 * C'est l'unité d'isolation de l'application : toute donnée du domaine lui
 * appartient, et le filtrage se fait automatiquement (voir le trait
 * `AppartientAUnEtablissement`). Seul le super administrateur les surplombe.
 */
class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'has_preprimaire',
        'has_primaire',
        'has_college',
        'has_lycee',
        'is_active',
        'city',
        'country',
        'address',
        'phone',
        'email',
        'bp',
        'notes',
    ];

    protected $casts = [
        'has_preprimaire' => 'boolean',
        'has_primaire' => 'boolean',
        'has_college' => 'boolean',
        'has_lycee' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Les quatre cycles, du plus jeune au plus âgé. */
    public const CYCLES = [
        'preprimaire' => 'Préprimaire',
        'primaire' => 'Primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SchoolSettings::class);
    }

    /** Le compte administrateur de l'établissement. */
    public function administrateur(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'admin');
    }

    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Les cycles ouverts, sous forme de clés : ['primaire', 'college'].
     */
    public function cyclesOuverts(): array
    {
        return collect(self::CYCLES)
            ->keys()
            ->filter(fn ($cycle) => (bool) $this->{'has_'.$cycle})
            ->values()
            ->all();
    }

    /**
     * Libellés des cycles ouverts, pour l'affichage.
     */
    public function libellesDesCycles(): array
    {
        return collect($this->cyclesOuverts())
            ->map(fn ($cycle) => self::CYCLES[$cycle])
            ->all();
    }

    public function ouvreLeCycle(string $cycle): bool
    {
        return (bool) ($this->{'has_'.$cycle} ?? false);
    }

    /**
     * Code court proposé pour un nouvel établissement : ETB001, ETB002…
     */
    public static function prochainCode(): string
    {
        $dernier = static::orderByDesc('id')->value('code');
        $numero = $dernier && preg_match('/(\d+)$/', $dernier, $m) ? ((int) $m[1]) + 1 : 1;

        return 'ETB'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }
}
