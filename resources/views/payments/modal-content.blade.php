<!-- Contenu du modal de détails -->
<div class="payment-details-content">
    <!-- Header avec informations principales -->
    <div class="payment-header mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div class="payment-info">
                <div class="d-flex align-items-center mb-2">
                    <div class="payment-icon me-3">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 text-dark">{{ $payment->transaction_id }}</h4>
                        <p class="text-muted mb-0">
                            @if($payment->created_at)
                                Créé le {{ $payment->created_at->format('d/m/Y à H:i') }}
                            @else
                                Date de création non disponible
                            @endif
                        </p>
                    </div>
                </div>
                <div class="payment-amount">
                    <span class="amount-value">{{ $payment->formatted_amount }}</span>
                    <span class="status-badge {{ $payment->status_badge_class }}">
                        {{ $payment->status_label }}
                    </span>
                </div>
            </div>
            <div class="payment-actions">
                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bi bi-pencil me-1"></i>Modifier
                </a>
                @if($payment->isPending())
                    <form method="POST" action="{{ route('payments.complete', $payment) }}" class="d-inline me-2">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" 
                                onclick="return confirm('Finaliser ce paiement ?')">
                            <i class="bi bi-check me-1"></i>Finaliser
                        </button>
                    </form>
                    <form method="POST" action="{{ route('payments.cancel', $payment) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Annuler ce paiement ?')">
                            <i class="bi bi-x me-1"></i>Annuler
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-lg-8">
            <!-- Détails du paiement -->
            <div class="card payment-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Détails du Paiement
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Type de Paiement</label>
                                <div class="info-value">
                                    <span class="badge bg-primary">{{ $payment->payment_type_label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Méthode de Paiement</label>
                                <div class="info-value">
                                    <span class="badge bg-secondary">{{ $payment->payment_method_label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Numéro de Reçu</label>
                                <div class="info-value">
                                    @if($payment->receipt_number)
                                        <code class="text-primary">{{ $payment->receipt_number }}</code>
                                    @else
                                        <span class="text-muted">Non généré</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="info-label">Date de Paiement</label>
                                <div class="info-value">
                                    @if($payment->paid_at)
                                        <span class="text-success">{{ $payment->paid_at->format('d/m/Y à H:i') }}</span>
                                    @else
                                        <span class="text-muted">Non payé</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations du payeur -->
            <div class="card payment-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-person me-2"></i>Informations du Payeur
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Nom complet</label>
                                <div class="info-value">{{ $payment->payer_name }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Téléphone</label>
                                <div class="info-value">
                                    <a href="tel:{{ $payment->payer_phone }}" class="text-decoration-none">
                                        {{ $payment->payer_phone }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-item">
                                <label class="info-label">Email</label>
                                <div class="info-value">
                                    @if($payment->payer_email)
                                        <a href="mailto:{{ $payment->payer_email }}" class="text-decoration-none">
                                            {{ $payment->payer_email }}
                                        </a>
                                    @else
                                        <span class="text-muted">Non renseigné</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations de l'étudiant -->
            @if($payment->student)
                <div class="card payment-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-person-badge me-2"></i>Étudiant Associé
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="student-avatar me-3">
                                {{ substr($payment->student->first_name, 0, 1) }}{{ substr($payment->student->last_name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</h6>
                                @if($payment->enrollment && $payment->enrollment->schoolClass)
                                    <p class="text-muted mb-0">{{ $payment->enrollment->schoolClass->name }}</p>
                                @endif
                            </div>
                            @if($payment->enrollment)
                                <div class="text-end">
                                    <small class="text-muted d-block">Année Académique</small>
                                    <span class="badge bg-info">{{ $payment->enrollment->academicYear->name ?? 'N/A' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Informations techniques -->
            @if($payment->gateway_transaction_id || $payment->gateway_response)
                <div class="card payment-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>Informations Techniques
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($payment->gateway_transaction_id)
                            <div class="info-item mb-3">
                                <label class="info-label">ID Transaction Passerelle</label>
                                <div class="info-value">
                                    <code class="bg-light p-2 rounded d-block">{{ $payment->gateway_transaction_id }}</code>
                                </div>
                            </div>
                        @endif
                        @if($payment->gateway_response)
                            <div class="info-item">
                                <label class="info-label">Réponse Passerelle</label>
                                <div class="info-value">
                                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if($payment->notes)
                <div class="card payment-card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-sticky me-2"></i>Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $payment->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Actions rapides -->
            <div class="card payment-card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>Actions Rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('payments.edit', $payment) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Modifier le Paiement
                        </a>
                        @if($payment->canBeRefunded())
                            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#refundModal">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Créer un Remboursement
                            </button>
                        @endif
                        <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="btn btn-outline-info">
                            <i class="bi bi-printer me-2"></i>Imprimer le Reçu
                        </a>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Retour à la Liste
                        </a>
                    </div>
                </div>
            </div>

            <!-- Historique des remboursements -->
            @if($payment->refunds && $payment->refunds->count() > 0)
                <div class="card payment-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Remboursements
                            <span class="badge bg-secondary ms-2">{{ $payment->refunds->count() }}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($payment->refunds as $refund)
                            <div class="refund-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <small class="text-muted">{{ $refund->refund_id }}</small>
                                        <p class="mb-0 fw-semibold">{{ $refund->formatted_amount }}</p>
                                    </div>
                                    <span class="badge {{ $refund->status_badge_class }}">{{ $refund->status_label }}</span>
                                </div>
                                @if($refund->reason)
                                    <small class="text-muted">{{ $refund->reason }}</small>
                                @endif
                            </div>
                            @if(!$loop->last)
                                <hr class="my-3">
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Styles pour le modal de détails de paiement -->
<style>
/* Styles pour le modal de détails de paiement */
.payment-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.payment-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    backdrop-filter: blur(10px);
}

.payment-amount {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
}

.amount-value {
    font-size: 2rem;
    font-weight: 700;
    color: white;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.payment-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.payment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.payment-card .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
}

.payment-card .card-title {
    color: #495057;
    font-weight: 600;
    font-size: 1rem;
}

.payment-card .card-body {
    padding: 1.5rem;
}

.info-item {
    margin-bottom: 1.5rem;
}

.info-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    display: block;
}

.info-value {
    font-size: 1rem;
    color: #212529;
    font-weight: 500;
}

.student-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.refund-item {
    padding: 1rem 0;
}

.refund-item:not(:last-child) {
    border-bottom: 1px solid #f1f3f4;
}

/* Badges personnalisés */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
    color: #212529 !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
}

.badge.bg-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}

/* Boutons personnalisés */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-outline-primary {
    border: 2px solid #007bff;
    color: #007bff;
}

.btn-outline-primary:hover {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    border-color: transparent;
    transform: translateY(-1px);
}

.btn-outline-warning {
    border: 2px solid #ffc107;
    color: #ffc107;
}

.btn-outline-warning:hover {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    border-color: transparent;
    color: #212529;
    transform: translateY(-1px);
}

.btn-outline-info {
    border: 2px solid #17a2b8;
    color: #17a2b8;
}

.btn-outline-info:hover {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    border-color: transparent;
    transform: translateY(-1px);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border-color: transparent;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .payment-header {
        padding: 1.5rem;
    }
    
    .payment-amount {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .amount-value {
        font-size: 1.5rem;
    }
    
    .payment-actions {
        margin-top: 1rem;
    }
    
    .payment-actions .btn {
        margin-bottom: 0.5rem;
    }
}

/* Animation d'entrée */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.payment-card {
    animation: fadeInUp 0.6s ease-out;
}

.payment-card:nth-child(2) {
    animation-delay: 0.1s;
}

.payment-card:nth-child(3) {
    animation-delay: 0.2s;
}

.payment-card:nth-child(4) {
    animation-delay: 0.3s;
}
</style>
