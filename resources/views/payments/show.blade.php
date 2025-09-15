@extends('layouts.app')

@section('title', 'Détails du Paiement - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Paiements</a></li>
<li class="breadcrumb-item active">Détails</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Détails du Paiement</h1>
                    <p class="text-muted">Informations complètes sur le paiement #{{ $payment->reference }}</p>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier
                        </a>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Details Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Informations du paiement
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold" style="width: 150px;">Référence:</td>
                                    <td><code class="fs-5">{{ $payment->reference }}</code></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Montant:</td>
                                    <td class="text-success fw-bold fs-4">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date de paiement:</td>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Méthode:</td>
                                    <td>
                                        @switch($payment->payment_method)
                                            @case('cash')
                                                <span class="badge bg-success">Espèces</span>
                                                @break
                                            @case('check')
                                                <span class="badge bg-info">Chèque</span>
                                                @break
                                            @case('bank_transfer')
                                                <span class="badge bg-primary">Virement</span>
                                                @break
                                            @case('mobile_money')
                                                <span class="badge bg-warning">Mobile Money</span>
                                                @break
                                            @case('card')
                                                <span class="badge bg-secondary">Carte</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold" style="width: 150px;">Statut:</td>
                                    <td><span class="badge bg-success fs-6">Terminé</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date de création:</td>
                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Dernière modification:</td>
                                    <td>{{ $payment->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($payment->notes)
                                <tr>
                                    <td class="fw-bold">Notes:</td>
                                    <td class="text-muted">{{ $payment->notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person me-2"></i>
                        Informations de l'étudiant
                    </h5>
                </div>
                <div class="card-body">
                    @if($payment->enrollment && $payment->enrollment->student)
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Nom complet:</strong><br>
                                <span class="text-muted">{{ $payment->enrollment->student->first_name }} {{ $payment->enrollment->student->last_name }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Classe:</strong><br>
                                <span class="text-muted">{{ $payment->enrollment->schoolClass->name }}</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Année académique:</strong><br>
                                <span class="text-muted">{{ $payment->enrollment->academicYear->name }}</span>
                            </div>
                        </div>
                        
                        @if($payment->enrollment->student->parents->count() > 0)
                        <div class="mt-3">
                            <strong>Parents:</strong><br>
                            @foreach($payment->enrollment->student->parents as $parent)
                                <span class="badge bg-info me-2">
                                    {{ $parent->first_name }} {{ $parent->last_name }}
                                    @if($parent->phone)
                                        - {{ $parent->phone }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                            <p class="text-muted mt-2">Informations de l'étudiant non disponibles</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier ce paiement
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash me-2"></i>
                            Supprimer
                        </button>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                            <i class="bi bi-list me-2"></i>
                            Voir tous les paiements
                        </a>
                        @if($payment->enrollment && $payment->enrollment->student)
                        <a href="{{ route('students.show', $payment->enrollment->student->id) }}" class="btn btn-info">
                            <i class="bi bi-person me-2"></i>
                            Voir l'étudiant
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Payments -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card-2-front me-2"></i>
                        Paiements liés
                    </h5>
                </div>
                <div class="card-body">
                    @if($payment->enrollment)
                        @php
                            $relatedPayments = $payment->enrollment->payments()->where('id', '!=', $payment->id)->orderBy('payment_date', 'desc')->get();
                        @endphp
                        
                        @if($relatedPayments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                            <th>Méthode</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($relatedPayments as $relatedPayment)
                                        <tr>
                                            <td><code>{{ $relatedPayment->reference }}</code></td>
                                            <td class="text-success fw-bold">{{ number_format($relatedPayment->amount, 0, ',', ' ') }} FCFA</td>
                                            <td>{{ $relatedPayment->payment_date->format('d/m/Y') }}</td>
                                            <td>
                                                @switch($relatedPayment->payment_method)
                                                    @case('cash')
                                                        <span class="badge bg-success">Espèces</span>
                                                        @break
                                                    @case('check')
                                                        <span class="badge bg-info">Chèque</span>
                                                        @break
                                                    @case('bank_transfer')
                                                        <span class="badge bg-primary">Virement</span>
                                                        @break
                                                    @case('mobile_money')
                                                        <span class="badge bg-warning">Mobile Money</span>
                                                        @break
                                                    @case('card')
                                                        <span class="badge bg-secondary">Carte</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $relatedPayment->payment_method }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <a href="{{ route('payments.show', $relatedPayment->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-credit-card-2-front fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Aucun autre paiement pour cette inscription</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                            <p class="text-muted mt-2">Impossible de récupérer les paiements liés</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le paiement <strong>#{{ $payment->reference }}</strong> ?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Cette action est irréversible !
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('payments.destroy', $payment->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function confirmDelete() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Afficher les messages de succès/erreur
@if(session('success'))
    // Créer et afficher un toast de succès
    const toast = document.createElement('div');
    toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast-header bg-success text-white">
            <i class="bi bi-check-circle me-2"></i>
            <strong class="me-auto">Succès</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            {{ session('success') }}
        </div>
    `;
    document.body.appendChild(toast);
    
    // Supprimer le toast après 5 secondes
    setTimeout(() => {
        toast.remove();
    }, 5000);
@endif

@if(session('error'))
    // Créer et afficher un toast d'erreur
    const toast = document.createElement('div');
    toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast-header bg-danger text-white">
            <i class="bi bi-exclamation-circle me-2"></i>
            <strong class="me-auto">Erreur</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            {{ session('error') }}
        </div>
    `;
    document.body.appendChild(toast);
    
    // Supprimer le toast après 5 secondes
    setTimeout(() => {
        toast.remove();
    }, 5000);
@endif
</script>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.375rem;
}

.btn-group .btn:not(:last-child) {
    margin-right: 0.5rem;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}
</style>
@endsection
