<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Level extends Model
{
    protected $fillable = [
        'name',
        'code',
        'cycle', // 'preprimaire', 'primaire', 'college', 'lycee'
        'order',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    // Relation avec les classes
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    // Relation avec les matières
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    // Relation avec les inscriptions (via les classes)
    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(Enrollment::class, SchoolClass::class, 'level_id', 'class_id');
    }

    // Scope pour les niveaux actifs
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour filtrer par cycle
    public function scopeByCycle($query, $cycle)
    {
        return $query->where('cycle', $cycle);
    }

    // Accesseur pour le nom complet avec cycle
    public function getFullNameAttribute()
    {
        return $this->name . ' (' . ucfirst($this->cycle) . ')';
    }

    // Méthode pour obtenir les niveaux du préprimaire
    public static function getPreprimaireLevels()
    {
        return self::where('cycle', 'preprimaire')->active()->orderBy('order')->get();
    }

    // Méthode pour obtenir les niveaux du primaire
    public static function getPrimaireLevels()
    {
        return self::where('cycle', 'primaire')->active()->orderBy('order')->get();
    }

    // Méthode pour obtenir les niveaux du collège
    public static function getCollegeLevels()
    {
        return self::where('cycle', 'college')->active()->orderBy('order')->get();
    }

    // Méthode pour obtenir les niveaux du lycée
    public static function getLyceeLevels()
    {
        return self::where('cycle', 'lycee')->active()->orderBy('order')->get();
    }

    /**
     * Relation avec les frais de niveau
     */
    public function levelFees(): HasMany
    {
        return $this->hasMany(LevelFee::class);
    }
}
