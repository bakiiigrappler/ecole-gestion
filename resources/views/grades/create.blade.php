@extends('layouts.app')

@section('title', 'Saisir des Notes - Egesco')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('grades.index') }}">Notes</a></li>
<li class="breadcrumb-item active">Saisir des notes</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-journal-plus me-2"></i>
                        Saisir des notes
                    </h5>
                </div>
                <div class="card-body">
                    <form id="gradeForm" onsubmit="return false;">
                        @csrf
                                                 <!-- Champs cachés pour les données dynamiques -->
                         <input type="hidden" id="hidden_class_id" name="class_id" value="">
                         <input type="hidden" id="teacher_id" name="teacher_id" value="">
                         
                         @if(isset($selectedStudent) && $selectedStudent)
                         <input type="hidden" id="preselected_student" 
                                data-student='@json($selectedStudent)' 
                                data-enrollment='@json($selectedStudent->enrollments->where("status", "active")->first())'>
                         @endif
                        
                        <!-- Étape 1: Sélection hiérarchique Niveau → Classe → Élève -->
                        <div class="step-container" id="step1">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="text-center mb-4">
                                        <div class="step-indicator active">
                                            <span class="step-number">1</span>
                                            <span class="step-label">Sélection de l'élève</span>
                                        </div>
                                    </div>
                                    
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-primary mb-3">
                                                <i class="bi bi-person-search me-2"></i>
                                                Choisir un élève (Sélection hiérarchique)
                                            </h6>
                                            
                                            <!-- Sélection du niveau -->
                                            <div class="mb-3">
                                                <label for="level_id" class="form-label">Niveau <span class="text-danger">*</span></label>
                                                <select class="form-select @error('level_id') is-invalid @enderror" 
                                                        id="level_id" name="level_id" required>
                                                    <option value="">Sélectionner un niveau...</option>
                                                    @foreach($levels ?? [] as $level)
                                                        <option value="{{ $level->id }}" {{ old('level_id') == $level->id ? 'selected' : '' }}>
                                                            {{ $level->name }} ({{ $level->cycle }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('level_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <!-- Sélection de la classe -->
                                            <div class="mb-3">
                                                <label for="class_id" class="form-label">Classe <span class="text-danger">*</span></label>
                                                <select class="form-select @error('class_id') is-invalid @enderror" 
                                                        id="class_id" name="class_id" required disabled>
                                                    <option value="">Sélectionner d'abord un niveau...</option>
                                                </select>
                                                @error('class_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                            <!-- Sélection de l'élève -->
                                            <div class="mb-3">
                                                <label for="student_id" class="form-label">Élève <span class="text-danger">*</span></label>
                                                <select class="form-select @error('student_id') is-invalid @enderror" 
                                                        id="student_id" name="student_id" required disabled>
                                                    <option value="">Sélectionner d'abord une classe...</option>
                                                </select>
                                                @error('student_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            
                                                                        <!-- Résumé de l'élève sélectionné -->
                                            <div id="selectedStudentInfo" style="display: none;">
                                <div class="card bg-light border-0 shadow-sm mt-3">
                                    <div class="card-header bg-light border-0 py-2">
                                        <h6 class="mb-0 text-dark">
                                            <i class="bi bi-person-check text-success me-2"></i>
                                            Résumé de l'élève sélectionné
                                        </h6>
                                                            </div>
                                    <div class="card-body bg-light py-3">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="bi bi-person-fill text-success fs-4"></i>
                                                        </div>
                                            </div>
                                            <div class="col">
                                                            <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <h5 class="mb-1 text-dark fw-bold" id="studentFullName">--</h5>
                                                        <small class="text-muted">Élève sélectionné pour la notation</small>
                                                                </div>
                                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-building text-primary me-2"></i>
                                                            <div>
                                                                <small class="text-muted d-block">Classe</small>
                                                                <strong class="text-dark" id="studentClassName">--</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-layers text-warning me-2"></i>
                                                            <div>
                                                                <small class="text-muted d-block">Niveau</small>
                                                                <strong class="text-dark" id="studentLevel">--</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-diagram-3 text-info me-2"></i>
                                                            <div>
                                                                <small class="text-muted d-block">Cycle</small>
                                                                <strong class="text-dark" id="studentCycle">--</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="button" class="btn btn-primary btn-lg" onclick="nextStep()" id="nextStep1" disabled>
                                                    <i class="bi bi-arrow-right me-2"></i>
                                                    Continuer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 2: Sélection de la matière -->
                        <div class="step-container" id="step2" style="display: none;">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="text-center mb-4">
                                        <div class="step-indicator">
                                            <span class="step-number">1</span>
                                            <span class="step-label">Sélection de l'élève</span>
                                        </div>
                                        <i class="bi bi-arrow-down text-muted mx-2"></i>
                                        <div class="step-indicator active">
                                            <span class="step-number">2</span>
                                            <span class="step-label">Sélection de la matière</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Informations de l'élève -->
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">
                                                <i class="bi bi-person-circle me-2"></i>
                                                Informations de l'élève
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4 text-center">
                                                    <div class="bg-primary bg-opacity-10 rounded-circle p-4 mx-auto" style="width: 80px; height: 80px;">
                                                        <i class="bi bi-person text-primary fs-1"></i>
                                                    </div>
                                                    <h5 class="mt-3 mb-1" id="studentName">--</h5>
                                                    <p class="text-muted mb-0" id="studentClass">--</p>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2">
                                                            <strong>Nom complet:</strong>
                                                            <span id="studentFullName2">--</span>
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <strong>Classe:</strong>
                                                            <span id="studentClassName2">--</span>
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <strong>Niveau:</strong>
                                                            <span id="studentLevel2">--</span>
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <strong>Cycle:</strong>
                                                            <span id="studentCycle2">--</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Sélection de la matière -->
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-primary mb-3">
                                                <i class="bi bi-book me-2"></i>
                                                Choisir la matière
                                            </h6>
                                            
                                            <!-- Section ajout de matières -->
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="form-label mb-0">Matières et notes <span class="text-danger">*</span></label>
                                                    <button type="button" class="btn btn-success btn-sm" id="addSubjectBtn">
                                                        <i class="bi bi-plus-circle me-1"></i>
                                                        Ajouter une matière
                                                    </button>
                                                </div>
                                                
                                                <!-- Formulaire d'ajout de matière -->
                                                <div class="card border-primary" id="addSubjectForm" style="display: none;">
                                                    <div class="card-header bg-primary text-white py-2">
                                                        <h6 class="mb-0">
                                                            <i class="bi bi-book me-2"></i>
                                                            Ajouter une matière
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <!-- Sélection de la matière et affichage du professeur -->
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <label for="temp_subject_id" class="form-label fw-bold">Matière <span class="text-danger">*</span></label>
                                                                <select class="form-select form-select-lg" id="temp_subject_id">
                                                                    <option value="">Sélectionner une matière...</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Professeur</label>
                                                                <div class="form-control-plaintext bg-light border rounded p-3" id="subject_teacher_display">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-person-circle text-primary me-2 fs-4"></i>
                                                                        <span class="text-muted">Sélectionnez une matière pour voir le professeur</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Note sur 20 (fixe) -->
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <label for="temp_score" class="form-label fw-bold">Note obtenue <span class="text-danger">*</span></label>
                                                                <div class="input-group input-group-lg">
                                                                    <input type="number" class="form-control" id="temp_score" step="0.01" min="0" max="20" placeholder="0.00">
                                                                    <span class="input-group-text bg-primary text-white fw-bold">/ 20</span>
                                                                </div>
                                                                <small class="text-muted">Note finale sur 20 (ex: 15.5)</small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Pourcentage</label>
                                                                <div class="form-control-plaintext bg-light border rounded p-3" id="score_percentage_display">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-percent text-success me-2 fs-4"></i>
                                                                        <span class="text-muted">Calculé automatiquement</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Observations -->
                                                        <div class="mb-3">
                                                            <label for="temp_notes" class="form-label fw-bold">
                                                                <i class="bi bi-chat-text me-2"></i>
                                                                Observations de l'enseignant (optionnel)
                                                            </label>
                                                            <textarea class="form-control" id="temp_notes" rows="3" placeholder="Commentaires et observations de l'enseignant sur la performance trimestrielle de l'élève..."></textarea>
                                                        </div>
                                                        
                                                        <!-- Boutons d'action -->
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-success btn-lg" id="saveSubjectBtn">
                                                                <i class="bi bi-check-circle me-2"></i>
                                                                Ajouter cette matière
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-lg" id="cancelSubjectBtn">
                                                                <i class="bi bi-x-circle me-2"></i>
                                                                Annuler
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Liste des matières ajoutées -->
                                                <div id="addedSubjectsList">
                                                    <!-- Les matières ajoutées apparaîtront ici -->
                                                </div>
                                                
                                                <!-- Message si aucune matière -->
                                                <div id="noSubjectsMessage" class="alert alert-warning">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    Aucune matière ajoutée. Cliquez sur "Ajouter une matière" pour commencer à constituer le bulletin.
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                                                    <i class="bi bi-arrow-left me-2"></i>
                                                    Retour
                                                </button>
                                                <button type="button" class="btn btn-primary" onclick="nextStep()" id="nextStep2" disabled>
                                                    <i class="bi bi-arrow-right me-2"></i>
                                                    Continuer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 3: Récapitulatif et enregistrement -->
                        <div class="step-container" id="step3" style="display: none;">
                            <div class="row">
                                <div class="col-md-10 mx-auto">
                                    <div class="text-center mb-4">
                                        <div class="step-indicator">
                                            <span class="step-number">1</span>
                                            <span class="step-label">Sélection de l'élève</span>
                                        </div>
                                        <i class="bi bi-arrow-down text-muted mx-2"></i>
                                        <div class="step-indicator">
                                            <span class="step-number">2</span>
                                            <span class="step-label">Sélection des matières</span>
                                        </div>
                                        <i class="bi bi-arrow-down text-muted mx-2"></i>
                                        <div class="step-indicator active">
                                            <span class="step-number">3</span>
                                            <span class="step-label">Récapitulatif et enregistrement</span>
                                        </div>
                                    </div>
                                    
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-success mb-3">
                                                <i class="bi bi-check-circle me-2"></i>
                                                Récapitulatif des notes à enregistrer
                                            </h6>
                                            
                                            <!-- Récapitulatif final -->
                                            <div id="finalSummary">
                                                <!-- Le contenu sera généré par JavaScript -->
                                                </div>
                                                
                                            <!-- Champs communs pour toutes les notes -->
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="final_term" class="form-label">Trimestre <span class="text-danger">*</span></label>
                                                    <select class="form-select" id="final_term" name="term" required>
                                                        <option value="">Sélectionner...</option>
                                                        <option value="1er trimestre">1er trimestre</option>
                                                        <option value="2ème trimestre">2ème trimestre</option>
                                                        <option value="3ème trimestre">3ème trimestre</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            
                                            
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-secondary" onclick="previousStep()">
                                                    <i class="bi bi-arrow-left me-2"></i>
                                                    Retour
                                                </button>
                                                <button type="button" class="btn btn-success" onclick="submitAllGrades()" id="submitBtn">
                                                    <i class="bi bi-check-circle me-2"></i>
                                                    Enregistrer toutes les notes
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles pour les étapes -->
<style>
.step-indicator {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    margin: 0 10px;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 5px;
}

.step-indicator.active .step-number {
    background-color: #0d6efd;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.step-indicator.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

.step-container {
    transition: all 0.3s ease;
}

/* Animation pour les transitions d'étapes */
.step-container {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Styles pour le formulaire de matière */
.form-select-lg {
    font-size: 1.1rem;
    padding: 0.75rem 1rem;
}

.input-group-lg .form-control {
    font-size: 1.1rem;
    padding: 0.75rem 1rem;
}

.input-group-lg .input-group-text {
    font-size: 1.1rem;
    padding: 0.75rem 1rem;
}

/* Animation pour les changements de pourcentage */
#score_percentage_display {
    transition: all 0.3s ease;
}

/* Style pour les boutons d'action */
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}
</style>
@endsection

@push('scripts')
<script>
let currentStep = 1;
let selectedStudent = null;

// Empêcher la soumission du formulaire et initialiser selectedStudent
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('gradeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Initialiser selectedStudent si un élève est pré-sélectionné
    const preselectedStudentEl = document.getElementById('preselected_student');
    if (preselectedStudentEl) {
        try {
            const studentData = JSON.parse(preselectedStudentEl.dataset.student);
            const enrollmentData = JSON.parse(preselectedStudentEl.dataset.enrollment);
            
            selectedStudent = {
                id: studentData.id,
                first_name: studentData.first_name,
                last_name: studentData.last_name,
                school_class: {
                    id: enrollmentData.school_class.id,
                    name: enrollmentData.school_class.name,
                    level: {
                        name: enrollmentData.school_class.level.name,
                        cycle: enrollmentData.school_class.level.cycle
                    }
                },
                school_class_id: enrollmentData.class_id
            };
            
            // Pré-remplir les sélecteurs
            if (selectedStudent.school_class && selectedStudent.school_class.level) {
                const levelSelect = document.getElementById('level_id');
                const levelOptions = Array.from(levelSelect.options);
                const levelOption = levelOptions.find(option => 
                    option.text.includes(selectedStudent.school_class.level.name)
                );
                if (levelOption) {
                    levelOption.selected = true;
                    levelSelect.dispatchEvent(new Event('change'));
                }
            }
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de selectedStudent:', error);
        }
    }
});

// Fonction pour charger les classes d'un niveau
function loadClassesForLevel() {
    const levelId = document.getElementById('level_id').value;
    const classSelect = document.getElementById('class_id');
    const studentSelect = document.getElementById('student_id');
    const nextStepBtn = document.getElementById('nextStep1');
    
    console.log('Chargement des classes pour le niveau:', levelId);
    
    // Réinitialiser les sélections suivantes
    studentSelect.innerHTML = '<option value="">Sélectionner d\'abord une classe...</option>';
    studentSelect.disabled = true;
    nextStepBtn.disabled = true;
    document.getElementById('selectedStudentInfo').style.display = 'none';
    
    if (!levelId) {
        classSelect.innerHTML = '<option value="">Sélectionner une classe...</option>';
        classSelect.disabled = true;
        return;
    }
    
    // Afficher l'indicateur de chargement
    classSelect.innerHTML = '<option value="">🔄 Chargement des classes...</option>';
    classSelect.disabled = true;
    
    // Charger les classes du niveau sélectionné
    fetch(`/api/levels/${levelId}/classes`)
        .then(response => {
            console.log('Réponse API classes:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues pour les classes:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Réinitialiser le select avec le placeholder
            classSelect.innerHTML = '<option value="">Sélectionner une classe...</option>';
            
            data.forEach(classItem => {
                const option = document.createElement('option');
                option.value = classItem.id;
                option.textContent = classItem.name;
                classSelect.appendChild(option);
            });
            
            classSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des classes:', error);
            classSelect.innerHTML = '<option value="">❌ Erreur de chargement</option>';
            classSelect.disabled = true;
            alert('Erreur lors du chargement des classes: ' + error.message);
        });
}

// Fonction pour charger les élèves d'une classe
function loadStudentsForClass() {
    const classId = document.getElementById('class_id').value;
    const studentSelect = document.getElementById('student_id');
    const nextStepBtn = document.getElementById('nextStep1');
    const hiddenClassId = document.getElementById('hidden_class_id');
    
    console.log('Chargement des élèves pour la classe:', classId);
    
    // Mettre à jour le champ caché
    hiddenClassId.value = classId;
    
    // Réinitialiser l'état
    nextStepBtn.disabled = true;
    document.getElementById('selectedStudentInfo').style.display = 'none';
    
    if (!classId) {
        studentSelect.innerHTML = '<option value="">Sélectionner un élève...</option>';
        studentSelect.disabled = true;
        return;
    }
    
    // Afficher l'indicateur de chargement
    studentSelect.innerHTML = '<option value="">🔄 Chargement des élèves...</option>';
    studentSelect.disabled = true;
    
    // Charger les élèves de la classe sélectionnée
    fetch(`/api/classes/${classId}/students`)
        .then(response => {
            console.log('Réponse API élèves:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues pour les élèves:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Réinitialiser le select avec le placeholder
            studentSelect.innerHTML = '<option value="">Sélectionner un élève...</option>';
            
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.first_name + ' ' + student.last_name;
                studentSelect.appendChild(option);
            });
            
            studentSelect.disabled = false;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des élèves:', error);
            studentSelect.innerHTML = '<option value="">❌ Erreur de chargement</option>';
            studentSelect.disabled = true;
            alert('Erreur lors du chargement des élèves: ' + error.message);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, test des routes API...');
    
    // Test de la route API
    fetch('/api/test-api')
        .then(response => response.json())
        .then(data => {
            console.log('Test API réussi:', data);
        })
        .catch(error => {
            console.error('Erreur test API:', error);
        });
    
    // Test des niveaux
    fetch('/api/test-levels')
        .then(response => response.json())
        .then(data => {
            console.log('Niveaux disponibles:', data);
        })
        .catch(error => {
            console.error('Erreur test niveaux:', error);
        });
    
    // Écouter les changements pour la sélection hiérarchique
    document.getElementById('level_id').addEventListener('change', loadClassesForLevel);
    document.getElementById('class_id').addEventListener('change', loadStudentsForClass);
    document.getElementById('student_id').addEventListener('change', loadStudentInfo);
    
    // Gestion des matières multiples
    document.getElementById('addSubjectBtn').addEventListener('click', showAddSubjectForm);
    document.getElementById('saveSubjectBtn').addEventListener('click', addSubject);
    document.getElementById('cancelSubjectBtn').addEventListener('click', hideAddSubjectForm);
    
    // Écouteur pour le calcul automatique du pourcentage
    document.addEventListener('input', function(e) {
        if (e.target.id === 'temp_score') {
            updateScorePercentage();
        }
    });
});

function loadStudentInfo() {
    const studentId = document.getElementById('student_id').value;
    const nextStepBtn = document.getElementById('nextStep1');
    const studentInfoDiv = document.getElementById('selectedStudentInfo');
    
    if (!studentId) {
        studentInfoDiv.style.display = 'none';
        nextStepBtn.disabled = true;
        return;
    }
    
    // Afficher l'indicateur de chargement
    studentInfoDiv.innerHTML = `
        <div class="card bg-light border-0 shadow-sm mt-3">
            <div class="card-body bg-light py-4 text-center">
                <div class="spinner-border text-primary me-3" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <span class="text-muted">🔄 Chargement des informations de l'élève...</span>
            </div>
        </div>
    `;
    studentInfoDiv.style.display = 'block';
    nextStepBtn.disabled = true;
    
    // Charger les informations de l'élève via API
    fetch(`/api/students/${studentId}/info`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.message || data.error);
            }
            
            selectedStudent = data;
            
            // Remplacer l'indicateur de chargement par le récapitulatif complet
            studentInfoDiv.innerHTML = `
                <div class="card bg-light border-0 shadow-sm mt-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-person-check text-success me-2"></i>
                            Résumé de l'élève sélectionné
                        </h6>
                    </div>
                    <div class="card-body bg-light py-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-person-fill text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <h5 class="mb-1 text-dark fw-bold" id="studentFullName">${data.first_name} ${data.last_name}</h5>
                                        <small class="text-muted">Élève sélectionné pour la notation</small>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-building text-primary me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Classe</small>
                                                <strong class="text-dark" id="studentClassName">${data.school_class ? data.school_class.name : 'N/A'}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-layers text-warning me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Niveau</small>
                                                <strong class="text-dark" id="studentLevel">${data.school_class && data.school_class.level ? data.school_class.level.name : 'N/A'}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-diagram-3 text-info me-2"></i>
                                            <div>
                                                <small class="text-muted d-block">Cycle</small>
                                                <strong class="text-dark" id="studentCycle">${data.school_class && data.school_class.level ? data.school_class.level.cycle : 'N/A'}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Afficher les informations et activer le bouton seulement maintenant
            studentInfoDiv.style.display = 'block';
            nextStepBtn.disabled = false;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des informations de l\'élève:', error);
            
            // Afficher un message d'erreur dans le container
            studentInfoDiv.innerHTML = `
                <div class="card bg-light border-0 shadow-sm mt-3">
                    <div class="card-body bg-light py-4 text-center">
                        <div class="text-danger mb-3">
                            <i class="bi bi-exclamation-triangle fs-2"></i>
                        </div>
                        <h6 class="text-danger mb-2">Erreur de chargement</h6>
                        <p class="text-muted mb-0">
                            Impossible de charger les informations de l'élève.<br>
                            <small>${error.message}</small>
                        </p>
                    </div>
                </div>
            `;
            
            studentInfoDiv.style.display = 'block';
            nextStepBtn.disabled = true;
        });
}

// Variables globales pour la gestion des matières
let addedSubjects = [];
let availableSubjects = [];

function loadAvailableSubjects() {
    if (!selectedStudent || !selectedStudent.school_class || !selectedStudent.school_class.id) return;
    
    const tempSubjectSelect = document.getElementById('temp_subject_id');
    
    // Charger les matières disponibles pour cette classe
    fetch(`/api/classes/${selectedStudent.school_class.id}/subjects`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            availableSubjects = data;
            updateSubjectSelect();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des matières:', error);
            alert('Erreur lors du chargement des matières.');
        });
}

function updateSubjectSelect() {
    const tempSubjectSelect = document.getElementById('temp_subject_id');
    if (!tempSubjectSelect) return;
    
    // Sauvegarder la valeur actuelle
    const currentValue = tempSubjectSelect.value;
    
    // Réinitialiser le select
    tempSubjectSelect.innerHTML = '<option value="">Sélectionner une matière...</option>';
    
    // Ajouter les matières non encore sélectionnées
    availableSubjects.forEach(subject => {
        if (!addedSubjects.find(added => added.subject_id === subject.id)) {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.name;
            tempSubjectSelect.appendChild(option);
        }
    });
    
    tempSubjectSelect.disabled = false;
    
    // Restaurer la valeur si elle était valide
    if (currentValue && availableSubjects.find(s => s.id == currentValue)) {
        tempSubjectSelect.value = currentValue;
        // Déclencher l'événement change pour charger le professeur
        loadSubjectTeacher();
    }
    
    // Supprimer l'ancien écouteur d'événement s'il existe
    tempSubjectSelect.removeEventListener('change', loadSubjectTeacher);
    
    // Ajouter l'écouteur d'événement pour afficher le professeur
    tempSubjectSelect.addEventListener('change', loadSubjectTeacher);
}

function loadSubjectTeacher() {
    const subjectId = document.getElementById('temp_subject_id').value;
    const teacherDisplay = document.getElementById('subject_teacher_display');
    
    if (!subjectId) {
        teacherDisplay.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle text-primary me-2 fs-4"></i>
                <span class="text-muted">Sélectionnez une matière pour voir le professeur</span>
            </div>
        `;
        return;
    }
    
    // Récupérer l'ID de la classe depuis le champ caché
    const classId = document.getElementById('hidden_class_id').value;
    
    if (!classId) {
        teacherDisplay.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle text-danger me-2 fs-4"></i>
                <span class="text-danger">Erreur: Classe non définie</span>
            </div>
        `;
        return;
    }
    
    // Afficher un indicateur de chargement
    teacherDisplay.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <span class="text-muted">Chargement du professeur...</span>
        </div>
    `;
    
    console.log('Chargement du professeur pour subject_id:', subjectId, 'et class_id:', classId);
    
    // Charger le professeur pour cette matière et cette classe
    fetch(`/api/subjects/${subjectId}/teacher/${classId}`)
        .then(response => {
            console.log('Réponse API professeur:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données professeur reçues:', data);
            if (data.error) {
                teacherDisplay.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle text-warning me-2 fs-4"></i>
                        <span class="text-warning">Aucun professeur assigné</span>
                    </div>
                `;
            } else {
                teacherDisplay.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check-circle text-success me-2 fs-4"></i>
                        <span class="text-success fw-bold">${data.first_name} ${data.last_name}</span>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement du professeur:', error);
            teacherDisplay.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle text-danger me-2 fs-4"></i>
                    <span class="text-danger">Erreur de chargement</span>
                </div>
            `;
        });
}

// Fonction pour calculer et afficher le pourcentage
function updateScorePercentage() {
    const score = parseFloat(document.getElementById('temp_score').value) || 0;
    const percentageDisplay = document.getElementById('score_percentage_display');
    
    if (score === 0) {
        percentageDisplay.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-percent text-success me-2 fs-4"></i>
                <span class="text-muted">Calculé automatiquement</span>
            </div>
        `;
        return;
    }
    
    const percentage = (score / 20) * 100;
    const percentageClass = percentage >= 80 ? 'text-success' : 
                           percentage >= 60 ? 'text-warning' : 'text-danger';
    
    percentageDisplay.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-percent text-success me-2 fs-4"></i>
            <span class="${percentageClass} fw-bold">${percentage.toFixed(1)}%</span>
        </div>
    `;
}

// Fonctions pour la gestion des matières
function showAddSubjectForm() {
    document.getElementById('addSubjectForm').style.display = 'block';
    document.getElementById('addSubjectBtn').style.display = 'none';
    
    // Charger les matières si pas encore fait
    if (availableSubjects.length === 0) {
        loadAvailableSubjects();
    } else {
        updateSubjectSelect();
    }
}

function hideAddSubjectForm() {
    document.getElementById('addSubjectForm').style.display = 'none';
    document.getElementById('addSubjectBtn').style.display = 'inline-block';
    
    // Réinitialiser le formulaire
    document.getElementById('temp_subject_id').value = '';
    document.getElementById('temp_score').value = '';
    document.getElementById('temp_notes').value = '';
    
    // Réinitialiser les affichages
    document.getElementById('subject_teacher_display').innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-person-circle text-primary me-2 fs-4"></i>
            <span class="text-muted">Sélectionnez une matière pour voir le professeur</span>
        </div>
    `;
    
    document.getElementById('score_percentage_display').innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-percent text-success me-2 fs-4"></i>
            <span class="text-muted">Calculé automatiquement</span>
        </div>
    `;
}

function addSubject() {
    const subjectId = document.getElementById('temp_subject_id').value;
    const score = document.getElementById('temp_score').value;
    const notes = document.getElementById('temp_notes').value;
    
    // Validation
    if (!subjectId || !score) {
        alert('Veuillez remplir tous les champs obligatoires.');
        return;
    }
    
    const scoreValue = parseFloat(score);
    if (scoreValue < 0 || scoreValue > 20) {
        alert('La note doit être comprise entre 0 et 20.');
        return;
    }
    
    const subject = availableSubjects.find(s => s.id == subjectId);
    if (!subject) {
        alert('Matière non trouvée.');
        return;
    }
    
    // Ajouter la matière à la liste (note fixe sur 20)
    const newSubject = {
        subject_id: subjectId,
        subject_name: subject.name,
        score: scoreValue,
        max_score: 20, // Toujours sur 20
        notes: notes,
        percentage: ((scoreValue / 20) * 100).toFixed(2)
    };
    
    addedSubjects.push(newSubject);
    updateAddedSubjectsList();
    updateSubjectSelect();
    hideAddSubjectForm();
    updateNextStepButton();
}

function removeSubject(index) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
        addedSubjects.splice(index, 1);
        updateAddedSubjectsList();
        updateSubjectSelect();
        updateNextStepButton();
    }
}

function updateAddedSubjectsList() {
    // Vérifier que le document est chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateAddedSubjectsList);
        return;
    }
    
    // Vérifier si l'étape 2 est visible
    const step2 = document.getElementById('step2');
    if (!step2 || step2.style.display === 'none') {
        // Si l'étape 2 n'est pas visible, ne rien faire pour le moment
        console.log('Étape 2 non visible, updateAddedSubjectsList ignoré');
        return;
    }
    
    // Fonction pour essayer de trouver les éléments avec plusieurs tentatives
    function tryUpdateDisplay(attempts = 0) {
        const maxAttempts = 10;
        
        const container = document.getElementById('addedSubjectsList');
        let noSubjectsMessage = document.getElementById('noSubjectsMessage');
        
        // Si le container existe mais pas le message, créons-le
        if (container && !noSubjectsMessage) {
            console.log('Container trouvé mais message manquant, création du message...');
            noSubjectsMessage = document.createElement('div');
            noSubjectsMessage.id = 'noSubjectsMessage';
            noSubjectsMessage.className = 'alert alert-warning';
            noSubjectsMessage.innerHTML = '<i class="bi bi-info-circle me-2"></i>Aucune matière ajoutée. Cliquez sur "Ajouter une matière" pour commencer à constituer le bulletin.';
            container.parentNode.insertBefore(noSubjectsMessage, container.nextSibling);
        }
        
        if (!container || !noSubjectsMessage) {
            if (attempts < maxAttempts) {
                console.log(`Tentative ${attempts + 1}/${maxAttempts} - Éléments non trouvés, nouvelle tentative...`);
                setTimeout(() => tryUpdateDisplay(attempts + 1), 200);
            } else {
                console.error('Éléments toujours non trouvés après', maxAttempts, 'tentatives');
                console.error('addedSubjectsList trouvé:', !!container);
                console.error('noSubjectsMessage trouvé:', !!noSubjectsMessage);
                console.error('step2 affiché:', step2.style.display !== 'none');
            }
            return;
        }
        
        // Si tous les éléments sont trouvés, procéder à la mise à jour
        console.log('Éléments trouvés, mise à jour en cours...');
        updateSubjectsDisplay(container, noSubjectsMessage);
    }
    
    // Commencer les tentatives avec un délai initial plus long
    setTimeout(() => tryUpdateDisplay(), 300);
}

function updateSubjectsDisplay(container, noSubjectsMessage) {
    if (addedSubjects.length === 0) {
        container.innerHTML = '';
        noSubjectsMessage.style.display = 'block';
        return;
    }
    
    noSubjectsMessage.style.display = 'none';
    
    let html = '';
    addedSubjects.forEach((subject, index) => {
        const gradeClass = subject.percentage >= 80 ? 'text-success' : 
                          subject.percentage >= 60 ? 'text-warning' : 'text-danger';
        
        html += `
            <div class="card border-success mb-3">
                <div class="card-header bg-success bg-opacity-10 py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-success">
                            <i class="bi bi-book-fill me-2"></i>
                            ${subject.subject_name}
                        </h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeSubject(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <small class="text-muted">Note trimestrielle</small>
                            <div class="fw-bold ${gradeClass}">${subject.score}/${subject.max_score} (${subject.percentage}%)</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Mention</small>
                            <div class="fw-bold ${gradeClass}">
                                ${subject.percentage >= 80 ? 'Très bien' : 
                                  subject.percentage >= 60 ? 'Bien' : 
                                  subject.percentage >= 50 ? 'Assez bien' : 'Insuffisant'}
                            </div>
                        </div>
                    </div>
                    ${subject.notes ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Observations de l'enseignant:</small>
                                <div class="text-muted fst-italic">${subject.notes}</div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateNextStepButton() {
    const nextStepBtn = document.getElementById('nextStep2');
    nextStepBtn.disabled = addedSubjects.length === 0;
}

function loadTeacherInfo() {
    const subjectId = document.getElementById('subject_id').value;
    const nextStepBtn = document.getElementById('nextStep2');
    const teacherInfoDiv = document.getElementById('teacherInfo');
    
    if (!subjectId || !selectedStudent || !selectedStudent.school_class_id) {
        teacherInfoDiv.style.display = 'none';
        nextStepBtn.disabled = true;
        return;
    }
    
    // Charger les informations du professeur
    fetch(`/api/subjects/${subjectId}/teacher-for-class/${selectedStudent.school_class_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.teacher) {
                document.getElementById('teacherName').textContent = data.teacher.first_name + ' ' + data.teacher.last_name;
                document.getElementById('teacherSpecialization').textContent = data.teacher.specialization || 'Enseignant';
                document.getElementById('teacher_id').value = data.teacher.id;
                teacherInfoDiv.style.display = 'block';
                nextStepBtn.disabled = false;
            } else {
                document.getElementById('teacher_id').value = '';
                teacherInfoDiv.style.display = 'none';
                nextStepBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des informations du professeur:', error);
            alert('Erreur lors du chargement des informations du professeur.');
        });
}

function nextStep() {
    if (currentStep === 1) {
        const studentSelect = document.getElementById('student_id');
        if (!studentSelect) {
            alert('Erreur: Élément de sélection d\'élève non trouvé');
            return;
        }
        
        const studentId = studentSelect.value;
        if (!studentId) {
            alert('Veuillez sélectionner un élève');
            return;
        }
        
        currentStep = 2;
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        
        // Charger les matières disponibles pour cette classe
        loadAvailableSubjects();
        
        // Mettre à jour l'affichage des matières ajoutées
        updateAddedSubjectsList();
        
        // Initialiser le bouton de l'étape 2
        updateNextStepButton();
        
        // Mettre à jour les informations dans l'étape 2
        const studentNameEl = document.getElementById('studentName');
        const studentClassEl = document.getElementById('studentClass');
        const studentFullName2El = document.getElementById('studentFullName2');
        const studentClassName2El = document.getElementById('studentClassName2');
        const studentLevel2El = document.getElementById('studentLevel2');
        const studentCycle2El = document.getElementById('studentCycle2');
        
        if (studentNameEl) {
            studentNameEl.textContent = selectedStudent.first_name + ' ' + selectedStudent.last_name;
        }
        if (studentClassEl) {
            studentClassEl.textContent = selectedStudent.school_class ? selectedStudent.school_class.name : 'N/A';
        }
        if (studentFullName2El) {
            studentFullName2El.textContent = selectedStudent.first_name + ' ' + selectedStudent.last_name;
        }
        if (studentClassName2El) {
            studentClassName2El.textContent = selectedStudent.school_class ? selectedStudent.school_class.name : 'N/A';
        }
        if (studentLevel2El) {
            studentLevel2El.textContent = selectedStudent.school_class && selectedStudent.school_class.level ? selectedStudent.school_class.level.name : 'N/A';
        }
        if (studentCycle2El) {
            studentCycle2El.textContent = selectedStudent.school_class && selectedStudent.school_class.level ? selectedStudent.school_class.level.cycle : 'N/A';
        }
        
        // Charger les matières disponibles
        loadAvailableSubjects();
    } else if (currentStep === 2) {
        // Vérifier qu'au moins une matière a été ajoutée
        if (addedSubjects.length === 0) {
            alert('Veuillez ajouter au moins une matière avec sa note');
            return;
        }
        
        currentStep = 3;
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';
        
        // Générer le récapitulatif final
        generateFinalSummary();
    }
}

function generateFinalSummary() {
    // Générer le récapitulatif final des notes à enregistrer
    const summaryContainer = document.getElementById('finalSummary');
    if (!summaryContainer) {
        console.error('Container de récapitulatif final non trouvé');
        return;
    }
    
    let html = '<div class="alert alert-info mb-4">';
    html += '<h5><i class="bi bi-info-circle me-2"></i>Récapitulatif des notes à enregistrer</h5>';
    html += `<p><strong>Élève:</strong> ${selectedStudent.first_name} ${selectedStudent.last_name}</p>`;
    html += `<p><strong>Classe:</strong> ${selectedStudent.school_class ? selectedStudent.school_class.name : 'N/A'}</p>`;
    html += '</div>';
    
    html += '<div class="table-responsive">';
    html += '<table class="table table-striped">';
    html += '<thead class="table-dark">';
    html += '<tr><th>Matière</th><th>Note</th><th>Note sur 20</th><th>Pourcentage</th><th>Observations</th></tr>';
    html += '</thead><tbody>';
    
    addedSubjects.forEach(subject => {
        const gradeClass = subject.percentage >= 80 ? 'text-success' : 
                          subject.percentage >= 60 ? 'text-warning' : 'text-danger';
        const gradeOn20 = ((subject.score / subject.max_score) * 20).toFixed(2);
        html += `<tr>
            <td>${subject.subject_name}</td>
            <td>${subject.score}/${subject.max_score}</td>
            <td><span class="${gradeClass}">${gradeOn20}/20</span></td>
            <td><span class="${gradeClass}">${subject.percentage}%</span></td>
            <td>${subject.notes || '-'}</td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    summaryContainer.innerHTML = html;
}

function submitAllGrades() {
    // Vérification des champs communs
    const term = document.getElementById('final_term').value;
    
    if (!term) {
        alert('Veuillez sélectionner un trimestre');
        return;
    }
    
    if (addedSubjects.length === 0) {
        alert('Aucune matière à enregistrer');
        return;
    }
    
    // Afficher le spinner
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enregistrement...';
    submitBtn.disabled = true;
    
    // Préparer les données pour l'envoi
    const gradesData = {
        student_id: selectedStudent.id,
        term: term,
        grades: addedSubjects.map(subject => ({
            subject_id: subject.subject_id,
            score: subject.score,
            max_score: subject.max_score,
            comments: subject.notes || ''
        }))
    };
    
    // Vérifier que le token CSRF est disponible
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('Token CSRF manquant. Veuillez recharger la page.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        return;
    }
    
    console.log('Données à envoyer:', gradesData);
    
    // Test simple d'abord
    console.log('URL cible:', '{!! route("grades.store") !!}');
    console.log('Token CSRF:', csrfToken.getAttribute('content'));
    console.log('Nombre de matières:', gradesData.grades.length);
    
    // Préparer les données FormData (au lieu de JSON)
    const formData = new FormData();
    formData.append('_token', csrfToken.getAttribute('content'));
    formData.append('student_id', gradesData.student_id);
    formData.append('term', gradesData.term);
    
    // Ajouter chaque note
    gradesData.grades.forEach((grade, index) => {
        formData.append(`grades[${index}][subject_id]`, grade.subject_id);
        formData.append(`grades[${index}][score]`, grade.score);
        formData.append(`grades[${index}][max_score]`, grade.max_score);
        formData.append(`grades[${index}][comments]`, grade.comments || '');
    });
    
    // Envoyer les données
    fetch('{!! route("grades.store") !!}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
            // Ne pas définir Content-Type, laissons le navigateur le faire pour FormData
        },
        body: formData
    })
    .then(response => {
        console.log('Réponse reçue:', response.status);
        if (!response.ok) {
            if (response.status === 419) {
                throw new Error('Session expirée. Veuillez recharger la page et réessayer.');
            } else if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Erreur de validation: ' + (data.message || JSON.stringify(data.errors || data)));
                });
            }
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
            let message = data.message || 'Notes enregistrées avec succès !';
            if (data.errors && data.errors.length > 0) {
                message += '\n\nErreurs détectées:\n' + data.errors.join('\n');
            }
            alert(message);
            window.location.href = '{!! route("grades.index") !!}';
        } else {
            throw new Error(data.error || 'Erreur lors de l\'enregistrement');
        }
    })
    .catch(error => {
        console.error('Erreur complète:', error);
        alert('Erreur lors de l\'enregistrement des notes :\n' + error.message);
        
        // Restaurer le bouton
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function previousStep() {
    if (currentStep === 2) {
        currentStep = 1;
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
    } else if (currentStep === 3) {
        currentStep = 2;
        document.getElementById('step3').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
    }
}


</script>
@endpush 
