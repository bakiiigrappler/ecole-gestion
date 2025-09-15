@extends('layouts.app')

@section('title', 'Gestion des Inscriptions - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Inscriptions</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Inscriptions</h1>
                    <p class="text-muted">Gérez les inscriptions et réinscriptions des élèves</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('enrollments.pending-students') }}" class="btn btn-warning">
                        <i class="bi bi-clock-history me-2"></i>
                        Inscriptions en attente <span class="badge bg-light text-dark ms-1">{{ $pendingCount ?? 0 }}</span>
                    </a>
                    <button type="button" class="btn btn-success" onclick="showUnEnrolledStudents()">
                        <i class="bi bi-person-exclamation me-2"></i>
                        Non inscrits
                    </button>
                    <a href="{{ route('enrollments.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nouvelle inscription
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $totalEnrollments ?? 0 }}</h4>
                            <span>Total inscriptions</span>
                        </div>
                        <i class="bi bi-clipboard-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $activeEnrollments ?? 0 }}</h4>
                            <span>Inscriptions actives</span>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $currentYearEnrollments ?? 0 }}</h4>
                            <span>Année courante</span>
                        </div>
                        <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $enrollmentsByCycle['college'] ?? 0 }}</h4>
                            <span>Collège</span>
                        </div>
                        <i class="bi bi-mortarboard fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics by Cycle -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pie-chart me-2"></i>
                        Répartition par cycle (Année courante)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="badge bg-info fs-6 mb-2">{{ $enrollmentsByCycle['preprimaire'] ?? 0 }}</div>
                                <div>Pré-primaire</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="badge bg-success fs-6 mb-2">{{ $enrollmentsByCycle['primaire'] ?? 0 }}</div>
                                <div>Primaire</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="badge bg-warning fs-6 mb-2">{{ $enrollmentsByCycle['college'] ?? 0 }}</div>
                                <div>Collège</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="badge bg-primary fs-6 mb-2">{{ $enrollmentsByCycle['lycee'] ?? 0 }}</div>
                                <div>Lycée</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('enrollments.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Rechercher élève</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Nom, prénom, matricule...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Année scolaire</label>
                                <select class="form-select" name="academic_year">
                                    <option value="">Toutes les années</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cycle</label>
                                <select class="form-select" name="cycle">
                                    <option value="">Tous les cycles</option>
                                    <option value="preprimaire" {{ request('cycle') == 'preprimaire' ? 'selected' : '' }}>Pré-primaire</option>
                                    <option value="primaire" {{ request('cycle') == 'primaire' ? 'selected' : '' }}>Primaire</option>
                                    <option value="college" {{ request('cycle') == 'college' ? 'selected' : '' }}>Collège</option>
                                    <option value="lycee" {{ request('cycle') == 'lycee' ? 'selected' : '' }}>Lycée</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Classe</label>
                                <select class="form-select" name="class">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes ?? [] as $class)
                                        <option value="{{ $class->id }}" {{ request('class') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                                    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>Transféré</option>
                                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Diplômé</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-funnel"></i>
                                    </button>
                                    <a href="{{ route('enrollments.index') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        Liste des inscriptions
                    </h5>
                    <span class="badge bg-primary fs-6">{{ $enrollments->count() ?? 0 }} inscriptions</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;">
                                <tr>
                                    <th class="border-0" style="width: 80px;">ID</th>
                                    <th class="border-0" style="min-width: 200px;">Élève</th>
                                    <th class="border-0" style="width: 150px;">Classe</th>
                                    <th class="border-0" style="width: 120px;">Niveau/Cycle</th>
                                    <th class="border-0" style="width: 130px;">Année scolaire</th>
                                    <th class="border-0" style="width: 120px;">Date inscription</th>
                                    <th class="border-0" style="width: 90px;">Statut</th>
                                    <th class="border-0" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($enrollments as $enrollment)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">#{{ $enrollment->id }}</span>
                                    </td>
                                    <td>
                                        @if($enrollment->student)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <div class="avatar-title bg-primary rounded-circle">
                                                        {{ strtoupper(substr($enrollment->student->first_name ?? '', 0, 1) . substr($enrollment->student->last_name ?? '', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $enrollment->student->full_name ?? 'Étudiant supprimé' }}</div>
                                                    <small class="text-muted">{{ $enrollment->student->student_id ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <div class="avatar-title bg-danger rounded-circle">
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-danger">Étudiant supprimé</div>
                                                    <small class="text-muted">Référence invalide</small>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $enrollment->schoolClass->name }}</span>
                                    </td>
                                    <td>
                                        @if($enrollment->schoolClass && $enrollment->schoolClass->level)
                                            <div>
                                                <div class="fw-bold">{{ is_object($enrollment->schoolClass->level) ? ($enrollment->schoolClass->level->name ?? 'N/A') : $enrollment->schoolClass->level }}</div>
                                                <small class="text-muted text-capitalize">
                                                    @if(is_object($enrollment->schoolClass->level))
                                                        {{ $enrollment->schoolClass->level->cycle ?? 'N/A' }}
                                                    @endif
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted">Non défini</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($enrollment->academicYear)
                                            <div>
                                                <div class="fw-bold">{{ $enrollment->academicYear->name ?? 'N/A' }}</div>
                                                @if($enrollment->academicYear->is_current)
                                                    <small class="badge bg-success">Courante</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">Non défini</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-nowrap">{{ $enrollment->getFormattedEnrollmentDate() }}</span>
                                    </td>
                                    <td>
                                        {!! $enrollment->status_badge !!}
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info" data-enrollment-id="{{ $enrollment->id }}" onclick="viewEnrollment(this.dataset.enrollmentId)" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @if($enrollment->canBeModified())
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-enrollment-id="{{ $enrollment->id }}" onclick="editEnrollment(this.dataset.enrollmentId)" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endif
                                            @if($enrollment->student)
                                                <a href="{{ route('enrollments.re-enroll', $enrollment->student) }}" class="btn btn-sm btn-outline-success" title="Réinscrire">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Étudiant supprimé">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-enrollment-id="{{ $enrollment->id }}" onclick="deleteEnrollment(this.dataset.enrollmentId)" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <h5>Aucune inscription trouvée</h5>
                                            <p>Aucune inscription ne correspond à vos critères de recherche.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($enrollments->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Affichage de {{ $enrollments->firstItem() ?? 0 }} à {{ $enrollments->lastItem() ?? 0 }} sur {{ $enrollments->total() ?? 0 }} inscriptions
                        </div>
                        <nav aria-label="Pagination des inscriptions">
                            {{ $enrollments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </nav>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Enrollment Modal -->
<div class="modal fade" id="addEnrollmentModal" tabindex="-1" aria-labelledby="addEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEnrollmentModalLabel">
                    <i class="bi bi-person-plus me-2"></i>
                    Nouvelle inscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addEnrollmentForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Élève <span class="text-danger">*</span></label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Sélectionner un élève</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="academic_year_id" class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                    <option value="">Sélectionner une année</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                            {{ $year->name }} {{ $year->is_current ? '(Courante)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="enrollment_date" class="form-label">Date d'inscription <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cycle_select" class="form-label">Cycle <span class="text-danger">*</span></label>
                                <select class="form-select" id="cycle_select" required>
                                    <option value="">Sélectionner un cycle</option>
                                    <option value="preprimaire">Pré-primaire</option>
                                    <option value="primaire">Primaire</option>
                                    <option value="college">Collège</option>
                                    <option value="lycee">Lycée</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Sélectionner une classe</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Notes sur l'inscription..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>
                        Enregistrer l'inscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unenrolled Students Modal -->
<div class="modal fade" id="unEnrolledStudentsModal" tabindex="-1" aria-labelledby="unEnrolledStudentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="unEnrolledStudentsModalLabel">
                    <i class="bi bi-person-exclamation me-2"></i>
                    Élèves non inscrits pour l'année courante
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="unEnrolledStudentsContent">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chargement des classes par cycle
    const cycleSelect = document.getElementById('cycle_select');
    const classSelect = document.getElementById('class_id');

    if (cycleSelect) {
        cycleSelect.addEventListener('change', function() {
            const cycle = this.value;
            classSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
            
            if (cycle) {
                fetch(`/api/students/classes-by-cycle?cycle=${cycle}`)
                    .then(response => response.json())
                    .then(classes => {
                        classes.forEach(classItem => {
                            classSelect.innerHTML += `<option value="${classItem.id}">${classItem.name}</option>`;
                        });
                    });
            }
        });
    }

    // Soumission du formulaire d'inscription
    document.getElementById('addEnrollmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("enrollments.store") }}', {
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
                bootstrap.Modal.getInstance(document.getElementById('addEnrollmentModal')).hide();
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'inscription.');
        });
    });
});

function viewEnrollment(id) {
    // Rediriger vers la page de détails de l'inscription
    window.location.href = `/enrollments/${id}/receipt`;
}

function editEnrollment(id) {
    // Implémentation à venir
    console.log('Modifier inscription ID:', id);
}

function deleteEnrollment(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette inscription ?')) {
        fetch(`/enrollments/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la suppression.');
        });
    }
}

function showUnEnrolledStudents() {
    fetch('/enrollments/unenrolled-students')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = new bootstrap.Modal(document.getElementById('unEnrolledStudentsModal'));
                const content = document.getElementById('unEnrolledStudentsContent');
                
                if (data.students.length === 0) {
                    content.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
                            <h5>Parfait !</h5>
                            <p>Tous les élèves actifs sont inscrits pour l'année courante ${data.current_year.year}.</p>
                        </div>
                    `;
                } else {
                    let studentsHtml = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>${data.students.length} élève(s)</strong> ne sont pas encore inscrits pour l'année courante ${data.current_year.year}.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Dernière classe</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.students.forEach(student => {
                        const lastEnrollment = student.enrollments.length > 0 ? student.enrollments[0] : null;
                        studentsHtml += `
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-bold">${student.full_name}</div>
                                        <small class="text-muted">${student.student_id}</small>
                                    </div>
                                </td>
                                <td>
                                    ${lastEnrollment ? 
                                        `<span class="badge bg-secondary">${lastEnrollment.school_class.name}</span><br>
                                         <small class="text-muted">${lastEnrollment.academic_year.year}</small>` : 
                                        '<span class="text-muted">Aucune</span>'
                                    }
                                </td>
                                <td>
                                    <a href="/students/${student.id}/re-enroll" class="btn btn-sm btn-success">
                                        <i class="bi bi-person-plus me-1"></i>
                                        Inscrire
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                    
                    studentsHtml += '</tbody></table></div>';
                    content.innerHTML = studentsHtml;
                }
                
                modal.show();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors du chargement.');
        });
}
</script>
@endpush 
