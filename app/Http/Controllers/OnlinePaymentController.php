<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Enrollment;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnlinePaymentController extends Controller
{
    /**
     * Afficher la page de paiement pour inscription/réinscription
     */
    public function showPaymentForm(Request $request)
    {
        $enrollmentId = $request->get('enrollment_id');
        $studentId = $request->get('student_id');
        $amount = $request->get('amount', 50000);
        $paymentType = $request->get('payment_type', 'enrollment');

        // Récupérer les passerelles disponibles
        $gateways = PaymentGateway::getAvailableForAmount($amount);

        return view('online-payment.form', compact('enrollmentId', 'studentId', 'amount', 'paymentType', 'gateways'));
    }

    /**
     * Traiter la demande de paiement
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_id' => 'nullable|exists:enrollments,id',
            'student_id' => 'nullable|exists:students,id',
            'amount' => 'required|numeric|min:100',
            'payment_type' => 'required|in:enrollment,re_enrollment,fees,other',
            'payment_method' => 'required|in:moov_money,airtel_money',
            'payer_name' => 'required|string|max:255',
            'payer_phone' => 'required|string|max:20',
            'payer_email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier la passerelle
        $gateway = PaymentGateway::byCode($request->payment_method)->active()->first();
        if (!$gateway) {
            return response()->json([
                'success' => false,
                'message' => 'Méthode de paiement non disponible'
            ], 400);
        }

        // Vérifier le montant
        if (!$gateway->isValidAmount($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Montant invalide pour cette méthode de paiement'
            ], 400);
        }

        // Récupérer les informations du parent si disponible
        $parentId = null;
        if ($request->enrollment_id) {
            $enrollment = Enrollment::find($request->enrollment_id);
            if ($enrollment) {
                $parent = ParentModel::where('phone', $request->payer_phone)
                    ->orWhere('email', $request->payer_email)
                    ->first();
                $parentId = $parent ? $parent->id : null;
            }
        }

        // Créer le paiement
        $payment = Payment::create([
            'transaction_id' => Payment::generateTransactionId(),
            'enrollment_id' => $request->enrollment_id,
            'parent_id' => $parentId,
            'student_id' => $request->student_id,
            'amount' => $request->amount,
            'payment_type' => $request->payment_type,
            'payment_method' => $request->payment_method,
            'payer_name' => $request->payer_name,
            'payer_phone' => $request->payer_phone,
            'payer_email' => $request->payer_email,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Calculer les frais
        $fees = $gateway->calculateFees($request->amount);
        $totalAmount = $gateway->getTotalAmount($request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Paiement initié avec succès',
            'data' => [
                'transaction_id' => $payment->transaction_id,
                'amount' => $request->amount,
                'fees' => $fees,
                'total_amount' => $totalAmount,
                'gateway' => [
                    'name' => $gateway->name,
                    'code' => $gateway->code,
                    'logo_url' => $gateway->logo_url,
                    'color' => $gateway->color
                ]
            ]
        ]);
    }

    /**
     * Afficher la page de paiement avec les détails
     */
    public function showPayment($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        $gateway = PaymentGateway::byCode($payment->payment_method)->first();

        if (!$gateway) {
            abort(400, 'Passerelle de paiement non disponible');
        }

        $fees = $gateway->calculateFees($payment->amount);
        $totalAmount = $gateway->getTotalAmount($payment->amount);

        return view('online-payment.payment', compact('payment', 'gateway', 'fees', 'totalAmount'));
    }

    /**
     * Traiter le paiement via la passerelle
     */
    public function processGatewayPayment(Request $request, $transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Paiement déjà traité'
            ], 400);
        }

        // Mettre à jour le statut
        $payment->update([
            'status' => 'processing',
            'gateway_response' => json_encode($request->all())
        ]);

        try {
            // Simuler l'appel à l'API de la passerelle
            $result = $this->callPaymentGateway($payment, $request);
            
            if ($result['success']) {
                $payment->markAsCompleted($result['gateway_transaction_id']);
                
                // Mettre à jour l'inscription si applicable
                if ($payment->enrollment) {
                    $this->updateEnrollmentAfterPayment($payment);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement traité avec succès',
                    'transaction_id' => $payment->transaction_id
                ]);
            } else {
                $payment->markAsFailed($result['message']);
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du paiement', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            $payment->markAsFailed('Erreur technique: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement'
            ], 500);
        }
    }

    /**
     * Callback des passerelles de paiement
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        Log::info("Callback reçu de la passerelle: {$gateway}", $request->all());

        $transactionId = $request->get('transaction_id');
        $status = $request->get('status');
        $gatewayTransactionId = $request->get('gateway_transaction_id');

        $payment = Payment::where('transaction_id', $transactionId)->first();
        
        if (!$payment) {
            Log::error("Paiement non trouvé pour le callback", ['transaction_id' => $transactionId]);
            return response()->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }

        // Traiter le statut
        switch ($status) {
            case 'success':
            case 'completed':
                $payment->markAsCompleted($gatewayTransactionId);
                if ($payment->enrollment) {
                    $this->updateEnrollmentAfterPayment($payment);
                }
                break;
                
            case 'failed':
            case 'cancelled':
                $payment->markAsFailed($request->get('reason', 'Paiement annulé'));
                break;
                
            default:
                Log::warning("Statut inconnu reçu", ['status' => $status, 'transaction_id' => $transactionId]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        
        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->formatted_amount,
                'payment_method' => $payment->payment_method_label,
                'paid_at' => $payment->paid_at,
                'gateway_transaction_id' => $payment->gateway_transaction_id
            ]
        ]);
    }

    /**
     * Page de succès du paiement
     */
    public function paymentSuccess($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        
        if (!$payment->isCompleted()) {
            return redirect()->route('online-payment.failed', $transactionId);
        }

        return view('online-payment.success', compact('payment'));
    }

    /**
     * Page d'échec du paiement
     */
    public function paymentFailed($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        
        return view('online-payment.failed', compact('payment'));
    }

    /**
     * Simuler l'appel à l'API de la passerelle
     */
    private function callPaymentGateway($payment, $request)
    {
        // En production, intégrer avec les vraies APIs
        // Pour l'instant, simulation
        
        $gateway = PaymentGateway::byCode($payment->payment_method)->first();
        
        // Simuler un délai de traitement
        sleep(2);
        
        // Simuler une réponse de succès (90% de succès en test)
        $success = rand(1, 100) <= 90;
        
        if ($success) {
            return [
                'success' => true,
                'gateway_transaction_id' => 'GATEWAY_' . strtoupper(Str::random(8)),
                'message' => 'Paiement traité avec succès'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Paiement refusé par la passerelle'
            ];
        }
    }

    /**
     * Mettre à jour l'inscription après paiement réussi
     */
    private function updateEnrollmentAfterPayment($payment)
    {
        $enrollment = $payment->enrollment;
        
        if ($enrollment) {
            $enrollment->update([
                'enrollment_status' => 'completed',
                'payment_status' => 'completed',
                'amount_paid' => $payment->amount,
                'receipt_number' => 'RCP' . date('Ymd') . strtoupper(Str::random(6))
            ]);

            // Créer l'élève si c'est une nouvelle inscription
            if ($enrollment->is_new_enrollment) {
                $this->createStudentFromEnrollment($enrollment);
            }
        }
    }

    /**
     * Créer un élève à partir d'une inscription
     */
    private function createStudentFromEnrollment($enrollment)
    {
        $student = Student::create([
            'student_id' => Student::generateStudentId(),
            'first_name' => $enrollment->applicant_first_name,
            'last_name' => $enrollment->applicant_last_name,
            'date_of_birth' => $enrollment->applicant_date_of_birth,
            'gender' => $enrollment->applicant_gender,
            'enrollment_date' => now(),
            'status' => 'active'
        ]);

        // Créer ou récupérer le parent
        $parent = ParentModel::firstOrCreate(
            ['phone' => $enrollment->parent_phone],
            [
                'first_name' => $enrollment->parent_first_name,
                'last_name' => $enrollment->parent_last_name,
                'email' => $enrollment->parent_email,
                'is_primary_contact' => true
            ]
        );

        // Lier l'élève au parent
        $student->parents()->attach($parent->id, [
            'relationship_type' => 'guardian',
            'is_primary_contact' => true
        ]);

        // Mettre à jour l'inscription avec l'ID de l'élève
        $enrollment->update(['student_id' => $student->id]);
    }
}
