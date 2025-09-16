<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_fee_id',
        'class_id',
        'academic_year_id',
        'fee_type',
        'name',
        'description',
        'amount',
        'frequency',
        'due_date',
        'is_mandatory',
        'is_active',
        'sort_order',
        'additional_amount',
        'discount_amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'additional_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'due_date' => 'date',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Relation avec le frais de niveau parent
     */
    public function levelFee(): BelongsTo
    {
        return $this->belongsTo(LevelFee::class);
    }

    /**
     * Relation avec la classe
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Relation avec l'année académique
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relation avec les frais d'inscription
     */
    public function enrollmentFees(): HasMany
    {
        return $this->hasMany(EnrollmentFee::class, 'class_fee_id');
    }

    /**
     * Scope pour les frais actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les frais obligatoires
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope par type de frais
     */
    public function scopeByType($query, $type)
    {
        return $query->where('fee_type', $type);
    }

    /**
     * Scope par classe
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Accessor pour le montant final (avec suppléments et réductions)
     */
    public function getFinalAmountAttribute(): float
    {
        return $this->amount + ($this->additional_amount ?? 0) - ($this->discount_amount ?? 0);
    }

    /**
     * Accessor pour le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->final_amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Accessor pour le type de frais formaté
     */
    public function getFeeTypeLabelAttribute(): string
    {
        return match($this->fee_type) {
            'tuition' => 'Scolarité',
            'registration' => 'Inscription',
            'uniform' => 'Uniforme',
            'transport' => 'Transport',
            'meal' => 'Cantine',
            'books' => 'Livres',
            'activities' => 'Activités',
            'other' => 'Autre',
            default => ucfirst($this->fee_type)
        };
    }

    /**
     * Accessor pour la fréquence formatée
     */
    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            'one_time' => 'Unique',
            default => ucfirst($this->frequency)
        };
    }

    /**
     * Accessor pour le statut formaté
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactif';
        }
        
        if ($this->due_date && $this->due_date < now()) {
            return 'Échu';
        }
        
        return 'Actif';
    }

    /**
     * Accessor pour la classe CSS du statut
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if (!$this->is_active) {
            return 'bg-secondary';
        }
        
        if ($this->due_date && $this->due_date < now()) {
            return 'bg-danger';
        }
        
        return 'bg-success';
    }

    /**
     * Méthode pour obtenir le montant total collecté
     */
    public function getTotalCollectedAttribute(): float
    {
        return $this->enrollmentFees()
            ->whereHas('payments', function($query) {
                $query->where('status', 'completed');
            })
            ->sum('amount');
    }

    /**
     * Méthode pour obtenir le nombre de paiements
     */
    public function getPaymentsCountAttribute(): int
    {
        return $this->enrollmentFees()
            ->whereHas('payments', function($query) {
                $query->where('status', 'completed');
            })
            ->count();
    }

    /**
     * Méthode pour obtenir le nombre d'étudiants éligibles
     */
    public function getEligibleStudentsCountAttribute(): int
    {
        return $this->schoolClass->enrollments()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Méthode pour obtenir le taux de collecte
     */
    public function getCollectionRateAttribute(): float
    {
        $eligibleCount = $this->eligible_students_count;
        if ($eligibleCount === 0) {
            return 0;
        }
        
        return ($this->payments_count / $eligibleCount) * 100;
    }
}
