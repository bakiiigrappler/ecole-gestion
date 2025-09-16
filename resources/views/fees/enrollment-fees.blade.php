@extends('layouts.app')

@section('title', 'Frais d\'Inscription - StudiaGabon')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fees.dashboard') }}">Frais Scolaires</a></li>
<li class="breadcrumb-item active">Frais d'Inscription</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Frais d'Inscription</h1>
                    <p class="text-muted">Suivez les frais assignés aux étudiants pour {{ $academicYear->name }}</p>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <a href="{{ route('fees.report') }}" class="btn btn-outline-info">
                            <i class="bi bi-file-earmark-text me-2"></i>Rapport
                        </a>
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                            <i class="bi bi-check-circle me-2"></i>Marquer Payé
                        </button>
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
                    <form method="GET" action="{{ route('fees.enrollment-fees') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="class_id" class="form-label">Classe</label>
                            <select class="form-select" id="class_id" name="class_id">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }} ({{ $class->level->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <label for="is_paid" class="form-label">Statut Paiement</label>
                            <select class="form-select" id="is_paid" name="is_paid">
                                <option value="">Tous</option>
                                <option value="1" {{ request('is_paid') == '1' ? 'selected' : '' }}>Payé</option>
                                <option value="0" {{ request('is_paid') == '0' ? 'selected' : '' }}>Non payé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="is_overdue" class="form-label">Échéance</label>
                            <select class="form-select" id="is_overdue" name="is_overdue">
                                <option value="">Tous</option>
                                <option value="1" {{ request('is_overdue') == '1' ? 'selected' : '' }}>Échus</option>
                                <option value="0" {{ request('is_overdue') == '0' ? 'selected' : '' }}>En cours</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Rechercher</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Nom de l'étudiant...">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Filtrer
                            </button>
                            <a href="{{ route('fees.enrollment-fees') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Effacer
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment Fees Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>Liste des Frais d'Inscription
                        <span class="badge bg-primary ms-2">{{ $enrollmentFees->total() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($enrollmentFees->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Étudiant</th>
                                        <th>Classe</th>
                                        <th>Type</th>
                                        <th>Frais</th>
                                        <th>Montant</th>
                                        <th>Échéance</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enrollmentFees as $fee)
                                        <tr class="{{ $fee->is_overdue() ? 'table-danger' : '' }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input fee-checkbox" 
                                                       value="{{ $fee->id }}" data-fee-id="{{ $fee->id }}">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="student-avatar me-2">
                                                        {{ substr($fee->enrollment->student->first_name, 0, 1) }}{{ substr($fee->enrollment->student->last_name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <strong>{{ $fee->enrollment->student->first_name }} {{ $fee->enrollment->student->last_name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $fee->enrollment->student->matricule ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ $fee->enrollment->schoolClass->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $fee->enrollment->schoolClass->level->name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $fee->fee_type_label }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $fee->name }}</strong>
                                                @if($fee->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($fee->description, 30) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-success">{{ $fee->formatted_amount }}</strong>
                                                @if($fee->is_paid)
                                                    <br>
                                                    <small class="text-success">
                                                        <i class="bi bi-check-circle"></i> Payé
                                                    </small>
                                                @else
                                                    <br>
                                                    <small class="text-warning">
                                                        <i class="bi bi-clock"></i> En attente
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($fee->due_date)
                                                    <span class="{{ $fee->is_overdue() ? 'text-danger fw-bold' : 'text-muted' }}">
                                                        {{ $fee->due_date->format('d/m/Y') }}
                                                    </span>
                                                    @if($fee->is_overdue())
                                                        <br>
                                                        <small class="text-danger">
                                                            <i class="bi bi-exclamation-triangle"></i> {{ $fee->days_overdue }} jour(s) de retard
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Non défini</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $fee->payment_status_badge_class }}">
                                                    {{ $fee->payment_status_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    @if($fee->is_paid)
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="markAsUnpaid({{ $fee->id }})">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                                onclick="markAsPaid({{ $fee->id }})">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="modal" data-bs-target="#viewEnrollmentFeeModal{{ $fee->id }}">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    Affichage de {{ $enrollmentFees->firstItem() }} à {{ $enrollmentFees->lastItem() }} 
                                    sur {{ $enrollmentFees->total() }} résultats
                                </small>
                            </div>
                            <div>
                                {{ $enrollmentFees->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <h5 class="text-muted mt-3">Aucun frais d'inscription trouvé</h5>
                            <p class="text-muted">Aucun frais ne correspond aux critères de recherche.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markPaidModalLabel">
                    <i class="bi bi-check-circle text-success me-2"></i>Marquer comme Payé
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="markPaidForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Méthode de Paiement <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Sélectionner une méthode</option>
                            <option value="cash">Espèces</option>
                            <option value="bank_transfer">Virement bancaire</option>
                            <option value="check">Chèque</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_reference" class="form-label">Référence du Paiement</label>
                        <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                               placeholder="Numéro de transaction, chèque, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Notes additionnelles..."></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Frais sélectionnés :</strong>
                        <div id="selectedFeesList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Marquer comme Payé
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

.student-avatar {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection multiple
    const selectAllCheckbox = document.getElementById('selectAll');
    const feeCheckboxes = document.querySelectorAll('.fee-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        feeCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedFeesList();
    });
    
    feeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedFeesList);
    });
    
    function updateSelectedFeesList() {
        const selectedFees = Array.from(feeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.dataset.feeId);
        
        const selectedFeesList = document.getElementById('selectedFeesList');
        
        if (selectedFees.length === 0) {
            selectedFeesList.innerHTML = '<em class="text-muted">Aucun frais sélectionné</em>';
        } else {
            selectedFeesList.innerHTML = `<strong>${selectedFees.length}</strong> frais sélectionné(s)`;
        }
    }
    
    // Gestion de la soumission du formulaire de marquage
    document.getElementById('markPaidForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedFees = Array.from(feeCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.dataset.feeId);
        
        if (selectedFees.length === 0) {
            showToast('Veuillez sélectionner au moins un frais', 'error');
            return;
        }
        
        const formData = new FormData(this);
        formData.append('fee_ids', JSON.stringify(selectedFees));
        
        fetch('{{ route("fees.enrollment-fees") }}/mark-paid-bulk', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('markPaidModal'));
                modal.hide();
                showToast('Frais marqués comme payés avec succès !', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du marquage des frais', 'error');
        });
    });
    
    // Initialiser la liste des frais sélectionnés
    updateSelectedFeesList();
});

// Fonctions pour marquer individuellement
function markAsPaid(feeId) {
    if (confirm('Marquer ce frais comme payé ?')) {
        fetch(`/fees/enrollment-fee/${feeId}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Frais marqué comme payé !', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du marquage', 'error');
        });
    }
}

function markAsUnpaid(feeId) {
    if (confirm('Marquer ce frais comme non payé ?')) {
        fetch(`/fees/enrollment-fee/${feeId}/mark-unpaid`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Frais marqué comme non payé !', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du marquage', 'error');
        });
    }
}

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
