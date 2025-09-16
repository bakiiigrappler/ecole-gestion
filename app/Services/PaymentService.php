<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Enrollment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Créer un nouveau paiement
     */
    public function createPayment(array $data): Payment
    {
        DB::beginTransaction();
        
        try {
            // Générer l'ID de transaction
            $data['transaction_id'] = Payment::generateTransactionId();
            
            // Définir la devise par défaut
            $data['currency'] = $data['currency'] ?? 'FCFA';
            
            // Créer le paiement
            $payment = Payment::create($data);
            
            // Mettre à jour l'inscription si nécessaire
            if ($payment->enrollment_id) {
                $this->updateEnrollmentPaymentStatus($payment);
            }
            
            DB::commit();
            
            Log::info('Paiement créé', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount
            ]);
            
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du paiement', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Traiter un paiement via passerelle
     */
    public function processPayment(Payment $payment, array $gatewayData): Payment
    {
        DB::beginTransaction();
        
        try {
            // Mettre à jour le statut du paiement
            $payment->update([
                'status' => 'processing',
                'gateway_response' => $gatewayData
            ]);
            
            // Simuler le traitement (en production, intégrer avec les vraies APIs)
            $this->simulateGatewayProcessing($payment, $gatewayData);
            
            DB::commit();
            
            return $payment->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $payment->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Marquer un paiement comme terminé
     */
    public function completePayment(Payment $payment, string $gatewayTransactionId = null): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment->markAsCompleted($gatewayTransactionId);
            
            // Mettre à jour l'inscription
            if ($payment->enrollment_id) {
                $this->updateEnrollmentPaymentStatus($payment);
            }
            
            DB::commit();
            
            Log::info('Paiement terminé', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id
            ]);
            
            return $payment->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Créer un remboursement
     */
    public function createRefund(Payment $payment, float $amount, string $reason, int $processedBy = null): PaymentRefund
    {
        if (!$payment->canBeRefunded()) {
            throw new \Exception('Ce paiement ne peut pas être remboursé');
        }

        if ($amount > $payment->refundable_amount) {
            throw new \Exception('Le montant du remboursement dépasse le montant remboursable');
        }

        DB::beginTransaction();
        
        try {
            $refund = PaymentRefund::create([
                'payment_id' => $payment->id,
                'refund_id' => PaymentRefund::generateRefundId(),
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'pending',
                'processed_by' => $processedBy
            ]);
            
            // Mettre à jour le statut du paiement si remboursement total
            if ($amount >= $payment->amount) {
                $payment->update(['status' => 'refunded']);
            } else {
                $payment->update(['status' => 'partially_refunded']);
            }
            
            DB::commit();
            
            Log::info('Remboursement créé', [
                'refund_id' => $refund->refund_id,
                'payment_id' => $payment->id,
                'amount' => $amount
            ]);
            
            return $refund;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtenir les statistiques des paiements
     */
    public function getPaymentStats(array $filters = []): array
    {
        $query = Payment::query();
        
        // Appliquer les filtres
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        if (isset($filters['enrollment_id'])) {
            $query->where('enrollment_id', $filters['enrollment_id']);
        }
        
        $totalRevenue = $query->where('status', 'completed')->sum('amount');
        $totalPayments = $query->count();
        $completedPayments = $query->where('status', 'completed')->count();
        $pendingPayments = $query->where('status', 'pending')->count();
        $failedPayments = $query->where('status', 'failed')->count();
        
        return [
            'total_revenue' => $totalRevenue,
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'failed_payments' => $failedPayments,
            'success_rate' => $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100, 2) : 0
        ];
    }

    /**
     * Obtenir les paiements avec filtres
     */
    public function getPayments(array $filters = [], int $perPage = 15)
    {
        $query = Payment::with(['enrollment.student', 'enrollment.schoolClass', 'enrollment.academicYear', 'parent', 'student', 'paymentGateway']);
        
        // Appliquer les filtres
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('payer_name', 'like', "%{$search}%")
                  ->orWhere('payer_phone', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        if (isset($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['enrollment_id'])) {
            $query->where('enrollment_id', $filters['enrollment_id']);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Mettre à jour le statut de paiement de l'inscription
     */
    private function updateEnrollmentPaymentStatus(Payment $payment): void
    {
        if (!$payment->enrollment) {
            return;
        }

        $enrollment = $payment->enrollment;
        $totalPaid = $enrollment->payments()->where('status', 'completed')->sum('amount');
        $totalFees = $enrollment->total_fees ?? 0;
        
        $paymentStatus = 'pending';
        if ($totalPaid >= $totalFees) {
            $paymentStatus = 'completed';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
        }
        
        $enrollment->update([
            'payment_status' => $paymentStatus,
            'amount_paid' => $totalPaid,
            'balance_due' => max(0, $totalFees - $totalPaid)
        ]);
    }

    /**
     * Simuler le traitement par la passerelle
     */
    private function simulateGatewayProcessing(Payment $payment, array $gatewayData): void
    {
        // Simuler un délai de traitement
        sleep(2);
        
        // Simuler une réponse de succès (en production, intégrer avec les vraies APIs)
        $gatewayTransactionId = 'GATEWAY_' . strtoupper(uniqid());
        
        $payment->markAsCompleted($gatewayTransactionId);
    }
}
