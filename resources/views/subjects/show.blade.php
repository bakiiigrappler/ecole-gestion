@extends('layouts.app')

@section('title', 'Détails de la Matière - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('subjects.index') }}">Matières</a></li>
<li class="breadcrumb-item active">{{ $subject->name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-eye me-2"></i>
                            Détails de la matière : {{ $subject->name }}
                        </h4>
                        <div class="btn-group">
                            <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil me-1"></i>Modifier
                            </a>
                            <a href="{{ route('subjects.index') }}" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left me-1"></i>Retour
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Informations principales -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                Informations générales
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom de la matière</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="bi bi-book me-2 text-primary"></i>
                                    {{ $subject->name }}
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Code</label>
                                <div class="p-2 bg-light rounded">
                                    <span class="badge bg-secondary">{{ $subject->code }}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Cycle</label>
                                <div class="p-2 bg-light rounded">
                                    @php
                                        $cycleClass = match($subject->cycle) {
                                            'preprimaire' => 'bg-success',
                                            'primaire' => 'bg-primary', 
                                            'college' => 'bg-warning',
                                            'lycee' => 'bg-purple',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $cycleClass }}">
                                        <i class="bi bi-layers me-1"></i>
                                        {{ ucfirst($subject->cycle) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Coefficient</label>
                                <div class="p-2 bg-light rounded">
                                    <span class="badge bg-success">{{ $subject->coefficient }}</span>
                                    <small class="text-muted ms-2">Points dans le calcul des moyennes</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Statut</label>
                                <div class="p-2 bg-light rounded">
                                    @if($subject->is_active)
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Actif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-x-circle me-1"></i>Inactif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Séries et description -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-bookmark me-2"></i>
                                Séries et détails
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Séries applicables</label>
                                <div class="p-3 bg-light rounded">
                                    @if($subject->cycle === 'lycee' && $subject->series)
                                        @php
                                            $seriesLabels = [
                                                'S' => 'S - Scientifique',
                                                'A1' => 'A1 - Lettres-Langues anciennes',
                                                'A2' => 'A2 - Lettres-Langues vivantes',
                                                'B' => 'B - Sciences économiques',
                                                'C' => 'C - Mathématiques-Sciences',
                                                'D' => 'D - Sciences naturelles',
                                                'E' => 'E - Techniques industrielles',
                                                'LE' => 'LE - Lettres modernes'
                                            ];
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($subject->series as $serie)
                                                <span class="badge bg-info">
                                                    {{ $seriesLabels[$serie] ?? $serie }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-3">
                                            <i class="bi bi-check-all text-success fs-3"></i>
                                            <div class="mt-2">
                                                <strong class="text-success">Tout le cycle</strong>
                                                <div class="small text-muted">
                                                    Cette matière s'applique à toutes les classes du {{ $subject->cycle }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <div class="p-3 bg-light rounded">
                                    @if($subject->description)
                                        <p class="mb-0">{{ $subject->description }}</p>
                                    @else
                                        <div class="text-center text-muted py-2">
                                            <i class="bi bi-file-text"></i>
                                            Aucune description fournie
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dates</label>
                                <div class="p-2 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Créé le :</small>
                                            <div>{{ $subject->created_at->format('d/m/Y à H:i') }}</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Modifié le :</small>
                                            <div>{{ $subject->updated_at->format('d/m/Y à H:i') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-bar-chart me-2"></i>
                                Statistiques
                            </h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="bi bi-journal-check fs-2 text-primary"></i>
                                            <h6 class="mt-2">Notes enregistrées</h6>
                                            <h4 class="text-primary">{{ $subject->grades()->count() }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="bi bi-people fs-2 text-success"></i>
                                            <h6 class="mt-2">Enseignants</h6>
                                            <h4 class="text-success">{{ $subject->teachers()->count() }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="bi bi-calendar-week fs-2 text-warning"></i>
                                            <h6 class="mt-2">Horaires</h6>
                                            <h4 class="text-warning">{{ $subject->schedules()->count() }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <i class="bi bi-graph-up fs-2 text-info"></i>
                                            <h6 class="mt-2">Coefficient</h6>
                                            <h4 class="text-info">{{ $subject->coefficient }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-purple {
    background-color: #8b5cf6 !important;
}
</style>
@endsection
