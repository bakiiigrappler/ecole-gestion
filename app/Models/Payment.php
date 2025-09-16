<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

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

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function enrollmentFee(): BelongsTo
    {
        return $this->belongsTo(EnrollmentFee::class);
    }

    // Générer un ID de transaction unique
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN' . date('Ymd') . strtoupper(Str::random(8));
        } while (self::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    // Générer un numéro de reçu
    public function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'RCP' . date('Ymd') . strtoupper(Str::random(6));
        } while (self::where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
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
            'refunded' => 'Remboursé',
            'partially_refunded' => 'Partiellement remboursé',
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
            'refunded' => 'bg-dark',
            'partially_refunded' => 'bg-warning',
            default => 'bg-secondary'
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces',
            'check' => 'Chèque',
            default => 'Inconnu'
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'tuition' => 'Frais de scolarité',
            'transport' => 'Transport',
            'canteen' => 'Cantine',
            'uniform' => 'Uniforme',
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

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByEnrollment($query, $enrollmentId)
    {
        return $query->where('enrollment_id', $enrollmentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Méthodes utilitaires
    public function markAsCompleted($gatewayTransactionId = null): bool
    {
        $updateData = [
            'status' => 'completed',
            'paid_at' => now()
        ];

        if ($gatewayTransactionId) {
            $updateData['gateway_transaction_id'] = $gatewayTransactionId;
        }

        if (!$this->receipt_number) {
            $updateData['receipt_number'] = $this->generateReceiptNumber();
        }

        return $this->update($updateData);
    }

    public function markAsFailed($reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'notes' => $reason ? $this->notes . "\nÉchec: " . $reason : $this->notes
        ]);
    }

    public function markAsCancelled($reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'notes' => $reason ? $this->notes . "\nAnnulé: " . $reason : $this->notes
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

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    // Méthode pour obtenir le montant formaté
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . ($this->currency ?? 'FCFA');
    }

    // Méthode pour calculer le montant total des remboursements
    public function getTotalRefundedAmountAttribute(): float
    {
        return $this->refunds()->sum('amount');
    }

    // Méthode pour vérifier si le paiement peut être remboursé
    public function canBeRefunded(): bool
    {
        return $this->isCompleted() && !$this->isRefunded();
    }

    // Méthode pour obtenir le montant remboursable
    public function getRefundableAmountAttribute(): float
    {
        return $this->amount - $this->total_refunded_amount;
    }
}