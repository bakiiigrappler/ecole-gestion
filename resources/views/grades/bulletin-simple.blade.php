@extends('layouts.app')

@section('title', 'Bulletin de ' . $student->first_name . ' ' . $student->last_name)

@section('content')
<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-alt"></i>
                        Bulletin de {{ $student->first_name }} {{ $student->last_name }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('grades.index') }}">Notes</a></li>
                            <li class="breadcrumb-item active">Bulletin</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('grades.bulletin', $student->id) }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-file-alt"></i> Bulletin Classique
                    </a>
                    <a href="{{ route('grades.bulletin.carte', $student->id) }}" class="btn btn-outline-info me-2">
                        <i class="fas fa-id-card"></i> Bulletin Carte
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations de l'élève -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user"></i>
                Informations de l'élève
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nom :</strong> {{ $student->first_name }} {{ $student->last_name }}</p>
                    <p><strong>Classe :</strong> {{ $class->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Niveau :</strong> {{ $class->level->name ?? 'N/A' }}</p>
                    <p><strong>Année scolaire :</strong> {{ $academicYear->name ?? '2024-2025' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets des trimestres -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="trimesterTabs" role="tablist">
                @foreach(['1er trimestre', '2ème trimestre', '3ème trimestre'] as $index => $trimester)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                            id="{{ str_replace(' ', '-', $trimester) }}-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#{{ str_replace(' ', '-', $trimester) }}" 
                            type="button" 
                            role="tab">
                        {{ $trimester }}
                        @if(isset($trimesterData[$trimester]) && $trimesterData[$trimester]['is_available'])
                            <span class="badge bg-success ms-2">Disponible</span>
                        @else
                            <span class="badge bg-secondary ms-2">Non disponible</span>
                        @endif
                    </button>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="trimesterTabsContent">
                @foreach(['1er trimestre', '2ème trimestre', '3ème trimestre'] as $index => $trimester)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                     id="{{ str_replace(' ', '-', $trimester) }}" 
                     role="tabpanel">
                    
                    @if(isset($trimesterData[$trimester]) && $trimesterData[$trimester]['is_available'])
                        <!-- Contenu du trimestre disponible -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Matière</th>
                                        <th>Note</th>
                                        <th>Coefficient</th>
                                        <th>Appréciation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trimesterData[$trimester]['subjects'] as $subject)
                                    <tr>
                                        <td>{{ $subject['name'] }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($subject['average'] >= 16) bg-success
                                                @elseif($subject['average'] >= 14) bg-info
                                                @elseif($subject['average'] >= 10) bg-warning
                                                @else bg-danger
                                                @endif">
                                                {{ $subject['average'] }}/20
                                            </span>
                                        </td>
                                        <td>{{ $subject['coefficient'] }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($subject['average'] >= 16) bg-success
                                                @elseif($subject['average'] >= 14) bg-info
                                                @elseif($subject['average'] >= 10) bg-warning
                                                @else bg-danger
                                                @endif">
                                                {{ $subject['appreciation'] ?? 'N/C' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Résumé du trimestre -->
                        <div class="alert alert-info mt-3">
                            <h6>Résumé du trimestre :</h6>
                            <p><strong>Moyenne trimestrielle :</strong> {{ $trimesterData[$trimester]['cumulative_score'] }}/20</p>
                            <p><strong>Nombre de matières :</strong> {{ $trimesterData[$trimester]['total_subjects'] }}</p>
                            <div class="mt-2">
                                <button onclick="showBulletinModal('{{ $trimester }}')" class="btn btn-sm btn-primary me-2">
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                                <button onclick="generatePDF('{{ $trimester }}')" class="btn btn-sm btn-warning">
                                    <i class="fas fa-download"></i> PDF
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Trimestre non disponible -->
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Trimestre non disponible</h5>
                            <p class="text-muted">Les notes pour ce trimestre ne sont pas encore disponibles.</p>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Boutons d'action -->
    <div class="text-center mt-4">
        <a href="{{ route('grades.create', ['student_id' => $student->id]) }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter une note
        </a>
        <a href="{{ route('grades.manage-student', $student->id) }}" class="btn btn-info">
            <i class="fas fa-edit"></i> Gérer les notes
        </a>
        <a href="{{ route('students.show', $student->id) }}" class="btn btn-secondary">
            <i class="fas fa-user"></i> Profil de l'élève
        </a>
        
        <!-- Boutons de téléchargement PDF par trimestre -->
        <div class="btn-group mt-2">
            <button type="button" class="btn btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download"></i> Télécharger PDF
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="showBulletinModal('1er trimestre')">
                    <i class="fas fa-eye me-2"></i>Voir 1er Trimestre
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="showBulletinModal('2ème trimestre')">
                    <i class="fas fa-eye me-2"></i>Voir 2ème Trimestre
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="showBulletinModal('3ème trimestre')">
                    <i class="fas fa-eye me-2"></i>Voir 3ème Trimestre
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="generatePDF('1er trimestre')">
                    <i class="fas fa-file-pdf me-2"></i>Télécharger 1er Trimestre
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="generatePDF('2ème trimestre')">
                    <i class="fas fa-file-pdf me-2"></i>Télécharger 2ème Trimestre
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="generatePDF('3ème trimestre')">
                    <i class="fas fa-file-pdf me-2"></i>Télécharger 3ème Trimestre
                </a></li>
            </ul>
        </div>
    </div>
</div>
<!-- Modal pour l'aperçu du bulletin -->
<div class="modal fade bulletin-modal" id="bulletinModal" tabindex="-1" aria-labelledby="bulletinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulletinModalLabel">
                    <i class="fas fa-file-alt"></i> Bulletin de {{ $student->first_name }} {{ $student->last_name }} - <span id="currentTrimester">1er trimestre</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="bulletinContent">
                    <!-- Le contenu du bulletin sera généré dynamiquement ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
                <button onclick="generatePDFFromModal()" class="btn btn-success">
                    <i class="fas fa-download"></i> Télécharger PDF
                </button>
                <button onclick="printPDFFromModal()" class="btn btn-info">
                    <i class="fas fa-print"></i> Imprimer PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
let currentTrimester = '1er trimestre';
let bulletinData = {!! json_encode($trimesterData) !!};

// Debug: afficher les données dans la console
console.log('Bulletin Data:', bulletinData);

// Fonction pour afficher le modal du bulletin
function showBulletinModal(trimester) {
    currentTrimester = trimester;
    document.getElementById('currentTrimester').textContent = trimester;
    
    // Générer le contenu du bulletin pour le trimestre sélectionné
    generateBulletinContent(trimester);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('bulletinModal'));
    modal.show();
}

// Fonction pour générer le contenu du bulletin
function generateBulletinContent(trimester) {
    const trimesterData = bulletinData[trimester];
    const bulletinContent = document.getElementById('bulletinContent');
    
    if (!trimesterData || !trimesterData.is_available) {
        bulletinContent.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Trimestre non disponible</h5>
                <p class="text-muted">Les notes pour ce trimestre ne sont pas encore disponibles.</p>
            </div>
        `;
        return;
    }
    
    // Générer le HTML du bulletin (similaire à show.blade.php)
    bulletinContent.innerHTML = generateBulletinHTML(trimester, trimesterData);
}

// URLs des images
const logoEcoleUrl = "{{ asset('images/logo-ecole.svg') }}";
const sceauRepubliqueUrl = "{{ asset('images/sceau-221128112237.png') }}";

// Fonction pour générer le HTML du bulletin
function generateBulletinHTML(trimester, data) {
    return `
        <div class="bulletin-page">
            <!-- HEADER -->
            <div class="bulletin-header">
                <div class="header-left">
                    <div class="school-logo">
                        <img src="${logoEcoleUrl}" alt="Logo École" style="max-height: 60px; max-width: 80px;">
                    </div>
                    <div class="school-info">
                        <div class="school-line">Établissement Scolaire</div>
                        <div class="contact-line">BP: 6, Téléphone: 06037499</div>
                    </div>
                </div>
                <div class="header-center">
                    <div class="bulletin-title">BULLETIN - ${trimester.toUpperCase()}</div>
                </div>
                <div class="header-right">
                    <div class="republic-seal">
                        <img src="${sceauRepubliqueUrl}" alt="Sceau République Gabonaise" style="max-height: 60px; max-width: 80px;">
                    </div>
                    <div class="year-info">
                        <div class="ministry-line">Ministère de l'Education Nationale</div>
                        <div class="year-line">Année Scolaire : 2024-2025</div>
                    </div>
                </div>
            </div>

            <!-- SECTION ÉTUDIANT -->
            <table class="student-info-table">
                <tr>
                    <td rowspan="3" class="photo-cell">
                        <div class="photo-placeholder">Photo<br>de<br>l'élève</div>
                    </td>
                    <td colspan="5" class="name-cell">
                        <strong>{{ strtoupper($student->last_name) }} {{ $student->first_name }} [{{ $student->student_id ?? 'STU' . str_pad($student->id, 6, '0', STR_PAD_LEFT) }}]</strong>
                    </td>
                </tr>
                <tr>
                    <td class="info-cell"><strong>Né(e) le :</strong> {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/C' }}</td>
                    <td class="info-cell"><strong>Lieu de naissance:</strong> {{ $student->place_of_birth ?? $student->birth_place ?? 'N/C' }}</td>
                    <td class="info-cell"><strong>Sexe :</strong> {{ ucfirst($student->gender ?? 'N/C') }} | Statut: [T]</td>
                </tr>
                <tr>
                    <td class="info-cell"><strong>Classe :</strong> {{ $class->name ?? 'N/C' }}</td>
                    <td class="info-cell"><strong>Effectif :</strong> {{ $totalStudents ?? 'N/C' }} élèves</td>
                    <td class="info-cell"><strong>Nationalité :</strong> Gabonaise</td>
                </tr>
            </table>

            <!-- TABLEAU DES NOTES -->
            <div class="grades-section">
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>DISCIPLINES</th>
                            <th>MOYENNE<br>Apprenant</th>
                            <th>COEF</th>
                            <th>NOTE X<br>COEF</th>
                            <th>RANG</th>
                            <th>ABSENCES</th>
                            <th>Appréciation</th>
                            <th>Professeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.subjects.map(subject => `
                            <tr>
                                <td class="subject-cell">${subject.name}</td>
                                <td><span class="green-triangle">▲</span> ${subject.average > 0 ? subject.average : 'N/C'}</td>
                                <td>${subject.coefficient}</td>
                                <td>${subject.average > 0 ? (subject.average * subject.coefficient).toFixed(1) : 'N/C'}</td>
                                <td>${subject.rank || 'N/C'}</td>
                                <td>0h00</td>
                                <td>${subject.appreciation || getAppreciation(subject.average)}</td>
                                <td>${subject.teacher_name || 'N/C'}</td>
                            </tr>
                        `).join('')}
                        <tr class="totals-row">
                            <td class="subject-cell"><strong>TOTAUX</strong></td>
                            <td><strong>${data.cumulative_score > 0 ? data.cumulative_score : 'N/C'}</strong></td>
                            <td><strong>${data.subjects.reduce((sum, s) => sum + s.coefficient, 0)}</strong></td>
                            <td><strong>${data.subjects.reduce((sum, s) => sum + (s.average * s.coefficient), 0).toFixed(1)}</strong></td>
                            <td>${data.rank || 'N/C'}</td>
                            <td>0h00</td>
                            <td colspan="2"><strong>Moyenne trimestrielle: ${data.cumulative_score > 0 ? data.cumulative_score : 'N/C'}</strong> <span class="green-triangle">▲</span></td>
                        </tr>
                    </tbody>
                </table>

                <!-- MOYENNE TRIMESTRIELLE -->
                <div class="moyenne-trimestre">
                    Moyenne trimestrielle: ${data.cumulative_score > 0 ? data.cumulative_score : 'N/C'} <span class="green-triangle">▲</span>
                </div>
            </div>

            <!-- SECTIONS INFÉRIEURES -->
            <div class="bottom-sections">
                <!-- PROFIL DE LA CLASSE -->
                <div class="profil-section">
                    <div class="section-title">PROFIL DE LA CLASSE</div>
                    <div class="profil-item">
                        <span>Moyenne de la classe</span>
                        <span>{{ $classProfile['moyenne_classe'] ?? 'N/C' }}</span>
                    </div>
                    <div class="profil-item" style="margin-top: 8px;">
                        <span><strong>PROFESSEUR PRINCIPAL</strong></span>
                    </div>
                    <div class="profil-item">
                        <span><strong>PRINCIPAL</strong></span>
                        <span><strong>{{ $principalTeacherName ?? 'N/C' }}</strong></span>
                    </div>
                </div>

                <!-- BILAN -->
                <div class="bilan-section">
                    <div class="section-title">BILAN</div>
                    <table class="bilan-table">
                        <thead>
                            <tr>
                                <th>Moyenne</th>
                                <th>Apprenant</th>
                                <th>Classe Rang</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${generateBilanRows(trimester, data)}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DÉCISION -->
            <div class="decision-section">
                <div class="decision-title">DECISION DU CONSEIL DE CLASSE</div>
                <div class="decision-items">
                    <span>Conduite : NC</span>
                    <span>Travail : Assez Bien/TH</span>
                    <span>Fréquentation : A suivre</span>
                </div>
                <div class="admission-section">
                    ${generateFinalDecision(trimester, data)}
                    <div class="decision-date">{{ date('d-m-Y') }}</div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="footer-section">
                <div class="left-seal-area">
                    <div class="barcode-section">
                        <img src="https://quickchart.io/barcode?type=code128&text={{ date('Y') . str_pad($student->id, 4, '0', STR_PAD_LEFT) . str_pad(substr(time(), -4), 4, '0', STR_PAD_LEFT) }}&includeText=true&width=3&height=50" 
                             alt="Code Barre" 
                             style="max-width: 200px; height: 50px; border: 1px solid #ccc;"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjUwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNmMGYwZjAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9Im1vbm9zcGFjZSIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzMzMyIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPio8L3RleHQ+PC90ZXh0Pjwvc3ZnPg=='">
                    </div>
                    <div class="bulletin-code" style="font-size: 10px; margin-top: 5px;">*{{ date('Y') . str_pad($student->id, 4, '0', STR_PAD_LEFT) . str_pad(substr(time(), -4), 4, '0', STR_PAD_LEFT) }}*</div>
                </div>
                
                <div class="center-area">
                    <div class="conseil-text">* Conseil de Classe</div>
                </div>
                
                <div class="right-seal-area">
                    <div class="seal-title">Le Proviseur,</div>
                    <div class="official-seal-right">
                        <!-- Zone blanche pour le cachet -->
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Fonction pour obtenir l'appréciation
function getAppreciation(score) {
    if (score >= 16) return 'Excellent';
    if (score >= 14) return 'Très bien';
    if (score >= 12) return 'Bien';
    if (score >= 10) return 'Assez bien';
    if (score >= 8) return 'Passable';
    return 'Insuffisant';
}

// Fonction pour générer les lignes du bilan avec évolution
function generateBilanRows(trimester, currentData) {
    const trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
    const currentIndex = trimesters.indexOf(trimester);
    
    let rows = '';
    
    // Afficher tous les trimestres précédents et le trimestre actuel
    for (let i = 0; i <= currentIndex; i++) {
        const currentTrimester = trimesters[i];
        const trimesterData = bulletinData[currentTrimester];
        
        if (trimesterData && trimesterData.is_available) {
            let evolutionIcon = '';
            
            // Ajouter l'icône d'évolution si ce n'est pas le premier trimestre
            if (i > 0) {
                const previousTrimester = trimesters[i - 1];
                const previousData = bulletinData[previousTrimester];
                
                if (previousData && previousData.is_available) {
                    const currentScore = trimesterData.cumulative_score;
                    const previousScore = previousData.cumulative_score;
                    
                    if (currentScore > previousScore) {
                        evolutionIcon = ' <span class="evolution-arrow evolution-up">↗</span>';
                    } else if (currentScore < previousScore) {
                        evolutionIcon = ' <span class="evolution-arrow evolution-down">↘</span>';
                    }
                    // Si égal, pas d'icône
                }
            }
            
            rows += `
                <tr>
                    <td>${currentTrimester}</td>
                    <td>${trimesterData.cumulative_score > 0 ? trimesterData.cumulative_score : 'N/C'}${evolutionIcon}</td>
                    <td>N/C ${trimesterData.rank || 'N/C'}</td>
                </tr>
            `;
        } else {
            rows += `
                <tr>
                    <td>${currentTrimester}</td>
                    <td>N/C</td>
                    <td>N/C</td>
                </tr>
            `;
        }
    }
    
    // Ajouter la ligne de moyenne générale pour le 3ème trimestre
    if (trimester === '3ème trimestre') {
        const averageScore = calculateGeneralAverage();
        rows += `
            <tr style="font-weight: bold; background: #f0f8ff;">
                <td>MOYENNE GÉNÉRALE</td>
                <td>${averageScore > 0 ? averageScore.toFixed(2) : 'N/C'}</td>
                <td>N/C</td>
            </tr>
        `;
    }
    
    return rows;
}

// Fonction pour calculer la moyenne générale
function calculateGeneralAverage() {
    const trimesters = ['1er trimestre', '2ème trimestre', '3ème trimestre'];
    let totalScore = 0;
    let validTrimesters = 0;
    
    trimesters.forEach(trimester => {
        const trimesterData = bulletinData[trimester];
        if (trimesterData && trimesterData.is_available && trimesterData.cumulative_score > 0) {
            totalScore += trimesterData.cumulative_score;
            validTrimesters++;
        }
    });
    
    return validTrimesters > 0 ? totalScore / validTrimesters : 0;
}

// Fonction pour générer la décision finale
function generateFinalDecision(trimester, currentData) {
    if (trimester === '3ème trimestre') {
        const generalAverage = calculateGeneralAverage();
        
        if (generalAverage >= 10) {
            return `
                <div class="admission-badge admission-passed">
                    <i class="fas fa-graduation-cap me-2"></i>
                    ADMIS(E) - PASSAGE EN CLASSE SUPÉRIEURE
                </div>
                <div class="decision-details mt-2">
                    <small>Moyenne générale: ${generalAverage.toFixed(2)}/20</small>
                </div>
            `;
        } else {
            return `
                <div class="admission-badge admission-failed">
                    <i class="fas fa-redo me-2"></i>
                    NON ADMIS(E) - REDOUBLEMENT
                </div>
                <div class="decision-details mt-2">
                    <small>Moyenne générale: ${generalAverage.toFixed(2)}/20</small>
                </div>
            `;
        }
    } else {
        // Pour les trimestres 1 et 2, décision basée sur le trimestre actuel
        const score = currentData.cumulative_score;
        if (score >= 10) {
            return `<div class="admission-badge">Admis(e) au ${trimester}</div>`;
        } else {
            return `<div class="admission-badge admission-failed">Non admis(e) au ${trimester}</div>`;
        }
    }
}

// Fonction pour générer le PDF
function generatePDF(trimester) {
    currentTrimester = trimester;
    generatePDFFromModal();
}

// Fonction pour générer le PDF depuis le modal
async function generatePDFFromModal() {
    try {
        showSpinnerOverlay('Génération du PDF en cours...', 'Récupération des données du bulletin');
        
        if (typeof window.jspdf === 'undefined') {
            throw new Error('jsPDF n\'est pas chargé. Veuillez recharger la page.');
        }
        
        if (typeof html2canvas === 'undefined') {
            throw new Error('html2canvas n\'est pas chargé. Veuillez recharger la page.');
        }

        showSpinnerOverlay('Création du PDF...', 'Conversion HTML vers PDF avec html2canvas');
        
        await generatePDFFromHTML();
        
        showNotification('PDF généré avec succès !', 'success');

    } catch (error) {
        console.error误 lors de la génération du PDF:', error);
        showNotification('Erreur lors de la génération du PDF: ' + error.message, 'error');
    } finally {
        hideSpinnerOverlay();
    }
}

// Fonction pour générer le PDF à partir de l'aperçu HTML avec html2canvas
async function generatePDFFromHTML() {
    try {
        const tempContainer = document.createElement('div');
        tempContainer.style.position = 'absolute';
        tempContainer.style.left = '-9999px';
        tempContainer.style.top = '-9999px';
        tempContainer.style.width = '210mm';
        tempContainer.style.backgroundColor = 'white';
        tempContainer.style.padding = '0';
        tempContainer.style.margin = '0';
        tempContainer.style.fontFamily = 'Arial, sans-serif';
        tempContainer.style.boxSizing = 'border-box';
        document.body.appendChild(tempContainer);

        const trimesterData = bulletinData[currentTrimester];
        if (!trimesterData || !trimesterData.is_available) {
            throw new Error('Aucune donnée disponible pour ce trimestre');
        }
        
        const bulletinHTML = generateBulletinHTML(currentTrimester, trimesterData);
        tempContainer.innerHTML = bulletinHTML;

        await new Promise(resolve => setTimeout(resolve, 1000));

        const canvas = await html2canvas(tempContainer, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            width: tempContainer.scrollWidth,
            height: tempContainer.scrollHeight
        });

        document.body.removeChild(tempContainer);

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        const margin = 5;
        pdf.setDrawColor(200, 200, 200);
        pdf.setLineWidth(0.5);
        pdf.rect(margin, margin, pdf.internal.pageSize.getWidth() - 2 * margin, pdf.internal.pageSize.getHeight() - 2 * margin);
        
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;
        
        const availableWidth = pdfWidth - 2 * margin;
        const availableHeight = pdfHeight - 2 * margin;
        const ratio = Math.min(availableWidth / (canvasWidth * 0.264583), availableHeight / (canvasHeight * 0.264583));
        const imgWidth = canvasWidth * 0.264583 * ratio;
        const imgHeight = canvasHeight * 0.264583 * ratio;
        
        const x = (pdfWidth - imgWidth) / 2;
        const y = (pdfHeight - imgHeight) / 2;
        
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', x, y, imgWidth, imgHeight);
        
        const fileName = `bulletin_{{ $student->first_name }}_{{ $student->last_name }}_${currentTrimester.replace(/\s+/g, '_')}_{{ date('Y-m-d') }}.pdf`;
        pdf.save(fileName);

    } catch (error) {
        console.error('Erreur lors de la génération du PDF avec html2canvas:', error);
        throw error;
    }
}

// Fonction pour imprimer le PDF
function printPDFFromModal() {
    generatePDFFromModal().then(() => {
        // Après génération, ouvrir pour impression
        // Cette fonctionnalité peut être ajoutée si nécessaire
    });
}

// Fonctions utilitaires
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

function showSpinnerOverlay(text, subtext = '') {
    hideSpinnerOverlay();
    
    const overlay = document.createElement('div');
    overlay.className = 'spinner-overlay';
    overlay.id = 'globalSpinnerOverlay';
    overlay.innerHTML = `
        <div class="spinner-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <div class="spinner-text">${text}</div>
            ${subtext ? `<div class="spinner-subtext">${subtext}</div>` : ''}
        </div>
    `;
    
    document.body.appendChild(overlay);
}

function hideSpinnerOverlay() {
    const overlay = document.getElementById('globalSpinnerOverlay');
    if (overlay) {
        overlay.remove();
    }
}
</script>

<style>
/* Styles pour le modal et le bulletin (copiés de show.blade.php) */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner-overlay .spinner-content {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.spinner-overlay .spinner-border {
    width: 3rem;
    height: 3rem;
    margin-bottom: 15px;
}

.spinner-overlay .spinner-text {
    font-size: 16px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.spinner-overlay .spinner-subtext {
    font-size: 14px;
    color: #666;
}

.bulletin-modal .modal-dialog {
    max-width: 98%;
    margin: 10px auto;
    height: 95vh;
    display: flex;
    align-items: center;
}

.bulletin-modal .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.bulletin-modal .modal-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-bottom: none;
    padding: 20px 30px;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}

.bulletin-modal .modal-body {
    padding: 20px;
    flex: 1;
    overflow-y: auto;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.bulletin-modal .modal-footer {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid #dee2e6;
    padding: 15px 30px;
    position: relative;
    flex-shrink: 0;
}

.bulletin-page {
    background: white;
    margin: 0 auto;
    padding: 0;
    width: 210mm;
    min-height: 280mm;
    font-family: Arial, sans-serif;
    font-size: 12px;
    color: black;
    position: relative;
    border: 2px solid #333;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    transform-origin: top center;
    transform: scale(1.0);
    margin: 20px auto;
}

.bulletin-header {
    display: flex;
    padding: 15px 20px;
    border-bottom: 2px solid black;
    align-items: flex-start;
    justify-content: space-between;
    background: #f9f9f9;
}

.header-left {
    display: flex;
    align-items: flex-start;
    flex: 1;
}

.republic-seal {
    width: 60px;
    height: 60px;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.seal-placeholder {
    font-size: 6px;
    font-weight: bold;
    text-align: center;
    line-height: 1.1;
}

.year-info {
    flex: 1;
}

.ministry-line {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 3px;
}

.year-line {
    font-size: 11px;
    font-weight: bold;
}

.header-center {
    flex: 1;
    text-align: center;
}

.bulletin-title {
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    margin: 15px 0;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.header-right {
    display: flex;
    align-items: flex-start;
    flex: 1;
    justify-content: flex-end;
}

.school-logo {
    margin-right: 15px;
}

.gabon-logo {
    width: 60px;
    height: 60px;
    border: 2px solid black;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-placeholder {
    font-size: 6px;
    font-weight: bold;
    text-align: center;
    line-height: 1.1;
}

.school-info {
    text-align: left;
}

.school-line {
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 2px;
}

.contact-line {
    font-size: 9px;
}

.student-info-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    font-size: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.student-info-table td {
    border: 1px solid black;
    padding: 4px;
    background: #f8f8f8;
    vertical-align: top;
}

.photo-cell {
    width: 80px;
    height: 100px;
    text-align: center;
    vertical-align: middle;
    background: white;
}

.photo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 8px;
    text-align: center;
    color: #666;
}

.name-cell {
    background: #f0f0f0;
    font-size: 12px;
    font-weight: bold;
    padding: 6px;
}

.info-cell {
    font-size: 9px;
    padding: 3px 5px;
    background: #f8f8f8;
}

.grades-section {
    padding: 15px;
    background: white;
    margin: 10px 0;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.grades-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.grades-table th {
    background: linear-gradient(135deg, #90EE90 0%, #7CFC00 100%);
    border: 1px solid #228B22;
    padding: 4px 3px;
    text-align: center;
    font-weight: bold;
    font-size: 8px;
    line-height: 1.2;
    color: #2c3e50;
}

.grades-table td {
    border: 1px solid #ddd;
    padding: 3px 2px;
    text-align: center;
    font-size: 9px;
    background: white;
}

.subject-cell {
    text-align: left;
    padding-left: 4px;
}

.green-triangle {
    color: #228B22;
    font-weight: bold;
    margin-right: 2px;
}

.totals-row {
    background: #f5f5f5;
    font-weight: bold;
}

.moyenne-trimestre {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    border: 2px solid #28a745;
    padding: 8px;
    text-align: center;
    font-weight: bold;
    margin-bottom: 10px;
    border-radius: 5px;
    color: #155724;
    font-size: 12px;
}

.bottom-sections {
    display: flex;
    padding: 0 15px;
    gap: 15px;
    margin-bottom: 15px;
}

.profil-section,
.bilan-section {
    flex: 1;
    border: 2px solid #333;
    padding: 10px;
    font-size: 10px;
    background: white;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-title {
    text-align: center;
    font-weight: bold;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
    margin-bottom: 8px;
    font-size: 12px;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profil-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2px;
    font-size: 8px;
}

.bilan-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7px;
}

.bilan-table th,
.bilan-table td {
    border: 1px solid black;
    padding: 2px;
    text-align: center;
}

.bilan-table th {
    background: linear-gradient(135deg, #90EE90 0%, #7CFC00 100%);
    font-weight: bold;
    color: #2c3e50;
    border: 1px solid #228B22;
}

.decision-section {
    border: 2px solid #333;
    margin: 0 15px 15px 15px;
    padding: 15px;
    background: white;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.decision-title {
    text-align: center;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 10px;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

.decision-items {
    display: flex;
    justify-content: space-around;
    margin-bottom: 10px;
    font-size: 9px;
}

.admission-section {
    text-align: center;
}

.admission-badge {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 8px 25px;
    font-weight: bold;
    font-size: 16px;
    display: inline-block;
    border-radius: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.decision-date {
    font-size: 9px;
    margin-top: 8px;
}

.footer-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 20px 15px;
    border-top: 2px solid #333;
    min-height: 100px;
    background: #f9f9f9;
    border-radius: 0 0 5px 5px;
}

.left-seal-area,
.right-seal-area {
    flex: 1;
    text-align: center;
}

.center-area {
    flex: 2;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.seal-title {
    font-size: 9px;
    font-weight: bold;
    margin-bottom: 10px;
}

.official-seal-right {
    width: 60px;
    height: 60px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
}

.barcode-section {
    margin-bottom: 8px;
    text-align: center;
}

.bulletin-code {
    font-family: monospace;
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 5px;
    text-align: center;
    color: #333;
}

.conseil-text {
    font-size: 8px;
    font-style: italic;
    color: #666;
    text-align: center;
    margin-top: 5px;
}

/* Styles pour les flèches d'évolution */
.evolution-arrow {
    font-weight: bold;
    font-size: 12px;
    margin-left: 3px;
}

.evolution-up {
    color: #28a745;
}

.evolution-down {
    color: #dc3545;
}

/* Styles pour les badges de décision */
.admission-passed {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white !important;
}

.admission-failed {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%) !important;
    color: white !important;
}

.decision-details {
    margin-top: 8px;
    font-size: 10px;
    color: #666;
}
</style>
@endsection
