@extends('layouts.app')

@section('title', 'Gestion des Professeurs - ' . $class->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
<li class="breadcrumb-item"><a href="{{ route('classes.show', $class->id) }}">{{ $class->name }}</a></li>
<li class="breadcrumb-item active">Professeurs</li>
@endsection

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
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 fw-bold">
                                <i class="bi bi-person-workspace me-3"></i>
                                Gestion des Professeurs
                            </h1>
                            <p class="mb-0 opacity-75">
                                Classe: <strong>{{ $class->name }}</strong> - 
                                Niveau: <strong>{{ is_object($class->level) ? $class->level->name : 'N/A' }}</strong> - 
                                Cycle: <strong>{{ ucfirst(is_object($class->level) ? $class->level->cycle : $class->level) }}</strong>
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('classes.show', $class->id) }}" class="btn btn-light btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>
                                Retour à la classe
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Professeurs assignés -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2"></i>
                        Professeurs Assignés ({{ $assignedTeachers->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($assignedTeachers->count() > 0)
                        <div class="row">
                            @foreach($assignedTeachers as $teacher)
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                                    <i class="bi bi-person-circle text-primary fs-4"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1 fw-bold">{{ $teacher->first_name }} {{ $teacher->last_name }}</h6>
                                                <div class="d-flex gap-2">
                                                    @if($teacher->pivot->role === 'principal')
                                                        <span class="badge bg-success">Principal</span>
                                                    @else
                                                        <span class="badge bg-info">Enseignant</span>
                                                    @endif
                                                    @if($teacher->teacher_type === 'specialized')
                                                        <span class="badge bg-warning">Spécialisé</span>
                                                    @else
                                                        <span class="badge bg-secondary">Généraliste</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @if($teacher->specialization && !in_array($teacher->cycle, ['preprimaire', 'primaire']))
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-book me-1"></i>
                                                    <strong>Matière:</strong> {{ $teacher->specialization }}
                                                </small>
                                            </div>
                                        @endif
                                        
                                        <div class="d-flex gap-2">
                                            @php
                                                $hasPrincipal = $assignedTeachers->where('pivot.role', 'principal')->count() > 0;
                                            @endphp
                                            @if($teacher->pivot->role === 'principal')
                                                <form method="POST" action="{{ route('classes.removePrincipalTeacher', [$class->id, $teacher->id]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Retirer le statut de professeur principal à {{ $teacher->first_name }} {{ $teacher->last_name }} ?')">
                                                        <i class="bi bi-star-fill me-1"></i>Retirer Principal
                                                    </button>
                                                </form>
                                            @elseif($teacher->pivot->role !== 'principal' && !$hasPrincipal)
                                                <form method="POST" action="{{ route('classes.setPrincipalTeacher', [$class->id, $teacher->id]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Définir {{ $teacher->first_name }} {{ $teacher->last_name }} comme professeur principal ?')">
                                                        <i class="bi bi-star me-1"></i>Définir Principal
                                                    </button>
                                                </form>
                                            @elseif($teacher->pivot->role !== 'principal' && $hasPrincipal)
                                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                    <i class="bi bi-star me-1"></i>Principal déjà défini
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="removeTeacher('{{ $teacher->id }}')">
                                                <i class="bi bi-x me-1"></i>Retirer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">Aucun professeur assigné</h5>
                            <p class="text-muted">Ajoutez des professeurs à cette classe en utilisant le formulaire ci-contre.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Formulaire d'ajout de professeurs (masqué pour primaire/pré-primaire) -->
        @php
            $isPrimaryLevel = in_array($class->getSafeCycle(), ['preprimaire', 'primaire']);
        @endphp
        
        @if(!$isPrimaryLevel)
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>
                        Ajouter des Professeurs
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('classes.updateTeachers', $class->id) }}" id="teachersForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-list-check me-1"></i>
                                Professeurs disponibles
                            </label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @foreach($availableTeachers as $teacher)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="teachers[]" value="{{ $teacher->id }}" 
                                               id="teacher_{{ $teacher->id }}"
                                               {{ $assignedTeachers->contains($teacher->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong>{{ $teacher->first_name }} {{ $teacher->last_name }}</strong>
                                                    @if($teacher->specialization && !in_array($teacher->cycle, ['preprimaire', 'primaire']))
                                                        <br><small class="text-muted">{{ $teacher->specialization }}</small>
                                                    @endif
                                                </span>
                                                <span class="badge {{ $teacher->teacher_type === 'specialized' ? 'bg-warning' : 'bg-secondary' }} small">
                                                    {{ $teacher->teacher_type === 'specialized' ? 'Spécialisé' : 'Généraliste' }}
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Mettre à jour les professeurs
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
        <!-- Message informatif pour primaire/pré-primaire -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Gestion des Enseignants
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-lightbulb me-2"></i>
                        <strong>Cycle {{ ucfirst($class->getSafeCycle()) }}</strong>
                    </div>
                    <p class="text-muted">
                        Au niveau <strong>{{ ucfirst($class->getSafeCycle()) }}</strong>, 
                        une classe ne peut avoir qu'un seul enseignant généraliste.
                    </p>
                    <p class="text-muted">
                        Pour modifier l'enseignant de cette classe, utilisez le bouton 
                        <strong>"Retirer"</strong> ci-contre, puis créez une nouvelle classe 
                        avec le nouvel enseignant.
                    </p>
                </div>
            </div>
        @endif

            <!-- Informations -->
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Informations
                    </h6>
                </div>
                <div class="card-body">
                    @if($isPrimaryLevel)
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-person-circle text-primary me-2"></i>
                                <strong>Enseignant unique:</strong> Un seul enseignant généraliste par classe
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-book text-info me-2"></i>
                                <strong>Matières:</strong> Toutes les matières enseignées par le même professeur
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                <strong>Règle importante:</strong> Impossible d'ajouter plusieurs enseignants
                            </li>
                        </ul>
                    @else
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-star-fill text-warning me-2"></i>
                                <strong>Professeur Principal:</strong> Responsable de la classe
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-person-check text-info me-2"></i>
                                <strong>Enseignants:</strong> Professeurs spécialisés par matière
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                <strong>Règle importante:</strong> Un seul professeur principal par classe
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <strong>Note:</strong> Une fois un professeur principal défini, les autres ne peuvent plus être promus
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function removeTeacher(teacherId) {
    if (confirm('Êtes-vous sûr de vouloir retirer ce professeur de la classe ?')) {
        // Créer un formulaire temporaire pour supprimer le professeur
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("classes.updateTeachers", $class->id) }}';
        
        // Token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Récupérer tous les professeurs assignés sauf celui à supprimer
        const checkboxes = document.querySelectorAll('input[name="teachers[]"]:checked');
        checkboxes.forEach(checkbox => {
            if (checkbox.value != teacherId) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'teachers[]';
                input.value = checkbox.value;
                form.appendChild(input);
            }
        });
        
        // Soumettre le formulaire
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
