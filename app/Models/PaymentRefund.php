<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class PaymentRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'refund_id',
        'amount',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'gateway_refund_id',
        'gateway_response',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_response' => 'array'
    ];

    // Relations
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Générer un ID de remboursement unique
    public static function generateRefundId(): string
    {
        do {
            $refundId = 'REF' . date('Ymd') . strtoupper(Str::random(8));
        } while (self::where('refund_id', $refundId)->exists());

        return $refundId;
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

    // Méthodes utilitaires
    public function markAsCompleted($gatewayRefundId = null): bool
    {
        $updateData = [
            'status' => 'completed',
            'processed_at' => now()
        ];

        if ($gatewayRefundId) {
            $updateData['gateway_refund_id'] = $gatewayRefundId;
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
