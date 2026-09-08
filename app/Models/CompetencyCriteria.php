<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyCriteria extends Model
{
    protected $table = 'competency_criteria';

    protected $fillable = [
        'competency_id',
        'code',
        'name',
        'description',
        'max_points',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relations
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accesseurs
    public function getFormattedNameAttribute()
    {
        return $this->code . ' - ' . $this->name;
    }
}
