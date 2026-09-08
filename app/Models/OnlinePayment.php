<?php

namespace App\Models;


use App\Models\Concerns\AppartientAUnEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OnlinePayment extends Model
{
    use AppartientAUnEtablissement;

    protected $fillable = [
        'transaction_id',
        'enrollment_id',
        'parent_id',
        'student_id',
        'amount',
        'currency',
        'payment_type',
        'payment_method',
        'payer_name',
        'payer_phone',
        'payer_email',
        'status',
        'gateway_response',
        'gateway_transaction_id',
        'paid_at',
        'ip_address',
        'user_agent',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relations
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    // Générer un ID de transaction unique
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN' . date('Ymd') . strtoupper(Str::random(8));
        } while (self::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    // Accesseurs
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => 'Inconnu'
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            default => 'Inconnu'
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'fees' => 'Frais scolaires',
            'other' => 'Autre',
            default => 'Inconnu'
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // Méthodes utilitaires
    public function markAsCompleted($gatewayTransactionId = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'gateway_transaction_id' => $gatewayTransactionId,
            'paid_at' => now()
        ]);
    }

    public function markAsFailed($reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'notes' => $reason
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Méthode pour obtenir le montant formaté
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }
}
