@extends('layouts.app')

@section('title', 'Modifier le Frais - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('fees.index') }}">Frais scolaires</a></li>
<li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Modifier le Frais</h1>
                    <p class="text-muted">Modifiez les informations du frais "{{ $fee->name }}"</p>
                </div>
                <div>
                    <a href="{{ route('fees.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Fee Info Card -->
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
                            <strong>Nom:</strong><br>
                            <span class="text-muted">{{ $fee->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Type:</strong><br>
                            <span class="badge bg-primary">{{ $fee->fee_type_label }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Montant:</strong><br>
                            <span class="text-success fw-bold">{{ $fee->formatted_amount }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Statut:</strong><br>
                            <span class="badge bg-{{ $fee->status_color }}">{{ $fee->status_label }}</span>
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
                    <form action="{{ route('fees.update', $fee->id) }}" method="POST" id="editFeeForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Nom du frais -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom du frais <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $fee->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Type de frais -->
                            <div class="col-md-6 mb-3">
                                <label for="fee_type" class="form-label">Type de frais <span class="text-danger">*</span></label>
                                <select class="form-select @error('fee_type') is-invalid @enderror" 
                                        id="fee_type" name="fee_type" required>
                                    <option value="">Sélectionner un type</option>
                                    @foreach($feeTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('fee_type', $fee->fee_type) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('fee_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $fee->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Montant -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" value="{{ old('amount', $fee->amount) }}" 
                                       min="0" step="0.01" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Fréquence -->
                            <div class="col-md-6 mb-3">
                                <label for="frequency" class="form-label">Fréquence <span class="text-danger">*</span></label>
                                <select class="form-select @error('frequency') is-invalid @enderror" 
                                        id="frequency" name="frequency" required>
                                    <option value="">Sélectionner une fréquence</option>
                                    @foreach($frequencies as $value => $label)
                                        <option value="{{ $value }}" {{ old('frequency', $fee->frequency) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Classe -->
                            <div class="col-md-6 mb-3">
                                <label for="class_id" class="form-label">Classe concernée</label>
                                <select class="form-select @error('class_id') is-invalid @enderror" 
                                        id="class_id" name="class_id">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $fee->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Année académique -->
                            <div class="col-md-6 mb-3">
                                <label for="academic_year_id" class="form-label">Année académique <span class="text-danger">*</span></label>
                                <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                        id="academic_year_id" name="academic_year_id" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $fee->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Date d'échéance -->
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Date d'échéance</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                   id="due_date" name="due_date" value="{{ old('due_date', $fee->due_date?->format('Y-m-d')) }}">
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Options -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_mandatory" 
                                           name="is_mandatory" {{ old('is_mandatory', $fee->is_mandatory) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_mandatory">
                                        Frais obligatoire
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" 
                                           name="is_active" {{ old('is_active', $fee->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Frais actif
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('fees.index') }}" class="btn btn-secondary">
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
            Frais mis à jour avec succès !
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Choices.js pour les selects
    if (typeof Choices !== 'undefined') {
        new Choices('#fee_type', {
            searchEnabled: false,
            itemSelectText: ''
        });
        
        new Choices('#frequency', {
            searchEnabled: false,
            itemSelectText: ''
        });
        
        new Choices('#class_id', {
            searchEnabled: true,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'Toutes les classes'
        });
        
        new Choices('#academic_year_id', {
            searchEnabled: false,
            itemSelectText: ''
        });
    }

    // Validation côté client
    const form = document.getElementById('editFeeForm');
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
