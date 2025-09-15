@extends('layouts.app')

@section('title', 'Gestion des Présences')

@section('content')
<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Gestion des Présences
                        </h1>
                        <p class="page-subtitle">Gérez les présences des élèves par classe</p>
                    </div>
                    <div class="page-actions">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="fas fa-filter me-2"></i>
                                Filtrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <form method="GET" action="{{ route('attendances.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="academic_year_id" class="form-label">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Année Académique
                            </label>
                            <select name="academic_year_id" id="academic_year_id" class="form-select">
                                <option value="">Toutes les années</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" 
                                            {{ $academicYearId == $year->id ? 'selected' : '' }}>
                                        {{ $year->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>
                                Appliquer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $classes->count() }}</h3>
                    <p>Classes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $classes->sum('students_count') }}</h3>
                    <p>Élèves Total</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ \App\Models\Attendance::whereDate('attendance_date', today())->whereIn('status', ['present', 'late'])->distinct('student_id')->count() }}</h3>
                    <p>Présents Aujourd'hui</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ \App\Models\Attendance::whereDate('attendance_date', today())->where('status', 'absent')->where('justified', false)->distinct('student_id')->count() }}</h3>
                    <p>Absents Aujourd'hui</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des classes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Classes et Présences
                    </h5>
                </div>
                <div class="card-body">
                    @if($classes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <i class="fas fa-school me-2"></i>
                                            Classe
                                        </th>
                                        <th>
                                            <i class="fas fa-layer-group me-2"></i>
                                            Niveau
                                        </th>
                                        <th>
                                            <i class="fas fa-users me-2"></i>
                                            Nombre d'Élèves
                                        </th>
                                        <th>
                                            <i class="fas fa-calendar-day me-2"></i>
                                            Présences Aujourd'hui
                                        </th>
                                        <th>
                                            <i class="fas fa-chart-line me-2"></i>
                                            Taux de Présence
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-cogs me-2"></i>
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($classes as $class)
                                        @php
                                            // Récupérer les présences d'aujourd'hui pour cette classe
                                            $todayAttendances = \App\Models\Attendance::where('class_id', $class->id)
                                                ->whereDate('attendance_date', today())
                                                ->get()
                                                ->groupBy('student_id');
                                            
                                            // Appliquer la logique intelligente de présence
                                            $presentCount = 0;
                                            $absentCount = 0;
                                            $lateCount = 0;
                                            $excusedCount = 0;
                                            
                                            foreach ($todayAttendances as $studentId => $studentAttendances) {
                                                $hasPresent = false;
                                                $hasLate = false;
                                                $hasJustifiedAbsence = false;
                                                $hasUnjustifiedAbsence = false;
                                                $firstArrivalTime = null;
                                                
                                                // Analyser les créneaux de l'étudiant pour ce jour
                                                $hasFirstCreneauAbsent = false;
                                                $hasSecondCreneauAbsent = false;
                                                
                                                foreach ($studentAttendances as $attendance) {
                                                    if ($attendance->status === 'present') {
                                                        $hasPresent = true;
                                                        
                                                        // Trouver la première heure d'arrivée réelle
                                                        if ($attendance->arrival_time && !empty($attendance->arrival_time)) {
                                                            if (!$firstArrivalTime) {
                                                                $firstArrivalTime = $attendance->arrival_time;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Vérifier si les premiers créneaux sont absents
                                                    if ($attendance->status === 'absent') {
                                                        if ($attendance->time_slot === '07:30') {
                                                            $hasFirstCreneauAbsent = true;
                                                        } elseif ($attendance->time_slot === '08:30') {
                                                            $hasSecondCreneauAbsent = true;
                                                        }
                                                        
                                                        if ($attendance->justified) {
                                                            $hasJustifiedAbsence = true;
                                                        } else {
                                                            $hasUnjustifiedAbsence = true;
                                                        }
                                                    }
                                                }
                                                
                                                // Appliquer la logique intelligente
                                                if ($hasPresent) {
                                                    // Si l'élève a été présent au moins une heure
                                                    if ($hasJustifiedAbsence) {
                                                        // Annuler les absences justifiées
                                                        $hasJustifiedAbsence = false;
                                                    }
                                                    
                                                    // Calculer le retard basé sur la logique spécifiée
                                                    if ($firstArrivalTime && !empty($firstArrivalTime)) {
                                                        try {
                                                            $arrivalTime = \Carbon\Carbon::createFromFormat('H:i', $firstArrivalTime);
                                                            $startTime = \Carbon\Carbon::createFromFormat('H:i', '07:30');
                                                            
                                                            // Si l'élève est arrivé après 07:30, c'est un retard
                                                            if ($arrivalTime && $startTime && $arrivalTime->gt($startTime)) {
                                                                $hasLate = true;
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Ignorer les erreurs de format de date
                                                        }
                                                    }
                                                    
                                                    // Si les premiers créneaux sont absents (non justifiés), l'élève est en retard
                                                    if (($hasFirstCreneauAbsent || $hasSecondCreneauAbsent) && !$hasJustifiedAbsence) {
                                                        $hasLate = true;
                                                    }
                                                }
                                                
                                                // Déterminer le statut final de l'étudiant pour ce jour
                                                if ($hasPresent) {
                                                    // L'élève est présent (même s'il est en retard)
                                                    $presentCount++;
                                                    
                                                    // Si en plus il est en retard, compter aussi le retard
                                                    if ($hasLate) {
                                                        $lateCount++;
                                                    }
                                                } else {
                                                    if ($hasJustifiedAbsence) {
                                                        $excusedCount++;
                                                    } else {
                                                        $absentCount++;
                                                    }
                                                }
                                            }
                                            
                                            // Calculer le taux de présence basé sur la logique intelligente
                                            $totalStudents = $class->students_count;
                                            $attendanceRate = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;
                                            
                                            // Pré-calculer la classe CSS pour éviter les erreurs de syntaxe
                                            $progressBarClass = $attendanceRate >= 90 ? 'bg-success' : ($attendanceRate >= 70 ? 'bg-warning' : 'bg-danger');
                                            $progressBarStyle = 'width: ' . $attendanceRate . '%';
                                        @endphp
                                        <tr class="class-row">
                                            <td>
                                                <div class="class-info">
                                                    <div class="class-name">
                                                        <i class="fas fa-graduation-cap me-2 text-primary"></i>
                                                        {{ $class->name }}
                                                    </div>
                                                    @if($class->level)
                                                        <small class="text-muted">{{ $class->level->name }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($class->level)
                                                    <span class="badge bg-secondary">
                                                        {{ $class->level->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Non défini</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="student-count">
                                                    <span class="count-number">{{ $class->students_count }}</span>
                                                    <small class="text-muted">élèves</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="attendance-today">
                                                    <div class="attendance-stats">
                                                        <div class="stat-item">
                                                            <span class="stat-number text-success">{{ $presentCount }}</span>
                                                            <small class="text-muted">présents</small>
                                                        </div>
                                                        @if($lateCount > 0)
                                                        <div class="stat-item">
                                                            <span class="stat-number text-warning">{{ $lateCount }}</span>
                                                            <small class="text-muted">retards</small>
                                                        </div>
                                                        @endif
                                                        @if($absentCount > 0)
                                                        <div class="stat-item">
                                                            <span class="stat-number text-danger">{{ $absentCount }}</span>
                                                            <small class="text-muted">absents</small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="attendance-rate">
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar {{ $progressBarClass }}" role="progressbar" data-width="{{ $attendanceRate }}">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">{{ $attendanceRate }}%</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('attendances.manage', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-clipboard-check me-1"></i>
                                                        Gérer
                                                    </a>
                                                    <a href="{{ route('attendances.reports', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                                                       class="btn btn-info btn-sm">
                                                        <i class="fas fa-chart-bar me-1"></i>
                                                        Rapports
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-school"></i>
                            </div>
                            <h4>Aucune classe trouvée</h4>
                            <p>Aucune classe n'est disponible pour l'année académique sélectionnée.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    background: #3498db;
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    margin: 0;
}

.page-subtitle {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.filter-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.5rem;
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #7f8c8d;
    font-weight: 500;
}

.class-row {
    transition: all 0.3s ease;
}

.class-row:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.class-info {
    display: flex;
    flex-direction: column;
}

.class-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
}

.student-count, .attendance-today {
    text-align: center;
}

.count-number, .attendance-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    display: block;
}

.attendance-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    display: block;
}

.stat-item small {
    font-size: 0.75rem;
    text-align: center;
}

.attendance-rate {
    min-width: 120px;
}

.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 10px;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.btn-group .btn {
    border-radius: 8px;
    margin: 0 2px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    border-color: #e9ecef;
}

.card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0 !important;
}

.card-title {
    color: #495057;
    font-weight: 600;
}

/* Amélioration des boutons */
.btn-primary {
    background-color: #3498db;
    border-color: #3498db;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #2980b9;
    border-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #138496;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
}

.btn-light {
    background-color: #ffffff;
    border-color: #dee2e6;
    color: #2c3e50;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-light:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #2c3e50;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* JavaScript pour gérer les barres de progression */
.progress-bar[data-width] {
    transition: width 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer les barres de progression
    document.querySelectorAll('.progress-bar[data-width]').forEach(function(bar) {
        const width = bar.getAttribute('data-width');
        bar.style.width = width + '%';
    });
});
</script>
@endsection
