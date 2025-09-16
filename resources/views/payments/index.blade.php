@extends('layouts.app')

@section('title', 'Gestion des Paiements - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Paiements</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Paiements</h1>
                    <p class="text-muted">Suivez et gérez tous les paiements des frais scolaires</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-2"></i>Exporter
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau Paiement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Erreurs dans le formulaire :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card revenue-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['total_revenue'] ?? 0, 0, ',', ' ') }}</h4>
                            <span class="text-muted">Total Revenus (FCFA)</span>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card payments-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['total_payments'] ?? 0 }}</h4>
                            <span class="text-muted">Total Paiements</span>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card success-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['completed_payments'] ?? 0 }}</h4>
                            <span class="text-muted">Paiements Terminés</span>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card pending-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">{{ $stats['pending_payments'] ?? 0 }}</h4>
                            <span class="text-muted">En Attente</span>
                        </div>
                        <div class="stats-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-funnel me-2"></i>Filtres et Recherche
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" 
                           placeholder="Transaction, nom, téléphone...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="processing" {{ ($filters['status'] ?? '') == 'processing' ? 'selected' : '' }}>En cours</option>
                        <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Terminé</option>
                        <option value="failed" {{ ($filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>Échoué</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                        <option value="refunded" {{ ($filters['status'] ?? '') == 'refunded' ? 'selected' : '' }}>Remboursé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Méthode</label>
                    <select class="form-select" name="payment_method">
                        <option value="">Toutes les méthodes</option>
                        <option value="moov_money" {{ ($filters['payment_method'] ?? '') == 'moov_money' ? 'selected' : '' }}>Moov Money</option>
                        <option value="airtel_money" {{ ($filters['payment_method'] ?? '') == 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                        <option value="card" {{ ($filters['payment_method'] ?? '') == 'card' ? 'selected' : '' }}>Carte bancaire</option>
                        <option value="bank_transfer" {{ ($filters['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Virement bancaire</option>
                        <option value="cash" {{ ($filters['payment_method'] ?? '') == 'cash' ? 'selected' : '' }}>Espèces</option>
                        <option value="check" {{ ($filters['payment_method'] ?? '') == 'check' ? 'selected' : '' }}>Chèque</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="payment_type">
                        <option value="">Tous les types</option>
                        <option value="enrollment" {{ ($filters['payment_type'] ?? '') == 'enrollment' ? 'selected' : '' }}>Inscription</option>
                        <option value="re_enrollment" {{ ($filters['payment_type'] ?? '') == 're_enrollment' ? 'selected' : '' }}>Réinscription</option>
                        <option value="tuition" {{ ($filters['payment_type'] ?? '') == 'tuition' ? 'selected' : '' }}>Frais de scolarité</option>
                        <option value="transport" {{ ($filters['payment_type'] ?? '') == 'transport' ? 'selected' : '' }}>Transport</option>
                        <option value="canteen" {{ ($filters['payment_type'] ?? '') == 'canteen' ? 'selected' : '' }}>Cantine</option>
                        <option value="uniform" {{ ($filters['payment_type'] ?? '') == 'uniform' ? 'selected' : '' }}>Uniforme</option>
                        <option value="other" {{ ($filters['payment_type'] ?? '') == 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date de début</label>
                    <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>Liste des Paiements
                <span class="badge bg-primary ms-2">{{ $payments->total() }}</span>
            </h6>
                </div>
                <div class="card-body p-0">
            @if($payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                        <thead class="table-light">
                                <tr>
                                <th>Transaction</th>
                                <th>Étudiant</th>
                                    <th>Montant</th>
                                <th>Type</th>
                                <th>Méthode</th>
                                <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($payments as $payment)
                                    <tr>
                                        <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">{{ $payment->transaction_id }}</span>
                                            @if($payment->receipt_number)
                                                <small class="text-muted">Reçu: {{ $payment->receipt_number }}</small>
                                            @endif
                                        </div>
                                        </td>
                                        <td>
                                        @if($payment->student)
                                            <div class="d-flex flex-column">
                                                <span class="fw-medium">{{ $payment->student->first_name }} {{ $payment->student->last_name }}</span>
                                                @if($payment->enrollment && $payment->enrollment->schoolClass)
                                                    <small class="text-muted">{{ $payment->enrollment->schoolClass->name }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        </td>
                                        <td>
                                        <span class="fw-bold text-success">{{ $payment->formatted_amount }}</span>
                                        </td>
                                        <td>
                                        <span class="badge bg-info">{{ $payment->payment_type_label }}</span>
                                        </td>
                                        <td>
                                        <span class="badge bg-secondary">{{ $payment->payment_method_label }}</span>
                                        </td>
                                        <td>
                                        <span class="badge {{ $payment->status_badge_class }}">
                                            {{ $payment->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                        <div class="d-flex flex-column">
                                            @if($payment->created_at)
                                                <span>{{ $payment->created_at->format('d/m/Y') }}</span>
                                                <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" title="Voir" 
                                                    data-bs-toggle="modal" data-bs-target="#paymentDetailModal" 
                                                    data-payment-id="{{ $payment->id }}">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            @if($payment->isPending())
                                                <form method="POST" action="{{ route('payments.complete', $payment) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Finaliser" 
                                                            onclick="return confirm('Finaliser ce paiement ?')">
                                                        <i class="bi bi-check"></i>
                                                </button>
                                                </form>
                                                <form method="POST" action="{{ route('payments.cancel', $payment) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Annuler" 
                                                            onclick="return confirm('Annuler ce paiement ?')">
                                                        <i class="bi bi-x"></i>
                                                </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('payments.receipt', $payment) }}" target="_blank" class="btn btn-outline-info" title="Imprimer le reçu">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary" title="Modifier" 
                                                    data-bs-toggle="modal" data-bs-target="#editPaymentModal" 
                                                    data-payment-id="{{ $payment->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            </div>
                                        </td>
                                    </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-credit-card fs-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Aucun paiement trouvé</h5>
                    <p class="text-muted">Commencez par créer un nouveau paiement</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau Paiement
                    </button>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Nouveau Paiement -->
<div class="modal fade" id="newPaymentModal" tabindex="-1" aria-labelledby="newPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPaymentModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Nouveau Paiement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('payments.store') }}" id="newPaymentForm">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Sélection de l'inscription -->
                        <div class="col-md-6">
                            <label class="form-label">Inscription <span class="text-danger">*</span></label>
                            <select class="form-select @error('enrollment_id') is-invalid @enderror" name="enrollment_id" required>
                                <option value="">Sélectionner une inscription</option>
                                @foreach($enrollments as $enrollment)
                                    <option value="{{ $enrollment->id }}" {{ old('enrollment_id') == $enrollment->id ? 'selected' : '' }}>
                                        {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} 
                                        - {{ $enrollment->schoolClass->name }} ({{ $enrollment->academicYear->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('enrollment_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Montant -->
                        <div class="col-md-6">
                            <label class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                   name="amount" value="{{ old('amount') }}" min="0" step="0.01" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type de paiement -->
                        <div class="col-md-6">
                            <label class="form-label">Type de Paiement <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_type') is-invalid @enderror" name="payment_type" required>
                                <option value="">Sélectionner un type</option>
                                @foreach($paymentTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('payment_type') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Méthode de paiement -->
                        <div class="col-md-6">
                            <label class="form-label">Méthode de Paiement <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
                                <option value="">Sélectionner une méthode</option>
                                @foreach($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" {{ old('payment_method') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Informations du payeur -->
                        <div class="col-12">
                            <h6 class="mt-3 mb-3">
                                <i class="bi bi-person me-2"></i>Informations du Payeur
                            </h6>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nom du Payeur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('payer_name') is-invalid @enderror" 
                                   name="payer_name" value="{{ old('payer_name') }}" required>
                            @error('payer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control @error('payer_phone') is-invalid @enderror" 
                                   name="payer_phone" value="{{ old('payer_phone') }}" required>
                            @error('payer_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('payer_email') is-invalid @enderror" 
                                   name="payer_email" value="{{ old('payer_email') }}">
                            @error('payer_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      name="notes" rows="3" placeholder="Notes additionnelles...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Détails Paiement -->
<div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentDetailModalLabel">
                    <i class="bi bi-credit-card me-2"></i>Détails du Paiement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Fermer
                </button>
                <button type="button" class="btn btn-outline-primary" id="editPaymentBtn">
                    <i class="bi bi-pencil me-2"></i>Modifier
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Édition Paiement -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content edit-payment-modal">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="edit-icon me-3">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="editPaymentModalLabel">Modifier le Paiement</h5>
                        <small class="text-muted" id="editPaymentTransactionId">Transaction ID</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" id="editPaymentContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement du formulaire...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Styles pour le modal d'édition */
.edit-payment-modal {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.edit-payment-modal .modal-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 16px 16px 0 0;
    padding: 1.5rem 2rem;
    border-bottom: none;
}

.edit-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    backdrop-filter: blur(10px);
}

.edit-payment-modal .modal-body {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}

.edit-payment-modal .modal-footer {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    padding: 1.5rem 2rem;
    border-radius: 0 0 16px 16px;
}

.edit-payment-modal .btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.edit-payment-modal .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
}

.edit-payment-modal .btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.edit-payment-modal .btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    transform: translateY(-1px);
}

/* Animation pour le modal d'édition */
@keyframes editModalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.edit-payment-modal {
    animation: editModalSlideIn 0.3s ease-out;
}

/* Responsive pour le modal d'édition */
@media (max-width: 768px) {
    .edit-payment-modal .modal-header,
    .edit-payment-modal .modal-footer {
        padding: 1rem 1.5rem;
    }
    
    .edit-payment-modal .modal-body {
        padding: 1.5rem;
    }
}

.stats-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.revenue-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.payments-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.success-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.pending-card {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stats-icon {
    font-size: 2rem;
    opacity: 0.7;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

/* Styles pour le modal */
.modal-lg {
    max-width: 800px;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.modal-header .btn-close {
    filter: invert(1);
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 1rem 2rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du modal de nouveau paiement
    const newPaymentModal = document.getElementById('newPaymentModal');
    const newPaymentForm = document.getElementById('newPaymentForm');
    
    // Réinitialiser le formulaire quand le modal se ferme
    newPaymentModal.addEventListener('hidden.bs.modal', function() {
        newPaymentForm.reset();
        // Supprimer les classes d'erreur
        newPaymentForm.querySelectorAll('.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });
        newPaymentForm.querySelectorAll('.invalid-feedback').forEach(function(element) {
            element.remove();
        });
    });
    
    // Auto-remplir les informations du payeur quand une inscription est sélectionnée
    const enrollmentSelect = newPaymentForm.querySelector('select[name="enrollment_id"]');
    const payerNameInput = newPaymentForm.querySelector('input[name="payer_name"]');
    const payerPhoneInput = newPaymentForm.querySelector('input[name="payer_phone"]');
    const payerEmailInput = newPaymentForm.querySelector('input[name="payer_email"]');
    
    enrollmentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            // Extraire le nom du parent depuis l'option sélectionnée
            const optionText = selectedOption.textContent;
            // Ici vous pourriez faire un appel AJAX pour récupérer les informations du parent
            // Pour l'instant, on laisse l'utilisateur remplir manuellement
        }
    });
    
    // Validation en temps réel
    newPaymentForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Vérifier les champs requis
        const requiredFields = newPaymentForm.querySelectorAll('[required]');
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Vérifier le format de l'email
        const emailField = newPaymentForm.querySelector('input[name="payer_email"]');
        if (emailField.value && !isValidEmail(emailField.value)) {
            emailField.classList.add('is-invalid');
            isValid = false;
        }
        
        // Vérifier le format du téléphone
        const phoneField = newPaymentForm.querySelector('input[name="payer_phone"]');
        if (phoneField.value && !isValidPhone(phoneField.value)) {
            phoneField.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
        }
    });
    
    // Fonctions utilitaires
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,20}$/;
        return phoneRegex.test(phone);
    }
    
    function showNotification(message, type = 'info') {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }
    
    // Gestion du modal de détails de paiement
    const paymentDetailModal = document.getElementById('paymentDetailModal');
    const paymentDetailContent = document.getElementById('paymentDetailContent');
    const editPaymentBtn = document.getElementById('editPaymentBtn');
    let currentPaymentId = null;

    // Gestion du modal d'édition de paiement
    const editPaymentModal = document.getElementById('editPaymentModal');
    const editPaymentContent = document.getElementById('editPaymentContent');
    const editPaymentForm = document.getElementById('editPaymentForm');
    const editPaymentTransactionId = document.getElementById('editPaymentTransactionId');
    let currentEditPaymentId = null;
    
    // Charger les détails du paiement quand le modal s'ouvre
    paymentDetailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        currentPaymentId = button.getAttribute('data-payment-id');
        
        // Charger les détails via AJAX
        loadPaymentDetails(currentPaymentId);
    });
    
    // Fonction pour charger les détails du paiement
    function loadPaymentDetails(paymentId) {
        // Afficher le spinner de chargement
        paymentDetailContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement des détails...</p>
            </div>
        `;
        
        fetch(`/payments/${paymentId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
                'Content-Type': 'text/html'
            }
        })
        .then(response => {
        if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Le contenu est déjà prêt pour le modal
            paymentDetailContent.innerHTML = html;
            
            // Mettre à jour le lien de modification
            editPaymentBtn.href = `/payments/${paymentId}/edit`;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails:', error);
            paymentDetailContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Erreur lors du chargement des détails du paiement.</strong><br>
                    <small>${error.message}</small>
                </div>
            `;
        });
    }

    // Charger le formulaire d'édition quand le modal s'ouvre
    editPaymentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        currentEditPaymentId = button.getAttribute('data-payment-id');
        
        // Charger le formulaire via AJAX
        loadEditForm(currentEditPaymentId);
    });

    // Gestion du bouton "Modifier" dans le modal de détails
    editPaymentBtn.addEventListener('click', function() {
        if (currentPaymentId) {
            // Fermer le modal de détails
            const modal = bootstrap.Modal.getInstance(paymentDetailModal);
            modal.hide();
            
            // Ouvrir le modal d'édition
            currentEditPaymentId = currentPaymentId;
            loadEditForm(currentEditPaymentId);
            
            // Afficher le modal d'édition
            const editModal = new bootstrap.Modal(editPaymentModal);
            editModal.show();
        }
    });

    // Fonction pour charger le formulaire d'édition
    function loadEditForm(paymentId) {
        // Afficher le spinner de chargement
        editPaymentContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement du formulaire...</p>
            </div>
        `;
        
        fetch(`/payments/${paymentId}/edit-modal`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
                'Content-Type': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            editPaymentContent.innerHTML = html;
            
            // Mettre à jour l'action du formulaire
            editPaymentForm.action = `/payments/${paymentId}`;
            
            // Mettre à jour le titre avec l'ID de transaction
            const transactionId = document.querySelector('[data-transaction-id]')?.getAttribute('data-transaction-id');
            if (transactionId) {
                editPaymentTransactionId.textContent = transactionId;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement du formulaire:', error);
            editPaymentContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Erreur lors du chargement du formulaire d'édition.</strong><br>
                    <small>${error.message}</small>
                </div>
            `;
        });
    }

    // Gestion de la soumission du formulaire d'édition
    editPaymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const paymentId = currentEditPaymentId;
        
        // Afficher un indicateur de chargement
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Enregistrement...';
        submitBtn.disabled = true;
        
        fetch(`/payments/${paymentId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(editPaymentModal);
                modal.hide();
                
                // Afficher un message de succès
                showToast('Paiement modifié avec succès !', 'success');
                
                // Recharger la page pour voir les changements
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.message || 'Erreur lors de la modification');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors de la modification du paiement: ' + error.message, 'error');
        })
        .finally(() => {
            // Réactiver le bouton
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });

    // Réinitialiser le modal d'édition quand il se ferme
    editPaymentModal.addEventListener('hidden.bs.modal', function() {
        editPaymentContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement du formulaire...</p>
            </div>
        `;
        currentEditPaymentId = null;
    });
});
</script>
@endpush
@endsection
