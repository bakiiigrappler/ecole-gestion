<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Afficher la liste des paiements
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'status', 'payment_method', 'payment_type', 
            'date_from', 'date_to', 'enrollment_id'
        ]);

        $payments = $this->paymentService->getPayments($filters);
        $stats = $this->paymentService->getPaymentStats($filters);

        // Données pour les filtres
        $classes = SchoolClass::orderBy('name')->get();
        $students = Student::orderBy('first_name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $enrollments = Enrollment::with(['student', 'schoolClass', 'academicYear'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentMethods = [
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces',
            'check' => 'Chèque'
        ];

        $paymentTypes = [
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'tuition' => 'Frais de scolarité',
            'transport' => 'Transport',
            'canteen' => 'Cantine',
            'uniform' => 'Uniforme',
            'other' => 'Autre'
        ];

        $statuses = [
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            'partially_refunded' => 'Partiellement remboursé'
        ];

        return view('payments.index', compact(
            'payments', 'stats', 'classes', 'students', 'academicYears',
            'enrollments', 'paymentMethods', 'paymentTypes', 'statuses', 'filters'
        ));
    }


    /**
     * Enregistrer un nouveau paiement
     */
    public function store(Request $request)
    {
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

        try {
            // Récupérer l'inscription pour obtenir les informations de l'étudiant
            $enrollment = Enrollment::with(['student'])->findOrFail($validated['enrollment_id']);
            
            // Ajouter les informations de l'étudiant et du parent
            $validated['student_id'] = $enrollment->student_id;
            $validated['parent_id'] = $enrollment->parent_id; // Peut être null si pas de parent associé
            $validated['status'] = 'completed'; // Paiement manuel = terminé directement
            $validated['currency'] = 'FCFA';

            $payment = $this->paymentService->createPayment($validated);

            return redirect()->route('payments.index')
                ->with('success', 'Paiement enregistré avec succès.');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Afficher un paiement
     */
    public function show(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.schoolClass', 'enrollment.academicYear', 'parent', 'student', 'paymentGateway', 'refunds']);
        
        // Si c'est une requête AJAX, retourner seulement le contenu du modal
        if (request()->ajax()) {
            return view('payments.modal-content', compact('payment'))->render();
        }
        
        return view('payments.show', compact('payment'));
    }

    /**
     * Afficher le reçu de paiement
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['enrollment.student', 'enrollment.schoolClass', 'enrollment.academicYear', 'parent', 'student', 'paymentGateway', 'refunds']);
        
        return view('payments.receipt', compact('payment'));
    }

    /**
     * Afficher le formulaire d'édition dans un modal
     */
    public function editModal(Payment $payment)
    {
        $enrollments = Enrollment::with(['student', 'schoolClass', 'academicYear'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentMethods = [
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces',
            'check' => 'Chèque'
        ];

        $paymentTypes = [
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'fees' => 'Frais de scolarité',
            'transport' => 'Transport',
            'cantine' => 'Cantine',
            'uniform' => 'Uniforme',
            'books' => 'Livres',
            'other' => 'Autre'
        ];

        $statuses = [
            'pending' => 'En attente',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé'
        ];

        // Si c'est une requête AJAX, retourner seulement le contenu du modal
        if (request()->ajax()) {
            return view('payments.modal-edit', compact('payment', 'enrollments', 'paymentMethods', 'paymentTypes', 'statuses'))->render();
        }
        
        return view('payments.edit', compact('payment', 'enrollments', 'paymentMethods', 'paymentTypes', 'statuses'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Payment $payment)
    {
        $enrollments = Enrollment::with(['student', 'schoolClass', 'academicYear'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentMethods = [
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces',
            'check' => 'Chèque'
        ];

        $paymentTypes = [
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'tuition' => 'Frais de scolarité',
            'transport' => 'Transport',
            'canteen' => 'Cantine',
            'uniform' => 'Uniforme',
            'other' => 'Autre'
        ];

        $gateways = PaymentGateway::active()->get();

        return view('payments.edit', compact('payment', 'enrollments', 'paymentMethods', 'paymentTypes', 'gateways'));
    }

    /**
     * Mettre à jour un paiement
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_type' => 'required|string',
            'payer_name' => 'required|string|max:255',
            'payer_phone' => 'required|string|max:20',
            'payer_email' => 'nullable|email',
            'status' => 'required|string|in:pending,completed,failed,cancelled,refunded',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Récupérer l'inscription pour obtenir les informations de l'étudiant
            $enrollment = Enrollment::with(['student'])->findOrFail($validated['enrollment_id']);
            
            // Ajouter les informations de l'étudiant et du parent
            $validated['student_id'] = $enrollment->student_id;
            $validated['parent_id'] = $enrollment->parent_id;
            $validated['currency'] = 'FCFA';

            $payment->update($validated);

            // Si c'est une requête AJAX, retourner une réponse JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement mis à jour avec succès.',
                    'payment' => $payment->fresh()
                ]);
            }

            return redirect()->route('payments.show', $payment)
                ->with('success', 'Paiement mis à jour avec succès.');

        } catch (\Exception $e) {
            // Si c'est une requête AJAX, retourner une réponse JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du paiement: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un paiement
     */
    public function destroy(Payment $payment)
    {
        try {
            // Vérifier si le paiement peut être supprimé
            if ($payment->isCompleted()) {
                return back()->with('error', 'Impossible de supprimer un paiement terminé.');
            }

            $payment->delete();

            return redirect()->route('payments.index')
                ->with('success', 'Paiement supprimé avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Annuler un paiement
     */
    public function cancel(Payment $payment)
    {
        try {
            if (!$payment->isPending()) {
                return back()->with('error', 'Seuls les paiements en attente peuvent être annulés.');
            }

            $payment->markAsCancelled('Annulé par l\'administrateur');

            return back()->with('success', 'Paiement annulé avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'annulation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Marquer un paiement comme terminé
     */
    public function complete(Payment $payment)
    {
        try {
            if (!$payment->isPending()) {
                return back()->with('error', 'Seuls les paiements en attente peuvent être marqués comme terminés.');
            }

            $this->paymentService->completePayment($payment);

            return back()->with('success', 'Paiement marqué comme terminé.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la finalisation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Exporter les paiements
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'search', 'status', 'payment_method', 'payment_type', 
            'date_from', 'date_to', 'enrollment_id'
        ]);

        $payments = $this->paymentService->getPayments($filters, 1000); // Limite pour l'export

        // Ici vous pouvez implémenter l'export Excel/CSV
        return back()->with('info', 'Fonction d\'export en cours de développement.');
    }

    /**
     * Créer un remboursement
     */
    public function refund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->refundable_amount,
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $refund = $this->paymentService->createRefund(
                $payment,
                $validated['amount'],
                $validated['reason'],
                auth()->id()
            );

            return back()->with('success', 'Remboursement créé avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création du remboursement: ' . $e->getMessage());
        }
    }
}