@extends('layouts.app')

@section('title', 'Gestion des Frais - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Frais scolaires</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Frais Scolaires</h1>
                    <p class="text-muted">Configurez et gérez les différents types de frais</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFeeModal">
                        <i class="bi bi-cash-stack me-2"></i>
                        Nouveau frais
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Total des frais</h6>
                            <h3 class="mb-0 text-primary">{{ $stats['total_fees'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-cash-stack fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Frais actifs</h6>
                            <h3 class="mb-0 text-success">{{ $stats['active_fees'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Montant total</h6>
                            <h3 class="mb-0 text-warning">{{ number_format($stats['total_amount'] ?? 0) }} FCFA</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-currency-exchange fs-1 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-muted">Taux de paiement</h6>
                            <h3 class="mb-0 text-info">{{ number_format($stats['payment_rate'] ?? 0, 1) }}%</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-graph-up fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fees.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Rechercher</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Nom du frais...">
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous</option>
                                <option value="tuition" {{ request('type') == 'tuition' ? 'selected' : '' }}>Scolarité</option>
                                <option value="registration" {{ request('type') == 'registration' ? 'selected' : '' }}>Inscription</option>
                                <option value="uniform" {{ request('type') == 'uniform' ? 'selected' : '' }}>Uniforme</option>
                                <option value="transport" {{ request('type') == 'transport' ? 'selected' : '' }}>Transport</option>
                                <option value="meal" {{ request('type') == 'meal' ? 'selected' : '' }}>Cantine</option>
                                <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="frequency" class="form-label">Fréquence</label>
                            <select class="form-select" id="frequency" name="frequency">
                                <option value="">Toutes</option>
                                <option value="monthly" {{ request('frequency') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                <option value="quarterly" {{ request('frequency') == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                <option value="yearly" {{ request('frequency') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                <option value="one_time" {{ request('frequency') == 'one_time' ? 'selected' : '' }}>Unique</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-2"></i>Filtrer
                            </button>
                            <a href="{{ route('fees.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Fees Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        Liste des frais ({{ $fees->total() }})
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="exportFees()">
                            <i class="bi bi-download me-2"></i>Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($fees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom du frais</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Fréquence</th>
                                        <th>Classes</th>
                                        <th>Statut</th>
                                        <th>Échéance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fees as $fee)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-cash-stack text-primary me-2"></i>
                                                <div>
                                                    <span class="fw-bold">{{ $fee->name }}</span>
                                                    @if($fee->description)
                                                        <br><small class="text-muted">{{ Str::limit($fee->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $fee->type_color }}">{{ $fee->type_label }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">{{ number_format($fee->amount) }} FCFA</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $fee->frequency_color }}">{{ $fee->frequency_label }}</span>
                                        </td>
                                        <td>
                                            @if($fee->class)
                                                <span class="badge bg-secondary">{{ $fee->class->name }}</span>
                                            @else
                                                <span class="badge bg-info">Toutes</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->is_active)
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-secondary">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->due_date)
                                                <span class="text-muted">{{ $fee->due_date->format('d/m/Y') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Voir" 
                                                        onclick="showFee({{ $fee->id }})">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Modifier" 
                                                        onclick="editFee({{ $fee->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" 
                                                        onclick="deleteFee({{ $fee->id }}, '{{ $fee->name }}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        @if($fees->hasPages())
                            <div class="card-footer bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        Affichage de {{ $fees->firstItem() ?? 0 }} à {{ $fees->lastItem() ?? 0 }} sur {{ $fees->total() ?? 0 }} frais
                                    </div>
                                    <nav aria-label="Pagination des frais">
                                        {{ $fees->links('pagination::bootstrap-5') }}
                                    </nav>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun frais trouvé</h5>
                            <p class="text-muted">Commencez par créer votre premier frais scolaire.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFeeModal">
                                <i class="bi bi-plus-circle me-2"></i>Créer un frais
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Fee Modal -->
<div class="modal fade" id="createFeeModal" tabindex="-1" aria-labelledby="createFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFeeModalLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i>
                    Nouveau Frais Scolaire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createFeeForm" method="POST" action="{{ route('fees.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du frais <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fee_type" class="form-label">Type de frais <span class="text-danger">*</span></label>
                                <select class="form-select" id="fee_type" name="fee_type" required>
                                    <option value="">Sélectionner un type</option>
                                    @foreach($feeTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" min="0" step="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Fréquence <span class="text-danger">*</span></label>
                                <select class="form-select" id="frequency" name="frequency" required>
                                    <option value="">Sélectionner une fréquence</option>
                                    @foreach($frequencies as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Classe</label>
                                <select class="form-select" id="class_id" name="class_id">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="academic_year_id" class="form-label">Année académique <span class="text-danger">*</span></label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Date d'échéance</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" value="1">
                                <label class="form-check-label" for="is_mandatory">
                                    Frais obligatoire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Frais actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Créer le frais
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Fee Modal -->
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFeeModalLabel">
                    <i class="bi bi-pencil text-warning me-2"></i>
                    Modifier le Frais
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFeeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Nom du frais <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_fee_type" class="form-label">Type de frais <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_fee_type" name="fee_type" required>
                                    <option value="">Sélectionner un type</option>
                                    @foreach($feeTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_amount" name="amount" min="0" step="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_frequency" class="form-label">Fréquence <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_frequency" name="frequency" required>
                                    <option value="">Sélectionner une fréquence</option>
                                    @foreach($frequencies as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_class_id" class="form-label">Classe</label>
                                <select class="form-select" id="edit_class_id" name="class_id">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_academic_year_id" class="form-label">Année académique <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_academic_year_id" name="academic_year_id" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_due_date" class="form-label">Date d'échéance</label>
                                <input type="date" class="form-control" id="edit_due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_mandatory" name="is_mandatory">
                                <label class="form-check-label" for="edit_is_mandatory">
                                    Frais obligatoire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Frais actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-2"></i>Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Show Fee Modal -->
<div class="modal fade" id="showFeeModal" tabindex="-1" aria-labelledby="showFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showFeeModalLabel">
                    <i class="bi bi-eye text-primary me-2"></i>
                    Détails du Frais
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="showFeeModalBody">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteFeeModal" tabindex="-1" aria-labelledby="deleteFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFeeModalLabel">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le frais <strong id="feeNameToDelete"></strong> ?</p>
                <p class="text-danger small">
                    <i class="bi bi-info-circle me-1"></i>
                    Cette action est irréversible et supprimera également tous les paiements associés.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Annuler
                </button>
                <form id="deleteFeeForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-info-circle me-2"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody">
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Fonction pour afficher un frais
function showFee(id) {
    fetch(`/fees/${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('showFeeModalBody').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('showFeeModal'));
            modal.show();
        })
        .catch(error => {
            showToast('Erreur lors du chargement des détails', 'error');
        });
}

// Fonction pour éditer un frais
function editFee(id) {
    fetch(`/fees/${id}/edit`)
        .then(response => response.text())
        .then(html => {
            // Extraire les données du HTML pour remplir le formulaire
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Remplir le formulaire avec les données existantes
            document.getElementById('edit_name').value = doc.querySelector('input[name="name"]')?.value || '';
            document.getElementById('edit_fee_type').value = doc.querySelector('select[name="fee_type"]')?.value || '';
            document.getElementById('edit_amount').value = doc.querySelector('input[name="amount"]')?.value || '';
            document.getElementById('edit_frequency').value = doc.querySelector('select[name="frequency"]')?.value || '';
            document.getElementById('edit_class_id').value = doc.querySelector('select[name="class_id"]')?.value || '';
            document.getElementById('edit_academic_year_id').value = doc.querySelector('select[name="academic_year_id"]')?.value || '';
            document.getElementById('edit_due_date').value = doc.querySelector('input[name="due_date"]')?.value || '';
            document.getElementById('edit_description').value = doc.querySelector('textarea[name="description"]')?.value || '';
            document.getElementById('edit_is_mandatory').checked = doc.querySelector('input[name="is_mandatory"]')?.checked || false;
            document.getElementById('edit_is_active').checked = doc.querySelector('input[name="is_active"]')?.checked || false;
            
            // Mettre à jour l'action du formulaire
            document.getElementById('editFeeForm').action = `/fees/${id}`;
            
            const modal = new bootstrap.Modal(document.getElementById('editFeeModal'));
            modal.show();
        })
        .catch(error => {
            showToast('Erreur lors du chargement des données', 'error');
        });
}

function deleteFee(id, name) {
    document.getElementById('feeNameToDelete').textContent = name;
    document.getElementById('deleteFeeForm').action = `/fees/${id}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteFeeModal'));
    modal.show();
}

function exportFees() {
    // Implementation for exporting fees data
    showToast('Fonctionnalité d\'export en cours de développement', 'info');
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastTitle = document.getElementById('toastTitle');
    const toastBody = document.getElementById('toastBody');
    
    // Set toast content based on type
    switch(type) {
        case 'success':
            toastTitle.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i>Succès';
            toast.classList.add('bg-success', 'text-white');
            break;
        case 'error':
            toastTitle.innerHTML = '<i class="bi bi-exclamation-triangle text-danger me-2"></i>Erreur';
            toast.classList.add('bg-danger', 'text-white');
            break;
        case 'warning':
            toastTitle.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-2"></i>Avertissement';
            toast.classList.add('bg-warning', 'text-dark');
            break;
        case 'info':
            toastTitle.innerHTML = '<i class="bi bi-info-circle text-info me-2"></i>Information';
            toast.classList.add('bg-info', 'text-white');
            break;
    }
    
    toastBody.textContent = message;
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove type-specific classes after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white', 'text-dark');
    }, { once: true });
}

// Handle create form submission
document.getElementById('createFeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Client-side validation
    const requiredFields = ['name', 'fee_type', 'amount', 'frequency', 'academic_year_id'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const element = this.querySelector(`[name="${field}"]`);
        if (!element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        showToast('Veuillez remplir tous les champs obligatoires', 'error');
        submitBtn.disabled = false;
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        
        // Debug: Log the form data being sent
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            console.error('Server response error:', error);
            
            if (error.errors) {
                // Handle validation errors
                const errorMessages = Object.values(error.errors).flat().join(', ');
                throw new Error('Erreurs de validation: ' + errorMessages);
            } else {
                throw new Error(error.message || 'Erreur lors de la création');
            }
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('createFeeModal'));
        modal.hide();
        
        // Show success message
        showToast('Frais créé avec succès !', 'success');
        
        // Reload page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        showToast(error.message || 'Erreur lors de la création', 'error');
    } finally {
        submitBtn.disabled = false;
    }
});

// Handle edit form submission
document.getElementById('editFeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || 'Erreur lors de la modification');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editFeeModal'));
        modal.hide();
        
        // Show success message
        showToast('Frais modifié avec succès !', 'success');
        
        // Reload page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        showToast(error.message || 'Erreur lors de la modification', 'error');
    } finally {
        submitBtn.disabled = false;
    }
});

// Handle delete form submission
document.getElementById('deleteFeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(new FormData(this))
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || 'Erreur lors de la suppression');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteFeeModal'));
        modal.hide();
        
        // Show success message
        showToast('Frais supprimé avec succès !', 'success');
        
        // Reload page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        showToast(error.message || 'Erreur lors de la suppression', 'error');
    } finally {
        submitBtn.disabled = false;
    }
});

// Show success message if redirected from create/edit
@if(session('success'))
    showToast('{{ session('success') }}', 'success');
@endif

@if(session('error'))
    showToast('{{ session('error') }}', 'error');
@endif
</script>
@endpush 
