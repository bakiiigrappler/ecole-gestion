<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'type',
        'title',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'is_active' => 'boolean',
        'day_of_week' => 'integer'
    ];

    // Relations
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Accesseurs et mutateurs
    public function getDayNameAttribute()
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        
        return $days[$this->day_of_week] ?? 'Inconnu';
    }

    public function getTimeSlotAttribute()
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    public function getDurationInMinutesAttribute()
    {
        $start = \Carbon\Carbon::createFromFormat('H:i:s', $this->start_time);
        $end = \Carbon\Carbon::createFromFormat('H:i:s', $this->end_time);
        return $start->diffInMinutes($end);
    }

    public function getDisplayTitleAttribute()
    {
        if ($this->type === 'break') {
            return $this->title ?? 'Pause';
        }
        
        return $this->subject ? $this->subject->name : 'Cours non défini';
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'break' ? 'Pause' : 'Cours';
    }

    public function isCourse()
    {
        return $this->type === 'course';
    }

    public function isBreak()
    {
        return $this->type === 'break';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeCourses($query)
    {
        return $query->where('type', 'course');
    }

    public function scopeBreaks($query)
    {
        return $query->where('type', 'break');
    }

    public function scopeByTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime]);
    }

    // Méthodes utiles
    public function hasConflictWith($schedule)
    {
        // Vérifier si deux créneaux se chevauchent
        return $this->day_of_week === $schedule->day_of_week &&
               $this->class_id === $schedule->class_id &&
               $this->academic_year_id === $schedule->academic_year_id &&
               (
                   ($this->start_time < $schedule->end_time && $this->end_time > $schedule->start_time)
               );
    }

    public function teacherHasConflict($schedule)
    {
        // Vérifier si l'enseignant a un conflit d'horaire
        return $this->day_of_week === $schedule->day_of_week &&
               $this->teacher_id === $schedule->teacher_id &&
               $this->academic_year_id === $schedule->academic_year_id &&
               (
                   ($this->start_time < $schedule->end_time && $this->end_time > $schedule->start_time)
               );
    }

    // Validation des créneaux
    public static function validateTimeSlot($classId, $teacherId, $academicYearId, $dayOfWeek, $startTime, $endTime, $excludeId = null)
    {
        $query = static::where('academic_year_id', $academicYearId)
                      ->where('day_of_week', $dayOfWeek)
                      ->where('is_active', true)
                      ->where(function($q) use ($startTime, $endTime) {
                          $q->where(function($subQ) use ($startTime, $endTime) {
                              $subQ->where('start_time', '<', $endTime)
                                   ->where('end_time', '>', $startTime);
                          });
                      });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Vérifier les conflits de classe
        $classConflicts = (clone $query)->where('class_id', $classId)->exists();
        
        // Vérifier les conflits d'enseignant
        $teacherConflicts = (clone $query)->where('teacher_id', $teacherId)->exists();

        return [
            'class_conflict' => $classConflicts,
            'teacher_conflict' => $teacherConflicts,
            'has_conflict' => $classConflicts || $teacherConflicts
        ];
    }
}