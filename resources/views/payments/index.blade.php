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
                    <p class="text-muted">Enregistrez et suivez les paiements des frais scolaires</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                        <i class="bi bi-credit-card me-2"></i>
                        Nouveau paiement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['totalRevenue'] ?? 0, 0, ',', ' ') }}</h4>
                            <span>Total revenus (FCFA)</span>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['totalPayments'] ?? 0 }}</h4>
                            <span>Paiements effectués</span>
                        </div>
                        <i class="bi bi-credit-card fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['pendingPayments'] ?? 0 }}</h4>
                            <span>En attente</span>
                        </div>
                        <i class="bi bi-clock fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['monthlyRevenue'] ?? 0, 0, ',', ' ') }}</h4>
                            <span>Ce mois (FCFA)</span>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Historique des paiements
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-funnel"></i> Filtrer
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>N° Reçu</th>
                                    <th>Élève</th>
                                    <th>Type de frais</th>
                                    <th>Montant</th>
                                    <th>Mode de paiement</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $payment->reference }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $student = optional(optional($payment->enrollment)->student);
                                                $firstInitial = mb_substr($student->first_name ?? 'N', 0, 1);
                                                $lastInitial = mb_substr($student->last_name ?? 'A', 0, 1);
                                                $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'Élève inconnu';
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                    {{ $firstInitial }}{{ $lastInitial }}
                                                </div>
                                                <span>{{ $studentName }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @php $className = optional(optional($payment->enrollment)->schoolClass)->name ?? 'N/A'; @endphp
                                            <span class="badge bg-primary">{{ $className }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                                        </td>
                                        <td>
                                            @php
                                                $methodLabels = [
                                                    'cash' => 'Espèces',
                                                    'check' => 'Chèque',
                                                    'bank_transfer' => 'Virement',
                                                    'mobile_money' => 'Mobile Money',
                                                    'card' => 'Carte'
                                                ];
                                            @endphp
                                            <span class="badge bg-info">{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $payment->payment_date->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $statusBadges = [
                                                    'completed' => 'bg-success',
                                                    'pending' => 'bg-warning',
                                                    'cancelled' => 'bg-danger'
                                                ];
                                                $statusLabels = [
                                                    'completed' => 'Confirmé',
                                                    'pending' => 'En attente',
                                                    'cancelled' => 'Annulé'
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusBadges[$payment->status] ?? 'bg-secondary' }}">
                                                {{ $statusLabels[$payment->status] ?? $payment->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Voir détails" 
                                                        onclick="showPayment({{ $payment->id }})">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Modifier" 
                                                        onclick="editPayment({{ $payment->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" 
                                                        onclick="deletePayment({{ $payment->id }}, '{{ $payment->reference }}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                <p class="mb-0">Aucun paiement trouvé</p>
                                                <small>Commencez par créer votre premier paiement</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Payment Modal -->
<div class="modal fade" id="newPaymentModal" tabindex="-1" aria-labelledby="newPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPaymentModalLabel">
                    <i class="bi bi-credit-card me-2"></i>
                    Nouveau paiement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPaymentForm" action="{{ route('payments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="enrollment_id_modal" class="form-label">Inscription <span class="text-danger">*</span></label>
                            <select class="form-select" id="enrollment_id_modal" name="enrollment_id" required>
                                <option value="">Sélectionner une inscription...</option>
                                @foreach(($enrollments ?? []) as $enrollment)
                                    <option value="{{ $enrollment->id }}">
                                        {{ optional($enrollment->student)->first_name }} {{ optional($enrollment->student)->last_name }}
                                        - {{ optional($enrollment->schoolClass)->name }}
                                        ({{ $enrollment->academicYear->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="amount_modal" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount_modal" name="amount" min="0" step="100" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_date_modal" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date_modal" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method_modal" class="form-label">Méthode de paiement <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method_modal" name="payment_method" required>
                                <option value="">Sélectionner une méthode...</option>
                                @foreach(($paymentMethods ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reference_modal" class="form-label">Référence</label>
                            <input type="text" class="form-control" id="reference_modal" name="reference" placeholder="Laissez vide pour génération automatique">
                        </div>
                        <div class="col-md-6">
                            <label for="notes_modal" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes_modal" name="notes" rows="2" placeholder="Commentaires optionnels"></textarea>
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

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">
                    <i class="bi bi-pencil text-warning me-2"></i>
                    Modifier le Paiement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_enrollment_id" class="form-label">Inscription <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_enrollment_id" name="enrollment_id" required>
                                <option value="">Sélectionner une inscription...</option>
                                @foreach(($enrollments ?? []) as $enrollment)
                                    <option value="{{ $enrollment->id }}">
                                        {{ optional($enrollment->student)->first_name }} {{ optional($enrollment->student)->last_name }}
                                        - {{ optional($enrollment->schoolClass)->name }}
                                        ({{ $enrollment->academicYear->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_amount" name="amount" min="0" step="100" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_payment_date" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_payment_date" name="payment_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_payment_method" class="form-label">Méthode de paiement <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_payment_method" name="payment_method" required>
                                <option value="">Sélectionner une méthode...</option>
                                @foreach(($paymentMethods ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_reference" class="form-label">Référence</label>
                            <input type="text" class="form-control" id="edit_reference" name="reference">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="completed">Confirmé</option>
                                <option value="pending">En attente</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="2" placeholder="Commentaires optionnels"></textarea>
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

<!-- Show Payment Modal -->
<div class="modal fade" id="showPaymentModal" tabindex="-1" aria-labelledby="showPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showPaymentModalLabel">
                    <i class="bi bi-eye text-primary me-2"></i>
                    Détails du Paiement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="showPaymentModalBody">
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

<!-- Delete Payment Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le paiement <strong id="paymentReferenceToDelete"></strong> ?</p>
                <p class="text-danger small">
                    <i class="bi bi-info-circle me-1"></i>
                    Cette action est irréversible.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Annuler
                </button>
                <form id="deletePaymentForm" method="POST" style="display: inline;">
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
// Fonction pour afficher un paiement
function showPayment(id) {
    fetch(`/payments/${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('showPaymentModalBody').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('showPaymentModal'));
            modal.show();
        })
        .catch(error => {
            showToast('Erreur lors du chargement des détails', 'error');
        });
}

// Fonction pour éditer un paiement
function editPayment(id) {
    fetch(`/payments/${id}/edit`)
        .then(response => response.text())
        .then(html => {
            // Extraire les données du HTML pour remplir le formulaire
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Remplir le formulaire avec les données existantes
            document.getElementById('edit_enrollment_id').value = doc.querySelector('select[name="enrollment_id"]')?.value || '';
            document.getElementById('edit_amount').value = doc.querySelector('input[name="amount"]')?.value || '';
            document.getElementById('edit_payment_date').value = doc.querySelector('input[name="payment_date"]')?.value || '';
            document.getElementById('edit_payment_method').value = doc.querySelector('select[name="payment_method"]')?.value || '';
            document.getElementById('edit_reference').value = doc.querySelector('input[name="reference"]')?.value || '';
            document.getElementById('edit_status').value = doc.querySelector('select[name="status"]')?.value || '';
            document.getElementById('edit_notes').value = doc.querySelector('textarea[name="notes"]')?.value || '';
            
            // Mettre à jour l'action du formulaire
            document.getElementById('editPaymentForm').action = `/payments/${id}`;
            
            const modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
            modal.show();
        })
        .catch(error => {
            showToast('Erreur lors du chargement des données', 'error');
        });
}

// Fonction pour supprimer un paiement
function deletePayment(id, reference) {
    document.getElementById('paymentReferenceToDelete').textContent = reference;
    document.getElementById('deletePaymentForm').action = `/payments/${id}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
    modal.show();
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
document.getElementById('createPaymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || 'Erreur lors de la création');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('newPaymentModal'));
        modal.hide();
        
        // Show success message
        showToast('Paiement enregistré avec succès !', 'success');
        
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
document.getElementById('editPaymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(this);
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || 'Erreur lors de la modification');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
        modal.hide();
        
        // Show success message
        showToast('Paiement modifié avec succès !', 'success');
        
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
document.getElementById('deletePaymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(new FormData(this))
        });
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || 'Erreur lors de la suppression');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
        modal.hide();
        
        // Show success message
        showToast('Paiement supprimé avec succès !', 'success');
        
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
