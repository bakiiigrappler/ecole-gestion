<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentGrade extends Model
{
    use AppartientAUnEtablissement, HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'academic_year_id',
        'teacher_id',
        'term',
        'score',
        'max_score',
        'comments'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2'
    ];

    // Relations
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
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
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByTerm($query, $term)
    {
        return $query->where('term', $term);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Accesseurs
    public function getFormattedScoreAttribute()
    {
        return $this->score . '/' . $this->max_score;
    }

    public function getPercentageAttribute()
    {
        if ($this->max_score > 0) {
            return round(($this->score / $this->max_score) * 100, 1);
        }
        return 0;
    }

    public function getGradeLevelAttribute()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 80) return 'Excellent';
        if ($percentage >= 70) return 'Très bien';
        if ($percentage >= 60) return 'Bien';
        if ($percentage >= 50) return 'Assez bien';
        if ($percentage >= 40) return 'Passable';
        return 'Insuffisant';
    }

    public function getGradeColorAttribute()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 80) return 'success';
        if ($percentage >= 70) return 'info';
        if ($percentage >= 60) return 'primary';
        if ($percentage >= 50) return 'warning';
        if ($percentage >= 40) return 'secondary';
        return 'danger';
    }

    // Méthodes utilitaires
    public function isExcellent()
    {
        return $this->percentage >= 80;
    }

    public function isPassing()
    {
        return $this->percentage >= 50;
    }

    public function needsImprovement()
    {
        return $this->percentage < 50;
    }
}
