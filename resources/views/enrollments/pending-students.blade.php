@extends('layouts.app')

@section('title', 'Inscriptions en attente - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Inscriptions</a></li>
<li class="breadcrumb-item active">En attente de création élève</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Inscriptions en attente</h1>
                    <p class="text-muted">Inscriptions enregistrées qui n'ont pas encore de profil élève créé</p>
                </div>
                <div>
                    <a href="{{ route('enrollments.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>
                        Nouvelle inscription
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $pendingEnrollments->total() ?? 0 }}</h4>
                            <span>Inscriptions en attente</span>
                        </div>
                        <i class="bi bi-clock-history fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Enrollments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Inscriptions en attente de création élève
                    </h5>
                    <span class="badge bg-warning fs-6">{{ $pendingEnrollments->count() ?? 0 }} en attente</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                                <tr>
                                    <th class="border-0" style="width: 100px;">ID</th>
                                    <th class="border-0" style="min-width: 200px;">Inscrit</th>
                                    <th class="border-0" style="width: 100px;">Âge</th>
                                    <th class="border-0" style="min-width: 180px;">Parent/Tuteur</th>
                                    <th class="border-0" style="width: 150px;">Classe demandée</th>
                                    <th class="border-0" style="width: 120px;">Date inscription</th>
                                    <th class="border-0" style="width: 100px;">Statut</th>
                                    <th class="border-0" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingEnrollments as $enrollment)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">#{{ $enrollment->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">{{ $enrollment->applicant_full_name }}</div>
                                            <small class="text-muted">
                                                {{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }} - 
                                                Né(e) le {{ $enrollment->applicant_date_of_birth->format('d/m/Y') }}
                                            </small>
                                            @if($enrollment->applicant_phone)
                                                <br><small class="text-muted">
                                                    <i class="bi bi-telephone me-1"></i>{{ $enrollment->applicant_phone }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $enrollment->applicant_age }} ans</td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">{{ $enrollment->parent_full_name }}</div>
                                            <small class="text-muted">{{ $enrollment->parent_relationship_label }}</small><br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i>{{ $enrollment->parent_phone }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="badge bg-info">{{ $enrollment->schoolClass->name ?? 'N/A' }}</span>
                                            @if($enrollment->schoolClass && $enrollment->schoolClass->level)
                                                <br><small class="text-muted">{{ $enrollment->schoolClass->level->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $enrollment->enrollment_date->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-warning">En attente</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('enrollments.create-student', $enrollment) }}" class="btn btn-sm btn-success" title="Créer l'élève">
                                                <i class="bi bi-person-plus"></i>
                                            </a>
                                            <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-sm btn-outline-info" title="Voir détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('enrollments.edit', $enrollment) }}" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <h5>Aucune inscription en attente</h5>
                                            <p>Toutes les inscriptions ont leur profil élève créé ou sont en cours de traitement.</p>
                                            <a href="{{ route('enrollments.create') }}" class="btn btn-primary">
                                                <i class="bi bi-person-plus me-2"></i>
                                                Créer une nouvelle inscription
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
                @if($pendingEnrollments->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Affichage de {{ $pendingEnrollments->firstItem() ?? 0 }} à {{ $pendingEnrollments->lastItem() ?? 0 }} sur {{ $pendingEnrollments->total() ?? 0 }} inscriptions
                        </div>
                        <nav aria-label="Pagination des inscriptions">
                            {{ $pendingEnrollments->links('pagination::bootstrap-5') }}
                        </nav>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actions bulk (sélection multiple) pourront être ajoutées ici
});
</script>
@endsection 
