@extends('layouts.app')

@section('title', 'Bulletin Carte de ' . $student->first_name . ' ' . $student->last_name)

@section('content')
<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-id-card"></i>
                        Bulletin Carte de {{ $student->first_name }} {{ $student->last_name }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('grades.index') }}">Notes</a></li>
                            <li class="breadcrumb-item active">Bulletin Carte</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('grades.bulletin', $student->id) }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-file-alt"></i> Bulletin Classique
                    </a>
                    <a href="{{ route('grades.bulletin.simple', $student->id) }}" class="btn btn-outline-success me-2">
                        <i class="fas fa-file"></i> Bulletin Simple
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte du bulletin -->
    <div class="row">
        <div class="col-12">
            <div class="card bulletin-carte">
                <div class="card-body p-4">
                    <!-- En-tête de la carte -->
                    <div class="bulletin-header mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="school-logo">
                                    <img src="{{ asset('images/logo-ecole.svg') }}" alt="Logo École" style="max-height: 60px; max-width: 80px;">
                                    <h5 class="mt-2 mb-0">Établissement Scolaire</h5>
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    <img src="{{ asset('images/sceau-221128112237.png') }}" alt="Sceau République Gabonaise" style="max-height: 40px; max-width: 40px; margin-right: 15px;">
                                    <h2 class="bulletin-title mb-0">BULLETIN SCOLAIRE</h2>
                                </div>
                                <h4 class="academic-year mb-0">Année Scolaire {{ $academicYear->name ?? '2024-2025' }}</h4>
                                <small class="text-muted">Ministère de l'Education Nationale</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="student-photo">
                                    <div class="photo-placeholder">
                                        <i class="fas fa-user fa-2x text-muted"></i>
                                        <small class="d-block mt-1">Photo de l'élève</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations de l'élève -->
                    <div class="student-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="info-title">
                                    <i class="fas fa-user"></i> Informations Personnelles
                                </h5>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Nom complet :</strong></td>
                                        <td>{{ strtoupper($student->last_name) }} {{ $student->first_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Matricule :</strong></td>
                                        <td>{{ $student->student_id ?? 'STU' . str_pad($student->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date de naissance :</strong></td>
                                        <td>{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/C' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sexe :</strong></td>
                                        <td>{{ ucfirst($student->gender ?? 'N/C') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="info-title">
                                    <i class="fas fa-graduation-cap"></i> Informations Scolaires
                                </h5>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td><strong>Classe :</strong></td>
                                        <td>{{ $class->name ?? 'N/C' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Niveau :</strong></td>
                                        <td>{{ $class->level->name ?? 'N/C' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Professeur principal :</strong></td>
                                        <td>{{ $principalTeacherName }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Année scolaire :</strong></td>
                                        <td>{{ $academicYear->name ?? '2024-2025' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Résultats par trimestre -->
                    <div class="trimesters-results">
                        <h5 class="results-title mb-3">
                            <i class="fas fa-chart-line"></i> Résultats par Trimestre
                        </h5>
                        
                        <div class="row">
                            @foreach(['1er trimestre', '2ème trimestre', '3ème trimestre'] as $trimester)
                            <div class="col-md-4 mb-3">
                                <div class="trimester-card">
                                    <div class="card-header-trimester">
                                        <h6 class="mb-0">{{ $trimester }}</h6>
                                        @if(isset($trimesterData[$trimester]) && $trimesterData[$trimester]['is_available'])
                                            <span class="badge bg-success">Disponible</span>
                                        @else
                                            <span class="badge bg-secondary">Non disponible</span>
                                        @endif
                                    </div>
                                    <div class="card-body-trimester">
                                        @if(isset($trimesterData[$trimester]) && $trimesterData[$trimester]['is_available'])
                                            <div class="trimester-score">
                                                <div class="score-circle">
                                                    <span class="score-number">{{ $trimesterData[$trimester]['cumulative_score'] }}</span>
                                                    <span class="score-label">/20</span>
                                                </div>
                                            </div>
                                            <div class="trimester-details">
                                                <p class="mb-1"><strong>Matières :</strong> {{ $trimesterData[$trimester]['total_subjects'] }}</p>
                                                <p class="mb-1"><strong>Rang :</strong> {{ $trimesterData[$trimester]['rank'] }}</p>
                                                <p class="mb-0">
                                                    <strong>Appréciation :</strong> 
                                                    <span class="appreciation-badge 
                                                        @if($trimesterData[$trimester]['cumulative_score'] >= 16) bg-success
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 14) bg-info
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 12) bg-primary
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 10) bg-warning
                                                        @else bg-danger @endif
                                                    ">
                                                        @if($trimesterData[$trimester]['cumulative_score'] >= 16) Excellent
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 14) Très bien
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 12) Bien
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 10) Assez bien
                                                        @elseif($trimesterData[$trimester]['cumulative_score'] >= 8) Passable
                                                        @else Insuffisant
                                                        @endif
                                                    </span>
                                                </p>
                                            </div>
                                        @else
                                            <div class="no-data">
                                                <i class="fas fa-exclamation-triangle fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Aucune donnée disponible pour ce trimestre</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Détail des matières pour le trimestre disponible -->
                    @php
                        $availableTrimester = null;
                        foreach(['1er trimestre', '2ème trimestre', '3ème trimestre'] as $trimester) {
                            if(isset($trimesterData[$trimester]) && $trimesterData[$trimester]['is_available']) {
                                $availableTrimester = $trimester;
                                break;
                            }
                        }
                    @endphp

                    @if($availableTrimester)
                    <div class="subjects-detail mt-4">
                        <h5 class="subjects-title mb-3">
                            <i class="fas fa-book"></i> Détail des Matières - {{ $availableTrimester }}
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Matière</th>
                                        <th>Note</th>
                                        <th>Coefficient</th>
                                        <th>Appréciation</th>
                                        <th>Professeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trimesterData[$availableTrimester]['subjects'] as $subject)
                                    <tr>
                                        <td><strong>{{ $subject['name'] }}</strong></td>
                                        <td>
                                            <span class="badge 
                                                @if($subject['average'] >= 16) bg-success
                                                @elseif($subject['average'] >= 14) bg-info
                                                @elseif($subject['average'] >= 12) bg-primary
                                                @elseif($subject['average'] >= 10) bg-warning
                                                @else bg-danger @endif
                                            ">
                                                {{ $subject['average'] }}/20
                                            </span>
                                        </td>
                                        <td>{{ $subject['coefficient'] }}</td>
                                        <td>{{ $subject['appreciation'] }}</td>
                                        <td>{{ $subject['teacher_name'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Signature et date -->
                    <div class="signature-section mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="signature-box">
                                    <p class="mb-1"><strong>Le Professeur Principal</strong></p>
                                    <div class="signature-line"></div>
                                    <p class="text-muted small">{{ $principalTeacherName }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="signature-box">
                                    <p class="mb-1"><strong>Le Directeur</strong></p>
                                    <div class="signature-line"></div>
                                    <p class="text-muted small">Directeur de l'Établissement</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <p class="text-muted small">
                                <i class="fas fa-calendar"></i> 
                                Bulletin généré le {{ date('d/m/Y à H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bulletin-carte {
    border: 2px solid #007bff;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 123, 255, 0.1);
}

.bulletin-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 20px;
}

.bulletin-title {
    color: #007bff;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.academic-year {
    color: #6c757d;
    font-weight: 500;
}

.photo-placeholder {
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    padding: 20px;
    background-color: #f8f9fa;
}

.info-title {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
    margin-bottom: 15px;
}

.results-title, .subjects-title {
    color: #007bff;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
    margin-bottom: 15px;
}

.trimester-card {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transition: transform 0.3s ease;
}

.trimester-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
}

.card-header-trimester {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 15px;
    border-radius: 10px 10px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-body-trimester {
    padding: 20px;
    text-align: center;
}

.score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin: 0 auto 15px;
    color: white;
    font-weight: bold;
}

.score-number {
    font-size: 24px;
    line-height: 1;
}

.score-label {
    font-size: 12px;
    line-height: 1;
}

.appreciation-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 15px;
}

.no-data {
    text-align: center;
    padding: 20px;
}

.signature-box {
    text-align: center;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    background-color: #f8f9fa;
}

.signature-line {
    height: 2px;
    background-color: #6c757d;
    margin: 10px 0;
}

@media print {
    .bulletin-carte {
        border: none;
        box-shadow: none;
    }
    
    .btn {
        display: none !important;
    }
}
</style>
@endsection
