@extends('layouts.app')

@section('title', 'Frais de Niveau - StudiaGabon')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fees.dashboard') }}">Frais Scolaires</a></li>
<li class="breadcrumb-item active">Frais de Niveau</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Frais de Niveau</h1>
                    <p class="text-muted">Gérez les frais applicables à tous les niveaux pour {{ $academicYear->name }}</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLevelFeeModal">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau Frais de Niveau
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('fees.level-fees') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="level_id" class="form-label">Niveau</label>
                            <select class="form-select" id="level_id" name="level_id">
                                <option value="">Tous les niveaux</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" {{ request('level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }} ({{ $level->cycle }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="fee_type" class="form-label">Type de Frais</label>
                            <select class="form-select" id="fee_type" name="fee_type">
                                <option value="">Tous les types</option>
                                <option value="tuition" {{ request('fee_type') == 'tuition' ? 'selected' : '' }}>Scolarité</option>
                                <option value="registration" {{ request('fee_type') == 'registration' ? 'selected' : '' }}>Inscription</option>
                                <option value="uniform" {{ request('fee_type') == 'uniform' ? 'selected' : '' }}>Uniforme</option>
                                <option value="transport" {{ request('fee_type') == 'transport' ? 'selected' : '' }}>Transport</option>
                                <option value="meal" {{ request('fee_type') == 'meal' ? 'selected' : '' }}>Cantine</option>
                                <option value="books" {{ request('fee_type') == 'books' ? 'selected' : '' }}>Livres</option>
                                <option value="activities" {{ request('fee_type') == 'activities' ? 'selected' : '' }}>Activités</option>
                                <option value="other" {{ request('fee_type') == 'other' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="frequency" class="form-label">Fréquence</label>
                            <select class="form-select" id="frequency" name="frequency">
                                <option value="">Toutes les fréquences</option>
                                <option value="monthly" {{ request('frequency') == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                <option value="quarterly" {{ request('frequency') == 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                <option value="yearly" {{ request('frequency') == 'yearly' ? 'selected' : '' }}>Annuel</option>
                                <option value="one_time" {{ request('frequency') == 'one_time' ? 'selected' : '' }}>Unique</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Rechercher</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Nom du frais...">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Filtrer
                            </button>
                            <a href="{{ route('fees.level-fees') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Effacer
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Level Fees Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-layers me-2"></i>Liste des Frais de Niveau
                        <span class="badge bg-primary ms-2">{{ $levelFees->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($levelFees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Type</th>
                                        <th>Nom</th>
                                        <th>Montant</th>
                                        <th>Fréquence</th>
                                        <th>Échéance</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($levelFees as $fee)
                                        <tr>
                                            <td>
                                                <strong>{{ $fee->level->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $fee->level->cycle }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $fee->fee_type_label }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $fee->name }}</strong>
                                                @if($fee->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($fee->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-success">{{ $fee->formatted_amount }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $fee->frequency_label }}</span>
                                            </td>
                                            <td>
                                                @if($fee->due_date)
                                                    {{ $fee->due_date->format('d/m/Y') }}
                                                @else
                                                    <span class="text-muted">Non défini</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $fee->status_badge_class }}">
                                                    {{ $fee->status_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#editLevelFeeModal{{ $fee->id }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#viewLevelFeeModal{{ $fee->id }}">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('fees.level-fee.destroy', $fee->id) }}" 
                                                          class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce frais ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <h5 class="text-muted mt-3">Aucun frais de niveau trouvé</h5>
                            <p class="text-muted">Commencez par créer votre premier frais de niveau.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLevelFeeModal">
                                <i class="bi bi-plus-circle me-2"></i>Créer un Frais de Niveau
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Level Fee Modal -->
<div class="modal fade" id="createLevelFeeModal" tabindex="-1" aria-labelledby="createLevelFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLevelFeeModalLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Nouveau Frais de Niveau
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createLevelFeeForm" method="POST" action="{{ route('fees.level-fee.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="level_id" class="form-label">Niveau <span class="text-danger">*</span></label>
                                <select class="form-select" id="level_id" name="level_id" required>
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($levels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }} ({{ $level->cycle }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fee_type" class="form-label">Type de Frais <span class="text-danger">*</span></label>
                                <select class="form-select" id="fee_type" name="fee_type" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="tuition">Scolarité</option>
                                    <option value="registration">Inscription</option>
                                    <option value="uniform">Uniforme</option>
                                    <option value="transport">Transport</option>
                                    <option value="meal">Cantine</option>
                                    <option value="books">Livres</option>
                                    <option value="activities">Activités</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du Frais <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Fréquence <span class="text-danger">*</span></label>
                                <select class="form-select" id="frequency" name="frequency" required>
                                    <option value="">Sélectionner une fréquence</option>
                                    <option value="monthly">Mensuel</option>
                                    <option value="quarterly">Trimestriel</option>
                                    <option value="yearly">Annuel</option>
                                    <option value="one_time">Unique</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Date d'Échéance</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" checked>
                                <label class="form-check-label" for="is_mandatory">
                                    Frais obligatoire
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Créer le Frais
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la soumission du formulaire de création
    document.getElementById('createLevelFeeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
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
                const modal = bootstrap.Modal.getInstance(document.getElementById('createLevelFeeModal'));
                modal.hide();
                
                // Afficher un message de succès
                showToast('Frais de niveau créé avec succès !', 'success');
                
                // Recharger la page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Erreur lors de la création: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors de la création du frais', 'error');
        });
    });
});

// Fonction pour afficher les toasts
function showToast(message, type) {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Supprimer le toast après 5 secondes
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>
@endsection
