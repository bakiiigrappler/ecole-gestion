@extends('layouts.app')

@section('title', 'Gestion des Enseignants - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Enseignants</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Enseignants</h1>
                    <p class="text-muted">Gérez le personnel enseignant de l'établissement</p>
                </div>
                <div>
                    <a href="{{ route('teachers.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-workspace me-2"></i>
                        Ajouter un enseignant
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $totalTeachers ?? 0 }}</h4>
                            <span>Total</span>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $activeTeachers ?? 0 }}</h4>
                            <span>Actifs</span>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $newThisMonth ?? 0 }}</h4>
                            <span>Nouveaux</span>
                        </div>
                        <i class="bi bi-person-plus fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($averageSalary ?? 0, 0, ',', ' ') }}</h4>
                            <span>Salaire moyen</span>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $teachersByCycle['preprimaire'] ?? 0 }}</h4>
                            <span>Pré-primaire</span>
                        </div>
                        <i class="bi bi-mortarboard fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white" style="background: linear-gradient(135deg, #1abc9c, #16a085);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $teachersByCycle['college'] ?? 0 }}</h4>
                            <span>Collège</span>
                        </div>
                        <i class="bi bi-book fs-1 opacity-50"></i>
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
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Rechercher</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Nom, prénom, ID employé..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cycle</label>
                            <select class="form-select" id="cycleFilter">
                                <option value="">Tous les cycles</option>
                                <option value="preprimaire">Pré-primaire</option>
                                <option value="primaire">Primaire</option>
                                <option value="college">Collège</option>
                                <option value="lycee">Lycée</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="typeFilter">
                                <option value="">Tous les types</option>
                                <option value="general">Généraliste</option>
                                <option value="specialized">Spécialisé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Spécialisation</label>
                            <select class="form-select" id="specializationFilter">
                                <option value="">Toutes</option>
                                <option value="Mathématiques">Mathématiques</option>
                                <option value="Français">Français</option>
                                <option value="Histoire-Géographie">Histoire-Géographie</option>
                                <option value="Sciences">Sciences</option>
                                <option value="Anglais">Anglais</option>
                                <option value="EPS">EPS</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tous</option>
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                                <option value="suspended">Suspendu</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teachers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary fs-6">{{ $teachers->count() ?? 0 }} enseignants</span>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="exportBtn">
                                <i class="bi bi-download me-1"></i>Exporter
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" id="printBtn">
                                <i class="bi bi-printer me-1"></i>Imprimer
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="teachersTable">
                            <thead style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;">
                                <tr>
                                    <th class="text-center border-0" style="width: 60px;">
                                        <i class="bi bi-person-circle"></i>
                                    </th>
                                    <th class="border-0" style="width: 100px;">ID Employé</th>
                                    <th class="border-0" style="min-width: 180px;">Enseignant</th>
                                    <th class="border-0" style="width: 90px;">Cycle</th>
                                    <th class="border-0" style="width: 100px;">Type</th>
                                    <th class="border-0" style="min-width: 140px;">Affectation</th>
                                    <th class="border-0" style="width: 160px;">Contact</th>
                                    <th class="border-0" style="width: 90px;">Statut</th>
                                    <th class="border-0" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teachers as $teacher)
                                <tr class="align-middle">
                                    <td class="text-center">
                                        <div class="avatar-sm mx-auto">
                                            @if($teacher->photo)
                                                <img src="{{ asset('storage/' . $teacher->photo) }}" 
                                                     alt="Photo {{ $teacher->full_name }}" 
                                                     class="rounded-circle" 
                                                     style="width: 45px; height: 45px; object-fit: cover;">
                                            @else
                                                <div class="avatar-title bg-primary rounded-circle" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                                    {{ strtoupper(substr($teacher->first_name, 0, 1) . substr($teacher->last_name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary fs-6">{{ $teacher->employee_id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-1 fw-bold">{{ $teacher->full_name }}</h6>
                                            <small class="text-muted">
                                                <i class="bi bi-briefcase me-1"></i>
                                                {{ $teacher->qualification ?? 'Enseignant' }}
                                            </small>
                                            @if($teacher->specialization && $teacher->teacher_type === 'specialized')
                                                <br>
                                                <small class="text-primary">
                                                    <i class="bi bi-book me-1"></i>
                                                    {{ $teacher->specialization }}
                                                </small>
                                            @endif
                                            <br>
                                            <small class="text-info">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ $teacher->years_of_service }} ans d'expérience
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $cycleColors = [
                                                'preprimaire' => 'bg-warning',
                                                'primaire' => 'bg-success',
                                                'college' => 'bg-info',
                                                'lycee' => 'bg-danger'
                                            ];
                                        @endphp
                                        <span class="badge {{ $cycleColors[$teacher->cycle] ?? 'bg-secondary' }} fs-6">
                                            {{ $teacher->cycle_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($teacher->teacher_type === 'general')
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-person-check me-1"></i>Généraliste
                                            </span>
                                        @else
                                            <span class="badge bg-primary fs-6">
                                                <i class="bi bi-person-gear me-1"></i>Spécialisé
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($teacher->teacher_type === 'general')
                                            @if($teacher->assignedClass)
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-mortarboard-fill text-success me-2"></i>
                                                    <span class="fw-bold">{{ $teacher->assignedClass->name }}</span>
                                                </div>
                                                <small class="text-muted">{{ $teacher->assignedClass->description }}</small>
                                            @else
                                                <span class="text-danger">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Non assigné
                                                </span>
                                            @endif
                                        @else
                                            @if($teacher->specialization)
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-book-fill text-primary me-2"></i>
                                                    <span class="fw-bold">{{ $teacher->specialization }}</span>
                                                </div>
                                                <small class="text-muted">Matière spécialisée</small>
                                            @else
                                                <span class="text-warning">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Non spécifiée
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-primary">
                                                <i class="bi bi-envelope me-1"></i>{{ $teacher->email }}
                                            </small>
                                            @if($teacher->phone)
                                                <small class="text-success">
                                                    <i class="bi bi-telephone me-1"></i>{{ $teacher->phone }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($teacher->status === 'active')
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-check-circle me-1"></i>Actif
                                            </span>
                                        @elseif($teacher->status === 'inactive')
                                            <span class="badge bg-secondary fs-6">
                                                <i class="bi bi-pause-circle me-1"></i>Inactif
                                            </span>
                                        @else
                                            <span class="badge bg-danger fs-6">
                                                <i class="bi bi-x-circle me-1"></i>Suspendu
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-sm btn-outline-info" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-id="{{ $teacher->id }}" 
                                                    data-name="{{ $teacher->full_name }}"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <h5>Aucun enseignant trouvé</h5>
                                            <p>Commencez par ajouter un enseignant en cliquant sur le bouton ci-dessus.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($teachers->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Affichage de {{ $teachers->firstItem() ?? 0 }} à {{ $teachers->lastItem() ?? 0 }} sur {{ $teachers->total() ?? 0 }} enseignants
                            </div>
                            <nav aria-label="Pagination des enseignants">
                                {{ $teachers->links('pagination::bootstrap-5') }}
                            </nav>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'enseignant <strong id="teacherName"></strong> ?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible et supprimera toutes les données associées à cet enseignant.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bi bi-trash me-2"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const cycleFilter = document.getElementById('cycleFilter');
    const typeFilter = document.getElementById('typeFilter');
    const specializationFilter = document.getElementById('specializationFilter');
    const statusFilter = document.getElementById('statusFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    
    function filterTeachers() {
        const searchTerm = searchInput.value.toLowerCase();
        const cycle = cycleFilter.value;
        const type = typeFilter.value;
        const specialization = specializationFilter.value;
        const status = statusFilter.value;
        
        const rows = document.querySelectorAll('#teachersTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const cycleCell = row.querySelector('td:nth-child(4)').textContent;
            const typeCell = row.querySelector('td:nth-child(5)').textContent;
            const specializationCell = row.querySelector('td:nth-child(6)').textContent;
            const statusCell = row.querySelector('td:nth-child(8)').textContent;
            
            const matchesSearch = text.includes(searchTerm);
            const matchesCycle = !cycle || cycleCell.includes(cycle);
            const matchesType = !type || typeCell.includes(type);
            const matchesSpecialization = !specialization || specializationCell.includes(specialization);
            const matchesStatus = !status || statusCell.includes(status);
            
            row.style.display = (matchesSearch && matchesCycle && matchesType && matchesSpecialization && matchesStatus) ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterTeachers);
    cycleFilter.addEventListener('change', filterTeachers);
    typeFilter.addEventListener('change', filterTeachers);
    specializationFilter.addEventListener('change', filterTeachers);
    statusFilter.addEventListener('change', filterTeachers);
    
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        cycleFilter.value = '';
        typeFilter.value = '';
        specializationFilter.value = '';
        statusFilter.value = '';
        filterTeachers();
    });
    
    // Delete functionality
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const teacherName = document.getElementById('teacherName');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            teacherName.textContent = name;
            confirmDeleteBtn.onclick = function() {
                fetch(`/teachers/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                });
            };
            
            deleteModal.show();
        });
    });
});
</script>
@endsection 
