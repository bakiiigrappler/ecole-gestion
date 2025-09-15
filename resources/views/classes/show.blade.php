@extends('layouts.app')

@section('title', 'Détails de la Classe - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
<li class="breadcrumb-item active">{{ $class->name }}</li>
@endsection

@push('styles')
<link href="{{ asset('css/classes-enhanced.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 fw-bold">
                                <i class="bi bi-door-open me-3"></i>
                                Classe {{ $class->name }}
                            </h1>
                            <p class="mb-0 opacity-75">Détails et informations de la classe</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('classes.edit', $class) }}" class="btn btn-light btn-lg">
                                <i class="bi bi-pencil me-2"></i>
                                Modifier
                            </a>
                            <a href="{{ route('classes.students', $class) }}" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-people me-2"></i>
                                Élèves
                            </a>
                            <a href="{{ route('classes.index') }}" class="btn btn-outline-light">
                                <i class="bi bi-arrow-left me-2"></i>
                                Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations de la classe -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        Informations Générales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-tag text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Nom de la classe</small>
                                    <div class="fw-bold">{{ $class->name }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-bookmark text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Niveau</small>
                                    <div class="fw-bold">{{ $class->getSafeLevelName() }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-layers text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Cycle</small>
                                    <div class="fw-bold">
                                        @php
                                            $cycle = $class->getSafeCycle();
                                            $cycleIcons = [
                                                'preprimaire' => '🍼',
                                                'primaire' => '📝',
                                                'college' => '🏫', 
                                                'lycee' => '🎓'
                                            ];
                                        @endphp
                                        {{ $cycleIcons[$cycle] ?? '📚' }} {{ ucfirst($cycle) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-people text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Capacité</small>
                                    <div class="fw-bold">{{ $class->capacity ?? 'Non définie' }} places</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-{{ $class->is_active ? 'success' : 'danger' }} bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-toggle-{{ $class->is_active ? 'on' : 'off' }} text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Statut</small>
                                    <div class="fw-bold">
                                        <span class="badge bg-{{ $class->is_active ? 'success' : 'secondary' }}">
                                            {{ $class->is_active ? '✅ Active' : '❌ Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($class->description)
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="bg-secondary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="bi bi-journal-text text-dark"></i>
                                </div>
                                <div>
                                    <small class="text-muted">Description</small>
                                    <div class="fw-bold">{{ $class->description }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Professeurs de la classe -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-person-workspace me-2 text-info"></i>
                        Professeurs de la classe
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $teachers = $class->allTeachers()->with('subjects')->get();
                        $principalTeacher = $teachers->where('pivot.role', 'principal')->first();
                        $otherTeachers = $teachers->where('pivot.role', '!=', 'principal');
                    @endphp
                    
                    @if($teachers->count() > 0)
                        <div class="mb-3">
                            @if($principalTeacher)
                                <!-- Professeur Principal -->
                                <div class="d-flex align-items-center mb-3 p-3 bg-success bg-opacity-10 rounded border border-success">
                                    <div class="bg-success bg-opacity-20 rounded-circle p-2 me-3">
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-success">{{ $principalTeacher->first_name }} {{ $principalTeacher->last_name }}</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success">Principal</span>
                                            @if($principalTeacher->specialization)
                                                <small class="text-muted">({{ $principalTeacher->specialization }})</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($otherTeachers->count() > 0)
                                <!-- Autres professeurs -->
                                @foreach($otherTeachers as $teacher)
                                    <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                            <i class="bi bi-person text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $teacher->first_name }} {{ $teacher->last_name }}</div>
                                            <small class="text-muted">
                                                @if($teacher->specialization)
                                                    {{ $teacher->specialization }}
                                                @else
                                                    Enseignant
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-person-x fs-1"></i>
                            <p class="mb-0 mt-2">Aucun professeur assigné</p>
                        </div>
                    @endif
                    
                    <a href="{{ route('classes.teachers', $class) }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-person-workspace me-2"></i>
                        Gérer les professeurs
                    </a>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2 text-warning"></i>
                        Actions Rapides
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('classes.students', $class) }}" class="btn btn-primary">
                            <i class="bi bi-people me-2"></i>
                            Voir les élèves ({{ \App\Models\Student::whereHas('enrollments', function($q) use ($class) { $q->where('class_id', $class->id)->where('status', 'active'); })->count() }})
                        </a>
                        <a href="{{ route('schedules.show', $class) }}" class="btn btn-outline-info">
                            <i class="bi bi-calendar me-2"></i>
                            Emploi du temps
                        </a>
                        <a href="{{ route('classes.edit', $class) }}" class="btn btn-outline-warning">
                            <i class="bi bi-pencil me-2"></i>
                            Modifier la classe
                        </a>
                        @if($class->is_active)
                        <button type="button" class="btn btn-outline-secondary toggle-status-btn" data-class-id="{{ $class->id }}">
                            <i class="bi bi-toggle-off me-2"></i>
                            Désactiver
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-success toggle-status-btn" data-class-id="{{ $class->id }}">
                            <i class="bi bi-toggle-on me-2"></i>
                            Activer
                        </button>
                        @endif
                        
                        <!-- Zone de danger -->
                        <div class="border-top pt-3 mt-3">
                            <h6 class="text-danger mb-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Zone de danger
                            </h6>
                            @php
                                $studentsCount = \App\Models\Student::whereHas('enrollments', function($q) use ($class) {
                                    $q->where('class_id', $class->id);
                                })->count();
                                $canDelete = $studentsCount === 0;
                            @endphp
                            
                            @if($canDelete)
                                <button type="button" class="btn btn-outline-danger w-100 delete-class-btn" 
                                        data-class-id="{{ $class->id }}" 
                                        data-class-name="{{ $class->name }}">
                                    <i class="bi bi-trash me-2"></i>
                                    Supprimer la classe
                                </button>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Cette classe peut être supprimée car elle ne contient aucun élève.
                                </small>
                            @else
                                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                    <i class="bi bi-lock me-2"></i>
                                    Suppression impossible
                                </button>
                                <small class="text-warning d-block mt-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Cette classe contient {{ $studentsCount }} élève(s). Désinscrire tous les élèves avant suppression.
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2 text-success"></i>
                        Statistiques
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $studentsCount = \App\Models\Student::whereHas('enrollments', function($q) use ($class) {
                            $q->where('class_id', $class->id)->where('status', 'active');
                        })->count();
                        $capacity = $class->capacity ?? 0;
                        $occupancyRate = $capacity > 0 ? round(($studentsCount / $capacity) * 100) : 0;
                    @endphp
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h3 text-primary mb-1">{{ $studentsCount }}</div>
                            <small class="text-muted">Élèves inscrits</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h3 text-success mb-1">{{ $capacity }}</div>
                            <small class="text-muted">Places totales</small>
                        </div>
                        <div class="col-12">
                            <div class="h4 text-warning mb-1">{{ $occupancyRate }}%</div>
                            <small class="text-muted">Taux d'occupation</small>
                            <div class="progress mt-2" style="height: 8px;">
                                @php
                                    $progressColor = $occupancyRate > 90 ? 'danger' : ($occupancyRate > 70 ? 'warning' : 'success');
                                @endphp
                                <div class="progress-bar bg-{{ $progressColor }}" 
                                     role="progressbar" 
                                     style="--progress-width: {{ $occupancyRate }}%; width: var(--progress-width);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du changement de statut
    const toggleButtons = document.querySelectorAll('.toggle-status-btn');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.dataset.classId;
            if (confirm('Êtes-vous sûr de vouloir changer le statut de cette classe ?')) {
                console.log('Toggle status for class:', classId);
                // Vous pouvez ajouter ici un appel AJAX pour changer le statut
            }
        });
    });
    
    // Gestion de la suppression de classe
    const deleteButtons = document.querySelectorAll('.delete-class-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const classId = this.dataset.classId;
            const className = this.dataset.className;
            
            // Première confirmation
            if (confirm(`⚠️ ATTENTION ⚠️\n\nVoulez-vous vraiment supprimer la classe "${className}" ?\n\nCette action est IRRÉVERSIBLE et supprimera :\n- La classe elle-même\n- Toutes les inscriptions d'élèves\n- L'historique des notes\n- Les présences enregistrées\n\nTapez le nom de la classe pour confirmer.`)) {
                
                // Demande de confirmation par saisie du nom
                const confirmation = prompt(`Pour confirmer la suppression, tapez exactement le nom de la classe :\n\n"${className}"`);
                
                if (confirmation === className) {
                    // Dernière confirmation
                    if (confirm(`DERNIÈRE CONFIRMATION\n\nÊtes-vous ABSOLUMENT certain de vouloir supprimer "${className}" ?\n\nCette action ne peut pas être annulée !`)) {
                        deleteClass(classId);
                    }
                } else if (confirmation !== null) {
                    alert('❌ Nom incorrect. Suppression annulée pour votre sécurité.');
                }
            }
        });
    });
});

// Fonction pour supprimer la classe
function deleteClass(classId) {
    // Créer et soumettre un formulaire de suppression
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/classes/${classId}`;
    
    // Token CSRF
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    
    // Méthode DELETE
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    form.appendChild(methodField);
    
    // Ajouter au document et soumettre
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
