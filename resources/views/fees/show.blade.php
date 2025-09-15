@extends('layouts.app')

@section('title', 'Détails du Frais - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fees.index') }}">Frais scolaires</a></li>
<li class="breadcrumb-item active">Détails</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Détails du Frais</h1>
                    <p class="text-muted">Informations complètes sur "{{ $fee->name }}"</p>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="{{ route('fees.edit', $fee->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier
                        </a>
                        <a href="{{ route('fees.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Fee Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cash-stack me-2"></i>
                        Informations principales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold" style="width: 150px;">Nom:</td>
                                    <td>{{ $fee->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Type:</td>
                                    <td><span class="badge bg-primary">{{ $fee->fee_type_label }}</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Fréquence:</td>
                                    <td><span class="badge bg-info">{{ $fee->frequency_label }}</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Statut:</td>
                                    <td><span class="badge bg-{{ $fee->status_color }}">{{ $fee->status_label }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold" style="width: 150px;">Montant:</td>
                                    <td class="text-success fw-bold fs-5">{{ $fee->formatted_amount }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Classe:</td>
                                    <td>{{ $fee->schoolClass ? $fee->schoolClass->name : 'Toutes les classes' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Année académique:</td>
                                    <td>{{ $fee->academicYear->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date d'échéance:</td>
                                    <td>{{ $fee->due_date ? $fee->due_date->format('d/m/Y') : 'Non définie' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    @if($fee->description)
                    <div class="mt-3">
                        <strong>Description:</strong>
                        <p class="text-muted mb-0">{{ $fee->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Actions rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('fees.edit', $fee->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier ce frais
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash me-2"></i>
                            Supprimer
                        </button>
                        <a href="{{ route('fees.index') }}" class="btn btn-secondary">
                            <i class="bi bi-list me-2"></i>
                            Voir tous les frais
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Informations sur les paiements
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle fs-1 text-info"></i>
                        <h6 class="mt-3">Gestion des paiements</h6>
                        <p class="text-muted mt-2">
                            Les paiements sont maintenant gérés au niveau des inscriptions (enrollments) 
                            plutôt qu'au niveau des frais individuels. Pour consulter l'historique des 
                            paiements, veuillez accéder à la section des inscriptions.
                        </p>
                        <a href="{{ route('enrollments.index') }}" class="btn btn-outline-primary mt-2">
                            <i class="bi bi-arrow-right me-2"></i>
                            Voir les inscriptions
                        </a>
                    </div>
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
                <p>Êtes-vous sûr de vouloir supprimer le frais <strong>"{{ $fee->name }}"</strong> ?</p>
                <p class="text-danger mb-0">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    Cette action est irréversible !
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('fees.destroy', $fee->id) }}" method="POST" class="d-inline">
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
</style>
@endsection
