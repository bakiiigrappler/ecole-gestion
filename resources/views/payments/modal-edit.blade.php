<!-- Contenu du modal d'édition -->
<div class="edit-payment-content">
    <!-- Informations actuelles -->
    <div class="current-info mb-4">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Informations actuelles
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <strong>Transaction ID:</strong><br>
                        <span class="text-muted">{{ $payment->transaction_id }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Montant actuel:</strong><br>
                        <span class="text-success fw-bold">{{ $payment->formatted_amount }}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Statut:</strong><br>
                        <span class="badge {{ $payment->status_badge_class }}">{{ $payment->status_label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire d'édition -->
    <div class="edit-form">
        <div class="row g-3">
            <!-- Inscription -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_enrollment_id" class="form-label fw-semibold">
                        <i class="bi bi-person-badge me-1"></i>Inscription <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('enrollment_id') is-invalid @enderror" 
                            id="edit_enrollment_id" name="enrollment_id" required>
                        <option value="">Sélectionner une inscription</option>
                        @foreach($enrollments as $enrollment)
                            <option value="{{ $enrollment->id }}" 
                                    {{ old('enrollment_id', $payment->enrollment_id) == $enrollment->id ? 'selected' : '' }}
                                    data-student-name="{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}"
                                    data-class-name="{{ $enrollment->schoolClass->name ?? 'N/A' }}">
                                {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}
                                @if($enrollment->schoolClass)
                                    - {{ $enrollment->schoolClass->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('enrollment_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Montant -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_amount" class="form-label fw-semibold">
                        <i class="bi bi-currency-exchange me-1"></i>Montant (FCFA) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" 
                               class="form-control @error('amount') is-invalid @enderror" 
                               id="edit_amount" 
                               name="amount" 
                               value="{{ old('amount', $payment->amount) }}" 
                               min="0" 
                               step="0.01" 
                               required>
                        <span class="input-group-text">FCFA</span>
                    </div>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Type de paiement -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_payment_type" class="form-label fw-semibold">
                        <i class="bi bi-credit-card me-1"></i>Type de Paiement <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('payment_type') is-invalid @enderror" 
                            id="edit_payment_type" name="payment_type" required>
                        <option value="">Sélectionner un type</option>
                        @foreach($paymentTypes as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('payment_type', $payment->payment_type) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Méthode de paiement -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_payment_method" class="form-label fw-semibold">
                        <i class="bi bi-wallet me-1"></i>Méthode de Paiement <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('payment_method') is-invalid @enderror" 
                            id="edit_payment_method" name="payment_method" required>
                        <option value="">Sélectionner une méthode</option>
                        @foreach($paymentMethods as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('payment_method', $payment->payment_method) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Date de paiement -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_paid_at" class="form-label fw-semibold">
                        <i class="bi bi-calendar me-1"></i>Date de Paiement
                    </label>
                    <input type="date" 
                           class="form-control @error('paid_at') is-invalid @enderror" 
                           id="edit_paid_at" 
                           name="paid_at" 
                           value="{{ old('paid_at', $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '') }}">
                    @error('paid_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Statut -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_status" class="form-label fw-semibold">
                        <i class="bi bi-flag me-1"></i>Statut <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('status') is-invalid @enderror" 
                            id="edit_status" name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" 
                                    {{ old('status', $payment->status) == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Informations du payeur -->
            <div class="col-12">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-person me-2"></i>Informations du Payeur
                </h6>
            </div>

            <!-- Nom du payeur -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="edit_payer_name" class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i>Nom complet <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('payer_name') is-invalid @enderror" 
                           id="edit_payer_name" 
                           name="payer_name" 
                           value="{{ old('payer_name', $payment->payer_name) }}" 
                           required>
                    @error('payer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Téléphone -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="edit_payer_phone" class="form-label fw-semibold">
                        <i class="bi bi-phone me-1"></i>Téléphone <span class="text-danger">*</span>
                    </label>
                    <input type="tel" 
                           class="form-control @error('payer_phone') is-invalid @enderror" 
                           id="edit_payer_phone" 
                           name="payer_phone" 
                           value="{{ old('payer_phone', $payment->payer_phone) }}" 
                           required>
                    @error('payer_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Email -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="edit_payer_email" class="form-label fw-semibold">
                        <i class="bi bi-envelope me-1"></i>Email
                    </label>
                    <input type="email" 
                           class="form-control @error('payer_email') is-invalid @enderror" 
                           id="edit_payer_email" 
                           name="payer_email" 
                           value="{{ old('payer_email', $payment->payer_email) }}">
                    @error('payer_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div class="col-12">
                <div class="form-group">
                    <label for="edit_notes" class="form-label fw-semibold">
                        <i class="bi bi-sticky me-1"></i>Notes
                    </label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="edit_notes" 
                              name="notes" 
                              rows="3" 
                              placeholder="Notes additionnelles...">{{ old('notes', $payment->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour le modal d'édition -->
<style>
.edit-payment-content {
    padding: 0;
}

.current-info .card {
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.edit-form .form-group {
    margin-bottom: 1.5rem;
}

.edit-form .form-label {
    color: #495057;
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
}

.edit-form .form-control,
.edit-form .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.edit-form .form-control:focus,
.edit-form .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.edit-form .input-group-text {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-left: none;
    color: #6c757d;
    font-weight: 600;
}

.edit-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.text-primary {
    color: #007bff !important;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-weight: 600;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
    color: #212529 !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%) !important;
}

.badge.bg-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}
</style>
