<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'enrollment_id',
        'enrollment_fee_id',
        'parent_id',
        'student_id',
        'amount',
        'currency',
        'payment_type',
        'payment_method',
        'payment_gateway_id',
        'status',
        'payer_name',
        'payer_phone',
        'payer_email',
        'gateway_transaction_id',
        'gateway_response',
        'paid_at',
        'ip_address',
        'user_agent',
        'metadata',
        'notes',
        'receipt_number'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    /**
     * Relation avec l'inscription
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Relation avec l'étudiant (via l'inscription)
     */
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            Enrollment::class,
            'id', // Clé étrangère sur enrollments
            'id', // Clé primaire sur students
            'enrollment_id', // Clé étrangère sur payments
            'student_id' // Clé étrangère sur enrollments
        );
    }

    /**
     * Relation avec la classe (via l'inscription)
     */
    public function schoolClass()
    {
        return $this->hasOneThrough(
            SchoolClass::class,
            Enrollment::class,
            'id', // Clé étrangère sur enrollments
            'id', // Clé primaire sur classes
            'enrollment_id', // Clé étrangère sur payments
            'class_id' // Clé étrangère sur enrollments
        );
    }

    /**
     * Relation avec l'année académique (via l'inscription)
     */
    public function academicYear()
    {
        return $this->hasOneThrough(
            AcademicYear::class,
            Enrollment::class,
            'id', // Clé étrangère sur enrollments
            'id', // Clé primaire sur academic_years
            'enrollment_id', // Clé étrangère sur payments
            'academic_year_id' // Clé étrangère sur enrollments
        );
    }
}
