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
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informations actuelles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Élève:</strong>
                            <p class="mb-0">{{ $grade->student->first_name ?? 'N/A' }} {{ $grade->student->last_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Classe:</strong>
                            <p class="mb-0">{{ $grade->schoolClass->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Matière:</strong>
                            <p class="mb-0">{{ $grade->subject->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-3">
                            <strong>Note actuelle:</strong>
                            <p class="mb-0 text-{{ $grade->grade_color }}">{{ $grade->formatted_score }}</p>
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
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Élève <span class="text-danger">*</span></label>
                                <select class="form-select @error('student_id') is-invalid @enderror" id="student_id" name="student_id" required>
                                    <option value="">Sélectionner un élève…</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ old('student_id', $grade->student_id) == $student->id ? 'selected' : '' }}>
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                                    <option value="">Sélectionner une classe…</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class_id', $grade->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="subject_id" class="form-label">Matière <span class="text-danger">*</span></label>
                                <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                                    <option value="">Sélectionner une matière…</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}" {{ old('subject_id', $grade->subject_id) == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="teacher_id" class="form-label">Enseignant <span class="text-danger">*</span></label>
                                <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id" name="teacher_id" required>
                                    <option value="">Sélectionner un enseignant…</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ old('teacher_id', $grade->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="exam_type" class="form-label">Type d'évaluation <span class="text-danger">*</span></label>
                                <select class="form-select @error('exam_type') is-invalid @enderror" id="exam_type" name="exam_type" required>
                                    <option value="">Sélectionner…</option>
                                    <option value="devoir" {{ old('exam_type', $grade->exam_type) == 'devoir' ? 'selected' : '' }}>Devoir</option>
                                    <option value="composition" {{ old('exam_type', $grade->exam_type) == 'composition' ? 'selected' : '' }}>Composition</option>
                                    <option value="controle" {{ old('exam_type', $grade->exam_type) == 'controle' ? 'selected' : '' }}>Contrôle</option>
                                    <option value="oral" {{ old('exam_type', $grade->exam_type) == 'oral' ? 'selected' : '' }}>Oral</option>
                                </select>
                                @error('exam_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="term" class="form-label">Trimestre <span class="text-danger">*</span></label>
                                <select class="form-select @error('term') is-invalid @enderror" id="term" name="term" required>
                                    <option value="">Sélectionner…</option>
                                    <option value="1er trimestre" {{ old('term', $grade->term) == '1er trimestre' ? 'selected' : '' }}>1er trimestre</option>
                                    <option value="2ème trimestre" {{ old('term', $grade->term) == '2ème trimestre' ? 'selected' : '' }}>2ème trimestre</option>
                                    <option value="3ème trimestre" {{ old('term', $grade->term) == '3ème trimestre' ? 'selected' : '' }}>3ème trimestre</option>
                                </select>
                                @error('term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4">
                                <label for="score" class="form-label">Note <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('score') is-invalid @enderror" 
                                       id="score" name="score" min="0" step="0.5" 
                                       value="{{ old('score', $grade->score) }}" required>
                                @error('score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4">
                                <label for="max_score" class="form-label">Sur <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('max_score') is-invalid @enderror" 
                                       id="max_score" name="max_score" min="1" 
                                       value="{{ old('max_score', $grade->max_score) }}" required>
                                @error('max_score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4">
                                <label for="exam_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('exam_date') is-invalid @enderror" 
                                       id="exam_date" name="exam_date" 
                                       value="{{ old('exam_date', $grade->exam_date ? $grade->exam_date->format('Y-m-d') : '') }}" required>
                                @error('exam_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="comments" class="form-label">Commentaires</label>
                                <textarea class="form-control @error('comments') is-invalid @enderror" 
                                          id="comments" name="comments" rows="3" 
                                          placeholder="Commentaires optionnels…">{{ old('comments', $grade->comments) }}</textarea>
                                @error('comments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('grades.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Mettre à jour
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer les select avec recherche
    const selects = ['student_id', 'class_id', 'subject_id', 'teacher_id'];
    selects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) {
            new Choices(select, {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: ''
            });
        }
    });

    // Validation en temps réel
    const form = document.getElementById('editGradeForm');
    const scoreInput = document.getElementById('score');
    const maxScoreInput = document.getElementById('max_score');

    function validateScore() {
        const score = parseFloat(scoreInput.value);
        const maxScore = parseFloat(maxScoreInput.value);
        
        if (score > maxScore) {
            scoreInput.setCustomValidity('La note ne peut pas dépasser le maximum');
            scoreInput.classList.add('is-invalid');
        } else {
            scoreInput.setCustomValidity('');
            scoreInput.classList.remove('is-invalid');
        }
    }

    scoreInput.addEventListener('input', validateScore);
    maxScoreInput.addEventListener('input', validateScore);

    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>
@endpush
