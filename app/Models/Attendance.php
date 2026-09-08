<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'student_id',
        'class_id',
        'attendance_date',
        'time_slot',
        'status',
        'arrival_time',
        'reason',
        'justified'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'arrival_time' => 'datetime:H:i',
        'justified' => 'boolean'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec la classe
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Scope pour filtrer par date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * Scope pour filtrer par classe
     */
    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer par créneau horaire
     */
    public function scopeForTimeSlot($query, $timeSlot)
    {
        return $query->where('time_slot', $timeSlot);
    }

    /**
     * Scope pour la semaine courante
     */
    public function scopeCurrentWeek($query)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        return $query->whereBetween('attendance_date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope pour le mois courant
     */
    public function scopeCurrentMonth($query)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        return $query->whereBetween('attendance_date', [$startOfMonth, $endOfMonth]);
    }

    /**
     * Scope pour l'année académique
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        $academicYear = AcademicYear::find($academicYearId);
        if ($academicYear) {
            return $query->whereBetween('attendance_date', [
                $academicYear->start_date,
                $academicYear->end_date
            ]);
        }
        return $query;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'En retard',
            'excused' => 'Excusé',
            default => 'Inconnu'
        };
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'excused' => 'info',
            default => 'secondary'
        };
    }
}
