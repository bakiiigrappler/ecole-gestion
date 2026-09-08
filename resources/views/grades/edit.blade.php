@extends('layouts.app')

@section('title', 'Modifier une Note - Egesco')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('grades.index') }}">Notes</a></li>
<li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Modifier une Note</h1>
                    <p class="text-muted">Modifiez les informations de cette note</p>
                </div>
                <div>
                    <a href="{{ route('grades.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Retour aux notes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informations de la note
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-fill text-primary fs-4 me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Élève</small>
                                    <strong>{{ $grade->student->first_name ?? 'N/A' }} {{ $grade->student->last_name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-house-door-fill text-primary fs-4 me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Classe</small>
                                    <strong>{{ $grade->schoolClass->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-book-fill text-primary fs-4 me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Matière</small>
                                    <strong>{{ $grade->subject->name ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar3 text-primary fs-4 me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Trimestre</small>
                                    <strong>{{ $grade->term ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-star-fill text-warning fs-4 me-2"></i>
                                <div>
                                    <small class="text-muted d-block">Note actuelle</small>
                                    <strong class="text-{{ $grade->grade_color }}">{{ $grade->formatted_score }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Type d'évaluation</small>
                            <strong>{{ ucfirst($grade->exam_type) ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Enseignant</small>
                            <strong>{{ $grade->teacher->first_name ?? 'N/A' }} {{ $grade->teacher->last_name ?? '' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Date de l'évaluation</small>
                            <strong>{{ $grade->exam_date ? $grade->exam_date->format('d/m/Y') : 'N/A' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Modifier la note
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('grades.update', $grade->id) }}" method="POST" id="editGradeForm">
                        @csrf
                        @method('PUT')
                        
                        <!-- Champs cachés pour les informations fixes -->
                        <input type="hidden" name="student_id" value="{{ $grade->student_id }}">
                        <input type="hidden" name="class_id" value="{{ $grade->class_id }}">
                        <input type="hidden" name="subject_id" value="{{ $grade->subject_id }}">
                        <input type="hidden" name="teacher_id" value="{{ $grade->teacher_id }}">
                        <input type="hidden" name="exam_type" value="{{ $grade->exam_type }}">
                        <input type="hidden" name="term" value="{{ $grade->term }}">
                        <input type="hidden" name="exam_date" value="{{ $grade->exam_date ? $grade->exam_date->format('Y-m-d') : '' }}">
                        
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note :</strong> Seuls les champs de note (Note et Sur) et les commentaires sont modifiables. Les autres informations sont fixes.
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-5">
                                <label for="score" class="form-label fw-bold">Note obtenue <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="bi bi-pencil-fill"></i>
                                    </span>
                                    <input type="number" class="form-control form-control-lg @error('score') is-invalid @enderror" 
                                           id="score" name="score" min="0" step="0.25" 
                                           value="{{ old('score', $grade->score) }}" 
                                           placeholder="Ex: 15.5"
                                           required autofocus>
                                    @error('score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Entrez la nouvelle note obtenue par l'élève</small>
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-center justify-content-center">
                                <div class="text-center pt-4">
                                    <i class="bi bi-slash-lg fs-1 text-muted"></i>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <label for="max_score" class="form-label fw-bold">Note maximale <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="bi bi-calculator-fill"></i>
                                    </span>
                                    <input type="number" class="form-control form-control-lg @error('max_score') is-invalid @enderror" 
                                           id="max_score" name="max_score" min="1" step="0.25"
                                           value="{{ old('max_score', $grade->max_score) }}"
                                           placeholder="Ex: 20"
                                           required>
                                    @error('max_score')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Entrez la note maximale possible</small>
                            </div>
                            
                            <div class="col-12">
                                <hr class="my-3">
                            </div>
                            
                            <div class="col-12">
                                <label for="comments" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text me-2"></i>Commentaires
                                </label>
                                <textarea class="form-control @error('comments') is-invalid @enderror" 
                                          id="comments" name="comments" rows="4" 
                                          placeholder="Ajoutez un commentaire sur cette note (optionnel)…">{{ old('comments', $grade->comments) }}</textarea>
                                @error('comments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Vous pouvez ajouter ou modifier un commentaire pour cette note</small>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Enregistrer la modification
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
    const form = document.getElementById('editGradeForm');
    const scoreInput = document.getElementById('score');
    const maxScoreInput = document.getElementById('max_score');

    // Validation en temps réel
    function validateScore() {
        const score = parseFloat(scoreInput.value);
        const maxScore = parseFloat(maxScoreInput.value);
        
        if (isNaN(score) || isNaN(maxScore)) {
            return;
        }
        
        if (score > maxScore) {
            scoreInput.setCustomValidity('La note ne peut pas dépasser le maximum');
            scoreInput.classList.add('is-invalid');
            
            // Afficher un message d'erreur personnalisé
            let feedback = scoreInput.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                scoreInput.parentNode.appendChild(feedback);
            }
            feedback.textContent = `La note (${score}) ne peut pas dépasser le maximum (${maxScore})`;
        } else if (score < 0) {
            scoreInput.setCustomValidity('La note ne peut pas être négative');
            scoreInput.classList.add('is-invalid');
        } else {
            scoreInput.setCustomValidity('');
            scoreInput.classList.remove('is-invalid');
            
            // Supprimer le message d'erreur
            const feedback = scoreInput.parentNode.querySelector('.invalid-feedback:not([class*="error"])');
            if (feedback) {
                feedback.remove();
            }
        }
    }
    
    // Calculer et afficher le pourcentage
    function displayPercentage() {
        const score = parseFloat(scoreInput.value);
        const maxScore = parseFloat(maxScoreInput.value);
        
        if (!isNaN(score) && !isNaN(maxScore) && maxScore > 0) {
            const percentage = ((score / maxScore) * 100).toFixed(2);
            const color = percentage >= 50 ? 'success' : 'danger';
            
            // Afficher le pourcentage sous le champ
            let percentDisplay = document.getElementById('percentDisplay');
            if (!percentDisplay) {
                percentDisplay = document.createElement('div');
                percentDisplay.id = 'percentDisplay';
                percentDisplay.className = 'mt-2';
                maxScoreInput.parentNode.parentNode.appendChild(percentDisplay);
            }
            percentDisplay.innerHTML = `<span class="badge bg-${color} fs-6">${percentage}%</span>`;
        }
    }

    scoreInput.addEventListener('input', function() {
        validateScore();
        displayPercentage();
    });
    
    maxScoreInput.addEventListener('input', function() {
        validateScore();
        displayPercentage();
    });
    
    // Afficher le pourcentage au chargement
    displayPercentage();

    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        validateScore();
        
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            
            // Focus sur le premier champ invalide
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
        form.classList.add('was-validated');
    });
});
</script>
@endpush
