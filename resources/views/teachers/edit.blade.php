@extends('layouts.app')

@section('title', 'Modifier Enseignant - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">Enseignants</a></li>
<li class="breadcrumb-item active">Modifier {{ $teacher->full_name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Modifier l'enseignant
                    </h4>
                </div>
                <div class="card-body">
                    <form id="teacherForm" method="POST" action="{{ route('teachers.update', $teacher) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Informations de base -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Informations personnelles</h5>
                                
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Matricule enseignant</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                        <input type="text" class="form-control" id="employee_id_display" readonly style="background-color: #f8f9fa;" value="{{ $teacher->employee_id }}">
                                        <input type="hidden" id="employee_id" name="employee_id" value="{{ $teacher->employee_id }}">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-shield-check me-1 text-success"></i>
                                        <span class="text-success">Matricule existant</span> - Ce matricule ne peut pas être modifié
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">Prénom *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="{{ $teacher->first_name }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Nom *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="{{ $teacher->last_name }}" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $teacher->email }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $teacher->phone }}">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">Date de naissance</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="{{ $teacher->date_of_birth ? $teacher->date_of_birth->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="gender" class="form-label">Genre</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Sélectionner</option>
                                                <option value="male" {{ $teacher->gender === 'male' ? 'selected' : '' }}>Masculin</option>
                                                <option value="female" {{ $teacher->gender === 'female' ? 'selected' : '' }}>Féminin</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="address" name="address" rows="3">{{ $teacher->address }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">
                                        <i class="bi bi-camera me-2"></i>Photo d'identité
                                    </label>
                                    @if($teacher->photo)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $teacher->photo) }}" 
                                                 alt="Photo actuelle" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 100px; max-height: 100px;">
                                            <div class="form-text">
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Photo actuelle
                                                </small>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Formats acceptés: JPG, PNG, GIF (max 2MB)
                                            @if($teacher->photo)
                                                <br><em>Laisser vide pour conserver la photo actuelle</em>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informations professionnelles -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Informations professionnelles</h5>
                                
                                <div class="mb-3">
                                    <label for="cycle" class="form-label">Cycle *</label>
                                    <select class="form-select" id="cycle" name="cycle" required>
                                        <option value="">Sélectionner un cycle</option>
                                        <option value="preprimaire" {{ $teacher->cycle === 'preprimaire' ? 'selected' : '' }}>Pré-primaire</option>
                                        <option value="primaire" {{ $teacher->cycle === 'primaire' ? 'selected' : '' }}>Primaire</option>
                                        <option value="college" {{ $teacher->cycle === 'college' ? 'selected' : '' }}>Collège</option>
                                        <option value="lycee" {{ $teacher->cycle === 'lycee' ? 'selected' : '' }}>Lycée</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="teacher_type" class="form-label">Type d'enseignant *</label>
                                    <select class="form-select" id="teacher_type" name="teacher_type" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="general" {{ $teacher->teacher_type === 'general' ? 'selected' : '' }}>Généraliste (une classe)</option>
                                        <option value="specialized" {{ $teacher->teacher_type === 'specialized' ? 'selected' : '' }}>Spécialisé (une matière)</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>Note :</strong> Le type sera automatiquement défini selon le cycle sélectionné :
                                        <ul class="mb-0 mt-1">
                                            <li><strong>Pré-primaire/Primaire :</strong> Généraliste (un enseignant par classe)</li>
                                            <li><strong>Collège/Lycée :</strong> Spécialisé (un enseignant par matière)</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3 {{ $teacher->teacher_type === 'general' ? '' : 'd-none' }}" id="assigned_class_div">
                                    <label for="assigned_class_id" class="form-label">Classe assignée *</label>
                                    <select class="form-select" id="assigned_class_id" name="assigned_class_id">
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ $teacher->assigned_class_id == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3 {{ $teacher->teacher_type === 'specialized' ? '' : 'd-none' }}" id="specialization_div">
                                    <label for="specialization" class="form-label">Matière de spécialisation *</label>
                                    <select class="form-select" id="specialization" name="specialization">
                                        <option value="">Sélectionner une matière</option>
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->name }}" {{ $teacher->specialization === $subject->name ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Sélectionnez la matière principale enseignée par ce professeur
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="qualification" class="form-label">Diplôme</label>
                                    <input type="text" class="form-control" id="qualification" name="qualification" value="{{ $teacher->qualification }}">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hire_date" class="form-label">Date d'embauche *</label>
                                            <input type="date" class="form-control" id="hire_date" name="hire_date" value="{{ $teacher->hire_date->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="salary" class="form-label">Salaire</label>
                                            <input type="number" class="form-control" id="salary" name="salary" step="0.01" min="0" value="{{ $teacher->salary }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Statut *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" {{ $teacher->status === 'active' ? 'selected' : '' }}>Actif</option>
                                        <option value="inactive" {{ $teacher->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                        <option value="suspended" {{ $teacher->status === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Annuler
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-check-circle me-2"></i>Mettre à jour
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle');
    const teacherTypeSelect = document.getElementById('teacher_type');
    const assignedClassDiv = document.getElementById('assigned_class_div');
    const specializationDiv = document.getElementById('specialization_div');
    const assignedClassSelect = document.getElementById('assigned_class_id');
    const specializationSelect = document.getElementById('specialization');
    
    // Gérer le changement de cycle (sélection automatique du type)
    cycleSelect.addEventListener('change', function() {
        const selectedCycle = this.value;
        
        // Sélection automatique du type selon le cycle
        if (selectedCycle === 'preprimaire' || selectedCycle === 'primaire') {
            teacherTypeSelect.value = 'general';
            teacherTypeSelect.disabled = true; // Désactiver le changement
            assignedClassDiv.style.display = 'block';
            specializationDiv.style.display = 'none';
            specializationSelect.removeAttribute('required');
            assignedClassSelect.setAttribute('required', 'required');
        } else if (selectedCycle === 'college' || selectedCycle === 'lycee') {
            teacherTypeSelect.value = 'specialized';
            teacherTypeSelect.disabled = false; // Permettre le changement
            assignedClassDiv.style.display = 'none';
            specializationDiv.style.display = 'block';
            assignedClassSelect.removeAttribute('required');
            specializationSelect.setAttribute('required', 'required');
        } else {
            teacherTypeSelect.value = '';
            teacherTypeSelect.disabled = false;
            assignedClassDiv.style.display = 'none';
            specializationDiv.style.display = 'none';
            specializationSelect.removeAttribute('required');
            assignedClassSelect.removeAttribute('required');
        }
        
        // Charger les classes selon le cycle sélectionné
        if (selectedCycle) {
            fetch(`/teachers/classes-by-cycle?cycle=${selectedCycle}`)
                .then(response => response.json())
                .then(classes => {
                    assignedClassSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                    classes.forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.id;
                        option.textContent = classItem.name;
                        assignedClassSelect.appendChild(option);
                    });
                });
        }
    });
    
    // Gérer le changement de type d'enseignant (pour collège/lycée)
    teacherTypeSelect.addEventListener('change', function() {
        if (this.value === 'general') {
            assignedClassDiv.style.display = 'block';
            specializationDiv.style.display = 'none';
            specializationSelect.removeAttribute('required');
            assignedClassSelect.setAttribute('required', 'required');
        } else if (this.value === 'specialized') {
            assignedClassDiv.style.display = 'none';
            specializationDiv.style.display = 'block';
            assignedClassSelect.removeAttribute('required');
            specializationSelect.setAttribute('required', 'required');
        } else {
            assignedClassDiv.style.display = 'none';
            specializationDiv.style.display = 'none';
            specializationSelect.removeAttribute('required');
            assignedClassSelect.removeAttribute('required');
        }
    });
    
    // Charger les classes selon le cycle sélectionné
    cycleSelect.addEventListener('change', function() {
        if (this.value) {
            fetch(`/teachers/classes-by-cycle?cycle=${this.value}`)
                .then(response => response.json())
                .then(classes => {
                    assignedClassSelect.innerHTML = '<option value="">Sélectionner une classe</option>';
                    classes.forEach(classItem => {
                        const option = document.createElement('option');
                        option.value = classItem.id;
                        option.textContent = classItem.name;
                        assignedClassSelect.appendChild(option);
                    });
                });
        }
    });
    
    // Gérer la soumission du formulaire
    document.getElementById('teacherForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher le message de succès avec le matricule généré
                let message = data.message;
                if (data.generated_matricule) {
                    message += `\n\nMatricule généré: ${data.generated_matricule}`;
                }
                
                alert(message);
                
                // Rediriger vers la page de détails
                window.location.href = '{{ route("teachers.show", $teacher) }}';
            } else {
                // Afficher les erreurs
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la mise à jour.');
        });
    });
});
</script>
@endsection 
