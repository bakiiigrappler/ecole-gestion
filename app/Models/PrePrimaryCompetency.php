<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrePrimaryCompetency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'domain',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Relation: une compétence peut avoir plusieurs évaluations
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(PrePrimaryCompetencyEvaluation::class);
    }

    /**
     * Scope: compétences actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: compétences par domaine
     */
    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Scope: compétences ordonnées
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('domain')->orderBy('sort_order');
    }

    /**
     * Obtenir tous les domaines disponibles
     */
    public static function getDomains()
    {
        return self::active()
            ->select('domain')
            ->distinct()
            ->orderBy('domain')
            ->pluck('domain');
    }
}
