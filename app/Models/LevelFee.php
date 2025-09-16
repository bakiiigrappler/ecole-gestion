<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LevelFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'academic_year_id',
        'fee_type',
        'name',
        'description',
        'amount',
        'frequency',
        'due_date',
        'is_mandatory',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Relation avec le niveau
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Relation avec l'année académique
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relation avec les frais de classe dérivés
     */
    public function classFees(): HasMany
    {
        return $this->hasMany(ClassFee::class, 'level_fee_id');
    }

    /**
     * Relation avec les frais d'inscription
     */
    public function enrollmentFees(): HasMany
    {
        return $this->hasMany(EnrollmentFee::class, 'level_fee_id');
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
     * Scope par fréquence
     */
    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope par niveau
     */
    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
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
     * Méthode pour créer des frais de classe à partir de ce frais de niveau
     */
    public function createClassFees()
    {
        $classes = SchoolClass::where('level_id', $this->level_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->get();

        foreach ($classes as $class) {
            ClassFee::create([
                'level_fee_id' => $this->id,
                'class_id' => $class->id,
                'academic_year_id' => $this->academic_year_id,
                'fee_type' => $this->fee_type,
                'name' => $this->name . ' - ' . $class->name,
                'description' => $this->description,
                'amount' => $this->amount,
                'frequency' => $this->frequency,
                'due_date' => $this->due_date,
                'is_mandatory' => $this->is_mandatory,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order
            ]);
        }
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
}
