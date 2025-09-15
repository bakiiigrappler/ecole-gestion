@extends('layouts.app')

@section('title', 'Modifier la Classe - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
<li class="breadcrumb-item"><a href="{{ route('classes.show', $class) }}">{{ $class->name }}</a></li>
<li class="breadcrumb-item active">Modifier</li>
@endsection

@push('styles')
<link href="{{ asset('css/classes-enhanced.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-2 fw-bold">
                                <i class="bi bi-pencil me-3"></i>
                                Modifier la Classe
                            </h1>
                            <p class="mb-0 opacity-75">Modifiez les informations de la classe {{ $class->name }}</p>
                        </div>
                        <div>
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-outline-light">
                                <i class="bi bi-arrow-left me-2"></i>
                                Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire d'édition -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-form-text me-2 text-primary"></i>
                        Informations de la Classe
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Erreurs détectées :</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('classes.update', $class) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-bold">
                                    <i class="bi bi-tag me-1"></i>
                                    Nom de la classe *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ old('name', $class->name) }}" required 
                                       placeholder="Ex: 6ème A">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="level_id" class="form-label fw-bold">
                                    <i class="bi bi-bookmark me-1"></i>
                                    Niveau *
                                </label>
                                <select class="form-select" id="level_id" name="level_id" required>
                                    <option value="">Sélectionner un niveau</option>
                                    @foreach($levels->groupBy('cycle') as $cycle => $cyclelevels)
                                        <optgroup label="{{ ucfirst($cycle) }}">
                                            @foreach($cyclelevels as $level)
                                                <option value="{{ $level->id }}" {{ old('level_id', $class->level_id) == $level->id ? 'selected' : '' }}>
                                                    {{ $level->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="level" class="form-label fw-bold">
                                    <i class="bi bi-layers me-1"></i>
                                    Cycle (auto-rempli)
                                </label>
                                <input type="text" class="form-control" id="level" name="level" 
                                       value="{{ old('level', $class->level) }}" readonly 
                                       placeholder="Sera rempli automatiquement">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label fw-bold">
                                    <i class="bi bi-people me-1"></i>
                                    Capacité
                                </label>
                                <input type="number" class="form-control" id="capacity" name="capacity" 
                                       value="{{ old('capacity', $class->capacity) }}" 
                                       min="1" max="50" placeholder="30">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">
                                <i class="bi bi-journal-text me-1"></i>
                                Description
                            </label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" placeholder="Description de la classe...">{{ old('description', $class->description) }}</textarea>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       {{ old('is_active', $class->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_active">
                                    <i class="bi bi-toggle-on me-1"></i>
                                    Classe active
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Mettre à jour la classe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill cycle when level is selected
    document.getElementById('level_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const cycleField = document.getElementById('level');
        
        if (this.value) {
            // Get the optgroup label (cycle name)
            const optgroup = selectedOption.closest('optgroup');
            if (optgroup) {
                cycleField.value = optgroup.label.toLowerCase();
            }
        } else {
            cycleField.value = '';
        }
    });
});
</script>
@endsection
