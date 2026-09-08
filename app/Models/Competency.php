<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'subject_area',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relations
    public function criteria(): HasMany
    {
        return $this->hasMany(CompetencyCriteria::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(StudentCompetencyEvaluation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySubjectArea($query, $subjectArea)
    {
        return $query->where('subject_area', $subjectArea);
    }

    // Accesseurs
    public function getFormattedNameAttribute()
    {
        return $this->code . ' - ' . $this->name;
    }

    // Méthodes utilitaires
    public function getTotalMaxPoints()
    {
        return $this->criteria()->sum('max_points');
    }

    public function getActiveCriteria()
    {
        return $this->criteria()->where('is_active', true)->orderBy('sort_order');
    }
}
