<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnrollmentFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'level_fee_id',
        'class_fee_id',
        'academic_year_id',
        'fee_type',
        'name',
        'description',
        'amount',
        'frequency',
        'due_date',
        'is_mandatory',
        'is_paid',
        'paid_at',
        'payment_method',
        'payment_reference',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_paid' => 'boolean'
    ];

    /**
     * Relation avec l'inscription
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Relation avec le frais de niveau
     */
    public function levelFee(): BelongsTo
    {
        return $this->belongsTo(LevelFee::class);
    }

    /**
     * Relation avec le frais de classe
     */
    public function classFee(): BelongsTo
    {
        return $this->belongsTo(ClassFee::class);
    }

    /**
     * Relation avec l'année académique
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relation avec les paiements
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'enrollment_fee_id');
    }

    /**
     * Scope pour les frais payés
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope pour les frais non payés
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
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
     * Scope par fréquence
     */
    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope pour les frais échus
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('is_paid', false);
    }

    /**
     * Accessor pour le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
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
     * Accessor pour le statut de paiement
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->is_paid) {
            return 'Payé';
        }
        
        if ($this->due_date && $this->due_date < now()) {
            return 'Échu';
        }
        
        return 'En attente';
    }

    /**
     * Accessor pour la classe CSS du statut de paiement
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        if ($this->is_paid) {
            return 'bg-success';
        }
        
        if ($this->due_date && $this->due_date < now()) {
            return 'bg-danger';
        }
        
        return 'bg-warning';
    }

    /**
     * Accessor pour le montant payé
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Accessor pour le montant restant
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    /**
     * Accessor pour le montant restant formaté
     */
    public function getFormattedRemainingAmountAttribute(): string
    {
        return number_format($this->remaining_amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Accessor pour le pourcentage payé
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ($this->amount == 0) {
            return 0;
        }
        
        return ($this->paid_amount / $this->amount) * 100;
    }

    /**
     * Méthode pour marquer comme payé
     */
    public function markAsPaid($paymentMethod = null, $paymentReference = null)
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference
        ]);
    }

    /**
     * Méthode pour marquer comme non payé
     */
    public function markAsUnpaid()
    {
        $this->update([
            'is_paid' => false,
            'paid_at' => null,
            'payment_method' => null,
            'payment_reference' => null
        ]);
    }

    /**
     * Méthode pour vérifier si le frais est échu
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date < now() && !$this->is_paid;
    }

    /**
     * Accessor pour vérifier si le frais est échu (compatible avec les vues)
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    /**
     * Méthode pour vérifier si le frais est échu (compatible avec les vues)
     */
    public function is_overdue(): bool
    {
        return $this->is_overdue;
    }

    /**
     * Méthode pour obtenir le nombre de jours de retard
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        return now()->diffInDays($this->due_date);
    }
}
