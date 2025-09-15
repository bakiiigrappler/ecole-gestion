@extends('layouts.app')

@section('title', 'Création d\'emploi du temps')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-11">
            <!-- En-tête de la page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 mb-1 text-primary">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Création d'emploi du temps
                    </h2>
                    <p class="text-muted mb-0">Formulaire complet en plusieurs étapes</p>
                </div>
                <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-1"></i>
                    Liste des emplois du temps
                </a>
            </div>

            <!-- Indicateur de progression -->
            <div class="progress mb-4" style="height: 8px;">
                <div class="progress-bar" id="progressBar" role="progressbar" style="width: 25%"></div>
            </div>

            <!-- Formulaire multi-étapes -->
            <form id="scheduleForm" method="POST" action="{{ route('schedules.store') }}">
                @csrf
                
                <!-- Étape 1: Sélection du cycle et de la classe -->
                <div id="step1" class="step-section">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-layer-group me-2"></i>
                                Étape 1 : Sélection du cycle et de la classe
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="cycle" class="form-label fw-semibold">
                                        <i class="fas fa-layer-group me-2 text-primary"></i>
                                        Cycle d'enseignement *
                                    </label>
                                    <select id="cycle" name="cycle" class="form-select form-select-lg" required>
                                        <option value="">-- Choisir un cycle --</option>
                                        <option value="preprimaire">🎨 Préprimaire</option>
                                        <option value="primaire">📚 Primaire</option>
                                        <option value="college">🏫 Collège</option>
                                        <option value="lycee">🎓 Lycée</option>
                                    </select>
                                    <div class="form-text">Sélectionnez le cycle d'enseignement pour filtrer les classes</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="academic_year_id" class="form-label fw-semibold">
                                        <i class="fas fa-calendar me-2 text-primary"></i>
                                        Année académique
                                    </label>
                                    <input type="hidden" id="academic_year_id" name="academic_year_id" 
                                           value="{{ $currentAcademicYear->id }}">
                                    <input type="text" class="form-control form-control-plaintext form-control-lg" 
                                           value="{{ $currentAcademicYear->name }}" readonly>
                                    <div class="form-text">Année académique en cours (non modifiable)</div>
                                </div>
                            </div>

                            <div id="classSelectionSection" class="d-none">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label for="class_id" class="form-label fw-semibold">
                                            <i class="fas fa-users me-2 text-primary"></i>
                                            Classe *
                                        </label>
                                        <select id="class_id" name="class_id" class="form-select form-select-lg" required>
                                            <option value="">-- Choisir une classe --</option>
                                        </select>
                                        <div class="form-text">Sélectionnez la classe pour laquelle créer l'emploi du temps</div>
                                    </div>
                                </div>

                                <div id="classInfoSection" class="d-none">
                                    <div class="card bg-light border-0 mb-4">
                                        <div class="card-body p-3">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Informations de la classe sélectionnée
                                            </h6>
                                            <div id="classInfoContent"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="button" id="nextStep1" class="btn btn-primary btn-lg px-5" disabled>
                                        <i class="fas fa-arrow-right me-2"></i>
                                        Continuer vers la construction
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Construction de l'emploi du temps -->
                <div id="step2" class="step-section d-none">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Étape 2 : Construction de l'emploi du temps
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Informations de la classe sélectionnée -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Configuration :</strong> 
                                <span id="selectedCycleDisplay"></span> - 
                                <span id="selectedClassDisplay"></span> - 
                                <span id="selectedYearDisplay"></span>
                            </div>

                            <!-- Grille d'emploi du temps -->
                            <div id="scheduleGrid" class="d-none">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-table me-2"></i>
                                    Grille horaire
                                </h6>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered schedule-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="width: 120px;">Horaires</th>
                                                <th class="text-center">Lundi</th>
                                                <th class="text-center">Mardi</th>
                                                <th class="text-center">Mercredi</th>
                                                <th class="text-center">Jeudi</th>
                                                <th class="text-center">Vendredi</th>
                                                <th class="text-center">Samedi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scheduleTableBody">
                                            <!-- Les créneaux seront générés dynamiquement -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="button" id="prevStep2" class="btn btn-outline-secondary me-3">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Retour à la sélection
                                    </button>
                                    <button type="submit" id="submitSchedule" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-save me-2"></i>
                                        Sauvegarder l'emploi du temps
                                    </button>
                                </div>
                            </div>

                                                         <!-- Chargement en cours -->
                             <div id="loadingSchedule" class="text-center py-5">
                                 <div class="spinner-border text-primary mb-3" role="status">
                                     <span class="visually-hidden">Chargement...</span>
                                 </div>
                                 <h6 class="text-primary">Chargement de la grille horaire...</h6>
                                 
                                 <!-- Bouton de test pour diagnostiquer -->
                                 <div class="mt-3">
                                     <button type="button" id="testApiBtn" class="btn btn-outline-info btn-sm">
                                         <i class="fas fa-bug me-1"></i>
                                         Tester l'API
                                     </button>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Guide d'utilisation -->
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Comment procéder
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="step-icon bg-primary text-white mb-3">
                                    <i class="fas fa-layer-group fa-2x"></i>
                                </div>
                                <h6 class="text-primary">1. Sélectionner</h6>
                                <p class="text-muted small">
                                    Choisissez le cycle et la classe.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="step-icon bg-success text-white mb-3">
                                    <i class="fas fa-calendar-plus fa-2x"></i>
                                </div>
                                <h6 class="text-success">2. Construire</h6>
                                <p class="text-muted small">
                                    Configurez les créneaux horaires.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="step-icon bg-info text-white mb-3">
                                    <i class="fas fa-edit fa-2x"></i>
                                </div>
                                <h6 class="text-info">3. Assigner</h6>
                                <p class="text-muted small">
                                    Assignez les matières et enseignants.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="step-icon bg-warning text-white mb-3">
                                    <i class="fas fa-save fa-2x"></i>
                                </div>
                                <h6 class="text-warning">4. Sauvegarder</h6>
                                <p class="text-muted small">
                                    Enregistrez l'emploi du temps.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.step-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.bg-gradient-primary {
    background: #0d6efd !important;
}

.card {
    border-radius: 1rem !important;
    overflow: hidden !important;
}

.form-select-lg, .form-control-lg {
    padding: 0.75rem 1rem !important;
    font-size: 1rem !important;
    border-radius: 0.5rem !important;
    border: 2px solid #e9ecef !important;
    transition: all 0.3s ease !important;
}

.form-select-lg:focus, .form-control-lg:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
}

.form-control-plaintext {
    background-color: #f8f9fa !important;
    border: 2px solid #e9ecef !important;
    color: #6c757d !important;
    font-weight: 500 !important;
}

.btn-lg {
    padding: 0.875rem 1.75rem !important;
    font-size: 1.125rem !important;
    border-radius: 0.5rem !important;
}

/* Styles pour la grille horaire */
.schedule-table {
    font-size: 0.875rem;
}

.schedule-table th {
    background-color: #343a40 !important;
    color: white !important;
    font-weight: 600 !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 0.75rem 0.5rem !important;
}

.schedule-table td {
    padding: 0.5rem !important;
    vertical-align: top !important;
    border: 1px solid #dee2e6 !important;
}

.schedule-cell {
    min-height: 80px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.schedule-cell:hover {
    background-color: #e9ecef;
}

.schedule-cell.has-subject {
    background-color: #d4edda;
    border-color: #c3e6cb !important;
}

.schedule-cell.has-teacher {
    background-color: #cce5ff;
    border-color: #b3d7ff !important;
}

.schedule-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.schedule-input-group .form-select {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid #ced4da;
}

.schedule-input-group .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.teacher-select:disabled {
    background-color: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

/* Animation pour le chargement */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.step-section {
    animation: fadeIn 0.5s ease-out;
}

/* Responsive pour la grille */
@media (max-width: 768px) {
    .schedule-table {
        font-size: 0.75rem;
    }
    
    .schedule-input-group .form-select {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    .schedule-cell {
        min-height: 60px;
    }
}
</style>
@endpush

@push('scripts')
<!-- Inclure Axios -->
<script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle');
    const classSelect = document.getElementById('class_id');
    const classSelectionSection = document.getElementById('classSelectionSection');
    const classInfoSection = document.getElementById('classInfoSection');
    const classInfoContent = document.getElementById('classInfoContent');
    const nextStep1Btn = document.getElementById('nextStep1');
    const academicYearId = document.getElementById('academic_year_id').value;
    const scheduleForm = document.getElementById('scheduleForm');

    // Charger les classes quand le cycle change
    cycleSelect.addEventListener('change', function() {
        const selectedCycle = this.value;
        
        if (selectedCycle) {
            loadClassesByCycle(selectedCycle);
            classSelectionSection.classList.remove('d-none');
        } else {
            classSelectionSection.classList.add('d-none');
            classInfoSection.classList.add('d-none');
            nextStep1Btn.disabled = true;
        }
    });

    // Charger les classes selon le cycle
    function loadClassesByCycle(cycle) {
        fetch(`/api/schedules/classes/by-cycle?cycle=${cycle}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateClassSelect(data.classes);
                } else {
                    showToast('Erreur lors du chargement des classes', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des classes:', error);
                showToast('Erreur lors du chargement des classes', 'error');
            });
    }

    // Remplir le select des classes
    function populateClassSelect(classes) {
        classSelect.innerHTML = '<option value="">-- Choisir une classe --</option>';
        
        classes.forEach(classItem => {
            const option = document.createElement('option');
            option.value = classItem.id;
            option.textContent = classItem.name;
            classSelect.appendChild(option);
        });
    }

    // Afficher les informations de la classe sélectionnée
    classSelect.addEventListener('change', function() {
        const selectedClassId = this.value;
        
        if (selectedClassId) {
            const selectedOption = this.options[this.selectedIndex];
            const className = selectedOption.textContent;
            
            // Afficher les informations de base
            classInfoContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Nom de la classe :</strong> ${className}
                    </div>
                    <div class="col-md-6">
                        <strong>Cycle :</strong> ${cycleSelect.options[cycleSelect.selectedIndex].textContent}
                    </div>
                </div>
            `;
            
            classInfoSection.classList.remove('d-none');
            nextStep1Btn.disabled = false;
        } else {
            classInfoSection.classList.add('d-none');
            nextStep1Btn.disabled = true;
        }
    });

    // Gérer le clic sur le bouton "Continuer vers la construction"
    nextStep1Btn.addEventListener('click', function() {
        const cycle = cycleSelect.value;
        const classId = classSelect.value;
        
        if (!cycle || !classId) {
            showToast('Veuillez remplir tous les champs requis', 'error');
            return;
        }
        
        // Afficher l'étape suivante : constitution de l'emploi du temps
        showScheduleBuilder(cycle, classId, academicYearId);
    });

         // Afficher le constructeur d'emploi du temps
     function showScheduleBuilder(cycle, classId, academicYearId) {
         console.log('🎬 showScheduleBuilder appelé avec:', { cycle, classId, academicYearId });
         
         // Masquer l'étape 1
         document.getElementById('step1').style.display = 'none';
         
         // Afficher l'étape 2
         const step2 = document.getElementById('step2');
         step2.classList.remove('d-none');
         
         // Mettre à jour les informations affichées avec vérification de sécurité
         const selectedCycleDisplay = document.getElementById('selectedCycleDisplay');
         const selectedClassDisplay = document.getElementById('selectedClassDisplay');
         const selectedYearDisplay = document.getElementById('selectedYearDisplay');
         
         if (selectedCycleDisplay) {
             selectedCycleDisplay.textContent = cycleSelect.options[cycleSelect.selectedIndex].textContent;
         }
         
         if (selectedClassDisplay) {
             selectedClassDisplay.textContent = classSelect.options[classSelect.selectedIndex].textContent;
         }
         
         if (selectedYearDisplay) {
             selectedYearDisplay.textContent = document.querySelector('input[readonly]').value;
         }
         
         // Initialiser les données de base pour éviter les erreurs
         window.scheduleData = {
             classId: classId,
             cycle: cycle,
             isRealData: false,
             subjects: [],
             teachers: []
         };
         
         console.log('💾 Données initiales créées:', window.scheduleData);
         
         // Charger les matières et enseignants pour cette classe
         loadSubjectsAndTeachers(classId, cycle);
         
         // Faire défiler vers l'étape 2
         step2.scrollIntoView({ behavior: 'smooth' });
     }

         // Charger les matières et enseignants pour la classe
     function loadSubjectsAndTeachers(classId, cycle) {
         console.log('🚀 loadSubjectsAndTeachers appelé avec:', { classId, cycle });
         
         // Pour le collège et lycée, charger les vraies données depuis la base
         if (cycle === 'college' || cycle === 'lycee') {
             console.log('🏫 Cycle secondaire détecté, chargement des vraies données...');
             loadRealSubjectsAndTeachers(classId);
         } else {
             console.log('🎨 Cycle primaire détecté, utilisation des données de base...');
             
             // Pour préprimaire et primaire, utiliser les données de base
             const subjects = getSubjectsByCycle(cycle);
             const teachers = getTeachersByCycle(cycle);
             
             console.log('📚 Matières de base récupérées:', subjects);
             console.log('👥 Enseignants de base récupérés:', teachers);
             
             // Stocker les données pour utilisation dans la grille
             window.scheduleData = {
                 subjects: subjects,
                 teachers: teachers,
                 classId: classId,
                 cycle: cycle,
                 isRealData: false
             };
             
             console.log('💾 Données de base stockées dans window.scheduleData:', window.scheduleData);
             
             // Générer la grille horaire
             generateScheduleGrid();
         }
     }

         // Charger les vraies matières et enseignants de la classe depuis la base
     function loadRealSubjectsAndTeachers(classId) {
         console.log('🔍 loadRealSubjectsAndTeachers appelé avec classId:', classId);
         
         // Afficher un indicateur de chargement
         const loadingSchedule = document.getElementById('loadingSchedule');
         const scheduleGrid = document.getElementById('scheduleGrid');
         
         loadingSchedule.classList.remove('d-none');
         scheduleGrid.classList.add('d-none');
         
         // URL de l'API
         const apiUrl = `/api/schedules/class-subjects-teachers?class_id=${classId}`;
         console.log('🌐 Appel API:', apiUrl);
         
         // TEST : Vérifier que Axios est bien chargé
         if (typeof axios === 'undefined') {
             console.error('❌ Axios n\'est pas défini !');
             showToast('Erreur: Axios non chargé', 'error');
             fallbackToBasicData();
             return;
         }
         
         console.log('✅ Axios est bien chargé:', typeof axios);
         
         // Utiliser Axios pour l'appel API
         axios.get(apiUrl)
             .then(response => {
                 console.log('✅ Réponse API reçue:', response);
                 console.log('📊 Données brutes:', response.data);
                 
                 const data = response.data;
                 
                                   if (data.success) {
                      console.log('🎯 API réussie, traitement des données...');
                      console.log('📚 Matières reçues:', data.subjects);
                      console.log('📊 Type des matières:', Array.isArray(data.subjects) ? 'Array' : typeof data.subjects);
                      console.log('📊 Longueur des matières:', data.subjects ? data.subjects.length : 'undefined');
                      
                      console.log('👥 Enseignants reçus:', data.teachers);
                      console.log('📊 Type des enseignants:', Array.isArray(data.teachers) ? 'Array' : typeof data.teachers);
                      console.log('📊 Longueur des enseignants:', data.teachers ? data.teachers.length : 'undefined');
                      
                      console.log('🔗 Mapping matière-professeur:', data.subjectTeachers);
                      console.log('📊 Type du mapping:', Array.isArray(data.subjectTeachers) ? 'Array' : typeof data.subjectTeachers);
                      console.log('📊 Longueur du mapping:', data.subjectTeachers ? data.subjectTeachers.length : 'undefined');
                      
                      // VÉRIFICATION : S'assurer que les données sont valides
                      if (!Array.isArray(data.subjects) || data.subjects.length === 0) {
                          console.warn('⚠️ Pas de matières reçues, utilisation des données de base');
                          fallbackToBasicData();
                          return;
                      }
                      
                      if (!Array.isArray(data.subjectTeachers) || data.subjectTeachers.length === 0) {
                          console.warn('⚠️ Pas de mapping matière-professeur, utilisation des données de base');
                          fallbackToBasicData();
                          return;
                      }
                      
                      // Stocker les données réelles
                      window.scheduleData = {
                          subjects: data.subjects,
                          teachers: data.teachers,
                          subjectTeachers: data.subjectTeachers,
                          classId: classId,
                          cycle: 'college', // ou 'lycee'
                          isRealData: true
                      };
                      
                      console.log('💾 Données stockées dans window.scheduleData:', window.scheduleData);
                      console.log('🔍 DÉTAIL subjectTeachers:', JSON.stringify(data.subjectTeachers, null, 2));
                      console.log('🔍 DÉTAIL subjects:', JSON.stringify(data.subjects, null, 2));
                      
                      // Générer la grille horaire avec les vraies données
                      generateScheduleGrid();
                  } else {
                      console.error('❌ API échouée:', data.message);
                      showToast('Erreur lors du chargement des matières et enseignants', 'error');
                      // Fallback vers les données de base
                      fallbackToBasicData();
                  }
             })
             .catch(error => {
                 console.error('💥 Erreur lors de l\'appel API:', error);
                 console.error('📋 Détails de l\'erreur:', {
                     message: error.message,
                     status: error.response?.status,
                     statusText: error.response?.statusText,
                     data: error.response?.data,
                     config: error.config
                 });
                 
                 showToast('Erreur lors du chargement des matières et enseignants', 'error');
                 // Fallback vers les données de base
                 fallbackToBasicData();
             });
     }

         // Fallback vers les données de base en cas d'erreur
     function fallbackToBasicData() {
         console.log('🔄 Fallback vers les données de base...');
         
         const cycle = document.getElementById('cycle').value;
         const subjects = getSubjectsByCycle(cycle);
         const teachers = getTeachersByCycle(cycle);
         
         console.log('📚 Matières de fallback:', subjects);
         console.log('👥 Enseignants de fallback:', teachers);
         
         // Mettre à jour les données existantes
         window.scheduleData.subjects = subjects;
         window.scheduleData.teachers = teachers;
         window.scheduleData.cycle = cycle;
         window.scheduleData.isRealData = false;
         
         console.log('💾 Données de fallback mises à jour:', window.scheduleData);
         
         generateScheduleGrid();
     }



    // Obtenir les matières selon le cycle
    function getSubjectsByCycle(cycle) {
        const subjectsMap = {
            'preprimaire': ['Éveil', 'Langage', 'Motricité', 'Arts plastiques'],
            'primaire': ['Français', 'Mathématiques', 'Histoire-Géo', 'Sciences', 'Anglais', 'EPS', 'Arts plastiques'],
            'college': ['Français', 'Mathématiques', 'Histoire-Géo', 'Sciences', 'Anglais', 'EPS', 'Arts plastiques', 'Technologie', 'SVT', 'Physique-Chimie'],
            'lycee': ['Français', 'Mathématiques', 'Histoire-Géo', 'Sciences', 'Anglais', 'EPS', 'Philosophie', 'SVT', 'Physique-Chimie', 'Sciences économiques']
        };
        
        return subjectsMap[cycle] || [];
    }

    // Obtenir les enseignants selon le cycle
    function getTeachersByCycle(cycle) {
        const teachersMap = {
            'preprimaire': ['Mme. Dupont', 'M. Martin', 'Mme. Bernard'],
            'primaire': ['M. Durand', 'Mme. Leroy', 'M. Moreau', 'Mme. Simon'],
            'college': ['M. Dubois', 'Mme. Michel', 'M. Garcia', 'Mme. David', 'M. Robert'],
            'lycee': ['M. Richard', 'Mme. Petit', 'M. Roux', 'Mme. Vincent', 'M. Fournier']
        };
        
        return teachersMap[cycle] || [];
    }

    // Générer la grille horaire
    function generateScheduleGrid() {
        console.log('🏗️ generateScheduleGrid appelé');
        console.log('📊 État de window.scheduleData:', window.scheduleData);
        
        const scheduleTableBody = document.getElementById('scheduleTableBody');
        const scheduleGrid = document.getElementById('scheduleGrid');
        const loadingSchedule = document.getElementById('loadingSchedule');
        
        if (!scheduleTableBody || !scheduleGrid || !loadingSchedule) {
            console.error('❌ Éléments DOM manquants:', {
                scheduleTableBody: !!scheduleTableBody,
                scheduleGrid: !!scheduleGrid,
                loadingSchedule: !!loadingSchedule
            });
            return;
        }
        
        // Créer les créneaux horaires
        const timeSlots = [
            '7h30 - 8h30', '8h30 - 9h30', '9h30 - 10h30', '10h45 - 11h45',
            '11h45 - 12h45', '14h00 - 15h00', '15h00 - 16h00', '16h15 - 17h15'
        ];
        
        console.log('⏰ Créneaux horaires:', timeSlots);
        console.log('🔍 Type de données:', window.scheduleData.isRealData ? 'Réelles (BD)' : 'Base (statiques)');
        
        let tableHTML = '';
        
        timeSlots.forEach((timeSlot, index) => {
            tableHTML += '<tr>';
            tableHTML += `<td class="text-center fw-bold bg-light">${timeSlot}</td>`;
            
            // Créer les cellules pour chaque jour
            for (let day = 0; day < 6; day++) {
                if (window.scheduleData.isRealData) {
                    console.log('📚 Génération cellule avec données réelles - Matières disponibles:', window.scheduleData.subjects);
                    
                                         // Pour les données réelles (collège/lycée)
                     tableHTML += `
                         <td class="schedule-cell" data-time="${timeSlot}" data-day="${day}">
                            <div class="schedule-input-group">
                                <select class="form-select form-select-sm subject-select" name="schedule[${index}][${day}][subject]">
                                    <option value="">-- Matière --</option>
                                    ${window.scheduleData.subjects.map(subject => {
                                        console.log('🔧 Génération option matière:', subject);
                                        return `<option value="${subject.id}" data-subject-name="${subject.name}">${subject.name}</option>`;
                                    }).join('')}
                                </select>
                                <div class="teacher-selection mt-1" style="display: none;">
                                    <select class="form-select form-select-sm teacher-select" name="schedule[${index}][${day}][teacher]">
                                        <option value="">-- Choisir le professeur --</option>
                                    </select>
                                </div>
                                <div class="teacher-display mt-1 p-2 bg-light rounded" style="display: none;">
                                    <small class="text-muted">Professeur : <span class="teacher-name fw-bold"></span></small>
                                </div>
                                <input type="hidden" class="teacher-input" name="schedule[${index}][${day}][teacher]">
                            </div>
                        </td>
                    `;
                } else {
                    console.log('📚 Génération cellule avec données de base - Matières disponibles:', window.scheduleData.subjects);
                    
                                         // Pour les données de base (préprimaire/primaire)
                     tableHTML += `
                         <td class="schedule-cell" data-time="${timeSlot}" data-day="${day}">
                            <div class="schedule-input-group">
                                <select class="form-select form-select-sm subject-select" name="schedule[${index}][${day}][subject]">
                                    <option value="">-- Matière --</option>
                                    ${window.scheduleData.subjects.map(subject => 
                                        `<option value="${subject}">${subject}</option>`
                                    ).join('')}
                                </select>
                                <select class="form-select form-select-sm teacher-select mt-1" name="schedule[${index}][${day}][teacher]">
                                    <option value="">-- Enseignant --</option>
                                    ${window.scheduleData.teachers.map(teacher => 
                                        `<option value="${teacher}">${teacher}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </td>
                    `;
                }
            }
            
            tableHTML += '</tr>';
        });
        
        console.log('📋 HTML généré (premiers 500 caractères):', tableHTML.substring(0, 500));
        
        scheduleTableBody.innerHTML = tableHTML;
        
        // Masquer le chargement et afficher la grille
        loadingSchedule.classList.add('d-none');
        scheduleGrid.classList.remove('d-none');
        
        console.log('✅ Grille générée et affichée');
        
        // Ajouter les événements pour la sélection des matières et enseignants
        addScheduleEventListeners();
    }

    // Ajouter les événements pour la grille
    function addScheduleEventListeners() {
        if (window.scheduleData.isRealData) {
            // Pour les données réelles (collège/lycée)
            addRealDataEventListeners();
        } else {
            // Pour les données de base (préprimaire/primaire)
            addBasicDataEventListeners();
        }
    }

                   // Événements pour les données réelles (collège/lycée)
      function addRealDataEventListeners() {
          console.log('🎯 addRealDataEventListeners appelé');
          console.log('📊 window.scheduleData.subjectTeachers:', window.scheduleData.subjectTeachers);
          
          // Gérer la sélection des matières
          document.querySelectorAll('.subject-select').forEach(select => {
              select.addEventListener('change', function() {
                  console.log('🔄 Changement de matière détecté, valeur sélectionnée:', this.value);
                  
                  const cell = this.closest('.schedule-cell');
                  const teacherSelection = cell.querySelector('.teacher-selection');
                  const teacherDisplay = cell.querySelector('.teacher-display');
                  const teacherName = cell.querySelector('.teacher-name');
                  const teacherInput = cell.querySelector('.teacher-input');
                  const teacherSelect = cell.querySelector('.teacher-select');
                  
                  if (this.value) {
                      // Récupérer le nom de la matière sélectionnée
                      const selectedOption = this.options[this.selectedIndex];
                      const subjectName = selectedOption.dataset.subjectName;
                      console.log('📚 Matière sélectionnée:', subjectName);
                      
                      // Trouver les professeurs pour cette matière
                      console.log('🔍 Recherche professeurs pour matière:', subjectName);
                      console.log('🔍 Tous les subjectTeachers disponibles:', window.scheduleData.subjectTeachers);
                      
                      // Recherche par nom ET par ID pour plus de fiabilité
                      let subjectTeacher = window.scheduleData.subjectTeachers.find(st => 
                          st.subject_name === subjectName || 
                          st.subject_id == this.value
                      );
                      
                      // Si pas trouvé par nom exact, essayer une recherche partielle
                      if (!subjectTeacher) {
                          subjectTeacher = window.scheduleData.subjectTeachers.find(st => 
                              st.subject_name.toLowerCase().includes(subjectName.toLowerCase()) ||
                              subjectName.toLowerCase().includes(st.subject_name.toLowerCase())
                          );
                      }
                      
                      console.log('🔍 Résultat de la recherche:', subjectTeacher);
                      
                                             // DEBUG : Vérifier la structure des données
                       console.log('🔍 Structure de subjectTeacher:', subjectTeacher);
                       console.log('🔍 Type de teachers:', typeof subjectTeacher.teachers);
                       console.log('🔍 Contenu de teachers:', subjectTeacher.teachers);
                       
                       // Vérifier si teachers est un tableau ou un objet
                       let teachersArray = [];
                       if (Array.isArray(subjectTeacher.teachers)) {
                           teachersArray = subjectTeacher.teachers;
                       } else if (typeof subjectTeacher.teachers === 'object' && subjectTeacher.teachers !== null) {
                           // Si c'est un objet, le convertir en tableau
                           teachersArray = Object.values(subjectTeacher.teachers);
                       }
                       
                       console.log('🔍 Tableau des professeurs converti:', teachersArray);
                       
                       if (teachersArray.length > 0) {
                           console.log('👥 Professeurs trouvés pour cette matière:', teachersArray);
                           
                           // Vider le select des professeurs
                           teacherSelect.innerHTML = '<option value="">-- Choisir le professeur --</option>';
                           
                           // Ajouter les options des professeurs
                           teachersArray.forEach(teacher => {
                               console.log('➕ Ajout professeur:', teacher);
                               const option = document.createElement('option');
                               option.value = teacher.id;
                               option.textContent = teacher.name;
                               teacherSelect.appendChild(option);
                           });
                          
                          // Afficher la sélection du professeur
                          teacherSelection.style.display = 'block';
                          teacherDisplay.style.display = 'none';
                          
                          cell.classList.add('has-subject');
                          cell.classList.remove('has-teacher');
                          
                          console.log('✅ Menu professeur affiché avec', subjectTeacher.teachers.length, 'options');
                      } else {
                          console.log('⚠️ Aucun professeur trouvé pour cette matière');
                          
                          // Pas de professeur trouvé
                          teacherSelection.style.display = 'none';
                          teacherName.textContent = 'Non assigné';
                          teacherDisplay.style.display = 'block';
                          teacherInput.value = '';
                          
                          cell.classList.add('has-subject');
                          cell.classList.remove('has-teacher');
                      }
                  } else {
                      console.log('❌ Aucune matière sélectionnée');
                      
                      // Masquer la sélection et l'affichage du professeur
                      teacherSelection.style.display = 'none';
                      teacherDisplay.style.display = 'none';
                      teacherInput.value = '';
                      cell.classList.remove('has-subject');
                      cell.classList.remove('has-teacher');
                  }
              });
          });
         
         // Gérer la sélection des professeurs
         document.addEventListener('change', function(e) {
             if (e.target.classList.contains('teacher-select')) {
                 const cell = e.target.closest('.schedule-cell');
                 const teacherInput = cell.querySelector('.teacher-input');
                 const teacherDisplay = cell.querySelector('.teacher-display');
                 const teacherName = cell.querySelector('.teacher-name');
                 const teacherSelection = cell.querySelector('.teacher-selection');
                 
                 if (e.target.value) {
                     // Récupérer le nom du professeur sélectionné
                     const selectedOption = e.target.options[e.target.selectedIndex];
                     const teacherNameText = selectedOption.textContent;
                     
                     // Afficher le nom du professeur
                     teacherName.textContent = teacherNameText;
                     teacherDisplay.style.display = 'block';
                     teacherSelection.style.display = 'none';
                     
                     // Stocker l'ID du professeur
                     teacherInput.value = e.target.value;
                     
                     cell.classList.add('has-teacher');
                 } else {
                     // Pas de professeur sélectionné
                     teacherDisplay.style.display = 'none';
                     teacherInput.value = '';
                     cell.classList.remove('has-teacher');
                 }
             }
         });
     }

    // Événements pour les données de base (préprimaire/primaire)
    function addBasicDataEventListeners() {
        // Gérer la sélection des matières
        document.querySelectorAll('.subject-select').forEach(select => {
            select.addEventListener('change', function() {
                const cell = this.closest('.schedule-cell');
                const teacherSelect = cell.querySelector('.teacher-select');
                
                if (this.value) {
                    // Activer la sélection de l'enseignant
                    teacherSelect.disabled = false;
                    cell.classList.add('has-subject');
                } else {
                    // Désactiver la sélection de l'enseignant
                    teacherSelect.disabled = true;
                    teacherSelect.value = '';
                    cell.classList.remove('has-subject');
                }
            });
        });

        // Gérer la sélection des enseignants
        document.querySelectorAll('.teacher-select').forEach(select => {
            select.addEventListener('change', function() {
                const cell = this.closest('.schedule-cell');
                if (this.value) {
                    cell.classList.add('has-teacher');
                } else {
                    cell.classList.remove('has-teacher');
                }
            });
        });
    }

         // Gérer le retour à l'étape 1
     document.getElementById('prevStep2').addEventListener('click', function() {
         document.getElementById('step2').classList.add('d-none');
         document.getElementById('step1').style.display = 'block';
         document.getElementById('step1').scrollIntoView({ behavior: 'smooth' });
     });
     
     // Gérer le bouton de test de l'API
     document.addEventListener('click', function(e) {
         if (e.target && e.target.id === 'testApiBtn') {
             console.log('🧪 Bouton de test API cliqué');
             testApi();
         }
     });

         // Gérer la soumission du formulaire
     document.getElementById('submitSchedule').addEventListener('click', function(e) {
         e.preventDefault();
         
         // Valider qu'au moins quelques créneaux sont remplis (pas besoin de tout remplir)
         const filledCells = document.querySelectorAll('.schedule-cell.has-subject');
         
         if (filledCells.length === 0) {
             showToast('Veuillez remplir au moins quelques créneaux horaires', 'warning');
             return;
         }
         
         console.log('✅ Créneaux remplis:', filledCells.length);
        
        // Collecter les données de l'emploi du temps
        const scheduleData = collectScheduleData();
        
        // Envoyer les données au serveur
        saveSchedule(scheduleData);
    });

    // Collecter les données de l'emploi du temps
    function collectScheduleData() {
        const scheduleData = {
            class_id: window.scheduleData.classId,
            academic_year_id: academicYearId,
            cycle: window.scheduleData.cycle,
            schedule: []
        };
        
        document.querySelectorAll('.schedule-cell').forEach(cell => {
            const timeSlot = cell.dataset.time; // Format: "7h30-8h30"
            const day = cell.dataset.day;
            const subjectSelect = cell.querySelector('.subject-select');
            
            console.log('🔍 Cellule traitée:', { timeSlot, day, hasSubject: !!subjectSelect });
            
            if (window.scheduleData.isRealData) {
                // Pour les données réelles (collège/lycée)
                const subjectId = subjectSelect.value;
                const teacherInput = cell.querySelector('.teacher-input');
                const teacherId = teacherInput.value;
                
                if (subjectId) {
                    // Convertir le format de temps "7h30-8h30" en "07:30:00-08:30:00"
                    const timeParts = timeSlot.split('-');
                    const startTime = convertTimeFormat(timeParts[0]);
                    const endTime = convertTimeFormat(timeParts[1]);
                    
                    // Trouver le nom de la matière
                    const subject = window.scheduleData.subjects.find(s => s.id == subjectId);
                    const subjectName = subject ? subject.name : 'Matière inconnue';
                    
                    scheduleData.schedule.push({
                        day: day,
                        start_time: startTime,
                        end_time: endTime,
                        subject_id: subjectId,
                        subject_name: subjectName,
                        teacher_id: teacherId || null,
                        type: 'course',
                        room: '',
                        title: ''
                    });
                }
            } else {
                // Pour les données de base (préprimaire/primaire)
                const subject = subjectSelect.value;
                const teacherSelect = cell.querySelector('.teacher-select');
                const teacher = teacherSelect.value;
                
                if (subject && teacher) {
                    // Convertir le format de temps
                    const timeParts = timeSlot.split('-');
                    const startTime = convertTimeFormat(timeParts[0]);
                    const endTime = convertTimeFormat(timeParts[1]);
                    
                    scheduleData.schedule.push({
                        day: day,
                        start_time: startTime,
                        end_time: endTime,
                        subject_id: subject,
                        teacher_id: teacher,
                        type: 'course',
                        room: '',
                        title: ''
                    });
                }
            }
        });
        
        return scheduleData;
    }
    
    // Convertir le format de temps "7h30" en "07:30:00"
    function convertTimeFormat(timeStr) {
        // Vérification de sécurité
        if (!timeStr || typeof timeStr !== 'string') {
            console.warn('⚠️ convertTimeFormat: timeStr invalide:', timeStr);
            return '00:00:00'; // Valeur par défaut
        }
        
        // Format attendu: "7h30" -> "07:30:00"
        const match = timeStr.match(/(\d+)h(\d+)/);
        if (match) {
            const hours = match[1].padStart(2, '0');
            const minutes = match[2].padStart(2, '0');
            return `${hours}:${minutes}:00`;
        }
        
        console.warn('⚠️ convertTimeFormat: format non reconnu:', timeStr);
        return '00:00:00'; // Valeur par défaut
    }

    // Sauvegarder l'emploi du temps
    function saveSchedule(scheduleData) {
        // Afficher un indicateur de chargement
        const submitBtn = document.getElementById('submitSchedule');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sauvegarde...';
        submitBtn.disabled = true;
        
        console.log('💾 Données à envoyer au serveur:', scheduleData);
        
        // Envoyer les données au serveur Laravel
        fetch('{{ route("schedules.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(scheduleData)
        })
        .then(response => {
            // Vérifier si la réponse est du JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Si ce n'est pas du JSON, c'est probablement une page d'erreur HTML
                throw new Error('Le serveur a retourné une page d\'erreur au lieu de JSON');
            }
        })
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                
                // Rediriger vers la liste des emplois du temps
                setTimeout(() => {
                    window.location.href = '{{ route("schedules.index") }}';
                }, 1500);
            } else {
                showToast('Erreur: ' + data.message, 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la sauvegarde:', error);
            console.error('Détails de l\'erreur:', error.message);
            showToast('Erreur lors de la sauvegarde: ' + error.message, 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

         // Fonction pour afficher les toasts
     function showToast(message, type = 'info') {
         // Utiliser une fonction globale ou alert() comme fallback
         if (typeof window.showToast === 'function') {
             window.showToast(message, type);
         } else {
             alert(message);
         }
     }
     
     // Fonction de test de l'API pour diagnostiquer
     function testApi() {
         const classId = window.scheduleData ? window.scheduleData.classId : 1;
         const apiUrl = `/api/schedules/class-subjects-teachers?class_id=${classId}`;
         
         console.log('🧪 TEST API - URL:', apiUrl);
         console.log('🧪 TEST API - Class ID:', classId);
         
         // Test avec Axios
         if (typeof axios !== 'undefined') {
             console.log('✅ Axios disponible pour le test');
             
             axios.get(apiUrl)
                 .then(response => {
                     console.log('🧪 TEST API - Réponse reçue:', response);
                     console.log('🧪 TEST API - Données:', response.data);
                     
                     if (response.data.success) {
                         console.log('🧪 TEST API - Succès !');
                         console.log('🧪 TEST API - Matières:', response.data.subjects);
                         console.log('🧪 TEST API - Enseignants:', response.data.teachers);
                         console.log('🧪 TEST API - Mapping:', response.data.subjectTeachers);
                         
                         showToast('Test API réussi ! Vérifiez la console', 'success');
                     } else {
                         console.log('🧪 TEST API - Échec:', response.data.message);
                         showToast('Test API échoué: ' + response.data.message, 'error');
                     }
                 })
                 .catch(error => {
                     console.error('🧪 TEST API - Erreur:', error);
                     console.error('🧪 TEST API - Détails:', {
                         message: error.message,
                         status: error.response?.status,
                         data: error.response?.data
                     });
                     
                     showToast('Test API - Erreur: ' + error.message, 'error');
                 });
         } else {
             console.error('❌ Axios non disponible pour le test');
             showToast('Axios non disponible pour le test', 'error');
         }
     }
});
</script>
@endpush
