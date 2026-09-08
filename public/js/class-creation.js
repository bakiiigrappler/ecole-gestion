// Coefficients par série pour le lycée
const SERIES_COEFFICIENTS = {
    'S': {
        'Mathématiques': 7, 'Sciences physiques': 6, 'Sciences de la Vie et de la Terre': 6,
        'Français': 4, 'Histoire-Géographie': 3, 'Anglais': 2, 'Philosophie': 4,
        'Éducation physique et sportive': 1
    },
    'C': {
        'Mathématiques': 7, 'Sciences physiques': 6, 'Sciences de la Vie et de la Terre': 5,
        'Français': 4, 'Histoire-Géographie': 3, 'Anglais': 2, 'Philosophie': 4,
        'Éducation physique et sportive': 1
    },
    'D': {
        'Sciences de la Vie et de la Terre': 7, 'Mathématiques': 5, 'Sciences physiques': 5,
        'Français': 4, 'Histoire-Géographie': 3, 'Anglais': 2, 'Philosophie': 4,
        'Éducation physique et sportive': 1
    },
    'A1': {
        'Français': 6, 'Littérature': 5, 'Latin': 4, 'Histoire-Géographie': 4,
        'Anglais': 3, 'Mathématiques': 2, 'Philosophie': 4, 'Éducation physique et sportive': 1
    },
    'A2': {
        'Français': 6, 'Littérature': 5, 'Espagnol': 4, 'Histoire-Géographie': 4,
        'Anglais': 3, 'Mathématiques': 2, 'Philosophie': 4, 'Éducation physique et sportive': 1
    },
    'B': {
        'Sciences économiques et sociales': 7, 'Mathématiques': 5, 'Français': 4,
        'Histoire-Géographie': 4, 'Anglais': 3, 'Philosophie': 4, 'Éducation physique et sportive': 1
    },
    'E': {
        'Technologie industrielle': 8, 'Mathématiques': 6, 'Sciences physiques': 5,
        'Français': 3, 'Histoire-Géographie': 2, 'Anglais': 2, 'Philosophie': 3,
        'Éducation physique et sportive': 1
    },
    'LE': {
        'Français': 6, 'Littérature': 5, 'Histoire-Géographie': 4, 'Anglais': 3,
        'Mathématiques': 2, 'Philosophie': 4, 'Éducation physique et sportive': 1
    }
};

// Données JSON pour la création de classe
const CLASS_CREATION_DATA = {
    // Données de test pour les professeurs par niveau (IDs réels de la base)
    teachers: {
        // Niveau 4 (CP - Primaire)
        4: [
            { id: 1, first_name: 'Jacques', last_name: 'Wilderman', teacher_type: 'general', specialization: '' },
            { id: 2, first_name: 'Delia', last_name: 'Price', teacher_type: 'general', specialization: '' },
            { id: 4, first_name: 'King', last_name: 'Hoppe', teacher_type: 'general', specialization: '' }
        ],
        // Niveau 5 (CE1 - Primaire)
        5: [
            { id: 5, first_name: 'Rozella', last_name: 'Kozey', teacher_type: 'general', specialization: '' },
            { id: 6, first_name: 'Dudley', last_name: 'Prosacco', teacher_type: 'general', specialization: '' },
            { id: 7, first_name: 'Gladyce', last_name: 'Reynolds', teacher_type: 'general', specialization: '' }
        ],
        // Niveau 9 (6ème - Collège)
        9: [
            { id: 17, first_name: 'Stacy', last_name: 'Terry', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 18, first_name: 'Tressa', last_name: 'Reinger', teacher_type: 'specialized', specialization: 'Français' },
            { id: 19, first_name: 'Vincenzo', last_name: 'Friesen', teacher_type: 'specialized', specialization: 'Histoire-Géographie' },
            { id: 20, first_name: 'Jerrod', last_name: 'Wuckert', teacher_type: 'specialized', specialization: 'Sciences' },
            { id: 21, first_name: 'Bertha', last_name: 'Cummings', teacher_type: 'specialized', specialization: 'Anglais' },
            { id: 22, first_name: 'Wiley', last_name: 'Kertzmann', teacher_type: 'specialized', specialization: 'EPS' }
        ],
        // Niveau 10 (5ème - Collège)
        10: [
            { id: 23, first_name: 'Willy', last_name: 'Kuhn', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 24, first_name: 'Alanis', last_name: 'Gibson', teacher_type: 'specialized', specialization: 'Physique-Chimie' },
            { id: 25, first_name: 'Cesar', last_name: 'Veum', teacher_type: 'specialized', specialization: 'SVT' },
            { id: 26, first_name: 'Kristoffer', last_name: 'Ziemann', teacher_type: 'specialized', specialization: 'Histoire-Géographie' },
            { id: 27, first_name: 'Jovanny', last_name: 'Osinski', teacher_type: 'specialized', specialization: 'Philosophie' },
            { id: 29, first_name: 'ESSONO', last_name: 'Florent', teacher_type: 'specialized', specialization: 'Français' }
        ],
        // Niveau 13 (2nde - Lycée)
        13: [
            { id: 30, first_name: 'ESSONO', last_name: 'Florent', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 17, first_name: 'Stacy', last_name: 'Terry', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 18, first_name: 'Tressa', last_name: 'Reinger', teacher_type: 'specialized', specialization: 'Français' },
            { id: 19, first_name: 'Vincenzo', last_name: 'Friesen', teacher_type: 'specialized', specialization: 'Histoire-Géographie' },
            { id: 20, first_name: 'Jerrod', last_name: 'Wuckert', teacher_type: 'specialized', specialization: 'Sciences' },
            { id: 21, first_name: 'Bertha', last_name: 'Cummings', teacher_type: 'specialized', specialization: 'Anglais' }
        ],
        // Niveau 14 (1ère - Lycée)
        14: [
            { id: 23, first_name: 'Willy', last_name: 'Kuhn', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 24, first_name: 'Alanis', last_name: 'Gibson', teacher_type: 'specialized', specialization: 'Physique-Chimie' },
            { id: 25, first_name: 'Cesar', last_name: 'Veum', teacher_type: 'specialized', specialization: 'SVT' },
            { id: 26, first_name: 'Kristoffer', last_name: 'Ziemann', teacher_type: 'specialized', specialization: 'Histoire-Géographie' },
            { id: 27, first_name: 'Jovanny', last_name: 'Osinski', teacher_type: 'specialized', specialization: 'Philosophie' },
            { id: 29, first_name: 'ESSONO', last_name: 'Florent', teacher_type: 'specialized', specialization: 'Français' }
        ],
        // Niveau 15 (Terminale - Lycée)
        15: [
            { id: 30, first_name: 'ESSONO', last_name: 'Florent', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 17, first_name: 'Stacy', last_name: 'Terry', teacher_type: 'specialized', specialization: 'Mathématiques' },
            { id: 18, first_name: 'Tressa', last_name: 'Reinger', teacher_type: 'specialized', specialization: 'Français' },
            { id: 19, first_name: 'Vincenzo', last_name: 'Friesen', teacher_type: 'specialized', specialization: 'Histoire-Géographie' },
            { id: 20, first_name: 'Jerrod', last_name: 'Wuckert', teacher_type: 'specialized', specialization: 'Sciences' },
            { id: 21, first_name: 'Bertha', last_name: 'Cummings', teacher_type: 'specialized', specialization: 'Anglais' }
        ]
    },
    
    // Configuration des niveaux (IDs réels de la base de données)
    levels: [
        { id: 4, name: 'CP', code: 'CP', cycle: 'primaire' },
        { id: 5, name: 'CE1', code: 'CE1', cycle: 'primaire' },
        { id: 9, name: '6ème', code: '6EME', cycle: 'college' },
        { id: 10, name: '5ème', code: '5EME', cycle: 'college' },
        { id: 13, name: '2nde', code: '2NDE', cycle: 'lycee' },
        { id: 14, name: '1ère', code: '1ERE', cycle: 'lycee' },
        { id: 15, name: 'Terminal', code: 'TERMINAL', cycle: 'lycee' }
    ]
};

// Classe pour gérer la création de classe
class ClassCreationManager {
    constructor() {
        this.teacherCounter = 0;
        this.selectedTeachers = new Map(); // Map pour stocker les professeurs sélectionnés
        this.selectedSubjects = new Set(); // Set pour stocker les matières sélectionnées
        this.currentCycle = null; // Cycle actuellement sélectionné
        this.isPrimaryLevel = false; // Si c'est un niveau primaire
        this.maxTeachers = null; // Nombre maximum d'enseignants
        this.availableTeachers = []; // Liste des enseignants disponibles
        
        this.init();
    }
    
    init() {
        console.log('ClassCreationManager initialisé');
        this.bindEvents();
        
        // Initialiser l'état du bouton d'ajout de professeur
        const levelSelect = document.getElementById('level_id');
        if (levelSelect) {
            this.updateAddTeacherButtonState(parseInt(levelSelect.value) || 0);
            this.updateSeriesField(levelSelect);
        }
        
        // Initialiser les champs de génération de nom comme désactivés
        this.initializeNameGeneration();
    }
    
    initializeNameGeneration() {
        const incrementType = document.getElementById('increment_type');
        const incrementValue = document.getElementById('increment_value');
        const incrementTypeStatus = document.getElementById('incrementTypeStatus');
        
        if (incrementType) {
            incrementType.disabled = true;
            incrementType.style.backgroundColor = '#f8f9fa';
        }
        
        if (incrementValue) {
            incrementValue.disabled = true; // Toujours désactivé, valeur automatique
            incrementValue.style.backgroundColor = '#f8f9fa';
            incrementValue.innerHTML = '<option value="">Sélectionner d\'abord un niveau</option>';
        }
        
        if (incrementTypeStatus) {
            incrementTypeStatus.textContent = 'Sélectionnez d\'abord un niveau.';
        }
    }
    
    bindEvents() {
        // Événement de changement de niveau
        const levelSelect = document.getElementById('level_id');
        if (levelSelect) {
            levelSelect.addEventListener('change', (e) => this.onLevelChange(e));
        }
        
        // Événement de changement de série
        const seriesSelect = document.getElementById('series');
        if (seriesSelect) {
            seriesSelect.addEventListener('change', () => this.onSeriesChange());
        }
        
        // Événements pour la génération du nom
        const incrementType = document.getElementById('increment_type');
        if (incrementType) {
            incrementType.addEventListener('change', () => this.onIncrementChange());
        }
        
        // Le champ increment_value n'a plus besoin d'event listener car il est toujours automatique
        
        // Événement d'ajout de professeur
        const addTeacherBtn = document.getElementById('addTeacherBtn');
        if (addTeacherBtn) {
            addTeacherBtn.addEventListener('click', () => this.addTeacherEntry());
        }
        
        // Événements de délégation pour les professeurs
        const teachersContainer = document.getElementById('teachersContainer');
        if (teachersContainer) {
            teachersContainer.addEventListener('click', (e) => this.handleTeacherContainerClick(e));
            teachersContainer.addEventListener('change', (e) => this.handleTeacherContainerChange(e));
            teachersContainer.addEventListener('input', (e) => this.handleTeacherContainerInput(e));
        }
        
        // Validation du formulaire
        const classForm = document.getElementById('classForm');
        if (classForm) {
            classForm.addEventListener('submit', (e) => this.validateForm(e));
        }
    }
    
    onLevelChange(event) {
        const levelId = parseInt(event.target.value);
        console.log('Niveau sélectionné:', levelId);
        
        // Mettre à jour l'état du bouton d'ajout de professeur
        this.updateAddTeacherButtonState(levelId);
        
        // Mettre à jour le champ série
        this.updateSeriesField(event.target);
        
        if (levelId) {
            // Charger les enseignants depuis l'API une seule fois
            this.loadTeachersFromAPI(levelId);
        }
    }
    
    onSeriesChange() {
        this.checkExistingClasses();
        
        // Mettre à jour les coefficients pour tous les professeurs sélectionnés
        if (this.selectedLevel && this.selectedLevel.cycle === 'lycee') {
            const seriesSelect = document.getElementById('series');
            if (seriesSelect && seriesSelect.value) {
                this.selectedSeries = seriesSelect.value;
                this.updateAllTeacherCoefficients();
            }
        }
    }
    
    checkExistingClasses() {
        const levelSelect = document.getElementById('level_id');
        const seriesSelect = document.getElementById('series');
        
        if (!levelSelect || !levelSelect.value) return;
        
        const levelId = levelSelect.value;
        const series = (seriesSelect && seriesSelect.value) ? seriesSelect.value : null;
        
        // Construire l'URL avec le paramètre série si nécessaire
        let url = `/api/levels/${levelId}/existing-classes`;
        if (series) {
            url += `?series=${series}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.handleExistingClassesResponse(data);
                } else {
                    console.error('Erreur lors de la récupération des classes existantes:', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur réseau:', error);
            });
    }
    
    handleExistingClassesResponse(data) {
        const incrementType = document.getElementById('increment_type');
        const incrementValue = document.getElementById('increment_value');
        const nameInput = document.getElementById('name');
        const nameHidden = document.getElementById('name_hidden');
        const incrementTypeStatus = document.getElementById('incrementTypeStatus');
        
        if (data.is_first_class) {
            // Première classe pour ce niveau/série - donner le choix du type seulement
            incrementType.disabled = false;
            incrementType.style.backgroundColor = '';
            incrementValue.disabled = true; // Toujours désactivé, valeur automatique
            incrementValue.style.backgroundColor = '#f8f9fa';
            
            this.updateIncrementOptions();
            
            // Sélectionner automatiquement la première valeur selon le type
            this.setFirstValueForType();
            
            if (incrementTypeStatus) {
                incrementTypeStatus.innerHTML = '<span class="text-success">Première classe - Choisissez le type, la valeur sera automatique</span>';
            }
        } else {
            // Classes existantes - incrémentation automatique
            incrementType.value = data.increment_type;
            incrementType.disabled = true; // Désactiver le choix
            incrementType.style.backgroundColor = '#f8f9fa';
            
            // Mettre à jour les options et sélectionner la suivante
            this.updateIncrementOptions();
            
            // Sélectionner automatiquement le prochain incrément
            if (data.next_increment) {
                incrementValue.value = data.next_increment;
                incrementValue.disabled = true;
                incrementValue.style.backgroundColor = '#f8f9fa';
            }
            
            if (incrementTypeStatus) {
                incrementTypeStatus.innerHTML = '<span class="text-primary">Incrémentation automatique selon les classes existantes</span>';
            }
        }
        
        // Générer le nom de classe
        this.generateClassName();
        
        // Afficher les classes existantes (pour information)
        this.displayExistingClassesInfo(data.existing_classes);
    }
    
    displayExistingClassesInfo(existingClasses) {
        // Créer ou mettre à jour une section d'information
        let infoDiv = document.getElementById('existingClassesInfo');
        if (!infoDiv) {
            infoDiv = document.createElement('div');
            infoDiv.id = 'existingClassesInfo';
            infoDiv.className = 'alert alert-info mt-2';
            
            const nameGenerationSection = document.getElementById('nameGenerationSection');
            nameGenerationSection.appendChild(infoDiv);
        }
        
        if (existingClasses.length === 0) {
            infoDiv.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Première classe pour ce niveau :</strong> Choisissez votre type d'incrémentation préféré.
            `;
        } else {
            let classNames = existingClasses.map(c => c.name).join(', ');
            infoDiv.innerHTML = `
                <i class="bi bi-list-ul me-2"></i>
                <strong>Classes existantes :</strong> ${classNames}
                <br><small class="text-muted">L'incrémentation se fera automatiquement selon le modèle existant.</small>
            `;
        }
    }
    
    onIncrementChange() {
        this.updateIncrementOptions();
        this.generateClassName();
    }
    
    updateIncrementOptions() {
        const incrementType = document.getElementById('increment_type');
        const incrementValue = document.getElementById('increment_value');
        
        if (!incrementType || !incrementValue) return;
        
        const type = incrementType.value;
        incrementValue.innerHTML = '';
        
        if (type === 'number') {
            // Générer les options de 1 à 20
            for (let i = 1; i <= 20; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i;
                incrementValue.appendChild(option);
            }
        } else if (type === 'letter') {
            // Générer les lettres de A à Z
            for (let i = 65; i <= 90; i++) {
                const letter = String.fromCharCode(i);
                const option = document.createElement('option');
                option.value = letter;
                option.textContent = letter;
                incrementValue.appendChild(option);
            }
        }
        
        // Pour les nouvelles classes, toujours commencer par la première valeur
        this.setFirstValueForType();
    }
    
    setFirstValueForType() {
        const incrementType = document.getElementById('increment_type');
        const incrementValue = document.getElementById('increment_value');
        
        if (!incrementType || !incrementValue) return;
        
        const type = incrementType.value;
        
        if (type === 'number') {
            // Pour les chiffres, commencer par 1
            incrementValue.value = '1';
        } else if (type === 'letter') {
            // Pour les lettres, commencer par A
            incrementValue.value = 'A';
        }
        
        // Mettre à jour le nom de classe
        this.generateClassName();
    }
    
    generateClassName() {
        const levelSelect = document.getElementById('level_id');
        const seriesSelect = document.getElementById('series');
        const incrementType = document.getElementById('increment_type');
        const incrementValue = document.getElementById('increment_value');
        const nameInput = document.getElementById('name');
        const nameHidden = document.getElementById('name_hidden');
        
        if (!levelSelect || !incrementType || !incrementValue || !nameInput) return;
        
        const selectedLevelOption = levelSelect.selectedOptions[0];
        if (!selectedLevelOption) {
            nameInput.value = '';
            return;
        }
        
        const levelName = selectedLevelOption.dataset.levelName;
        const cycle = selectedLevelOption.dataset.cycle;
        const increment = incrementValue.value;
        
        let className = levelName;
        
        // Ajouter la série si lycée et série sélectionnée
        if (cycle === 'lycee' && seriesSelect && seriesSelect.value) {
            className += ` ${seriesSelect.value}`;
        }
        
        // Ajouter l'incrémentation si disponible
        if (increment) {
            className += ` ${increment}`;
        }
        
        nameInput.value = className;
    }
    
    updateAddTeacherButtonState(levelId) {
        const addTeacherBtn = document.getElementById('addTeacherBtn');
        if (addTeacherBtn) {
            if (levelId) {
                // Le bouton sera masqué/affiché selon le cycle dans updateTeacherSectionForCycle
                addTeacherBtn.disabled = false;
                addTeacherBtn.classList.remove('btn-secondary');
                addTeacherBtn.classList.add('btn-success');
                addTeacherBtn.innerHTML = '<i class="bi bi-plus me-1"></i>Ajouter un professeur';
            } else {
                addTeacherBtn.disabled = true;
                addTeacherBtn.classList.remove('btn-success');
                addTeacherBtn.classList.add('btn-secondary');
                addTeacherBtn.innerHTML = '<i class="bi bi-plus me-1"></i>Sélectionnez d\'abord un niveau';
                addTeacherBtn.style.display = 'inline-block'; // Réafficher au cas où
            }
        }
    }
    
    updateSeriesField(levelSelect) {
        const seriesField = document.getElementById('seriesField');
        const seriesSelect = document.getElementById('series');
        const nameGenerationSection = document.getElementById('nameGenerationSection');
        
        if (!seriesField || !seriesSelect || !levelSelect) return;
        
        const selectedOption = levelSelect.selectedOptions[0];
        if (!selectedOption) {
            seriesField.classList.add('d-none');
            nameGenerationSection.classList.add('d-none');
            return;
        }
        
        const cycle = selectedOption.dataset.cycle;
        const levelName = selectedOption.dataset.levelName;
        
        // Définir les séries selon le système éducatif gabonais
        const seriesData = {
            'Seconde': ['S', 'LE'],
            '2nde': ['S', 'LE'],
            'Première': ['S', 'A1', 'A2', 'B'],
            '1ère': ['S', 'A1', 'A2', 'B'],
            'Terminale': ['S', 'A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3'],
            'Terminal': ['S', 'A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3'],
            'Tle': ['S', 'A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3']
        };
        
        if (cycle === 'lycee' && seriesData[levelName]) {
            // Afficher le champ série pour le lycée dans l'étape 1
            seriesField.classList.remove('d-none');
            seriesSelect.required = true;
            
            // Vider les options actuelles
            seriesSelect.innerHTML = '<option value="">Sélectionner une série</option>';
            
            // Ajouter les séries appropriées
            seriesData[levelName].forEach(serie => {
                const option = document.createElement('option');
                option.value = serie;
                option.textContent = this.getSeriesFullName(serie);
                seriesSelect.appendChild(option);
            });
        } else {
            // Masquer le champ série pour primaire et collège
            seriesField.classList.add('d-none');
            seriesSelect.required = false;
            seriesSelect.value = '';
        }
        
        // Afficher la section de génération du nom
        nameGenerationSection.classList.remove('d-none');
        this.checkExistingClasses();
    }
    
    getSeriesFullName(serie) {
        const seriesNames = {
            'S': 'S - Scientifique',
            'LE': 'LE - Lettres modernes',
            'A1': 'A1 - Lettres-Langues anciennes',
            'A2': 'A2 - Lettres-Langues vivantes',
            'B': 'B - Sciences économiques et sociales',
            'C': 'C - Mathématiques-Sciences physiques',
            'D': 'D - Sciences naturelles',
            'E': 'E - Techniques industrielles',
            'F1': 'F1 - Mécanique générale',
            'F2': 'F2 - Électronique',
            'F3': 'F3 - Électrotechnique',
            'F4': 'F4 - Génie civil',
            'G1': 'G1 - Secrétariat',
            'G2': 'G2 - Comptabilité',
            'G3': 'G3 - Commerce'
        };
        
        return seriesNames[serie] || serie;
    }
    
    loadTeachersFromAPI(levelId) {
        fetch(`/api/levels/${levelId}/teachers`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.currentCycle = data.cycle;
                    this.isPrimaryLevel = data.is_primary;
                    this.maxTeachers = data.max_teachers;
                    this.availableTeachers = data.teachers; // Stocker les enseignants disponibles
                    
                    // Mettre à jour l'interface selon le cycle
                    this.updateTeacherSectionForCycle(data);
                    
                    // Mettre à jour les options des selects existants
                    const teacherSelects = document.querySelectorAll('.teacher-select');
                    teacherSelects.forEach(select => {
                        this.updateTeacherOptionsFromAPI(select, data.teachers);
                    });
                } else {
                    console.error('Erreur lors de la récupération des enseignants:', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur réseau:', error);
            });
    }
    
    updateTeacherSectionForCycle(data) {
        const addTeacherBtn = document.getElementById('addTeacherBtn');
        const teachersContainer = document.getElementById('teachersContainer');
        const teachersSectionTitle = document.getElementById('teachersSectionTitle');
        
        if (data.is_primary) {
            // Pour le primaire : masquer le bouton + et s'assurer qu'il n'y a qu'un seul enseignant
            if (addTeacherBtn) {
                addTeacherBtn.style.display = 'none';
            }
            
            // Mettre à jour le titre pour le primaire
            if (teachersSectionTitle) {
                teachersSectionTitle.textContent = 'Enseignant de la classe';
            }
            
            // Vider le conteneur existant
            if (teachersContainer) {
                teachersContainer.innerHTML = '';
            }
            
            // Ajouter automatiquement un seul enseignant pour le primaire
            this.addTeacherEntry(true); // true = mode primaire
            
            // Mettre à jour le texte d'aide
            this.updateTeacherInstructions('primaire');
        } else {
            // Pour collège/lycée : afficher le bouton + et permettre plusieurs enseignants
            if (addTeacherBtn) {
                addTeacherBtn.style.display = 'inline-block';
            }
            
            // Mettre à jour le titre pour le collège/lycée
            if (teachersSectionTitle) {
                teachersSectionTitle.textContent = 'Professeurs de la classe';
            }
            
            // Ne pas vider le conteneur pour le collège/lycée, garder les enseignants existants
            // if (teachersContainer) {
            //     teachersContainer.innerHTML = '';
            // }
            
            this.updateTeacherInstructions(data.cycle);
        }
    }
    
    updateTeacherInstructions(cycle) {
        const instructions = document.querySelector('.alert-info');
        if (instructions) {
            if (cycle === 'primaire') {
                instructions.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Instructions pour le primaire :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Au primaire, une classe a un seul enseignant généraliste</li>
                        <li>Cet enseignant enseigne toutes les matières de base</li>
                        <li>Sélectionnez l'enseignant titulaire de cette classe</li>
                    </ul>
                `;
            } else {
                instructions.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Instructions pour le ${cycle} :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Cliquez sur "Ajouter un professeur" pour associer des enseignants à cette classe</li>
                        <li>Sélectionnez des professeurs spécialisés (ils sont déjà liés à leurs matières)</li>
                        <li>Un professeur ne peut être sélectionné qu'une seule fois</li>
                        <li>Les professeurs qui enseignent la même matière sont automatiquement filtrés</li>
                        <li>Vous pouvez supprimer un professeur avec le bouton "×"</li>
                    </ul>
                `;
            }
        }
    }
    
    updateTeacherOptionsFromAPI(select, teachers) {
        console.log(`=== updateTeacherOptionsFromAPI ===`);
        console.log(`Mise à jour des options pour ${teachers.length} professeurs`);
        
        // Sauvegarder la valeur actuellement sélectionnée
        const currentValue = select.value;
        console.log('Valeur actuelle du select:', currentValue);
        
        // Récupérer les IDs des professeurs déjà sélectionnés (sauf celui de ce select)
        const selectedTeacherIds = Array.from(this.selectedTeachers.keys()).filter(id => id != currentValue);
        console.log('IDs des professeurs déjà sélectionnés (excluant le courant):', selectedTeacherIds);
        
        // Garder l'option par défaut
        const defaultOption = select.querySelector('option[value=""]');
        select.innerHTML = '';
        select.appendChild(defaultOption);

        if (teachers && teachers.length > 0) {
            teachers.forEach(teacher => {
                console.log(`Vérification du professeur ${teacher.first_name} ${teacher.last_name} (ID: ${teacher.id})`);
                
                // Vérifier si ce professeur est déjà sélectionné dans un autre select
                if (selectedTeacherIds.includes(teacher.id)) {
                    console.log(`❌ Professeur ${teacher.first_name} ${teacher.last_name} déjà sélectionné dans un autre select - IGNORÉ`);
                    return;
                }
                
                console.log(`✅ Professeur ${teacher.first_name} ${teacher.last_name} ajouté à la liste`);
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = `${teacher.first_name} ${teacher.last_name} (${teacher.teacher_type === 'general' ? 'Généraliste' : 'Spécialisé'})`;
                option.dataset.teacherType = teacher.teacher_type;
                option.dataset.specialization = teacher.specialization || '';
                select.appendChild(option);
            });
        }
        
        // Si aucune option n'a été ajoutée
        if (select.children.length === 1) {
            const noTeacherOption = document.createElement('option');
            noTeacherOption.value = '';
            noTeacherOption.textContent = 'Aucun professeur disponible';
            noTeacherOption.disabled = true;
            select.appendChild(noTeacherOption);
        }
        
        // Restaurer la valeur sélectionnée si elle existe toujours dans les options
        if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
            select.value = currentValue;
            console.log(`Valeur restaurée: ${currentValue}`);
            this.updateTeacherInfo(select);
        } else {
            console.log(`Valeur ${currentValue} non trouvée dans les nouvelles options`);
        }
    }
    
    loadTeachersForLevel(levelId) {
        console.log('Chargement des professeurs pour le niveau:', levelId);
        
        // Récupérer les professeurs depuis les données JSON (fallback)
        const teachers = CLASS_CREATION_DATA.teachers[levelId] || [];
        console.log('Professeurs trouvés:', teachers);
        
        // Mettre à jour tous les selects existants
        const teacherSelects = document.querySelectorAll('.teacher-select');
        teacherSelects.forEach(select => {
            this.updateTeacherOptions(select, teachers);
        });
    }
    
    updateTeacherOptions(select, teachers) {
        console.log(`=== updateTeacherOptions ===`);
        console.log(`Mise à jour des options pour ${teachers.length} professeurs`);
        
        // Sauvegarder la valeur actuellement sélectionnée
        const currentValue = select.value;
        console.log('Valeur actuelle du select:', currentValue);
        
        // Récupérer les IDs des professeurs déjà sélectionnés (sauf celui de ce select)
        const selectedTeacherIds = Array.from(this.selectedTeachers.keys()).filter(id => id != currentValue);
        console.log('IDs des professeurs déjà sélectionnés (excluant le courant):', selectedTeacherIds);
        console.log('Matières déjà sélectionnées:', Array.from(this.selectedSubjects));
        
        // Garder l'option par défaut
        const defaultOption = select.querySelector('option[value=""]');
        select.innerHTML = '';
        select.appendChild(defaultOption);

        if (teachers && teachers.length > 0) {
            teachers.forEach(teacher => {
                console.log(`Vérification du professeur ${teacher.first_name} ${teacher.last_name} (ID: ${teacher.id})`);
                console.log(`Matière du professeur: "${teacher.specialization}"`);
                
                // Vérifier si ce professeur est déjà sélectionné dans un autre select
                if (selectedTeacherIds.includes(teacher.id)) {
                    console.log(`❌ Professeur ${teacher.first_name} ${teacher.last_name} déjà sélectionné dans un autre select - IGNORÉ`);
                    return;
                }
                
                // Vérifier si ce professeur enseigne une matière déjà enseignée (sauf par lui-même)
                if (teacher.specialization && this.selectedSubjects.has(teacher.specialization)) {
                    // Vérifier si c'est lui-même qui enseigne cette matière
                    const currentTeacher = this.selectedTeachers.get(parseInt(currentValue));
                    if (!currentTeacher || currentTeacher.specialization !== teacher.specialization) {
                        console.log(`❌ Professeur ${teacher.first_name} ${teacher.last_name} enseigne ${teacher.specialization} déjà couverte - IGNORÉ`);
                        return;
                    }
                }
                
                console.log(`✅ Professeur ${teacher.first_name} ${teacher.last_name} ajouté à la liste`);
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = `${teacher.first_name} ${teacher.last_name} (${teacher.teacher_type})`;
                option.dataset.teacherType = teacher.teacher_type;
                option.dataset.specialization = teacher.specialization || '';
                select.appendChild(option);
            });
        }
        
        // Si aucune option n'a été ajoutée
        if (select.children.length === 1) {
            const noTeacherOption = document.createElement('option');
            noTeacherOption.value = '';
            noTeacherOption.textContent = 'Aucun professeur disponible';
            noTeacherOption.disabled = true;
            select.appendChild(noTeacherOption);
        }
        
        // Restaurer la valeur sélectionnée si elle existe toujours dans les options
        if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
            select.value = currentValue;
            console.log(`Valeur restaurée: ${currentValue}`);
            this.updateTeacherInfo(select);
        } else {
            console.log(`Valeur ${currentValue} non trouvée dans les nouvelles options`);
        }
    }
    
    addTeacherEntry(isPrimaryMode = false) {
        console.log('Fonction addTeacherEntry appelée, mode primaire:', isPrimaryMode);
        
        // Vérifier si un niveau est sélectionné
        const levelSelect = document.getElementById('level_id');
        if (!levelSelect || !levelSelect.value) {
            if (!isPrimaryMode) {
            alert('Veuillez d\'abord sélectionner un niveau avant d\'ajouter un professeur.');
            }
            return;
        }
        
        // Pour le primaire, vérifier qu'il n'y a pas déjà un enseignant (sauf si mode auto)
        if (this.isPrimaryLevel && !isPrimaryMode) {
            const teachersContainer = document.getElementById('teachersContainer');
            if (teachersContainer && teachersContainer.children.length >= 1) {
                alert('Au primaire, une classe ne peut avoir qu\'un seul enseignant généraliste.');
                return;
            }
        }
        
        this.teacherCounter++;
        console.log('Nouveau compteur:', this.teacherCounter);
        
        const teacherTemplate = document.getElementById('teacherTemplate');
        const teachersContainer = document.getElementById('teachersContainer');
        
        if (!teacherTemplate || !teachersContainer) {
            console.error('Template ou conteneur non trouvé');
            return;
        }
        
        const clone = teacherTemplate.content.cloneNode(true);
        console.log('Template cloné');
        
        // Mettre à jour le numéro du professeur
        const teacherNumber = clone.querySelector('.teacher-number');
        if (this.isPrimaryLevel) {
            teacherNumber.textContent = 'Principal';
        } else {
            // Pour collège/lycée : le premier professeur est "Principal"
            const existingTeachers = document.querySelectorAll('.teacher-entry');
            if (existingTeachers.length === 0) {
                teacherNumber.textContent = 'Principal';
            } else {
                teacherNumber.textContent = existingTeachers.length + 1;
            }
        }
        
        // Pour le primaire, masquer le bouton de suppression
        if (this.isPrimaryLevel) {
            const removeBtn = clone.querySelector('.remove-teacher');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }
        
        // Ajouter au conteneur
        teachersContainer.appendChild(clone);
        console.log('Entrée ajoutée au conteneur');
        
        // Mettre à jour les numéros des professeurs
        this.updateTeacherNumbers();
        
        // Mettre à jour les options du nouveau select avec les données déjà chargées
        const levelId = parseInt(levelSelect.value);
        const newTeacherSelect = teachersContainer.lastElementChild.querySelector('.teacher-select');
        if (newTeacherSelect && this.availableTeachers) {
            this.updateTeacherOptionsFromAPI(newTeacherSelect, this.availableTeachers);
        }
    }
    
    handleTeacherContainerClick(event) {
        if (event.target.closest('.remove-teacher')) {
            console.log('Bouton supprimer cliqué');
            const teacherEntry = event.target.closest('.teacher-entry');
            const teacherSelect = teacherEntry.querySelector('.teacher-select');
            
            // Retirer le professeur des sélections
            if (teacherSelect.value) {
                this.removeTeacherFromSelection(parseInt(teacherSelect.value));
            }
            
            teacherEntry.remove();
            this.updateTeacherNumbers();
            
            // Mettre à jour les options des autres sélecteurs après suppression
            this.updateAllTeacherOptions();
            
            // Vérifier s'il faut réactiver le bouton d'ajout
            this.checkAvailableTeachers();
        }
    }
    
    handleTeacherContainerChange(event) {
        if (event.target.classList.contains('teacher-select')) {
            this.onTeacherSelectionChange(event.target);
        }
    }
    
    handleTeacherContainerInput(event) {
        if (event.target.classList.contains('teacher-search')) {
            this.filterTeachers(event.target);
        }
    }
    
    onTeacherSelectionChange(select) {
        const teacherId = parseInt(select.value);
        const selectedOption = select.selectedOptions[0];
        
        if (teacherId && selectedOption) {
            // Ajouter le professeur aux sélections
            const teacher = {
                id: teacherId,
                name: selectedOption.textContent.split(' (')[0],
                type: selectedOption.dataset.teacherType,
                specialization: selectedOption.dataset.specialization || ''
            };
            
            this.selectedTeachers.set(teacherId, teacher);
            
            if (teacher.specialization) {
                this.selectedSubjects.add(teacher.specialization);
            }
            
            // Afficher le coefficient pour le lycée
            if (this.selectedLevel && this.selectedLevel.cycle === 'lycee' && this.selectedSeries) {
                const teacherEntry = select.closest('.teacher-entry');
                if (teacherEntry) {
                    displayCoefficientForTeacher(teacherEntry, this.selectedSeries);
                }
            }
            
            console.log('Professeur ajouté aux sélections:', teacher);
            console.log('Sélections actuelles:', Array.from(this.selectedTeachers.values()));
            console.log('Matières sélectionnées:', Array.from(this.selectedSubjects));
        } else {
            // Retirer le professeur des sélections
            if (teacherId) {
                this.removeTeacherFromSelection(teacherId);
            }
        }
        
        this.updateTeacherInfo(select);
        this.updateAllTeacherOptions();
        this.updateSelectedTeachersSummary();
        
        // Vérifier s'il reste des professeurs disponibles après la sélection
        this.checkAvailableTeachers();
    }
    
    removeTeacherFromSelection(teacherId) {
        const teacher = this.selectedTeachers.get(teacherId);
        if (teacher) {
            this.selectedTeachers.delete(teacherId);
            
            // Vérifier si d'autres professeurs enseignent la même matière
            const hasOtherTeacherForSubject = Array.from(this.selectedTeachers.values())
                .some(t => t.specialization === teacher.specialization);
            
            if (!hasOtherTeacherForSubject && teacher.specialization) {
                this.selectedSubjects.delete(teacher.specialization);
            }
            
            console.log('Professeur retiré des sélections:', teacher);
        }
    }
    
    updateTeacherInfo(select) {
        const teacherEntry = select.closest('.teacher-entry');
        const teacherInfo = teacherEntry.querySelector('.teacher-info');
        const teacherName = teacherEntry.querySelector('.teacher-name');
        const teacherType = teacherEntry.querySelector('.teacher-type');
        const teacherSpecialization = teacherEntry.querySelector('.teacher-specialization');

        if (select.value) {
            const selectedOption = select.selectedOptions[0];
            const teacherTypeValue = selectedOption.dataset.teacherType;
            const specialization = selectedOption.dataset.specialization;

            teacherName.textContent = selectedOption.textContent.split(' (')[0];
            teacherType.textContent = teacherTypeValue === 'general' ? 'Généraliste' : 'Spécialisé';
            
            // Ne pas afficher la spécialisation pour les enseignants de primaire/pré-primaire
            if (specialization && !this.isPrimaryLevel) {
                teacherSpecialization.textContent = ` - ${specialization}`;
            } else {
                teacherSpecialization.textContent = '';
            }

            teacherInfo.style.display = 'block';
        } else {
            teacherInfo.style.display = 'none';
        }
    }
    
    updateAllTeacherOptions() {
        if (!this.availableTeachers || this.availableTeachers.length === 0) return;
        
        const teacherSelects = document.querySelectorAll('.teacher-select');
        teacherSelects.forEach(select => {
            this.updateTeacherOptionsFromAPI(select, this.availableTeachers);
        });
        
        // Vérifier s'il reste des professeurs disponibles
        this.checkAvailableTeachers();
    }
    
    updateAllTeacherCoefficients() {
        if (!this.selectedSeries) return;
        
        const teacherEntries = document.querySelectorAll('.teacher-entry');
        teacherEntries.forEach(entry => {
            const teacherSelect = entry.querySelector('.teacher-select');
            if (teacherSelect && teacherSelect.value) {
                displayCoefficientForTeacher(entry, this.selectedSeries);
            }
        });
    }
    
    checkAvailableTeachers() {
        const addTeacherBtn = document.getElementById('addTeacherBtn');
        const teachersContainer = document.getElementById('teachersContainer');
        
        if (!addTeacherBtn || !teachersContainer || this.isPrimaryLevel) return;
        
        // Compter les professeurs déjà sélectionnés
        const selectedTeacherIds = Array.from(this.selectedTeachers.keys());
        const availableCount = this.availableTeachers.length;
        const selectedCount = selectedTeacherIds.length;
        
        console.log(`Professeurs disponibles: ${availableCount}, Sélectionnés: ${selectedCount}`);
        
        if (selectedCount >= availableCount) {
            // Tous les professeurs ont été assignés
            addTeacherBtn.disabled = true;
            addTeacherBtn.classList.remove('btn-success');
            addTeacherBtn.classList.add('btn-secondary');
            addTeacherBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Tous les professeurs assignés';
            
            // Afficher un message informatif
            this.showAllTeachersAssignedMessage();
        } else {
            // Il reste des professeurs disponibles
            addTeacherBtn.disabled = false;
            addTeacherBtn.classList.remove('btn-secondary');
            addTeacherBtn.classList.add('btn-success');
            addTeacherBtn.innerHTML = '<i class="bi bi-plus me-1"></i>Ajouter un professeur';
            
            // Masquer le message si il existe
            this.hideAllTeachersAssignedMessage();
        }
    }
    
    showAllTeachersAssignedMessage() {
        // Vérifier si le message existe déjà
        let messageDiv = document.getElementById('allTeachersAssignedMessage');
        if (messageDiv) return;
        
        // Créer le message
        messageDiv = document.createElement('div');
        messageDiv.id = 'allTeachersAssignedMessage';
        messageDiv.className = 'alert alert-info mt-3';
        messageDiv.innerHTML = `
            <i class="bi bi-info-circle me-2"></i>
            <strong>Tous les professeurs de la classe ont été assignés !</strong>
            <br><small class="text-muted">Vous pouvez maintenant procéder à la création de la classe.</small>
        `;
        
        // Insérer après le conteneur des professeurs
        const teachersContainer = document.getElementById('teachersContainer');
        if (teachersContainer) {
            teachersContainer.parentNode.insertBefore(messageDiv, teachersContainer.nextSibling);
        }
    }
    
    hideAllTeachersAssignedMessage() {
        const messageDiv = document.getElementById('allTeachersAssignedMessage');
        if (messageDiv) {
            messageDiv.remove();
        }
    }
    
    updateSelectedTeachersSummary() {
        const selectedTeachers = Array.from(this.selectedTeachers.values());
        const summaryContainer = document.getElementById('selectedTeachersSummary');
        const summaryRow = document.getElementById('teachersSummaryRow');
        
        if (!summaryContainer || !summaryRow) {
            console.error('Conteneur de résumé non trouvé');
            return;
        }
        
        if (selectedTeachers.length === 0) {
            // Masquer la section du résumé
            summaryRow.style.display = 'none';
            summaryContainer.innerHTML = '<p class="mb-0 text-muted">Aucun professeur sélectionné</p>';
        } else {
            // Afficher la section du résumé
            summaryRow.style.display = 'block';
            
            let summaryHTML = '<ul class="mb-0">';
            selectedTeachers.forEach((teacher, index) => {
                const typeLabel = teacher.type === 'general' ? 'Généraliste' : 'Spécialisé';
                // Ne pas afficher la spécialisation pour les enseignants de primaire/pré-primaire
                const subjectInfo = (teacher.specialization && !this.isPrimaryLevel) ? ` - ${teacher.specialization}` : '';
                
                // Marquer le premier professeur comme "Principal" pour tous les niveaux
                let principalLabel = '';
                if (index === 0) {
                    principalLabel = ' (Professeur Principal)';
                }
                
                summaryHTML += `<li><strong>${teacher.name}</strong>${principalLabel} - ${typeLabel}${subjectInfo}</li>`;
            });
            summaryHTML += '</ul>';
            summaryContainer.innerHTML = summaryHTML;
        }
    }
    
    updateTeacherNumbers() {
        const teacherEntries = document.querySelectorAll('.teacher-entry');
        console.log(`Mise à jour des numéros pour ${teacherEntries.length} professeurs`);
        
        teacherEntries.forEach((entry, index) => {
            const teacherNumber = entry.querySelector('.teacher-number');
            if (teacherNumber) {
                // Pour le primaire, garder "Principal", sinon numéroter
                if (this.isPrimaryLevel) {
                    teacherNumber.textContent = 'Principal';
                } else {
                    // Pour collège/lycée : le premier est "Principal", les autres numérotés
                    if (index === 0) {
                        teacherNumber.textContent = 'Principal';
                    } else {
                        teacherNumber.textContent = index;
                    }
                }
                console.log(`Professeur ${index + 1} mis à jour`);
            }
        });
        
        // Mettre à jour le résumé des professeurs sélectionnés
        this.updateSelectedTeachersSummary();
    }
    
    filterTeachers(searchInput) {
        const teacherEntry = searchInput.closest('.teacher-entry');
        const teacherSelect = teacherEntry.querySelector('.teacher-select');
        const searchTerm = searchInput.value.toLowerCase();
        
        // Récupérer toutes les options originales
        const allOptions = Array.from(teacherSelect.querySelectorAll('option'));
        
        // Filtrer les options
        allOptions.forEach(option => {
            if (option.value === '') {
                // Garder l'option par défaut toujours visible
                option.style.display = '';
            } else {
                const teacherName = option.textContent.toLowerCase();
                if (teacherName.includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
    }
    
    validateForm(event) {
        const teacherEntries = document.querySelectorAll('.teacher-entry');
        const nameInput = document.getElementById('name');
        let isValid = true;

        // Le nom est directement dans le champ principal, pas besoin de copie

        // Vérifier qu'un nom de classe est généré
        if (!nameInput || !nameInput.value.trim()) {
            alert('Veuillez sélectionner un niveau pour générer automatiquement le nom de la classe.');
            isValid = false;
        }

        // Vérifier qu'au moins un professeur est sélectionné
        if (teacherEntries.length === 0) {
            alert('Veuillez ajouter au moins un professeur à la classe.');
            isValid = false;
        }

        // Vérifier que tous les professeurs sélectionnés sont valides
        teacherEntries.forEach(entry => {
            const teacherSelect = entry.querySelector('.teacher-select');
            
            if (!teacherSelect.value) {
                alert('Veuillez sélectionner un professeur pour toutes les entrées.');
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
        } else {
            // Afficher le spinner et désactiver le bouton
            this.showSubmitSpinner();
            
            // Debug : afficher les données qui vont être envoyées
            console.log('=== DONNÉES DU FORMULAIRE ===');
            const formData = new FormData(event.target);
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
        }
    }

    showSubmitSpinner() {
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnContent = document.getElementById('submitBtnContent');
        const submitBtnSpinner = document.getElementById('submitBtnSpinner');
        
        if (submitBtn && submitBtnContent && submitBtnSpinner) {
            submitBtn.disabled = true;
            submitBtnContent.classList.add('d-none');
            submitBtnSpinner.classList.remove('d-none');
        }
    }

    hideSubmitSpinner() {
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnContent = document.getElementById('submitBtnContent');
        const submitBtnSpinner = document.getElementById('submitBtnSpinner');
        
        if (submitBtn && submitBtnContent && submitBtnSpinner) {
            submitBtn.disabled = false;
            submitBtnContent.classList.remove('d-none');
            submitBtnSpinner.classList.add('d-none');
        }
    }
}

// Fonction pour récupérer le coefficient selon la série et la matière
function getCoefficientForSeries(seriesCode, subjectName) {
    if (!seriesCode || !subjectName) return 1;
    
    const seriesData = SERIES_COEFFICIENTS[seriesCode];
    if (!seriesData) return 1;
    
    return seriesData[subjectName] || 1;
}

// Fonction pour afficher le coefficient dans l'interface
function displayCoefficientForTeacher(teacherEntry, seriesCode) {
    const teacherSelect = teacherEntry.querySelector('.teacher-select');
    const selectedOption = teacherSelect.options[teacherSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const specialization = selectedOption.getAttribute('data-specialization');
        if (specialization && seriesCode) {
            const coefficient = getCoefficientForSeries(seriesCode, specialization);
            
            // Afficher le coefficient dans l'interface
            let coefficientDisplay = teacherEntry.querySelector('.coefficient-display');
            if (!coefficientDisplay) {
                coefficientDisplay = document.createElement('div');
                coefficientDisplay.className = 'coefficient-display mt-2 p-2 bg-info text-white rounded';
                teacherEntry.appendChild(coefficientDisplay);
            }
            
            coefficientDisplay.innerHTML = `
                <small>
                    <i class="bi bi-calculator me-1"></i>
                    Coefficient pour la série ${seriesCode}: <strong>${coefficient}</strong>
                </small>
            `;
        }
    }
}

// Initialiser le gestionnaire quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de création de classe chargé');
    new ClassCreationManager();
});
