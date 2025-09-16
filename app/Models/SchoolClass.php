<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolClass extends Model
{
    protected $table = 'classes';
    
    protected $fillable = [
        'name',
        'description',
        'capacity',
        'is_active',
        'level_id',
        'series'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relation avec le niveau
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
    
    // Alias pour éviter le conflit avec la colonne level
    public function levelData(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    // Relation avec les horaires
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    // Relation avec les étudiants via les inscriptions
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
                    ->withPivot('academic_year_id', 'enrollment_date', 'status')
                    ->withTimestamps();
    }

    // Relation avec les professeurs généralistes assignés
    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'assigned_class_id');
    }

    // Relation many-to-many avec tous les professeurs de la classe
    public function allTeachers()
    {
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    // Relation avec les présences
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    // Relation avec les inscriptions
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    // Relation avec les notes
    public function grades()
    {
        return $this->hasMany(Grade::class, 'class_id');
    }

    // Accesseur pour le nom complet avec niveau
    public function getFullNameAttribute()
    {
        $levelName = $this->levelData ? $this->levelData->name : '';
        return $this->name . ' - ' . $levelName;
    }

    // Scope pour les classes actives
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope pour filtrer par niveau
    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    // Scope pour filtrer par cycle
    public function scopeByCycle($query, $cycle)
    {
        return $query->whereHas('levelData', function($q) use ($cycle) {
            $q->where('cycle', $cycle);
        });
    }

    /**
     * Relation avec les frais de classe
     */
    public function classFees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassFee::class, 'class_id');
    }

    /**
     * Obtenir le niveau de façon sûre (objet Level ou null)
     */
    public function getSafeLevel()
    {
        // Si level_id existe et qu'on a une relation, retourner la relation
        if ($this->level_id && $this->relationLoaded('levelData') && $this->levelData instanceof Level) {
            return $this->levelData;
        }
        
        // Si level_id existe mais pas de relation chargée, charger le niveau
        if ($this->level_id) {
            return Level::find($this->level_id);
        }
        
        return null;
    }

    /**
     * Obtenir le nom du niveau de façon sûre
     */
    public function getSafeLevelName()
    {
        $safeLevel = $this->getSafeLevel();
        return $safeLevel ? $safeLevel->name : 'Non défini';
    }

    /**
     * Obtenir le cycle de façon sûre
     */
    public function getSafeCycle()
    {
        $safeLevel = $this->getSafeLevel();
        if ($safeLevel) {
            return $safeLevel->cycle;
        }
        
        return 'non-defini';
    }
}
