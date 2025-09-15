@extends('layouts.app')

@section('title', 'Créer Élève - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Inscriptions</a></li>
<li class="breadcrumb-item"><a href="{{ route('enrollments.show', $enrollment) }}">Inscription #{{ $enrollment->id }}</a></li>
<li class="breadcrumb-item active">Créer élève</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Résumé de l'inscription -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-file-text me-2"></i>
                        Résumé de l'inscription
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Inscrit :</strong><br>
                        {{ $enrollment->applicant_full_name }}<br>
                        <small class="text-muted">{{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }}</small><br>
                        <small class="text-muted">Né(e) le {{ $enrollment->applicant_date_of_birth->format('d/m/Y') }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Contact :</strong><br>
                        @if($enrollment->applicant_phone)
                            <i class="bi bi-telephone me-1"></i>{{ $enrollment->applicant_phone }}<br>
                        @endif
                        @if($enrollment->applicant_email)
                            <i class="bi bi-envelope me-1"></i>{{ $enrollment->applicant_email }}<br>
                        @endif
                        <small class="text-muted">{{ $enrollment->applicant_address }}</small>
                    </div>
                    
                    @if($enrollment->parent_first_name && $enrollment->parent_last_name)
                    <div class="mb-3">
                        <strong>Parent/Tuteur :</strong><br>
                        {{ $enrollment->parent_full_name }}<br>
                        <small class="text-muted">{{ $enrollment->parent_relationship_label }}</small><br>
                        @if($enrollment->parent_phone)
                            <i class="bi bi-telephone me-1"></i>{{ $enrollment->parent_phone }}
                        @endif
                        @if($enrollment->parent_email)
                            <br><i class="bi bi-envelope me-1"></i>{{ $enrollment->parent_email }}
                        @endif
                    </div>
                    @else
                    <div class="mb-3">
                        <strong>Parent/Tuteur :</strong><br>
                        <small class="text-muted">Informations parent à saisir ultérieurement</small>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Inscription :</strong><br>
                        @if($enrollment->schoolClass && is_object($enrollment->schoolClass))
                            {{ $enrollment->schoolClass->name }}<br>
                            @if($enrollment->schoolClass->levelData && is_object($enrollment->schoolClass->levelData))
                                <small class="text-muted">
                                    <i class="bi bi-mortarboard me-1"></i>
                                    {{ $enrollment->schoolClass->levelData->name }} ({{ ucfirst($enrollment->schoolClass->levelData->cycle) }})
                                </small><br>
                            @else
                                <small class="text-muted">Niveau non trouvé</small><br>
                            @endif
                        @else
                            <span class="text-danger">Classe non trouvée</span><br>
                        @endif
                        <small class="text-muted">
                            <i class="bi bi-calendar-range me-1"></i>
                            @if($enrollment->academicYear && is_object($enrollment->academicYear))
                                {{ $enrollment->academicYear->name }}
                            @else
                                Année non trouvée (ID: {{ $enrollment->academic_year_id ?? 'null' }})
                            @endif
                        </small><br>
                        <small class="text-muted">
                            <i class="bi bi-calendar-plus me-1"></i>
                            Inscrit le {{ $enrollment->enrollment_date->format('d/m/Y') }}
                        </small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Ces informations seront automatiquement copiées dans le profil élève.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de création d'élève -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-person-plus me-2"></i>
                        Créer le profil élève
                    </h4>
                    <p class="mb-0 small">Complétez les informations manquantes pour créer le profil élève</p>
                </div>
                <div class="card-body">
                    <form id="createStudentForm" method="POST" action="{{ route('enrollments.store-student', $enrollment) }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-person me-2"></i>
                                    Informations personnelles
                                    <small class="text-muted">(pré-remplies)</small>
                                </h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nom complet</label>
                                    <input type="text" class="form-control" value="{{ $enrollment->applicant_full_name }}" readonly>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Date de naissance</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->applicant_date_of_birth->format('d/m/Y') }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Âge</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->applicant_age }} ans" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Sexe</label>
                                    <input type="text" class="form-control" value="{{ $enrollment->applicant_gender === 'male' ? 'Masculin' : 'Féminin' }}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Adresse</label>
                                    <textarea class="form-control" readonly rows="2">{{ $enrollment->applicant_address }}</textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Informations complémentaires
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Matricule élève</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                        <input type="text" class="form-control" id="student_id_display" readonly style="background-color: #f8f9fa;" value="Chargement...">
                                        <input type="hidden" id="student_id" name="student_id">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-magic me-1 text-primary"></i>
                                        <span class="text-primary">Matricule généré automatiquement</span> - Ce matricule sera attribué à l'élève lors de l'enregistrement
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="place_of_birth" class="form-label">Lieu de naissance</label>
                                    <input type="text" class="form-control" id="place_of_birth" name="place_of_birth" placeholder="Ville, Pays">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="emergency_contact" class="form-label">Contact d'urgence</label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" value="{{ $enrollment->parent_phone }}" placeholder="+241 XX XX XX XX">
                                    <div class="form-text">Téléphone à contacter en cas d'urgence</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="medical_conditions" class="form-label">Conditions médicales</label>
                                    <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="3" placeholder="Allergies, conditions particulières, médicaments..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Photo de l'élève</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">Formats acceptés: JPG, PNG, GIF (max 2MB)</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-warning mb-3">
                                    <i class="bi bi-mortarboard me-2"></i>
                                    Informations scolaires (de l'inscription)
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Classe</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->schoolClass && is_object($enrollment->schoolClass) ? $enrollment->schoolClass->name : 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Niveau</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->schoolClass && is_object($enrollment->schoolClass) && $enrollment->schoolClass->levelData && is_object($enrollment->schoolClass->levelData) ? $enrollment->schoolClass->levelData->name : 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Année scolaire</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->academicYear && is_object($enrollment->academicYear) ? $enrollment->academicYear->name : 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Date d'inscription</label>
                                            <input type="text" class="form-control" value="{{ $enrollment->enrollment_date->format('d/m/Y') }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('enrollments.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Retour aux inscriptions
                                        </a>
                                        <button type="button" class="btn btn-warning ms-2" onclick="markAsPending()">
                                            <i class="bi bi-clock me-2"></i>Marquer en attente
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>Créer l'élève
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
    // Charger automatiquement le prochain matricule disponible
    function loadNextStudentMatricule() {
        const studentIdDisplay = document.getElementById('student_id_display');
        const studentIdHidden = document.getElementById('student_id');
        
        if (studentIdDisplay && studentIdHidden) {
            // Afficher un indicateur de chargement
            studentIdDisplay.value = 'Chargement...';
            
            fetch('/api/next-student-matricule')
                .then(response => response.json())
                .then(data => {
                    studentIdDisplay.value = data.matricule;
                    studentIdHidden.value = data.matricule;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du matricule:', error);
                    studentIdDisplay.value = 'Erreur de chargement';
                });
        }
    }

    // Charger le matricule au chargement de la page
    loadNextStudentMatricule();
    
    // Soumission du formulaire
    document.getElementById('createStudentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Afficher le spinner et désactiver le bouton
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Création en cours...';
        submitBtn.disabled = true;
        
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
                // Changer le texte pour indiquer le succès
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Élève créé avec succès !';
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-success');
                
                alert(data.message);
                window.location.href = '{{ route("enrollments.index") }}';
            } else {
                alert('Erreur: ' + data.message);
                // Restaurer le bouton original
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la création de l\'élève.');
            // Restaurer le bouton original
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

function markAsPending() {
    if (confirm('Voulez-vous marquer cette inscription en attente pour créer l\'élève plus tard ?')) {
        fetch(`/enrollments/{{ $enrollment->id }}/mark-pending`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = '{{ route("enrollments.index") }}';
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
}
</script>
@endsection 
