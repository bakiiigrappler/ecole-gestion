@extends('layouts.app')

@section('title', 'Modifier la Matière - Egesco')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('subjects.index') }}">Matières</a></li>
<li class="breadcrumb-item active">Modifier {{ $subject->name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Modifier la matière : {{ $subject->name }}
                    </h4>
                </div>
                <div class="card-body">
                    <form id="subjectForm" method="POST" action="{{ route('subjects.update', $subject) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Cycle et séries -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-1-circle me-2"></i>
                                    Étape 1 : Cycle et séries
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="cycle" class="form-label">Cycle *</label>
                                    <select class="form-select" id="cycle" name="cycle" required>
                                        <option value="">Sélectionner un cycle</option>
                                        <option value="primaire" {{ $subject->cycle === 'primaire' ? 'selected' : '' }}>Primaire</option>
                                        <option value="college" {{ $subject->cycle === 'college' ? 'selected' : '' }}>Collège</option>
                                        <option value="lycee" {{ $subject->cycle === 'lycee' ? 'selected' : '' }}>Lycée</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Choisissez d'abord le cycle pour voir les matières disponibles.
                                    </div>
                                </div>
                                
                                <!-- Séries pour le lycée -->
                                <div class="mb-3 {{ $subject->cycle === 'lycee' ? '' : 'd-none' }}" id="seriesField">
                                    <label class="form-label">Séries concernées (Lycée uniquement)</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            @php
                                                $subjectSeries = is_array($subject->series) ? $subject->series : [];
                                            @endphp
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="S" id="series_S" 
                                                       {{ in_array('S', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_S">S - Scientifique</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="A1" id="series_A1"
                                                       {{ in_array('A1', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_A1">A1 - Lettres-Langues anciennes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="A2" id="series_A2"
                                                       {{ in_array('A2', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_A2">A2 - Lettres-Langues vivantes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="B" id="series_B"
                                                       {{ in_array('B', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_B">B - Sciences économiques</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="C" id="series_C"
                                                       {{ in_array('C', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_C">C - Mathématiques-Sciences</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="D" id="series_D"
                                                       {{ in_array('D', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_D">D - Sciences naturelles</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="E" id="series_E"
                                                       {{ in_array('E', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_E">E - Techniques industrielles</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[]" value="LE" id="series_LE"
                                                       {{ in_array('LE', $subjectSeries) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="series_LE">LE - Lettres modernes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Sélectionnez toutes les séries où cette matière est enseignée.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Statut</label>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1" {{ $subject->is_active ? 'selected' : '' }}>Actif</option>
                                        <option value="0" {{ !$subject->is_active ? 'selected' : '' }}>Inactif</option>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note :</strong> 
                                    <ul class="mb-0 mt-2">
                                        <li>Pour primaire et collège : la matière s'applique à tout le cycle</li>
                                        <li>Pour lycée : sélectionnez les séries concernées</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Informations de base -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-2-circle me-2"></i>
                                    Étape 2 : Informations de la matière
                                </h5>
                                
                                <div class="mb-3" id="subjectSelectionField">
                                    <label for="predefined_subject" class="form-label">Matières suggérées</label>
                                    <select class="form-select" id="predefined_subject">
                                        <option value="">Choisir une matière courante...</option>
                                        <!-- Options dynamiques selon le cycle -->
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Sélectionnez une matière suggérée ou modifiez le nom ci-dessous.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom de la matière *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="{{ $subject->name }}" placeholder="Saisir le nom de la matière..." required>
                                    <div class="form-text text-success" id="nameFieldHelp">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Vous pouvez modifier le nom de la matière.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="code" class="form-label">Code</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                        <input type="text" class="form-control" id="code" name="code" 
                                               value="{{ $subject->code }}" readonly style="background-color: #f8f9fa;">
                                        <input type="hidden" id="code_hidden" name="code" value="{{ $subject->code }}">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-magic me-1 text-primary"></i>
                                        <span class="text-primary">Code généré automatiquement</span> - Il sera mis à jour lors de l'enregistrement
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="coefficient" class="form-label">Coefficient *</label>
                                    <input type="number" class="form-control" id="coefficient" name="coefficient" 
                                           value="{{ $subject->coefficient }}" min="0" step="0.5" required>
                                    <div class="form-text">
                                        <i class="bi bi-calculator me-1"></i>
                                        Détermine l'importance de la matière dans le calcul des moyennes.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Description optionnelle de la matière...">{{ $subject->description }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('subjects.index') }}" class="btn btn-secondary">
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
    const seriesField = document.getElementById('seriesField');
    const subjectSelectionField = document.getElementById('subjectSelectionField');
    const predefinedSubjectSelect = document.getElementById('predefined_subject');
    const nameInput = document.getElementById('name');
    const codeInput = document.getElementById('code');
    const coefficientInput = document.getElementById('coefficient');
    
    // Définir les matières par cycle (même que create)
    const subjectsByCycle = {
        'primaire': [
            'Français', 'Mathématiques', 'Sciences d\'observation', 'Histoire-Géographie', 
            'Instruction civique', 'Éducation physique et sportive'
        ],
        'college': [
            'Français', 'Mathématiques', 'Anglais', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
            'Histoire-Géographie', 'Éducation civique', 'Arts plastiques', 'Musique', 'Technologie',
            'Éducation physique et sportive'
        ],
        'lycee': [
            'Français', 'Anglais', 'Histoire-Géographie', 'Éducation civique', 'Éducation physique et sportive',
            'Philosophie', 'Mathématiques', 'Sciences physiques', 'Sciences de la Vie et de la Terre',
            'Mathématiques spécialisées', 'Biologie approfondie', 'Littérature', 'Latin', 'Grec', 'Espagnol',
            'Sciences économiques et sociales', 'Comptabilité', 'Mathématiques appliquées',
            'Technologie industrielle', 'Dessin technique'
        ]
    };
    
    // Coefficients suggérés par matière
    const coefficients = {
        'Français': 4, 'Mathématiques': 4, 'Sciences physiques': 3, 'Histoire-Géographie': 3,
        'Anglais': 3, 'Sciences de la Vie et de la Terre': 3, 'Philosophie': 4,
        'Sciences économiques et sociales': 5, 'Éducation physique et sportive': 1,
        'Littérature': 4, 'Latin': 3, 'Grec': 3, 'Espagnol': 3, 'Comptabilité': 4,
        'Technologie industrielle': 6, 'Dessin technique': 4
    };
    
    // Initialiser les matières suggérées au chargement
    function initializePredefinedSubjects() {
        const currentCycle = cycleSelect.value;
        if (currentCycle && subjectsByCycle[currentCycle]) {
            updatePredefinedSubjects(currentCycle);
        }
    }
    
    // Mettre à jour les matières suggérées selon le cycle
    function updatePredefinedSubjects(cycle) {
        predefinedSubjectSelect.innerHTML = '<option value="">Choisir une matière courante...</option>';
        
        if (subjectsByCycle[cycle]) {
            subjectsByCycle[cycle].forEach(subject => {
                const option = document.createElement('option');
                option.value = subject;
                option.textContent = subject;
                predefinedSubjectSelect.appendChild(option);
            });
        }
    }
    
    // Gestion du changement de cycle
    cycleSelect.addEventListener('change', function() {
        const selectedCycle = this.value;
        
        if (selectedCycle === 'lycee') {
            seriesField.classList.remove('d-none');
        } else {
            seriesField.classList.add('d-none');
            clearSeriesSelection();
        }
        
        updatePredefinedSubjects(selectedCycle);
        generateCode(nameInput.value);
    });
    
    // Gestion du select de matières prédéfinies
    predefinedSubjectSelect.addEventListener('change', function() {
        if (this.value) {
            nameInput.value = this.value;
            generateCode(this.value);
            
            if (coefficients[this.value]) {
                coefficientInput.value = coefficients[this.value];
            }
        }
    });
    
    // Générer automatiquement le code
    function generateCode(subjectName) {
        const cycle = cycleSelect.value;
        let prefix = '';
        
        switch(cycle) {
            case 'primaire': prefix = 'PRIM_'; break;
            case 'college': prefix = 'COL_'; break;
            case 'lycee': prefix = 'LYC_'; break;
        }
        
        const cleanName = subjectName.toUpperCase()
            .replace(/[ÀÂÄÃ]/g, 'A')
            .replace(/[ÈÊËÉ]/g, 'E')
            .replace(/[ÎÏÌ]/g, 'I')
            .replace(/[ÔÖÒÕ]/g, 'O')
            .replace(/[ÛÜÙÚ]/g, 'U')
            .replace(/[^A-Z]/g, '')
            .substring(0, 6);
        
        const generatedCode = prefix + cleanName;
        codeInput.value = generatedCode;
        document.getElementById('code_hidden').value = generatedCode;
    }
    
    // Générer le code quand on tape dans le nom
    nameInput.addEventListener('input', function() {
        if (this.value && cycleSelect.value) {
            generateCode(this.value);
        }
    });
    
    // Décocher toutes les séries
    function clearSeriesSelection() {
        const seriesCheckboxes = seriesField.querySelectorAll('input[type="checkbox"]');
        seriesCheckboxes.forEach(checkbox => checkbox.checked = false);
    }
    
    // Validation et soumission du formulaire
    document.getElementById('subjectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const cycle = cycleSelect.value;
        if (!cycle) {
            alert('Veuillez sélectionner un cycle.');
            return;
        }
        
        if (cycle === 'lycee') {
            const checkedSeries = seriesField.querySelectorAll('input[type="checkbox"]:checked');
            if (checkedSeries.length === 0) {
                alert('Veuillez sélectionner au moins une série pour les matières du lycée.');
                return;
            }
        }
        
        if (!nameInput.value.trim()) {
            alert('Veuillez saisir un nom de matière.');
            nameInput.focus();
            return;
        }
        
        // Mettre à jour le code dans le champ hidden
        document.getElementById('code_hidden').value = codeInput.value;
        
        const formData = new FormData(this);
        // Remplacer le code par celui généré
        formData.set('code', codeInput.value);
        
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
                alert(data.message);
                window.location.href = '{{ route("subjects.index") }}';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la mise à jour.');
        });
    });
    
    // Initialiser
    initializePredefinedSubjects();
});
</script>
@endsection
