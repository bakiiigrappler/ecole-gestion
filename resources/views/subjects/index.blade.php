@extends('layouts.app')

@section('title', 'Gestion des Matières - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Matières</li>
@endsection

@push('styles')
<style>
:root {
    --primary-blue: #2563eb;
    --success-green: #059669;
    --warning-orange: #d97706;
    --danger-red: #dc2626;
    --info-cyan: #0891b2;
    --purple-violet: #7c3aed;
    --gray-neutral: #6b7280;
}

.subjects-header {
    background: var(--primary-blue);
    color: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.stats-card {
    border-radius: 12px;
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stats-card.primary { background-color: var(--primary-blue); }
.stats-card.success { background-color: var(--success-green); }
.stats-card.warning { background-color: var(--warning-orange); }
.stats-card.purple { background-color: var(--purple-violet); }

.filters-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.subjects-table-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table-modern {
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background-color: #f8fafc;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    font-weight: 600;
    color: var(--gray-neutral);
    padding: 1rem;
}

.table-modern tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.2s ease;
}

.table-modern tbody tr:hover {
    background-color: #f8fafc;
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
}

.badge-modern {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
    color: white;
}

.badge-success { background-color: var(--success-green); }
.badge-primary { background-color: var(--primary-blue); }
.badge-warning { background-color: var(--warning-orange); }
.badge-purple { background-color: var(--purple-violet); }
.badge-secondary { background-color: var(--gray-neutral); }

.avatar-subject {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
}

.btn-action {
    padding: 0.375rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: white;
    transition: all 0.2s ease;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-action.view { border-color: var(--info-cyan); color: var(--info-cyan); }
.btn-action.edit { border-color: var(--warning-orange); color: var(--warning-orange); }
.btn-action.delete { border-color: var(--danger-red); color: var(--danger-red); }

.btn-action.view:hover { background-color: var(--info-cyan); color: white; }
.btn-action.edit:hover { background-color: var(--warning-orange); color: white; }
.btn-action.delete:hover { background-color: var(--danger-red); color: white; }

.custom-pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0.25rem 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background-color: white;
    color: var(--gray-neutral);
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
}

.pagination-btn:hover {
    background-color: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}

.pagination-btn.active {
    background-color: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.pagination-btn.disabled {
    background-color: #f9fafb;
    border-color: #f3f4f6;
    color: #d1d5db;
    cursor: not-allowed;
    pointer-events: none;
}

.pagination-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--gray-neutral);
    font-size: 0.8rem;
}

.pagination-divider {
    width: 1px;
    height: 1.5rem;
    background-color: #e5e7eb;
}

.pagination-jump {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-jump input {
    width: 3rem;
    height: 2rem;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    text-align: center;
    font-size: 0.8rem;
}

.pagination-jump button {
    padding: 0.25rem 0.5rem;
    border: 1px solid var(--primary-blue);
    border-radius: 4px;
    background-color: var(--primary-blue);
    color: white;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pagination-jump button:hover {
    background-color: #1d4ed8;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: var(--gray-neutral);
}

.form-control-modern, .form-select-modern {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.625rem 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control-modern:focus, .form-select-modern:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="subjects-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2 fw-bold">
                    <i class="bi bi-book me-3"></i>
                    Gestion des Matières
                </h1>
                <p class="mb-0 opacity-75">Gérez les matières enseignées par cycle et niveau</p>
            </div>
            <div>
                <a href="{{ route('subjects.create') }}" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Ajouter une matière
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 fw-bold">{{ $subjects->count() ?? 0 }}</h3>
                            <p class="mb-0 opacity-80 small">Total matières</p>
                        </div>
                        <div class="bg-light bg-opacity-20 rounded-circle p-3">
                            <i class="bi bi-book fs-3 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 fw-bold">{{ $subjects->filter(function($s) { return $s->level && is_object($s->level) && $s->level->cycle === 'preprimaire'; })->count() }}</h3>
                            <p class="mb-0 opacity-80 small">Pré-primaire</p>
                        </div>
                        <div class="bg-light bg-opacity-20 rounded-circle p-3">
                            <i class="bi bi-egg fs-3 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 fw-bold">{{ $subjects->filter(function($s) { return $s->level && is_object($s->level) && $s->level->cycle === 'primaire'; })->count() }}</h3>
                            <p class="mb-0 opacity-80 small">Primaire</p>
                        </div>
                        <div class="bg-light bg-opacity-20 rounded-circle p-3">
                            <i class="bi bi-pencil fs-3 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card purple text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 fw-bold">{{ $subjects->filter(function($s) { return $s->level && is_object($s->level) && in_array($s->level->cycle, ['college', 'lycee']); })->count() }}</h3>
                            <p class="mb-0 opacity-80 small">Collège & Lycée</p>
                        </div>
                        <div class="bg-light bg-opacity-20 rounded-circle p-3">
                            <i class="bi bi-mortarboard fs-3 text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filters-card">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2 text-primary"></i>
                        Filtres et Recherche
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-search me-1"></i>
                                Rechercher une matière
                            </label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control form-control-modern border-start-0 ps-0" 
                                       placeholder="Nom de la matière..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-layers me-1"></i>
                                Cycle
                            </label>
                            <select class="form-select form-select-modern" id="cycleFilter">
                                <option value="">📚 Tous les cycles</option>
                                <option value="preprimaire">🍼 Pré-primaire</option>
                                <option value="primaire">📝 Primaire</option>
                                <option value="college">🏫 Collège</option>
                                <option value="lycee">🎓 Lycée</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6" id="seriesFilterContainer" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-bookmark me-1"></i>
                                Série (Lycée)
                            </label>
                            <select class="form-select form-select-modern" id="seriesFilter">
                                <option value="">Toutes les séries</option>
                                <option value="S">S - Scientifique</option>
                                <option value="A1">A1 - Lettres-Langues anciennes</option>
                                <option value="A2">A2 - Lettres-Langues vivantes</option>
                                <option value="B">B - Sciences économiques</option>
                                <option value="C">C - Mathématiques-Sciences</option>
                                <option value="D">D - Sciences naturelles</option>
                                <option value="E">E - Techniques industrielles</option>
                                <option value="LE">LE - Lettres modernes</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label fw-semibold text-transparent">Actions</label>
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                                <i class="bi bi-x-circle me-1"></i>
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subjects Table -->
    <div class="row">
        <div class="col-12">
            <div class="card subjects-table-card">
                <div class="card-header bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-table me-2 text-primary"></i>
                                Liste des Matières
                            </h5>
                            <small class="text-muted">{{ $subjects->total() ?? 0 }} matière(s) au total</small>
                        </div>
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
                        <table class="table table-modern mb-0" id="subjectsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Matière</th>
                                    <th>Cycle</th>
                                    <th>Séries (Lycée)</th>
                                    <th>Coefficient</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subjects as $subject)
                                <tr>
                                    <td>
                                        <span class="badge badge-modern" style="background-color: var(--gray-neutral); color: white;">{{ $subject->code }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-subject me-3" style="background-color: var(--primary-blue);">
                                                {{ strtoupper(substr($subject->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{ $subject->name }}</h6>
                                                <small class="text-muted">{{ $subject->code }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $cycleClass = match($subject->cycle) {
                                                'preprimaire' => 'badge-success',
                                                'primaire' => 'badge-primary', 
                                                'college' => 'badge-warning',
                                                'lycee' => 'badge-purple',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-modern {{ $cycleClass }}">
                                            {{ ucfirst($subject->cycle) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($subject->cycle === 'lycee' && $subject->series)
                                            @php
                                                $seriesLabels = [
                                                    'S' => 'S',
                                                    'A1' => 'A1',
                                                    'A2' => 'A2',
                                                    'B' => 'B',
                                                    'C' => 'C',
                                                    'D' => 'D',
                                                    'E' => 'E',
                                                    'LE' => 'LE'
                                                ];
                                            @endphp
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($subject->series as $serie)
                                                    <span class="badge badge-modern" style="background-color: var(--info-cyan); color: white; font-size: 0.7rem;">
                                                        {{ $seriesLabels[$serie] ?? $serie }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                <i class="bi bi-dash"></i>
                                                {{ $subject->cycle === 'lycee' ? 'Non défini' : 'Tout le cycle' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-modern" style="background-color: var(--success-green); color: white;">
                                            {{ $subject->coefficient }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ Str::limit($subject->description, 40) }}</span>
                                    </td>
                                    <td>
                                        @if($subject->is_active)
                                            <span class="badge badge-modern" style="background-color: var(--success-green); color: white;">
                                                <i class="bi bi-check-circle me-1"></i>Actif
                                            </span>
                                        @else
                                            <span class="badge badge-modern" style="background-color: var(--gray-neutral); color: white;">
                                                <i class="bi bi-x-circle me-1"></i>Inactif
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('subjects.show', $subject) }}" class="btn-action view" title="Voir">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('subjects.edit', $subject) }}" class="btn-action edit" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn-action delete delete-btn" 
                                                    data-id="{{ $subject->id }}" 
                                                    data-name="{{ $subject->name }}"
                                                    title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <h5>Aucune matière trouvée</h5>
                                            <p class="text-muted">Commencez par ajouter votre première matière.</p>
                                            <a href="{{ route('subjects.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-2"></i>
                                                Ajouter une matière
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                </div>
                
                <!-- Pagination -->
                @if($subjects->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 py-2">
                        <!-- Informations de pagination -->
                        <div class="pagination-info">
                            <span>
                                <i class="bi bi-list-ol me-1"></i>
                                {{ $subjects->firstItem() }}-{{ $subjects->lastItem() }} sur {{ $subjects->total() }}
                            </span>
                            <div class="pagination-divider"></div>
                            <span>Page {{ $subjects->currentPage() }} sur {{ $subjects->lastPage() }}</span>
                        </div>

                        <!-- Contrôles de pagination -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Navigation rapide -->
                            <div class="pagination-jump d-none d-md-flex">
                                <span class="text-muted small">Aller à:</span>
                                <input type="number" min="1" max="{{ $subjects->lastPage() }}" 
                                       value="{{ $subjects->currentPage() }}" id="pageJump"
                                       data-current-page="{{ $subjects->currentPage() }}"
                                       data-last-page="{{ $subjects->lastPage() }}">
                                <button type="button" onclick="jumpToPage()">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>

                            <!-- Boutons de navigation -->
                            <div class="custom-pagination">
                                <!-- Première page -->
                                @if($subjects->currentPage() > 2)
                                    <a href="{{ $subjects->url(1) }}" class="pagination-btn" title="Première page">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                @endif

                                <!-- Page précédente -->
                                @if($subjects->currentPage() > 1)
                                    <a href="{{ $subjects->previousPageUrl() }}" class="pagination-btn" title="Page précédente">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        <i class="bi bi-chevron-left"></i>
                                    </span>
                                @endif

                                <!-- Pages numérotées -->
                                @foreach(range(max(1, $subjects->currentPage() - 1), min($subjects->lastPage(), $subjects->currentPage() + 1)) as $page)
                                    @if($page == $subjects->currentPage())
                                        <span class="pagination-btn active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $subjects->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                    @endif
                                @endforeach

                                <!-- Page suivante -->
                                @if($subjects->currentPage() < $subjects->lastPage())
                                    <a href="{{ $subjects->nextPageUrl() }}" class="pagination-btn" title="Page suivante">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        <i class="bi bi-chevron-right"></i>
                                    </span>
                                @endif

                                <!-- Dernière page -->
                                @if($subjects->currentPage() < $subjects->lastPage() - 1)
                                    <a href="{{ $subjects->url($subjects->lastPage()) }}" class="pagination-btn" title="Dernière page">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la matière <strong id="subjectName"></strong> ?</p>
                <p class="text-danger">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const cycleFilter = document.getElementById('cycleFilter');
    const seriesFilter = document.getElementById('seriesFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    
    function filterSubjects() {
        const searchTerm = searchInput.value.toLowerCase();
        const cycle = cycleFilter.value;
        const series = seriesFilter.value;
        
        const rows = document.querySelectorAll('#subjectsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const cycleCell = row.querySelector('td:nth-child(3)').textContent;
            const seriesCell = row.querySelector('td:nth-child(4)').textContent;
            
            const matchesSearch = text.includes(searchTerm);
            const matchesCycle = !cycle || cycleCell.toLowerCase().includes(cycle.toLowerCase());
            const matchesSeries = !series || seriesCell.includes(series);
            
            row.style.display = (matchesSearch && matchesCycle && matchesSeries) ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterSubjects);
    cycleFilter.addEventListener('change', filterSubjects);
    seriesFilter.addEventListener('change', filterSubjects);
    
    // Gérer l'affichage du filtre série selon le cycle
    cycleFilter.addEventListener('change', function() {
        const seriesContainer = document.getElementById('seriesFilterContainer');
        if (this.value === 'lycee') {
            seriesContainer.style.display = 'block';
        } else {
            seriesContainer.style.display = 'none';
            seriesFilter.value = '';
        }
        filterSubjects();
    });
    
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        cycleFilter.value = '';
        seriesFilter.value = '';
        document.getElementById('seriesFilterContainer').style.display = 'none';
        filterSubjects();
    });
    
    // Delete functionality
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const subjectName = document.getElementById('subjectName');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            subjectName.textContent = name;
            confirmDeleteBtn.onclick = function() {
                fetch(`/subjects/${id}`, {
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

// Fonction pour navigation rapide vers une page
function jumpToPage() {
    const pageInput = document.getElementById('pageJump');
    const pageNumber = parseInt(pageInput.value);
    const currentPage = parseInt(pageInput.dataset.currentPage);
    const maxPages = parseInt(pageInput.dataset.lastPage);
    
    if (pageNumber >= 1 && pageNumber <= maxPages) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('page', pageNumber);
        window.location.href = currentUrl.toString();
    } else {
        pageInput.value = currentPage;
        alert('Numéro de page invalide. Veuillez entrer un numéro entre 1 et ' + maxPages);
    }
}

// Permettre la navigation avec Enter dans le champ de saut de page
document.addEventListener('DOMContentLoaded', function() {
    const pageJumpInput = document.getElementById('pageJump');
    if (pageJumpInput) {
        pageJumpInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                jumpToPage();
            }
        });
    }
});
</script>
@endsection 
