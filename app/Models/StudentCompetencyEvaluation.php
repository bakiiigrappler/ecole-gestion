<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCompetencyEvaluation extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'student_id',
        'competency_id',
        'class_id',
        'academic_year_id',
        'teacher_id',
        'palier',
        'evaluation_date',
        'c1_points',
        'c2_points',
        'c3_points',
        'c4_points',
        'c1_max_points',
        'c2_max_points',
        'c3_max_points',
        'c4_max_points',
        'total_points_obtained',
        'total_points_max',
        'competency_mastery',
        'subject_mastery',
        'palier_mastery',
        'is_exit_profile',
        'exit_profile',
        'comments'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'is_exit_profile' => 'boolean'
    ];

    // Relations
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    // Scopes
    public function scopeByPalier($query, $palier)
    {
        return $query->where('palier', $palier);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByCompetency($query, $competencyId)
    {
        return $query->where('competency_id', $competencyId);
    }

    public function scopeExitProfile($query)
    {
        return $query->where('is_exit_profile', true);
    }

    public function scopeRegularEvaluation($query)
    {
        return $query->where('is_exit_profile', false);
    }

    // Accesseurs
    public function getTotalPointsObtainedAttribute()
    {
        return $this->c1_points + $this->c2_points + $this->c3_points + $this->c4_points;
    }

    public function getTotalPointsMaxAttribute()
    {
        return $this->c1_max_points + $this->c2_max_points + $this->c3_max_points + $this->c4_max_points;
    }

    public function getPercentageAttribute()
    {
        if ($this->total_points_max > 0) {
            return round(($this->total_points_obtained / $this->total_points_max) * 100, 1);
        }
        return 0;
    }

    public function getCompetencyMasteryLevelAttribute()
    {
        return $this->calculateMasteryLevel($this->percentage);
    }

    // Méthodes utilitaires
    public function calculateMasteryLevel($percentage)
    {
        if ($percentage >= 90) return 'maximale';
        if ($percentage >= 75) return 'minimale';
        if ($percentage >= 50) return 'partielle';
        return 'non_maitrise';
    }

    public function getMasteryBadgeClass($mastery)
    {
        return match($mastery) {
            'maximale' => 'bg-success',
            'minimale' => 'bg-info',
            'partielle' => 'bg-warning',
            'non_maitrise' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getMasteryText($mastery)
    {
        return match($mastery) {
            'maximale' => 'Maximale',
            'minimale' => 'Minimale',
            'partielle' => 'Partielle',
            'non_maitrise' => 'Non maîtrisée',
            default => 'Non évaluée'
        };
    }

    public function isMaximale()
    {
        return $this->competency_mastery === 'maximale';
    }

    public function isMinimale()
    {
        return $this->competency_mastery === 'minimale';
    }

    public function isPartielle()
    {
        return $this->competency_mastery === 'partielle';
    }

    public function isNonMaitrise()
    {
        return $this->competency_mastery === 'non_maitrise';
    }
}
