@extends('layouts.app')

@section('title', 'Gestion des Parents - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item active">Parents</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestion des Parents</h1>
                    <p class="text-muted">Gérez les informations des parents et tuteurs</p>
                </div>
                <div>
                    <a href="{{ route('parents.create') }}" class="btn btn-primary">
                        <i class="bi bi-people-fill me-2"></i>
                        Ajouter un parent
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de succès/erreur -->
    @if(session('success'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $totalParents ?? 156 }}</h4>
                            <span>Total parents</span>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $activeContacts ?? 145 }}</h4>
                            <span>Contacts actifs</span>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $primaryContacts ?? 98 }}</h4>
                            <span>Contacts principaux</span>
                        </div>
                        <i class="bi bi-star-fill fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $canPickup ?? 134 }}</h4>
                            <span>Autorisés récupération</span>
                        </div>
                        <i class="bi bi-shield-check fs-1 opacity-50"></i>
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
                        <div class="col-md-4">
                            <label class="form-label">Rechercher</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Nom, prénom, téléphone..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Relation</label>
                            <select class="form-select" id="relationFilter">
                                <option value="">Toutes</option>
                                <option value="father">Père</option>
                                <option value="mother">Mère</option>
                                <option value="guardian">Tuteur</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Contact principal</label>
                            <select class="form-select" id="primaryFilter">
                                <option value="">Tous</option>
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Récupération</label>
                            <select class="form-select" id="pickupFilter">
                                <option value="">Tous</option>
                                <option value="1">Autorisé</option>
                                <option value="0">Non autorisé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success w-100" onclick="exportData()">
                                    <i class="bi bi-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parents Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people-fill me-2"></i>
                        Liste des parents
                    </h5>
                    <span class="badge bg-primary fs-6">{{ $parents->total() }} parent{{ $parents->total() > 1 ? 's' : '' }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Parent</th>
                                    <th>Relation</th>
                                    <th>Enfants</th>
                                    <th>Contact</th>
                                    <th>Profession</th>
                                    <th>Autorisations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($parents as $parent)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $initials = substr($parent->first_name, 0, 1) . substr($parent->last_name, 0, 1);
                                                $colors = ['007bff', '28a745', 'dc3545', 'ffc107', '17a2b8', '6610f2'];
                                                $color = $colors[abs(crc32($parent->id)) % count($colors)];
                                            @endphp
                                            <img src="https://via.placeholder.com/40x40/{{ $color }}/ffffff?text={{ $initials }}" 
                                                 class="rounded-circle me-3" width="40" height="40">
                                            <div>
                                                <div class="fw-bold">{{ $parent->first_name }} {{ $parent->last_name }}</div>
                                                <small class="text-muted">
                                                    @switch($parent->relationship)
                                                        @case('father') Père @break
                                                        @case('mother') Mère @break
                                                        @case('guardian') Tuteur/Tutrice @break
                                                        @default {{ ucfirst($parent->relationship) }}
                                                    @endswitch
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $relationColors = [
                                                'father' => 'primary',
                                                'mother' => 'success', 
                                                'guardian' => 'warning',
                                                'other' => 'secondary'
                                            ];
                                            $relationLabels = [
                                                'father' => 'Père',
                                                'mother' => 'Mère',
                                                'guardian' => 'Tuteur',
                                                'other' => 'Autre'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $relationColors[$parent->relationship] ?? 'secondary' }}">
                                            {{ $relationLabels[$parent->relationship] ?? ucfirst($parent->relationship) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($parent->students->count() > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($parent->students->take(2) as $student)
                                                    <span class="badge bg-info">{{ $student->first_name }} {{ $student->last_name }}</span>
                                                @endforeach
                                                @if($parent->students->count() > 2)
                                                    <span class="badge bg-secondary">+{{ $parent->students->count() - 2 }} autre(s)</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $parent->students->count() }} enfant{{ $parent->students->count() > 1 ? 's' : '' }}</small>
                                        @else
                                            <span class="text-muted">Aucun enfant</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <small class="d-block">{{ $parent->phone }}</small>
                                            @if($parent->email)
                                                <small class="text-muted">{{ $parent->email }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($parent->profession || $parent->workplace)
                                            <div>
                                                @if($parent->profession)
                                                    <div class="fw-bold small">{{ $parent->profession }}</div>
                                                @endif
                                                @if($parent->workplace)
                                                    <small class="text-muted">{{ $parent->workplace }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">Non renseigné</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if($parent->is_primary_contact)
                                                <span class="badge bg-success">Contact principal</span>
                                            @endif
                                            @if($parent->can_pickup)
                                                <span class="badge bg-info">Récupération</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('parents.show', $parent->id) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('parents.edit', $parent->id) }}" 
                                               class="btn btn-sm btn-outline-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-parent-btn" 
                                                    data-parent-id="{{ $parent->id }}" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-people fs-1 mb-3"></i>
                                            <h5>Aucun parent trouvé</h5>
                                            <p>Commencez par ajouter votre premier parent.</p>
                                            <a href="{{ route('parents.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-2"></i>Ajouter un parent
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($parents->hasPages())
<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex justify-content-center">
            {{ $parents->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function deleteParent(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce parent ?')) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/parents/${id}`;
        form.innerHTML = `
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('relationFilter').value = '';
    document.getElementById('primaryFilter').value = '';
    document.getElementById('pickupFilter').value = '';
    // Rediriger vers la page sans filtres
    window.location.href = "{{ route('parents.index') }}";
}

function exportData() {
    alert('Export en cours...');
}

// Filtrage en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const relationFilter = document.getElementById('relationFilter');
    const primaryFilter = document.getElementById('primaryFilter');
    const pickupFilter = document.getElementById('pickupFilter');
    
    // Event listener pour les boutons de suppression
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-parent-btn')) {
            const parentId = e.target.closest('.delete-parent-btn').dataset.parentId;
            deleteParent(parentId);
        }
    });
    
    let searchTimeout;
    
    function applyFilters() {
        const params = new URLSearchParams();
        
        if (searchInput.value.trim()) {
            params.append('search', searchInput.value.trim());
        }
        if (relationFilter.value) {
            params.append('relationship', relationFilter.value);
        }
        if (primaryFilter.value) {
            params.append('is_primary_contact', primaryFilter.value);
        }
        if (pickupFilter.value) {
            params.append('can_pickup', pickupFilter.value);
        }
        
        const url = new URL(window.location.href);
        url.search = params.toString();
        window.location.href = url.toString();
    }
    
    // Recherche avec délai
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500);
    });
    
    // Filtres immédiats
    relationFilter.addEventListener('change', applyFilters);
    primaryFilter.addEventListener('change', applyFilters);
    pickupFilter.addEventListener('change', applyFilters);
    
    // Pré-remplir les filtres avec les valeurs de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('search')) {
        searchInput.value = urlParams.get('search');
    }
    if (urlParams.get('relationship')) {
        relationFilter.value = urlParams.get('relationship');
    }
    if (urlParams.get('is_primary_contact')) {
        primaryFilter.value = urlParams.get('is_primary_contact');
    }
    if (urlParams.get('can_pickup')) {
        pickupFilter.value = urlParams.get('can_pickup');
    }
});
</script>
@endpush 
