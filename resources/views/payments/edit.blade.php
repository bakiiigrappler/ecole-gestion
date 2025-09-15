@extends('layouts.app')

@section('title', 'Modifier le Paiement - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Paiements</a></li>
<li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Modifier le Paiement</h1>
                    <p class="text-muted">Modifiez les informations du paiement #{{ $payment->reference }}</p>
                </div>
                <div>
                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Payment Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informations actuelles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Référence:</strong><br>
                            <span class="text-muted">{{ $payment->reference }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Montant:</strong><br>
                            <span class="text-success fw-bold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Date:</strong><br>
                            <span class="text-muted">{{ $payment->payment_date->format('d/m/Y') }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Statut:</strong><br>
                            <span class="badge bg-success">Terminé</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Modifier les informations
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.update', $payment->id) }}" method="POST" id="editPaymentForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Inscription -->
                            <div class="col-md-6 mb-3">
                                <label for="enrollment_id" class="form-label">Inscription <span class="text-danger">*</span></label>
                                <select class="form-select @error('enrollment_id') is-invalid @enderror" 
                                        id="enrollment_id" name="enrollment_id" required>
                                    <option value="">Sélectionner une inscription</option>
                                    @foreach($enrollments as $enrollment)
                                        <option value="{{ $enrollment->id }}" {{ old('enrollment_id', $payment->enrollment_id) == $enrollment->id ? 'selected' : '' }}>
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
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" value="{{ old('amount', $payment->amount) }}" 
                                       min="0" step="0.01" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Date de paiement -->
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                       id="payment_date" name="payment_date" 
                                       value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Méthode de paiement -->
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Méthode de paiement <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_method') is-invalid @enderror" 
                                        id="payment_method" name="payment_method" required>
                                    <option value="">Sélectionner une méthode</option>
                                    @foreach($paymentMethods as $value => $label)
                                        <option value="{{ $value }}" {{ old('payment_method', $payment->payment_method) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Référence -->
                        <div class="mb-3">
                            <label for="reference" class="form-label">Référence</label>
                            <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                                   id="reference" name="reference" value="{{ old('reference', $payment->reference) }}">
                            @error('reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Laissez vide pour générer automatiquement une référence</div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="bi bi-check-circle me-2"></i>
            <strong class="me-auto">Succès</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Paiement mis à jour avec succès !
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Choices.js pour les selects
    if (typeof Choices !== 'undefined') {
        new Choices('#enrollment_id', {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Sélectionner une inscription'
        });
        
        new Choices('#payment_method', {
            searchEnabled: false,
            itemSelectText: ''
        });
    }

    // Validation côté client
    const form = document.getElementById('editPaymentForm');
    const amountInput = document.getElementById('amount');

    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });

    // Validation du montant
    amountInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (value < 0) {
            this.setCustomValidity('Le montant ne peut pas être négatif');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
@endsection
