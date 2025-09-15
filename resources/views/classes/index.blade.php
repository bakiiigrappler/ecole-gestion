@extends('layouts.app')

@section('title', 'Gestion des Classes - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Classes</li>
@endsection

@push('styles')
<link href="{{ asset('css/classes-enhanced.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Messages de statut -->
    @if(session('success'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 fw-bold">
                                <i class="bi bi-door-open me-3"></i>
                                Gestion des Classes
                            </h1>
                            <p class="mb-0 opacity-75">Gérez les classes et niveaux de votre établissement</p>
                        </div>
                        <div>
                            <a href="{{ route('classes.create') }}" class="btn btn-light btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>
                                Nouvelle Classe
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-1 fw-bold">{{ $classes->count() ?? 0 }}</h3>
                            <p class="mb-0 opacity-80 small">Classes Actives</p>
                        </div>
                        <div class="ms-3">
                            <div class="bg-light bg-opacity-20 rounded-circle p-3">
                                <i class="bi bi-door-open fs-3 text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #059669, #10b981);">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-1 fw-bold">{{ $classes->sum('capacity') ?? 0 }}</h3>
                            <p class="mb-0 opacity-80 small">Capacité Totale</p>
                        </div>
                        <div class="ms-3">
                            <div class="bg-light bg-opacity-20 rounded-circle p-3">
                                <i class="bi bi-people fs-3 text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-1 fw-bold">{{ $classes->count() > 0 ? round($classes->avg('capacity')) : 0 }}</h3>
                            <p class="mb-0 opacity-80 small">Capacité Moyenne</p>
                        </div>
                        <div class="ms-3">
                            <div class="bg-light bg-opacity-20 rounded-circle p-3">
                                <i class="bi bi-bar-chart fs-3 text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                <div class="card-body text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-1 fw-bold">{{ $levels->count() ?? 0 }}</h3>
                            <p class="mb-0 opacity-80 small">Niveaux Disponibles</p>
                        </div>
                        <div class="ms-3">
                            <div class="bg-light bg-opacity-20 rounded-circle p-3">
                                <i class="bi bi-layers fs-3 text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres Avancés -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2 text-primary"></i>
                        Filtres et Recherche
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-search me-1"></i>
                                Rechercher
                            </label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" 
                                       placeholder="Nom de classe..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-layers me-1"></i>
                                Cycle
                            </label>
                            <select class="form-select" id="cycleFilter">
                                <option value="">📚 Tous les cycles</option>
                                <option value="preprimaire">🍼 Pré-primaire</option>
                                <option value="primaire">📝 Primaire</option>
                                <option value="college">🏫 Collège</option>
                                <option value="lycee">🎓 Lycée</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-bookmark me-1"></i>
                                Niveau
                            </label>
                            <select class="form-select" id="levelFilter">
                                <option value="">Tous les niveaux</option>
                                @foreach($levels->groupBy('cycle') as $cycle => $cyclevels)
                                    <optgroup label="{{ ucfirst($cycle) }}">
                                        @foreach($cyclevels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-toggle-on me-1"></i>
                                Statut
                            </label>
                            <select class="form-select" id="statusFilter">
                                <option value="">📊 Tous</option>
                                <option value="active">✅ Active</option>
                                <option value="inactive">❌ Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-6">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="applyFilters()">
                                    <i class="bi bi-funnel-fill me-1"></i>
                                    Appliquer les filtres
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Réinitialiser
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex gap-2 justify-content-lg-end">
                                <label class="form-label fw-semibold mb-0 align-self-center">
                                    <i class="bi bi-sort-down me-1"></i>
                                    Trier par:
                                </label>
                                <select class="form-select" style="width: auto;" onchange="changeSorting()">
                                    <option value="created_at_desc" {{ request('sort') == 'created_at' && request('direction') == 'desc' ? 'selected' : '' }}>➡️ Plus récentes</option>
                                    <option value="created_at_asc" {{ request('sort') == 'created_at' && request('direction') == 'asc' ? 'selected' : '' }}>⬅️ Plus anciennes</option>
                                    <option value="name_asc" {{ request('sort') == 'name' && request('direction') == 'asc' ? 'selected' : '' }}>🔤 Nom (A-Z)</option>
                                    <option value="name_desc" {{ request('sort') == 'name' && request('direction') == 'desc' ? 'selected' : '' }}>🔤 Nom (Z-A)</option>
                                    <option value="capacity_desc" {{ request('sort') == 'capacity' && request('direction') == 'desc' ? 'selected' : '' }}>📊 Capacité ↓</option>
                                    <option value="capacity_asc" {{ request('sort') == 'capacity' && request('direction') == 'asc' ? 'selected' : '' }}>📊 Capacité ↑</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classes Grid -->
    <div class="row" id="classesContainer">
        @forelse($classes as $class)
        <div class="col-lg-4 col-md-6 mb-4 class-card" 
             data-cycle="{{ $class->getSafeCycle() }}" 
             data-level="{{ $class->level_id }}" 
             data-name="{{ strtolower($class->name) }}"
             data-status="{{ $class->is_active ? 'active' : 'inactive' }}">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                <!-- Cycle Badge -->
                <div class="position-absolute top-0 end-0 m-2">
                    @php
                        $cycleColors = [
                            'preprimaire' => 'bg-success',
                            'primaire' => 'bg-primary', 
                            'college' => 'bg-warning',
                            'lycee' => 'bg-info'
                        ];
                        $cycleIcons = [
                            'preprimaire' => '🍼',
                            'primaire' => '📝',
                            'college' => '🏫', 
                            'lycee' => '🎓'
                        ];
                        $cycle = $class->getSafeCycle();
                    @endphp
                    <span class="badge {{ $cycleColors[$cycle] ?? 'bg-secondary' }} rounded-pill">
                        {{ $cycleIcons[$cycle] ?? '📚' }} {{ ucfirst($cycle) }}
                    </span>
                </div>

                <!-- Class Header -->
                <div class="card-header border-0 {{ $class->is_active ? 'bg-success' : 'bg-secondary' }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-door-open me-2"></i>
                            {{ $class->name }}
                        </h5>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="bi bi-people me-1"></i>
                            {{ $class->capacity ?? 0 }}
                        </span>
                    </div>
                </div>

                <!-- Class Content -->
                <div class="card-body">
                    <!-- Stats Row -->
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border-end">
                                <div class="h4 text-primary mb-1 fw-bold">{{ $class->capacity ?? 0 }}</div>
                                <small class="text-muted">Places</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-1 fw-bold {{ $class->is_active ? 'text-success' : 'text-danger' }}">
                                {{ $class->is_active ? '✅' : '❌' }}
                            </div>
                            <small class="text-muted">{{ $class->is_active ? 'Active' : 'Inactive' }}</small>
                        </div>
                    </div>
                    
                    <!-- Class Details -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-bookmark-fill text-primary me-2"></i>
                            <strong>Niveau:</strong>
                            <span class="ms-2">{{ $class->getSafeLevelName() }}</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-layers-fill text-info me-2"></i>
                            <strong>Cycle:</strong>
                            <span class="badge bg-light text-dark ms-2">{{ ucfirst($class->getSafeCycle()) }}</span>
                        </div>
                    </div>
                    
                    <!-- Professeurs de la classe -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-workspace text-warning me-2"></i>
                            <strong>Professeurs:</strong>
                        </div>
                        @php
                            $teachers = $class->allTeachers()->with('subjects')->get();
                            $principalTeacher = $teachers->where('pivot.role', 'principal')->first();
                            $otherTeachers = $teachers->where('pivot.role', '!=', 'principal');
                        @endphp
                        @if($teachers->count() > 0)
                            <div class="bg-light p-2 rounded">
                                @if($principalTeacher)
                                    <!-- Professeur Principal -->
                                    <div class="d-flex align-items-center mb-2 p-2 bg-success bg-opacity-10 rounded">
                                        <i class="bi bi-star-fill text-warning me-1"></i>
                                        <span class="small fw-bold">
                                            <strong>{{ $principalTeacher->first_name }} {{ $principalTeacher->last_name }}</strong>
                                            <span class="badge bg-success ms-1">Principal</span>
                                            @if($principalTeacher->specialization)
                                                <span class="text-muted">({{ $principalTeacher->specialization }})</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                
                                @if($otherTeachers->count() > 0)
                                    <!-- Autres professeurs -->
                                    @foreach($otherTeachers as $teacher)
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="bi bi-person-circle text-primary me-1"></i>
                                            <span class="small">
                                                <strong>{{ $teacher->first_name }} {{ $teacher->last_name }}</strong>
                                                @if($teacher->specialization)
                                                    <span class="text-muted">({{ $teacher->specialization }})</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @else
                            <div class="bg-light p-2 rounded text-muted small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Aucun professeur assigné
                            </div>
                        @endif
                    </div>

                    @if($class->description)
                        <div class="mb-3">
                            <div class="bg-light p-3 rounded">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-info-circle me-1"></i>Description
                                </small>
                                <span class="text-dark">{{ Str::limit($class->description, 80) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Action Footer -->
                <div class="card-footer border-0 bg-light">
                    <div class="d-grid gap-2">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                    data-class-id="{{ $class->id }}" onclick="viewClass(this.dataset.classId)">
                                <i class="bi bi-eye me-1"></i>Voir
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                    data-class-id="{{ $class->id }}" onclick="editClass(this.dataset.classId)">
                                <i class="bi bi-pencil me-1"></i>Modifier
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" 
                                    data-class-id="{{ $class->id }}" onclick="manageStudents(this.dataset.classId)">
                                <i class="bi bi-people me-1"></i>Élèves
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="{{ route('classes.teachers', $class->id) }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-person-workspace me-1"></i>Professeurs
                            </a>
                        </div>
                        
                        <!-- Bouton Supprimer -->
                        @php
                            $studentsCount = \App\Models\Student::whereHas('enrollments', function($q) use ($class) {
                                $q->where('class_id', $class->id);
                            })->count();
                            $canDelete = $studentsCount === 0;
                        @endphp
                        
                        @if($canDelete)
                            <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                    onclick="deleteClass('{{ $class->id }}', '{{ $class->name }}')">
                                <i class="bi bi-trash me-1"></i>Supprimer
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled 
                                    title="Impossible de supprimer - {{ $studentsCount }} élève(s) inscrit(s)">
                                <i class="bi bi-lock me-1"></i>Suppression impossible
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-door-closed text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-muted mb-3">Aucune classe trouvée</h3>
                    <p class="text-muted mb-4">Commencez par créer votre première classe pour organiser vos élèves.</p>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>
                        Créer ma première classe
                    </a>
                </div>
            </div>
        </div>
        @endforelse

        <!-- Add New Class Card -->
        @if($classes->count() > 0)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm" style="border: 2px dashed #2563eb !important; background: linear-gradient(135deg, #f1f5f9, #e2e8f0);">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="mb-3">
                        <i class="bi bi-plus-circle" style="font-size: 3rem; color: #2563eb;"></i>
                    </div>
                    <h5 class="mb-3 fw-bold" style="color: #2563eb;">Ajouter une nouvelle classe</h5>
                    <p class="text-muted small mb-3">Créez une nouvelle classe pour vos élèves</p>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                        <i class="bi bi-plus me-2"></i>
                        Créer une classe
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Pagination Améliorée -->
    @if($classes->hasPages())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Affichage de {{ $classes->firstItem() }} à {{ $classes->lastItem() }} 
                            sur {{ $classes->total() }} classes
                        </div>
                        <div>
                            {{ $classes->onEachSide(2)->links('vendor.pagination.custom-bootstrap') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>



<script>




function viewClass(id) {
    // Redirection vers la page de détail
    window.location.href = `/classes/${id}`;
}

function editClass(id) {
    // Redirection vers la page d'édition
    window.location.href = `/classes/${id}/edit`;
}

function manageStudents(id) {
    // Redirection vers la gestion des élèves
    window.location.href = `/classes/${id}/students`;
}

// Configuration du filtrage en temps réel
function setupRealTimeFiltering() {
    const searchInput = document.getElementById('searchInput');
    const cycleFilter = document.getElementById('cycleFilter');
    const levelFilter = document.getElementById('levelFilter');
    const statusFilter = document.getElementById('statusFilter');

    // Écouter les changements sur tous les filtres
    [searchInput, cycleFilter, levelFilter, statusFilter].forEach(filter => {
        filter.addEventListener('input', applyFilters);
        filter.addEventListener('change', applyFilters);
    });
}

// Appliquer les filtres
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const selectedCycle = document.getElementById('cycleFilter').value;
    const selectedLevel = document.getElementById('levelFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;
    
    const classCards = document.querySelectorAll('.class-card');
    let visibleCount = 0;
    
    classCards.forEach(card => {
        const className = card.dataset.name;
        const cardCycle = card.dataset.cycle;
        const cardLevel = card.dataset.level;
        const cardStatus = card.dataset.status;
        
        let shouldShow = true;
        
        // Filtre par nom
        if (searchTerm && !className.includes(searchTerm)) {
            shouldShow = false;
        }
        
        // Filtre par cycle
        if (selectedCycle && cardCycle !== selectedCycle) {
            shouldShow = false;
        }
        
        // Filtre par niveau
        if (selectedLevel && cardLevel !== selectedLevel) {
            shouldShow = false;
        }
        
        // Filtre par statut
        if (selectedStatus && cardStatus !== selectedStatus) {
            shouldShow = false;
        }
        
        // Afficher/masquer la carte
        if (shouldShow) {
            card.classList.remove('filtered-out');
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.classList.add('filtered-out');
            card.style.display = 'none';
        }
    });
    
    // Afficher un message si aucun résultat
    updateNoResultsMessage(visibleCount);
}

// Réinitialiser les filtres
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('cycleFilter').value = '';
    document.getElementById('levelFilter').value = '';
    document.getElementById('statusFilter').value = '';
    
    // Afficher toutes les cartes
    const classCards = document.querySelectorAll('.class-card');
    classCards.forEach(card => {
        card.classList.remove('filtered-out');
        card.style.display = 'block';
    });
    
    updateNoResultsMessage(classCards.length);
}

// Mettre à jour le message "aucun résultat"
function updateNoResultsMessage(visibleCount) {
    let noResultsMessage = document.getElementById('noResultsMessage');
    
    if (visibleCount === 0) {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.id = 'noResultsMessage';
            noResultsMessage.className = 'col-12 text-center py-5';
            noResultsMessage.innerHTML = `
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">Aucun résultat trouvé</h4>
                        <p class="text-muted">Essayez de modifier vos critères de recherche.</p>
                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="bi bi-x-circle me-1"></i>
                            Réinitialiser les filtres
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('classesContainer').appendChild(noResultsMessage);
        }
    } else {
        if (noResultsMessage) {
            noResultsMessage.remove();
        }
    }
}

// Initialiser le filtrage lors du chargement
document.addEventListener('DOMContentLoaded', function() {
    setupRealTimeFiltering();
});



// Fonction pour changer le tri
function changeSorting() {
    const select = event.target;
    const [sort, direction] = select.value.split('_');
    
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('direction', direction);
    
    window.location.href = url.toString();
}

// Fonction pour supprimer une classe
function deleteClass(classId, className) {
    // Première confirmation
    if (confirm(`⚠️ ATTENTION ⚠️\n\nVoulez-vous vraiment supprimer la classe "${className}" ?\n\nCette action est IRRÉVERSIBLE et supprimera :\n- La classe elle-même\n- Toutes les inscriptions d'élèves\n- L'historique des notes\n- Les présences enregistrées\n\nÊtes-vous sûr de vouloir continuer ?`)) {
        
        // Demande de confirmation par saisie du nom
        const confirmation = prompt(`Pour confirmer la suppression, tapez exactement le nom de la classe :\n\n"${className}"`);
        
        if (confirmation === className) {
            // Dernière confirmation
            if (confirm(`DERNIÈRE CONFIRMATION\n\nÊtes-vous ABSOLUMENT certain de vouloir supprimer "${className}" ?\n\nCette action ne peut pas être annulée !`)) {
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
        } else if (confirmation !== null) {
            alert('❌ Nom incorrect. Suppression annulée pour votre sécurité.');
        }
    }
}
</script>



<style>
/* Styles personnalisés pour les classes */
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    transition: all 0.3s ease;
}

.transition-all {
    transition: all 0.3s ease;
}

.class-card {
    transition: all 0.3s ease;
}

.class-card.filtered-out {
    display: none !important;
}

/* Animation pour les cartes */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.class-card {
    animation: fadeInUp 0.5s ease forwards;
}

/* Amélioration de la pagination */
.pagination .page-link {
    border-radius: 8px !important;
    margin: 0 2px;
    border: none;
    color: #6f42c1;
}

.pagination .page-link:hover {
    background-color: #6f42c1;
    color: white;
}

.pagination .page-item.active .page-link {
    background-color: #6f42c1;
    border-color: #6f42c1;
}
</style>
@endsection 
