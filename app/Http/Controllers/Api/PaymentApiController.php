<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentApiController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Liste des paiements avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'payment_method', 'payment_type', 
                'date_from', 'date_to', 'enrollment_id', 'student_id', 'parent_id'
            ]);

            $perPage = $request->get('per_page', 15);
            $payments = $this->paymentService->getPayments($filters, $perPage);
            $stats = $this->paymentService->getPaymentStats($filters);

            return response()->json([
                'success' => true,
                'data' => $payments,
                'stats' => $stats,
                'message' => 'Paiements récupérés avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un paiement
     */
    public function show(Payment $payment): JsonResponse
    {
        try {
            $payment->load(['enrollment.student', 'enrollment.schoolClass', 'enrollment.academicYear', 'parent', 'student', 'paymentGateway', 'refunds']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Paiement récupéré avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau paiement
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_type' => 'required|string',
                'payer_name' => 'required|string|max:255',
                'payer_phone' => 'required|string|max:20',
                'payer_email' => 'nullable|email',
                'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Récupérer l'inscription pour obtenir les informations de l'étudiant
            $enrollment = \App\Models\Enrollment::with(['student', 'parent'])->findOrFail($validated['enrollment_id']);
            
            // Ajouter les informations de l'étudiant et du parent
            $validated['student_id'] = $enrollment->student_id;
            $validated['parent_id'] = $enrollment->parent_id;
            $validated['status'] = 'completed';
            $validated['currency'] = 'FCFA';

            $payment = $this->paymentService->createPayment($validated);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.schoolClass', 'enrollment.academicYear', 'parent', 'student']),
                'message' => 'Paiement créé avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un paiement
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_type' => 'required|string',
                'payer_name' => 'required|string|max:255',
                'payer_phone' => 'required|string|max:20',
                'payer_email' => 'nullable|email',
                'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh(),
                'message' => 'Paiement mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un paiement
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            if ($payment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un paiement terminé'
                ], 400);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finaliser un paiement
     */
    public function complete(Payment $payment): JsonResponse
    {
        try {
            if (!$payment->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les paiements en attente peuvent être finalisés'
                ], 400);
            }

            $this->paymentService->completePayment($payment);

            return response()->json([
                'success' => true,
                'data' => $payment->fresh(),
                'message' => 'Paiement finalisé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Payment $payment): JsonResponse
    {
        try {
            if (!$payment->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les paiements en attente peuvent être annulés'
                ], 400);
            }

            $payment->markAsCancelled('Annulé via API');

            return response()->json([
                'success' => true,
                'data' => $payment->fresh(),
                'message' => 'Paiement annulé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un remboursement
     */
    public function refund(Request $request, Payment $payment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0|max:' . $payment->refundable_amount,
                'reason' => 'required|string|max:1000'
            ]);

            $refund = $this->paymentService->createRefund(
                $payment, 
                $validated['amount'], 
                $validated['reason'], 
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'data' => $refund,
                'message' => 'Remboursement créé avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du remboursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des paiements
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'status', 'payment_method', 'payment_type'
            ]);

            $stats = $this->paymentService->getPaymentStats($filters);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
