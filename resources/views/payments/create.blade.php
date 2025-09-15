@extends('layouts.app')

@section('title', 'Nouveau Paiement - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Paiements</a></li>
<li class="breadcrumb-item active">Nouveau paiement</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Nouveau Paiement</h1>
                    <p class="text-muted">Enregistrer un nouveau paiement de frais scolaires</p>
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

    <!-- Payment Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Informations du paiement
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">Informations de base</h6>
                                
                                <div class="mb-3">
                                    <label for="enrollment_id" class="form-label">Inscription <span class="text-danger">*</span></label>
                                    <select class="form-select @error('enrollment_id') is-invalid @enderror" id="enrollment_id" name="enrollment_id" required>
                                        <option value="">Sélectionner une inscription...</option>
                                        @foreach($enrollments as $enrollment)
                                            <option value="{{ $enrollment->id }}" {{ old('enrollment_id') == $enrollment->id ? 'selected' : '' }}>
                                                {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} 
                                                - {{ $enrollment->schoolClass->name }} 
                                                ({{ $enrollment->academicYear->year }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('enrollment_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" name="amount" min="0" step="100" 
                                           value="{{ old('amount') }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="payment_date" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                           id="payment_date" name="payment_date" 
                                           value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                    @error('payment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="fw-bold text-success mb-3">Méthode et détails</h6>
                                
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Méthode de paiement <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                                        <option value="">Sélectionner une méthode...</option>
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

                                <div class="mb-3">
                                    <label for="reference" class="form-label">Référence</label>
                                    <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                                           id="reference" name="reference" 
                                           value="{{ old('reference') }}" 
                                           placeholder="Laissez vide pour générer automatiquement">
                                    @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Laissez vide pour générer une référence automatique</small>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Commentaires</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" name="notes" rows="3" 
                                              placeholder="Commentaires optionnels...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Enregistrer le paiement
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Générer automatiquement une référence si le champ est vide
    const referenceInput = document.getElementById('reference');
    const enrollmentSelect = document.getElementById('enrollment_id');
    
    enrollmentSelect.addEventListener('change', function() {
        if (this.value && !referenceInput.value) {
            const now = new Date();
            const dateStr = now.getFullYear().toString() + 
                           (now.getMonth() + 1).toString().padStart(2, '0') + 
                           now.getDate().toString().padStart(2, '0');
            const randomId = Math.random().toString(36).substr(2, 9).toUpperCase();
            referenceInput.value = `PAY-${dateStr}-${randomId}`;
        }
    });
});
</script>
@endpush 
