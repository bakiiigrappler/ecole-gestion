@extends('layouts.app')

@section('title', 'Réinscription - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Inscriptions</a></li>
<li class="breadcrumb-item active">Réinscription</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Réinscription d'élève</h1>
                    <p class="text-muted">Inscrire un élève pour une nouvelle année scolaire</p>
                </div>
                <div>
                    <a href="{{ route('enrollments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Retour aux inscriptions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations de l'élève -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>
                        Informations de l'élève
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($student->photo)
                            <img src="{{ asset('storage/' . $student->photo) }}" class="rounded-circle mb-3" width="80" height="80" alt="Photo">
                        @else
                            <div class="avatar-xl mx-auto mb-3">
                                <div class="avatar-title bg-primary rounded-circle" style="width: 80px; height: 80px; font-size: 2rem;">
                                    {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                </div>
                            </div>
                        @endif
                        <h5 class="mb-1">{{ $student->full_name }}</h5>
                        <span class="badge bg-primary">{{ $student->student_id }}</span>
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <td><strong>Date de naissance:</strong></td>
                            <td>{{ $student->date_of_birth->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Âge:</strong></td>
                            <td>{{ $student->age }} ans</td>
                        </tr>
                        <tr>
                            <td><strong>Sexe:</strong></td>
                            <td>{{ $student->gender === 'male' ? 'Masculin' : 'Féminin' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Statut:</strong></td>
                            <td>
                                @if($student->status === 'active')
                                    <span class="badge bg-success">Actif</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($student->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Dernière inscription -->
            @if($lastEnrollment)
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Dernière inscription
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                                                                <tr>
                                            <td><strong>Année:</strong></td>
                                            <td>{{ $lastEnrollment->academicYear->name }}</td>
                                        </tr>
                        <tr>
                            <td><strong>Classe:</strong></td>
                            <td>
                                <span class="badge bg-secondary">{{ $lastEnrollment->schoolClass->name }}</span>
                            </td>
                        </tr>
                        @if($lastEnrollment->schoolClass->level)
                        <tr>
                            <td><strong>Niveau:</strong></td>
                            <td>
                                {{ is_object($lastEnrollment->schoolClass->level) ? $lastEnrollment->schoolClass->level->name : $lastEnrollment->schoolClass->level }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ $lastEnrollment->enrollment_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Statut:</strong></td>
                            <td>{!! $lastEnrollment->status_badge !!}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Formulaire de réinscription -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Nouvelle inscription
                    </h5>
                </div>
                <div class="card-body">
                    <form id="reEnrollmentForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Année scolaire
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="academic_year_id" class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                    <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Sélectionner une année</option>
                                        @if($currentAcademicYear)
                                            <option value="{{ $currentAcademicYear->id }}" selected>
                                                {{ $currentAcademicYear->name }} (Année courante)
                                            </option>
                                        @endif
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="enrollment_date" class="form-label">Date d'inscription <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Notes sur la réinscription...">Réinscription pour l'année {{ $currentAcademicYear->name ?? '' }}</textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="text-success mb-3">
                                    <i class="bi bi-mortarboard me-2"></i>
                                    Classe et niveau
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="cycle" class="form-label">Cycle <span class="text-danger">*</span></label>
                                    <select class="form-select" id="cycle" required>
                                        <option value="">Sélectionner un cycle</option>
                                        <option value="preprimaire">Pré-primaire</option>
                                        <option value="primaire">Primaire</option>
                                        <option value="college">Collège</option>
                                        <option value="lycee">Lycée</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Le cycle détermine les classes disponibles
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="level_id" class="form-label">Niveau</label>
                                    <select class="form-select" id="level_id">
                                        <option value="">Sélectionner un niveau</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                    <select class="form-select" id="class_id" name="class_id" required>
                                        <option value="">Sélectionner une classe</option>
                                    </select>
                                </div>

                                @if($lastEnrollment && $lastEnrollment->schoolClass->level)
                                <div class="alert alert-info">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Suggestion:</strong> 
                                    L'élève était en {{ is_object($lastEnrollment->schoolClass->level) ? $lastEnrollment->schoolClass->level->name : $lastEnrollment->schoolClass->level }}. 
                                    Considérez le niveau suivant selon la progression normale.
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('enrollments.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>
                                        Annuler
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Confirmer la réinscription
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historique des inscriptions -->
            @if($student->enrollments->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Historique des inscriptions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($student->enrollments->sortByDesc('enrollment_date') as $enrollment)
                        <div class="timeline-item">
                            <div class="timeline-marker {{ $enrollment->isActive() ? 'bg-success' : 'bg-secondary' }}"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $enrollment->academicYear->name }}</h6>
                                        <p class="mb-1">
                                            Classe: <span class="badge bg-info">{{ $enrollment->schoolClass->name }}</span>
                                            @if($enrollment->schoolClass->level)
                                                <br><small class="text-muted">
                                                    Niveau: {{ is_object($enrollment->schoolClass->level) ? $enrollment->schoolClass->level->name : $enrollment->schoolClass->level }}
                                                </small>
                                            @endif
                                        </p>
                                        <small class="text-muted">
                                            Inscrit le {{ $enrollment->enrollment_date->format('d/m/Y') }}
                                        </small>
                                    </div>
                                    <div>
                                        {!! $enrollment->status_badge !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.avatar-xl .avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle');
    const levelSelect = document.getElementById('level_id');
    const classSelect = document.getElementById('class_id');

    // Chargement des niveaux par cycle
    cycleSelect.addEventListener('change', function() {
        const cycle = this.value;
        levelSelect.innerHTML = '<option value="">Sélectionner un niveau</option>';
        classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
        
        if (cycle) {
            fetch(`/api/levels-by-cycle?cycle=${cycle}`)
                .then(response => response.json())
                .then(levels => {
                    levels.forEach(level => {
                        levelSelect.innerHTML += `<option value="${level.id}">${level.name}</option>`;
                    });
                });
        }
    });

    // Chargement des classes par niveau
    levelSelect.addEventListener('change', function() {
        const levelId = this.value;
        classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
        
        if (levelId) {
            fetch(`/api/students/classes-by-level?level_id=${levelId}`)
                .then(response => response.json())
                .then(classes => {
                    classes.forEach(classItem => {
                        classSelect.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                    });
                });
        }
    });

    // Soumission du formulaire
    document.getElementById('reEnrollmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("enrollments.process-re-enrollment", $student) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route("enrollments.index") }}';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la réinscription.');
        });
    });
});
</script>
@endpush 
