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
        // La liste porte sur les transactions de la table `payments` : ce sont
        // elles que `show`, `edit` et `receipt` manipulent. Elle affichait des
        // inscriptions, si bien qu'aucune action de la ligne ne visait l'objet
        // affiche, et que les statuts propres a l'inscription (partial,
        // overdue) tombaient tous en « Inconnu ».
        $requete = Payment::query()
            ->with(['student:id,first_name,last_name,student_id', 'enrollment.schoolClass:id,name']);

        if ($terme = trim((string) $request->input('search'))) {
            $motif = '%' . mb_strtolower($terme) . '%';

            $requete->where(function ($q) use ($motif) {
                $q->whereRaw('LOWER(transaction_id) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(payer_name) LIKE ?', [$motif])
                  ->orWhereRaw('LOWER(payer_phone) LIKE ?', [$motif])
                  ->orWhereHas('student', fn ($s) => $s
                      ->whereRaw('LOWER(first_name) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(last_name) LIKE ?', [$motif])
                      ->orWhereRaw('LOWER(student_id) LIKE ?', [$motif]));
            });
        }

        foreach (['status', 'payment_method', 'payment_type'] as $champ) {
            if ($valeur = $request->input($champ)) {
                $requete->where($champ, $valeur);
            }
        }

        if ($classeId = $request->input('class_id')) {
            $requete->whereHas('enrollment', fn ($q) => $q->where('class_id', $classeId));
        }

        if ($depuis = $request->input('date_from')) {
            $requete->whereDate('created_at', '>=', $depuis);
        }

        if ($jusqua = $request->input('date_to')) {
            $requete->whereDate('created_at', '<=', $jusqua);
        }

        // Les chiffres portent sur le resultat filtre, pas sur la page.
        $filtre = (clone $requete);

        $bilan = [
            'transactions' => (clone $filtre)->count(),
            'encaisse' => (clone $filtre)->where('status', 'completed')->sum('amount'),
            'en_attente' => (clone $filtre)->whereIn('status', ['pending', 'processing'])->count(),
            'echouees' => (clone $filtre)->whereIn('status', ['failed', 'cancelled'])->count(),
        ];

        $payments = $requete->latest('created_at')->paginate(10)->withQueryString();

        // Ce qu'il reste a recouvrer ne se lit pas dans les transactions mais
        // dans les inscriptions : c'est la seule source du montant du.
        $inscriptions = Enrollment::where('status', 'active');
        $recouvrement = [
            'du' => (clone $inscriptions)->sum('total_fees'),
            'paye' => (clone $inscriptions)->sum('amount_paid'),
            'en_retard' => (clone $inscriptions)->whereIn('payment_status', ['pending', 'partial'])->count(),
        ];
        $recouvrement['reste'] = max(0, $recouvrement['du'] - $recouvrement['paye']);

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $paymentMethods = [
            'moov_money' => 'Moov Money',
            'airtel_money' => 'Airtel Money',
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces',
            'check' => 'Chèque',
        ];

        $paymentTypes = [
            'enrollment' => 'Inscription',
            're_enrollment' => 'Réinscription',
            'tuition' => 'Frais de scolarité',
            'transport' => 'Transport',
            'canteen' => 'Cantine',
            'uniform' => 'Uniforme',
            'other' => 'Autre',
        ];

        $statuses = [
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            'partially_refunded' => 'Partiellement remboursé',
        ];

        $enrollments = Enrollment::with(['student:id,first_name,last_name', 'schoolClass:id,name'])
            ->where('status', 'active')
            ->latest('created_at')
            ->limit(200)
            ->get();

        return view('payments.index', compact(
            'payments', 'bilan', 'recouvrement', 'classes', 'academicYears',
            'enrollments', 'paymentMethods', 'paymentTypes', 'statuses'
        ));
    }

    /**
     * Récupérer les paiements des inscriptions
     */
    private function getEnrollmentPayments(array $filters = [], int $perPage = 15)
    {
        $query = Enrollment::with(['student', 'schoolClass', 'academicYear'])
            ->where('status', 'active');
        
        // Appliquer les filtres
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('enrollment_code', 'like', "%{$search}%")
                  ->orWhere('applicant_first_name', 'like', "%{$search}%")
                  ->orWhere('applicant_last_name', 'like', "%{$search}%")
                  ->orWhere('applicant_phone', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }
        
        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('enrollment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('enrollment_date', '<=', $filters['date_to']);
        }
        
        return $query->orderBy('enrollment_date', 'desc')->paginate($perPage);
    }

    /**
     * Récupérer les statistiques des paiements des inscriptions
     */
    private function getEnrollmentPaymentStats(array $filters = []): array
    {
        $query = Enrollment::where('status', 'active');
        
        // Appliquer les filtres
        if (isset($filters['date_from'])) {
            $query->where('enrollment_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('enrollment_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }
        
        $totalRevenue = $query->sum('amount_paid');
        $totalPayments = $query->count();

        // Un query builder est mutable : enchainer les where sur $query cumulait
        // les trois statuts (completed ET pending ET partial) et renvoyait zero.
        $completedPayments = (clone $query)->where('payment_status', 'completed')->count();
        $pendingPayments = (clone $query)->where('payment_status', 'pending')->count();
        $partialPayments = (clone $query)->where('payment_status', 'partial')->count();
        
        return [
            'total_revenue' => $totalRevenue,
            'total_payments' => $totalPayments,
            'completed_payments' => $completedPayments,
            'pending_payments' => $pendingPayments,
            'partial_payments' => $partialPayments,
            'success_rate' => $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100, 2) : 0
        ];
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
    /**
     * Les versements déclarés par les parents : à vérifier, refusés, validés.
     *
     * Ils arrivaient au milieu du journal des paiements, entre deux
     * encaissements de guichet, alors qu'ils appellent une action et une seule :
     * retrouver l'opération chez l'opérateur, puis valider ou refuser. Ils ont
     * donc leur écran, et le menu porte le nombre de ceux qui attendent — un
     * versement oublié, c'est un parent qui a payé et dont le dossier ne le dit
     * pas.
     */
    public function declarations(Request $request)
    {
        $moyens = array_keys(\App\Support\MobileMoney::OPERATEURS);

        $etats = [
            'a-verifier' => ['pending', 'processing'],
            'refuses' => ['cancelled', 'failed'],
            'valides' => ['completed'],
        ];

        $onglet = array_key_exists($request->input('onglet'), $etats)
            ? $request->input('onglet')
            : 'a-verifier';

        $base = fn () => Payment::query()->whereIn('payment_method', $moyens);

        $declarations = $base()
            ->with(['student:id,first_name,last_name,student_id', 'enrollment.schoolClass:id,name'])
            ->whereIn('status', $etats[$onglet])
            // Les plus anciennes d'abord quand elles attendent : c'est le parent
            // qui patiente le plus qu'il faut servir en premier.
            ->orderBy('created_at', $onglet === 'a-verifier' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $compte = [];

        foreach ($etats as $cle => $statuts) {
            $compte[$cle] = $base()->whereIn('status', $statuts)->count();
        }

        return view('payments.declarations', [
            'declarations' => $declarations,
            'onglet' => $onglet,
            'compte' => $compte,
            'attendu' => (float) $base()->whereIn('status', $etats['a-verifier'])->sum('amount'),
        ]);
    }

    /**
     * Le recu d'un versement — le meme pour l'administration et pour le parent.
     *
     * Il en existait deux lectures : celle du secretariat, et rien du tout
     * pour le parent, dont le lien menait a un vieux gabarit autonome. C'est
     * un seul document, avec une seule verification d'acces : l'administration
     * voit les versements de son etablissement, le parent ceux de ses enfants.
     */
    public function receipt(Payment $payment)
    {
        $payment->load([
            'enrollment.schoolClass.level', 'enrollment.academicYear',
            'parent', 'student', 'refunds',
        ]);

        // Le recu porte le nom d'un eleve : c'est lui qui commande l'acces.
        if ($payment->student) {
            \App\Support\AccesEleve::verifier($payment->student);
        } elseif (auth()->user()?->role === 'parent') {
            abort(403, 'Ce reçu ne relève pas de votre compte.');
        }

        $reglages = \App\Models\SchoolSettings::getSettings();

        return view('payments.receipt', [
            'payment' => $payment,
            'schoolSettings' => $reglages,
            'schoolName' => $reglages->school_name ?? config('app.name'),
        ]);
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
    /**
     * Refuser un versement, en disant pourquoi.
     *
     * Le refus etait muet : le parent voyait « annule » et rien d'autre — ni
     * s'il avait mal recopie un chiffre, ni si son argent etait perdu, ni ce
     * qu'il devait faire. Le motif est desormais exige, choisi dans les cas
     * qui reviennent au guichet ou ecrit a la main, et il accompagne le
     * versement jusque sur l'ecran du parent.
     */
    public function cancel(Request $request, Payment $payment)
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'Seuls les versements en attente peuvent être refusés.');
        }

        $donnees = $request->validate([
            'motif' => 'required|in:'.implode(',', \App\Support\MotifsDeRejet::cles()),
            // « Autre motif » ne dit rien a lui seul : sans un mot, le parent
            // resterait aussi demuni qu'avec un refus muet.
            'precision' => [
                \Illuminate\Validation\Rule::requiredIf(
                    fn () => \App\Support\MotifsDeRejet::exigeUnePrecision($request->input('motif'))
                ),
                'nullable', 'string', 'max:500',
            ],
        ], [
            'motif.required' => 'Indiquez pourquoi ce versement est refusé.',
            'precision.required' => 'Précisez le motif : le parent n’aura que cette phrase pour comprendre.',
        ]);

        $precision = trim((string) ($donnees['precision'] ?? ''));

        try {
            $payment->forceFill([
                'metadata' => array_merge((array) $payment->metadata, [
                    'rejet' => [
                        'motif' => $donnees['motif'],
                        'libelle' => \App\Support\MotifsDeRejet::libelle($donnees['motif']),
                        'precision' => $precision ?: null,
                        'le' => now()->toDateTimeString(),
                        'par' => auth()->user()?->name,
                    ],
                ]),
            ])->save();

            $payment->markAsCancelled(
                \App\Support\MotifsDeRejet::libelle($donnees['motif']).($precision ? ' — '.$precision : '')
            );

            return back()->with('success', 'Versement refusé. Le parent en est informé sur son portail.');
        } catch (\Exception $e) {
            return back()->with('error', 'Refus impossible : '.$e->getMessage());
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