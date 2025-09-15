@extends('layouts.app')

@section('title', 'Gestion des Notes - Egesco')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
<li class="breadcrumb-item active">Notes</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Notes</h1>
                    <p class="text-muted">Saisissez et consultez les notes des élèves</p>
                </div>
                <div>
                    <a href="{{ route('grades.create') }}" class="btn btn-primary">
                        <i class="bi bi-journal-plus me-2"></i>
                        Saisir des notes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['totalGrades'] ?? 0 }}</h4>
                            <span>Notes saisies</span>
                        </div>
                        <i class="bi bi-journal-text fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['averageGrade'] ?? 0 }}/20</h4>
                            <span>Moyenne générale</span>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['pendingGrades'] ?? 0 }}</h4>
                            <span>En attente de saisie</span>
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
                            <h4 class="mb-0">{{ $stats['completionRate'] ?? 0 }}%</h4>
                            <span>Taux de complétion</span>
                        </div>
                        <i class="bi bi-check-all fs-1 opacity-50"></i>
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
                    <form method="GET" action="{{ route('grades.index') }}">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Classe</label>
                                <select class="form-select" name="class_id" id="classFilter">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes ?? [] as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Matière</label>
                                <select class="form-select" name="subject_id" id="subjectFilter">
                                    <option value="">Toutes les matières</option>
                                    @foreach($subjects ?? [] as $subject)
                                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type d'évaluation</label>
                                <select class="form-select" name="exam_type" id="examTypeFilter">
                                    <option value="">Tous les types</option>
                                    <option value="devoir" {{ request('exam_type') == 'devoir' ? 'selected' : '' }}>Devoir</option>
                                    <option value="composition" {{ request('exam_type') == 'composition' ? 'selected' : '' }}>Composition</option>
                                    <option value="controle" {{ request('exam_type') == 'controle' ? 'selected' : '' }}>Contrôle</option>
                                    <option value="oral" {{ request('exam_type') == 'oral' ? 'selected' : '' }}>Oral</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Trimestre</label>
                                <select class="form-select" name="term" id="termFilter">
                                    <option value="">Tous</option>
                                    <option value="1er trimestre" {{ request('term') == '1er trimestre' ? 'selected' : '' }}>1er trimestre</option>
                                    <option value="2ème trimestre" {{ request('term') == '2ème trimestre' ? 'selected' : '' }}>2ème trimestre</option>
                                    <option value="3ème trimestre" {{ request('term') == '3ème trimestre' ? 'selected' : '' }}>3ème trimestre</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Enseignant</label>
                                <select class="form-select" name="teacher_id" id="teacherFilter">
                                    <option value="">Tous</option>
                                    @foreach($teachers ?? [] as $teacher)
                                        <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button type="submit" class="btn btn-outline-success w-100">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-journal-text me-2"></i>
                        Relevé de notes
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleView('table')">
                            <i class="bi bi-table"></i> Tableau
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleView('cards')">
                            <i class="bi bi-grid"></i> Cartes
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(isset($grades) && $grades->count() > 0)
                        <div class="table-responsive" id="gradesTable">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Classe</th>
                                        <th>Note cumulée</th>
                                        <th>Nombre de notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grades as $studentData)
                                        @php
                                            $student = $studentData['student'];
                                            $cumulativeScore = $studentData['cumulative_score'];
                                            $cumulativeGradeColor = $studentData['cumulative_grade_color'];
                                            $totalGrades = $studentData['total_grades'];
                                            $class = $studentData['class'];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-initials small primary me-2 fade-in">
                                                        {{ substr($student->first_name ?? 'E', 0, 1) }}{{ substr($student->last_name ?? 'L', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold">{{ $student->first_name ?? 'N/A' }} {{ $student->last_name ?? 'N/A' }}</span>
                                                        <br>
                                                        <small class="text-muted">ID: {{ $student->id }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="badge bg-primary">{{ $class->name ?? 'N/A' }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ optional($class->level)->name ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="h5 mb-0 me-2 text-{{ $cumulativeGradeColor }}">{{ $cumulativeScore }}/20</span>
                                                    <div class="progress" style="width: 80px; height: 8px;">
                                                        <div class="progress-bar bg-{{ $cumulativeGradeColor }}" data-width="{{ $studentData['cumulative_percentage'] }}"></div>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    @if($studentData['cumulative_percentage'] >= 80) Excellent
                                                    @elseif($studentData['cumulative_percentage'] >= 70) Très bien
                                                    @elseif($studentData['cumulative_percentage'] >= 60) Bien
                                                    @elseif($studentData['cumulative_percentage'] >= 50) Assez bien
                                                    @elseif($studentData['cumulative_percentage'] >= 40) Passable
                                                    @else Insuffisant
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <span class="badge bg-info fs-6">{{ $totalGrades }}</span>
                                                    <br>
                                                    <small class="text-muted">notes saisies</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('grades.bulletin', $studentData['student']->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Voir bulletin">
                                                        <i class="bi bi-journal-text"></i>
                                                        <span class="d-none d-md-inline ms-1">Bulletin</span>
                                                    </a>
                                                    <a href="{{ route('grades.create', ['student_id' => $studentData['student']->id]) }}" 
                                                       class="btn btn-sm btn-outline-success" title="Ajouter une note">
                                                        <i class="bi bi-plus-circle"></i>
                                                        <span class="d-none d-md-inline ms-1">Ajouter</span>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Modifier" 
                                                            data-student-id="{{ $studentData['student']->id }}" data-action="edit">
                                                        <i class="bi bi-pencil"></i>
                                                        <span class="d-none d-md-inline ms-1">Modifier</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" 
                                                            data-student-id="{{ $studentData['student']->id }}" 
                                                            data-student-name="{{ $studentData['student']->first_name ?? 'N/A' }} {{ $studentData['student']->last_name ?? 'N/A' }}" 
                                                            data-action="delete">
                                                        <i class="bi bi-trash"></i>
                                                        <span class="d-none d-md-inline ms-1">Supprimer</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination alignée au style Élèves -->
                        @if($grades->hasPages())
                            <div class="card-footer bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        Affichage de {{ $grades->firstItem() ?? 0 }} à {{ $grades->lastItem() ?? 0 }} sur {{ $grades->total() ?? 0 }} élèves
                                    </div>
                                    <nav aria-label="Pagination des notes">
                                        {{ $grades->links('pagination::bootstrap-5') }}
                                    </nav>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-journal-x fs-1 text-muted"></i>
                            <h5 class="text-muted mt-3">Aucun élève avec des notes trouvé</h5>
                            <p class="text-muted">Commencez par saisir des notes pour les élèves</p>
                            <a href="{{ route('grades.create') }}" class="btn btn-primary">
                                <i class="bi bi-journal-plus me-2"></i>
                                Saisir la première note
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>



@endsection



@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
// Fonction pour afficher les toasts
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Créer le conteneur de toast s'il n'existe pas
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    document.getElementById('toast-container').insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Supprimer le toast après qu'il soit caché
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
function resetFilters() {
    document.getElementById('classFilter').value = '';
    document.getElementById('subjectFilter').value = '';
    document.getElementById('examTypeFilter').value = '';
    document.getElementById('termFilter').value = '';
    document.getElementById('teacherFilter').value = '';
    
    // Soumettre le formulaire pour appliquer les filtres
    document.querySelector('form').submit();
}

function toggleView(type) {
    if (type === 'cards') {
        alert('Vue en cartes à implémenter');
    }
}

    // Auto-submit du formulaire lors du changement de filtre
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelects = document.querySelectorAll('select[name]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
        
        // Appliquer les largeurs des barres de progression
        document.querySelectorAll('.progress-bar[data-width]').forEach(bar => {
            const width = bar.dataset.width;
            bar.style.width = width + '%';
        });
    });

// Gestionnaire d'événements pour les boutons d'action
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer les largeurs des barres de progression
    document.querySelectorAll('.progress-bar[data-width]').forEach(bar => {
        const width = bar.dataset.width;
        bar.style.width = width + '%';
    });
    
    // Gestionnaire pour les boutons d'action des notes
    document.addEventListener('click', function(e) {
        const button = e.target.closest('button[data-action]');
        if (!button) return;
        
        const action = button.dataset.action;
        const studentId = button.dataset.studentId;
        
        switch(action) {
            case 'show':
                showStudentGrades(studentId);
                break;
            case 'edit':
                editStudentGrades(studentId);
                break;
            case 'delete':
                const studentName = button.dataset.studentName;
                deleteStudentGrades(studentId, studentName);
                break;
        }
    });
});

// Fonction pour afficher les notes d'un élève
function showStudentGrades(studentId) {
    // Rediriger vers le bulletin de l'élève
    window.location.href = `/grades/student/${studentId}/bulletin`;
}

// Fonction pour éditer les notes d'un élève
function editStudentGrades(studentId) {
    // Rediriger vers la page de gestion des notes de l'élève
    window.location.href = `/grades/student/${studentId}/manage`;
}

// Fonction pour supprimer les notes d'un élève
function deleteStudentGrades(studentId, studentName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer toutes les notes de ${studentName} ?\n\nCette action est irréversible.`)) {
        // Créer un formulaire temporaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/grades/student/${studentId}/delete-all`;
        
        // Ajouter le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.getAttribute('content');
            form.appendChild(csrfInput);
        }
        
        // Ajouter la méthode DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Ajouter le formulaire au DOM et le soumettre
        document.body.appendChild(form);
        form.submit();
    }
}


</script>
@endpush 
