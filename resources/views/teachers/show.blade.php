@extends('layouts.app')

@section('title', 'Détails Enseignant - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Enseignants</a></li>
<li class="breadcrumb-item active">{{ $teacher->full_name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Détails de l'Enseignant</h1>
                    <p class="text-muted">{{ $teacher->full_name }}</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Modifier
                    </a>
                    <a href="{{ route('teachers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-workspace me-2"></i>
                        Informations de l'enseignant
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">ID Employé</label>
                                <p class="form-control-plaintext">{{ $teacher->employee_id }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom complet</label>
                                <p class="form-control-plaintext">{{ $teacher->full_name }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <p class="form-control-plaintext">{{ $teacher->email }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Téléphone</label>
                                <p class="form-control-plaintext">{{ $teacher->phone ?? 'Non renseigné' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date de naissance</label>
                                <p class="form-control-plaintext">
                                    {{ $teacher->date_of_birth ? $teacher->date_of_birth->format('d/m/Y') : 'Non renseignée' }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Genre</label>
                                <p class="form-control-plaintext">
                                    @if($teacher->gender === 'male')
                                        Masculin
                                    @elseif($teacher->gender === 'female')
                                        Féminin
                                    @else
                                        Non renseigné
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Adresse</label>
                                <p class="form-control-plaintext">{{ $teacher->address ?? 'Non renseignée' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Statut</label>
                                <p class="form-control-plaintext">
                                    @if($teacher->status === 'active')
                                        <span class="badge bg-success">Actif</span>
                                    @elseif($teacher->status === 'inactive')
                                        <span class="badge bg-secondary">Inactif</span>
                                    @else
                                        <span class="badge bg-danger">Suspendu</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ancienneté</label>
                                <p class="form-control-plaintext">{{ $teacher->years_of_service }} ans</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations professionnelles -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-briefcase me-2"></i>
                        Informations professionnelles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Cycle</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">{{ $teacher->cycle_label }}</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Type d'enseignant</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-warning">{{ $teacher->teacher_type_label }}</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Qualification</label>
                                <p class="form-control-plaintext">{{ $teacher->qualification ?? 'Non renseignée' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date d'embauche</label>
                                <p class="form-control-plaintext">{{ $teacher->hire_date->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($teacher->teacher_type === 'general')
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Classe assignée</label>
                                    <p class="form-control-plaintext">
                                        @if($teacher->assignedClass)
                                            <span class="badge bg-success">{{ $teacher->assignedClass->name }}</span>
                                        @else
                                            <span class="text-muted">Aucune classe assignée</span>
                                        @endif
                                    </p>
                                </div>
                            @elseif($teacher->teacher_type === 'specialized')
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Matière de spécialisation</label>
                                    <p class="form-control-plaintext">
                                        @if($teacher->specialization)
                                            <span class="badge bg-primary">
                                                <i class="bi bi-book me-1"></i>
                                                {{ $teacher->specialization }}
                                            </span>
                                        @else
                                            <span class="text-muted">Aucune spécialisation définie</span>
                                        @endif
                                    </p>
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Spécialisation</label>
                                    <p class="form-control-plaintext">
                                        @if($teacher->specialization)
                                            <span class="badge bg-primary">{{ $teacher->specialization }}</span>
                                        @else
                                            <span class="text-muted">Non spécifiée</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label fw-bold">Salaire</label>
                                <p class="form-control-plaintext">
                                    @if($teacher->salary)
                                        {{ number_format($teacher->salary, 0, ',', ' ') }} FCFA
                                    @else
                                        Non renseigné
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques et actions -->
        <div class="col-md-4">
            <!-- Carte de profil -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar-lg mx-auto mb-3">
                        @if($teacher->photo)
                            <img src="{{ asset('storage/' . $teacher->photo) }}" 
                                 alt="Photo {{ $teacher->full_name }}" 
                                 class="rounded-circle" 
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="avatar-title bg-primary rounded-circle" style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ strtoupper(substr($teacher->first_name, 0, 1) . substr($teacher->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <h5 class="card-title">{{ $teacher->full_name }}</h5>
                    <p class="text-muted">{{ $teacher->qualification ?? 'Enseignant' }}</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>Modifier
                        </a>
                        <button type="button" class="btn btn-outline-danger delete-btn" 
                                data-id="{{ $teacher->id }}" 
                                data-name="{{ $teacher->full_name }}">
                            <i class="bi bi-trash me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Statistiques
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-0">{{ $teacher->grades->count() }}</h4>
                                <small class="text-muted">Notes attribuées</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-0">{{ $teacher->years_of_service }}</h4>
                            <small class="text-muted">Années d'expérience</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Actions rapides
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-calendar3 me-2"></i>Voir emploi du temps
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-file-text me-2"></i>Générer rapport
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-envelope me-2"></i>Envoyer message
                        </button>
                    </div>
                </div>
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
                <p>Êtes-vous sûr de vouloir supprimer l'enseignant <strong id="teacherName"></strong> ?</p>
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
                        window.location.href = '{{ route("teachers.index") }}';
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
