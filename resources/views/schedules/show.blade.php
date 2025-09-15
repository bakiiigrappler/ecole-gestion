@extends('layouts.app')

@section('title', 'Détails de l\'emploi du temps')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Emploi du temps - {{ $class->name }}
                    </h5>
                    <div>
                        <button onclick="generatePDF()" class="btn btn-outline-danger me-2">
                            <i class="fas fa-file-pdf me-1"></i>
                            Télécharger PDF
                        </button>
                        <a href="{{ route('schedules.build', ['class_id' => $class->id, 'academic_year_id' => $academicYear->id]) }}" 
                           class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>
                            Modifier
                        </a>
                        <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Retour
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Informations générales -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-users me-1"></i> Classe :</strong> {{ $class->name }}
                                        @if($class->getSafeLevelName() !== 'Non défini')
                                            <span class="badge bg-primary ms-2">{{ $class->getSafeLevelName() }}</span>
                                        @endif
                                    </div>
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-calendar me-1"></i> Année académique :</strong> {{ $academicYear->name }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong><i class="fas fa-clock me-1"></i> Total créneaux :</strong> 
                                        <span class="badge bg-success">{{ $schedulesByDay->flatten()->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($schedulesByDay->count() > 0)
                        <!-- Grille d'emploi du temps modernisée -->
                        <div class="schedule-container">
                            <div class="table-responsive shadow-sm">
                            <table class="table table-bordered schedule-display-table">
                                    <thead class="schedule-header">
                                        <tr>
                                            <th class="time-header">
                                                <i class="fas fa-clock me-2"></i>
                                                Horaires
                                            </th>
                                            @php
                                                // Afficher tous les jours de Lundi à Samedi
                                                $allDaysNames = [
                                                    1 => 'Lundi',
                                                    2 => 'Mardi', 
                                                    3 => 'Mercredi',
                                                    4 => 'Jeudi',
                                                    5 => 'Vendredi',
                                                    6 => 'Samedi'
                                                ];
                                            @endphp
                                            @foreach($allDaysNames as $dayNumber => $dayName)
                                                <th class="day-header">
                                                    <i class="fas fa-calendar-day me-2"></i>
                                                    {{ $dayName }}
                                                </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Définir tous les créneaux horaires possibles (comme dans le PDF)
                                        $allTimeSlots = [
                                            '08:00-09:00',
                                            '09:00-10:00', 
                                            '10:00-10:15', // Pause
                                            '10:15-11:15',
                                            '11:15-12:15',
                                            '12:15-13:15', // Pause déjeuner
                                            '13:15-14:15',
                                            '14:15-15:15',
                                            '15:15-15:30', // Pause
                                            '15:30-16:30'
                                        ];
                                        
                                        // Récupérer tous les emplois du temps existants
                                        $allSchedules = $schedulesByDay->flatten();
                                        
                                        // Créneaux horaires par défaut selon le cycle
                                        $cycle = $class->level ? $class->level->cycle : 'primaire';
                                        $defaultTimeSlots = [];
                                        
                                        switch ($cycle) {
                                            case 'maternelle':
                                                $defaultTimeSlots = [
                                                    ['start' => '08:00:00', 'end' => '08:30:00'],
                                                    ['start' => '08:30:00', 'end' => '09:00:00'],
                                                    ['start' => '09:00:00', 'end' => '09:30:00'],
                                                    ['start' => '09:30:00', 'end' => '10:00:00'],
                                                    ['start' => '10:00:00', 'end' => '10:15:00'], // Récréation
                                                    ['start' => '10:15:00', 'end' => '10:45:00'],
                                                    ['start' => '10:45:00', 'end' => '11:15:00'],
                                                    ['start' => '11:15:00', 'end' => '11:45:00'],
                                                    ['start' => '11:45:00', 'end' => '12:00:00'], // Pause
                                                ];
                                                break;
                                                
                                            case 'primaire':
                                                $defaultTimeSlots = [
                                                    ['start' => '08:00:00', 'end' => '09:00:00'],
                                                    ['start' => '09:00:00', 'end' => '10:00:00'],
                                                    ['start' => '10:00:00', 'end' => '10:15:00'], // Récréation
                                                    ['start' => '10:15:00', 'end' => '11:15:00'],
                                                    ['start' => '11:15:00', 'end' => '12:15:00'],
                                                    ['start' => '12:15:00', 'end' => '13:15:00'], // Pause déjeuner
                                                    ['start' => '13:15:00', 'end' => '14:15:00'],
                                                    ['start' => '14:15:00', 'end' => '15:15:00'],
                                                    ['start' => '15:15:00', 'end' => '15:30:00'], // Récréation
                                                    ['start' => '15:30:00', 'end' => '16:30:00'],
                                                ];
                                                break;
                                                
                                            default: // Collège et Lycée
                                                $defaultTimeSlots = [
                                                    ['start' => '08:00:00', 'end' => '09:00:00'],
                                                    ['start' => '09:00:00', 'end' => '10:00:00'],
                                                    ['start' => '10:00:00', 'end' => '10:15:00'], // Récréation
                                                    ['start' => '10:15:00', 'end' => '11:15:00'],
                                                    ['start' => '11:15:00', 'end' => '12:15:00'],
                                                    ['start' => '12:15:00', 'end' => '13:15:00'], // Pause déjeuner
                                                    ['start' => '13:15:00', 'end' => '14:15:00'],
                                                    ['start' => '14:15:00', 'end' => '15:15:00'],
                                                    ['start' => '15:15:00', 'end' => '15:30:00'], // Récréation
                                                    ['start' => '15:30:00', 'end' => '16:30:00'],
                                                    ['start' => '16:30:00', 'end' => '17:30:00'],
                                                ];
                                                break;
                                        }
                                        
                                        // Grouper par créneaux horaires
                                        $schedulesByTimeSlot = [];
                                        foreach($schedules as $schedule) {
                                            $timeSlot = $schedule->start_time . '-' . $schedule->end_time;
                                            $schedulesByTimeSlot[$timeSlot][$schedule->day_of_week] = $schedule;
                                        }
                                        
                                        // Combiner les créneaux existants et les créneaux par défaut
                                        $existingTimeSlots = [];
                                        foreach($schedules as $schedule) {
                                            $timeSlot = $schedule->start_time . '-' . $schedule->end_time;
                                            $existingTimeSlots[$timeSlot] = true;
                                        }
                                        
                                        $allTimeSlots = [];
                                        
                                        // Ajouter d'abord les créneaux par défaut
                                        foreach($defaultTimeSlots as $slot) {
                                            $timeSlot = $slot['start'] . '-' . $slot['end'];
                                            $allTimeSlots[] = $timeSlot;
                                        }
                                        
                                        // Ajouter les créneaux existants qui ne sont pas dans les créneaux par défaut
                                        foreach($existingTimeSlots as $timeSlot => $exists) {
                                            if (!in_array($timeSlot, $allTimeSlots)) {
                                                $allTimeSlots[] = $timeSlot;
                                            }
                                        }
                                        
                                        // Trier les créneaux par heure de début
                                        usort($allTimeSlots, function($a, $b) {
                                            $timeA = explode('-', $a)[0];
                                            $timeB = explode('-', $b)[0];
                                            return strcmp($timeA, $timeB);
                                        });
                                        
                                        // Tous les jours de la semaine (1=Lundi, 2=Mardi, ..., 6=Samedi)
                                        $allDays = [1, 2, 3, 4, 5, 6];
                                    @endphp

                                    @foreach($allTimeSlots as $timeSlot)
                                        <tr class="schedule-row">
                                            <td class="time-slot">
                                                <div class="time-display">
                                                    <i class="fas fa-clock text-primary me-2"></i>
                                                    <span class="time-text">{{ str_replace('-', ' - ', $timeSlot) }}</span>
                                                </div>
                                            </td>
                                            @foreach($allDays as $dayNumber)
                                                <td class="schedule-cell">
                                                        @if(isset($schedulesByTimeSlot[$timeSlot][$dayNumber]))
                                                            @php $schedule = $schedulesByTimeSlot[$timeSlot][$dayNumber] @endphp
                                                            
                                                            @if($schedule->type === 'course')
                                                            <div class="course-card">
                                                                <div class="course-header">
                                                                    <i class="fas fa-book me-2"></i>
                                                                    <span class="subject-name">{{ $schedule->subject->name ?? 'Matière non définie' }}</span>
                                                                    </div>
                                                                <div class="course-teacher">
                                                                    <i class="fas fa-user-tie me-2"></i>
                                                                    <span>{{ $schedule->teacher ? $schedule->teacher->first_name . ' ' . $schedule->teacher->last_name : 'Enseignant non défini' }}</span>
                                                                    </div>
                                                                    @if($schedule->room)
                                                                    <div class="course-room">
                                                                        <i class="fas fa-door-open me-2"></i>
                                                                        <span>{{ $schedule->room }}</span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @elseif($schedule->type === 'break')
                                                            <div class="break-card">
                                                                <div class="break-content">
                                                                    <i class="fas fa-coffee me-2"></i>
                                                                    <span class="break-title">{{ $schedule->title ?? 'Pause' }}</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @else
                                                        <div class="empty-cell">
                                                            <!-- Cellule vide sans contenu -->
                                                            </div>
                                                        @endif
                                                    </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>

                        <!-- Statistiques -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            Statistiques de l'emploi du temps
                                        </h6>
                                        <div class="row">
                                            @php
                                                $totalSchedules = $schedules->count();
                                                $courseCount = $schedules->where('type', 'course')->count();
                                                $breakCount = $schedules->where('type', 'break')->count();
                                                
                                                // Calculer les heures de cours par matière
                                                $subjectHours = [];
                                                foreach($schedules->where('type', 'course') as $schedule) {
                                                    $subjectName = $schedule->subject->name ?? 'Non définie';
                                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time);
                                                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->end_time);
                                                    $duration = $start->diffInMinutes($end);
                                                    $subjectHours[$subjectName] = ($subjectHours[$subjectName] ?? 0) + $duration;
                                                }
                                                
                                                // Calculer les heures par enseignant
                                                $teacherHours = [];
                                                foreach($schedules->where('type', 'course') as $schedule) {
                                                    $teacherName = $schedule->teacher ? $schedule->teacher->first_name . ' ' . $schedule->teacher->last_name : 'Non défini';
                                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time);
                                                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $schedule->end_time);
                                                    $duration = $start->diffInMinutes($end);
                                                    $teacherHours[$teacherName] = ($teacherHours[$teacherName] ?? 0) + $duration;
                                                }
                                            @endphp

                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-primary">{{ $totalSchedules }}</h4>
                                                    <small class="text-muted">Total créneaux</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-success">{{ $courseCount }}</h4>
                                                    <small class="text-muted">Heures de cours</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-warning">{{ $breakCount }}</h4>
                                                    <small class="text-muted">Pauses</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-info">{{ count($subjectHours) }}</h4>
                                                    <small class="text-muted">Matières différentes</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Répartition par matière -->
                        @if(count($subjectHours) > 0)
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-book me-2"></i>
                                                Répartition par matière
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($subjectHours as $subject => $minutes)
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>{{ $subject }}</span>
                                                    <span class="badge bg-primary">
                                                        {{ floor($minutes / 60) }}h{{ $minutes % 60 > 0 ? sprintf('%02d', $minutes % 60) : '' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Répartition par enseignant -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user-tie me-2"></i>
                                                Répartition par enseignant
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($teacherHours as $teacher => $minutes)
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>{{ $teacher }}</span>
                                                    <span class="badge bg-success">
                                                        {{ floor($minutes / 60) }}h{{ $minutes % 60 > 0 ? sprintf('%02d', $minutes % 60) : '' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        <!-- Aucun emploi du temps -->
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun emploi du temps défini</h5>
                            <p class="text-muted mb-4">
                                Cette classe n'a pas encore d'emploi du temps pour l'année {{ $academicYear->name }}.
                            </p>
                            <a href="{{ route('schedules.build', ['class_id' => $class->id, 'academic_year_id' => $academicYear->id]) }}" 
                               class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                Créer l'emploi du temps
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Container principal */
.schedule-container {
    margin: 20px 0;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Tableau principal */
.schedule-display-table {
    font-size: 0.95rem;
    margin-bottom: 0;
    border: none;
    background: #fff;
}

/* En-têtes */
.schedule-header {
    background: #2c3e50;
    color: white;
}

.time-header, .day-header {
    font-weight: 600;
    font-size: 1.1rem;
    text-align: center;
    padding: 18px 12px;
    border: none;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    color: white;
}

.time-header {
    background: #34495e;
    min-width: 140px;
}

/* Lignes du tableau */
.schedule-row {
    transition: all 0.3s ease;
}

.schedule-row:hover {
    background-color: #f8f9ff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Cellule horaire */
.time-slot {
    background: #3498db;
    color: white;
    text-align: center;
    padding: 15px 10px;
    font-weight: 600;
    border: none;
}

.time-display {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.time-text {
    font-size: 1rem;
    margin-top: 5px;
}

/* Cellules d'emploi du temps */
.schedule-cell {
    min-width: 220px;
    padding: 12px;
    vertical-align: top;
    border-left: 3px solid #e9ecef;
    position: relative;
}

/* Cartes de cours */
.course-card {
    background: #e8f4fd;
    border-radius: 8px;
    padding: 16px;
    min-height: 90px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
    border: 1px solid #007bff;
    transition: all 0.3s ease;
    position: relative;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background: #d1ecf1;
}

.course-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #007bff;
}

.course-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.subject-name {
    font-weight: 700;
    font-size: 1.1rem;
    color: #0056b3;
    text-shadow: none;
}

.course-teacher {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
    color: #495057;
    font-size: 0.95rem;
}

.course-room {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.9rem;
    font-style: italic;
}

/* Cartes de pause */
.break-card {
    background: #fff3cd;
    border-radius: 8px;
    padding: 16px;
    min-height: 60px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #ffc107;
    border: 1px solid #ffc107;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.break-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background: #ffeaa7;
}

.break-content {
    display: flex;
    align-items: center;
    justify-content: center;
}

.break-title {
    font-weight: 600;
    color: #856404;
    font-size: 1.05rem;
}

/* Cellules vides */
.empty-cell {
    background: #ffffff;
    border-radius: 4px;
    padding: 20px;
    min-height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.empty-cell:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
}

.empty-text {
    display: none; /* Masquer complètement le texte pour avoir des cellules vraiment vides */
}

/* Responsive */
@media (max-width: 992px) {
    .schedule-cell {
        min-width: 180px;
    }
    
    .course-card, .break-card {
        padding: 12px;
        min-height: 80px;
    }
    
    .subject-name {
        font-size: 1rem;
    }
}

@media (max-width: 768px) {
    .schedule-cell {
        min-width: 150px;
    }
    
    .time-header, .day-header {
        font-size: 0.95rem;
        padding: 12px 8px;
    }
    
    .course-card {
        padding: 10px;
        min-height: 70px;
    }
}

@media print {
    /* Masquer les éléments non nécessaires à l'impression */
    .card-header .btn,
    .print-btn,
    nav,
    .navbar,
    footer,
    .alert {
        display: none !important;
    }
    
    /* Styles pour le titre */
    .card-header {
        background: white !important;
        border-bottom: 2px solid #000 !important;
        margin-bottom: 20px !important;
        padding: 10px 0 !important;
    }
    
    .card-header h5 {
        font-size: 18px !important;
        font-weight: bold !important;
        color: black !important;
        text-align: center !important;
    }
    
    /* Styles pour le tableau */
    .schedule-display-table {
        font-size: 11px !important;
        border-collapse: collapse !important;
        width: 100% !important;
        margin-top: 20px !important;
    }
    
    .schedule-display-table th,
    .schedule-display-table td {
        border: 1px solid #000 !important;
        padding: 8px 4px !important;
        vertical-align: top !important;
    }
    
    .schedule-display-table th {
        background-color: #f8f9fa !important;
        font-weight: bold !important;
        text-align: center !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    /* Styles pour les cours */
    .course-display {
        min-height: auto !important;
        padding: 4px !important;
        border: 1px solid #007bff !important;
        background-color: #f8f9ff !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        border-radius: 3px !important;
    }
    
    .course-display .fw-bold {
        font-size: 10px !important;
        margin-bottom: 2px !important;
        color: #000 !important;
    }
    
    .course-display .small {
        font-size: 8px !important;
        color: #333 !important;
    }
    
    /* Styles pour les pauses */
    .break-display {
        min-height: auto !important;
        padding: 4px !important;
        border: 1px solid #ffc107 !important;
        background-color: #fff9e6 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        border-radius: 3px !important;
        text-align: center !important;
    }
    
    .break-display .fw-bold {
        font-size: 9px !important;
        color: #000 !important;
    }
    
    /* Styles pour les cellules vides */
    .empty-slot {
        height: 30px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #999 !important;
    }
    
    /* Masquer les statistiques pour économiser l'espace */
    .card:not(:first-child),
    .row.mt-4 {
        display: none !important;
    }
    
    /* Informations générales */
    .alert-info {
        background-color: #e9ecef !important;
        border: 1px solid #000 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        margin-bottom: 20px !important;
        padding: 10px !important;
    }
    
    /* Ajustements pour la page */
    @page {
        margin: 1cm;
        size: A4 landscape;
    }
    
    body {
        font-family: Arial, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
}
</style>
@endpush

@push('scripts')
<!-- Bibliothèques jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

@php
    // Préparer les données PHP pour JavaScript
    $jsScheduleData = [];
    if(isset($schedulesByDay) && $schedulesByDay->count() > 0) {
        $allSchedules = $schedulesByDay->flatten();
        foreach($allSchedules as $schedule) {
            $jsScheduleData[] = [
                'day' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'type' => $schedule->type,
                'subject' => $schedule->subject ? $schedule->subject->name : null,
                'teacher' => $schedule->teacher ? $schedule->teacher->first_name . ' ' . $schedule->teacher->last_name : null,
                'room' => $schedule->room,
                'title' => $schedule->title
            ];
        }
    }
@endphp

<script>
// Données d'emploi du temps depuis le serveur
window.serverScheduleData = <?php echo json_encode($jsScheduleData); ?>;
</script>

<script>
// Fonctionnalité d'impression
function printSchedule() {
    // Ajouter un titre spécifique pour l'impression
    const originalTitle = document.title;
    document.title = 'Emploi du temps - {{ $class->name }} - {{ $academicYear->name }}';
    
    // Déclencher l'impression
    window.print();
    
    // Restaurer le titre original
    document.title = originalTitle;
}

// Vérifier si l'impression automatique est demandée
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === '1') {
        // Attendre que la page soit complètement chargée
        setTimeout(function() {
            printSchedule();
            // Fermer automatiquement l'onglet après l'impression (optionnel)
            // window.close();
        }, 1000);
    }
});

// Raccourci clavier pour l'impression (Ctrl+P)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        printSchedule();
    }
});

// Fonction pour générer le PDF avec jsPDF
function generatePDF() {
    const { jsPDF } = window.jspdf;
    
    // Créer un nouveau document PDF en format paysage
    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4'
    });

    // Informations de base
    const className = '{{ $class->name }}';
    const academicYear = '{{ $academicYear->name }}';
    const levelName = '{{ $class->getSafeLevelName() }}';
    const currentDate = new Date().toLocaleDateString('fr-FR');

    // Dimensions de la page (A4 paysage)
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;

    // En-tête compact avec design amélioré
    // Fond coloré pour l'en-tête (plus petit)
    doc.setFillColor(41, 128, 185); // Bleu professionnel
    doc.rect(0, 0, pageWidth, 35, 'F');
    
    // Titre principal
    doc.setTextColor(255, 255, 255); // Blanc
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('EMPLOI DU TEMPS', pageWidth / 2, 15, { align: 'center' });
    
    // Nom de la classe
    doc.setFontSize(13);
    doc.text(`Classe: ${className}`, pageWidth / 2, 25, { align: 'center' });
    
    // Informations secondaires (en une ligne)
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text(`Niveau: ${levelName} | Annee: ${academicYear} | Imprime le: ${currentDate}`, pageWidth / 2, 32, { align: 'center' });
    
    // Ligne décorative fine
    doc.setDrawColor(52, 152, 219);
    doc.setLineWidth(1);
    doc.line(margin, 37, pageWidth - margin, 37);
    
    // Remettre la couleur du texte en noir pour le reste
    doc.setTextColor(0, 0, 0);

    // Définir les jours de la semaine (sans emojis)
    const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    
    // Définir les créneaux horaires standards (modifiable selon vos besoins)
    const timeSlots = [
        { start: '08:00', end: '09:00' },
        { start: '09:00', end: '10:00' },
        { start: '10:00', end: '10:15' },
        { start: '10:15', end: '11:15' },
        { start: '11:15', end: '12:15' },
        { start: '12:15', end: '13:15' },
        { start: '13:15', end: '14:15' },
        { start: '14:15', end: '15:15' },
        { start: '15:15', end: '15:30' },
        { start: '15:30', end: '16:30' }
    ];

    // Récupérer les données d'emploi du temps existantes depuis le serveur
    const scheduleData = window.serverScheduleData;

    // Préparer les données pour le tableau
    const tableData = [];
    
    // En-tête du tableau (sans emojis)
    const headers = ['HORAIRES', ...days];
    
    // Pour chaque créneau horaire
    timeSlots.forEach(timeSlot => {
        const timeLabel = `${timeSlot.start} - ${timeSlot.end}`;
        const row = [timeLabel];
        
        // Pour chaque jour de la semaine
        days.forEach((day, dayIndex) => {
            const dayNumber = dayIndex + 1; // Lundi = 1, Mardi = 2, etc.
            
            // Chercher s'il y a un cours à ce créneau pour ce jour
            const schedule = findScheduleForTimeAndDay(scheduleData, timeSlot, dayNumber);
            
            if (schedule) {
                if (schedule.type === 'course') {
                    let cellContent = `${schedule.subject || 'Matiere'}`;
                    if (schedule.teacher) {
                        cellContent += `\n${schedule.teacher}`;
                    }
                    if (schedule.room) {
                        cellContent += `\nSalle: ${schedule.room}`;
                    }
                    row.push(cellContent);
                } else if (schedule.type === 'break') {
                    row.push(`${schedule.title || 'Pause'}`);
                }
            } else {
                // Case vide
                row.push('');
            }
        });
        
        tableData.push(row);
    });

    // Générer le tableau avec design amélioré
    doc.autoTable({
        head: [headers],
        body: tableData,
        startY: 42,
        margin: { left: margin, right: margin },
        styles: {
            fontSize: 9,
            cellPadding: 4,
            lineColor: [52, 152, 219], // Bleu pour les bordures
            lineWidth: 0.3,
            textColor: [44, 62, 80], // Gris foncé pour le texte
            halign: 'center',
            valign: 'middle'
        },
        headStyles: {
            fillColor: [41, 128, 185], // Bleu header
            textColor: [255, 255, 255], // Blanc
            fontStyle: 'bold',
            fontSize: 10,
            halign: 'center'
        },
        columnStyles: {
            0: { 
                cellWidth: 28,
                fontStyle: 'bold',
                fillColor: [236, 240, 241], // Gris très clair pour les horaires
                textColor: [52, 73, 94],
                halign: 'center'
            }
        },
        alternateRowStyles: {
            fillColor: [250, 252, 253] // Gris très léger pour les lignes alternées
        },
        tableLineColor: [52, 152, 219],
        tableLineWidth: 0.3,
        didParseCell: function(data) {
            // Style spécial pour les cellules selon le contenu
            if (data.column.index > 0 && data.cell.text && data.cell.text.length > 0) {
                const cellText = data.cell.text[0];
                
                // Détecter les cours (contient un nom de matière et potentiellement un enseignant)
                if (cellText && cellText.includes('\n') && !cellText.includes('Pause')) {
                    // Cellule de cours
                    data.cell.styles.fillColor = [232, 245, 233]; // Vert très clair
                    data.cell.styles.textColor = [27, 94, 32]; // Vert foncé
                    data.cell.styles.cellHeight = Math.max(18, data.cell.text.length * 5);
                } else if (cellText && cellText.includes('Pause')) {
                    // Cellule de pause
                    data.cell.styles.fillColor = [255, 248, 225]; // Orange très clair
                    data.cell.styles.textColor = [230, 126, 34]; // Orange
                    data.cell.styles.cellHeight = 15;
                } else if (cellText === '') {
                    // Cellule vide
                    data.cell.styles.fillColor = [248, 249, 250]; // Gris très clair
                    data.cell.styles.textColor = [108, 117, 125]; // Gris moyen
                    data.cell.styles.cellHeight = 15;
                }
            }
            
            // Style pour les en-têtes de jours
            if (data.row.index === -1 && data.column.index > 0) {
                data.cell.styles.fontSize = 11;
            }
        }
    });

    // Pied de page avec design amélioré
    const finalY = doc.lastAutoTable.finalY + 15;
    
    // Ligne décorative
    doc.setDrawColor(52, 152, 219);
    doc.setLineWidth(1);
    doc.line(margin, finalY, pageWidth - margin, finalY);
    
    // Statistiques en bas (sans emojis)
    const courseCount = tableData.filter(row => 
        row.slice(1).some(cell => cell && cell.includes('\n') && !cell.includes('Pause'))
    ).length;
    const breakCount = tableData.filter(row => 
        row.slice(1).some(cell => cell && cell.includes('Pause'))
    ).length;
    
    doc.setTextColor(52, 73, 94);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    
    // Statistiques
    doc.text(`${courseCount} creneaux de cours`, margin + 20, finalY + 8);
    doc.text(`${breakCount} pauses`, pageWidth / 2 - 20, finalY + 8);
    doc.text(`${tableData.length} creneaux total`, pageWidth - margin - 60, finalY + 8);
    
    // Signature
    doc.setFontSize(8);
    doc.setFont('helvetica', 'italic');
    doc.setTextColor(108, 117, 125);
    doc.text('Document genere automatiquement par le systeme de gestion scolaire', pageWidth / 2, finalY + 16, { align: 'center' });

    // Télécharger le PDF
    const filename = `Emploi_du_temps_${className.replace(/\s+/g, '_')}_${academicYear.replace(/\s+/g, '_')}.pdf`;
    doc.save(filename);
}



// Fonction pour trouver l'emploi du temps pour un créneau et un jour donnés
function findScheduleForTimeAndDay(scheduleData, timeSlot, dayNumber) {
    // Utiliser les données du serveur déjà chargées
    return window.serverScheduleData.find(schedule => 
        schedule.day === dayNumber && 
        schedule.start_time === timeSlot.start && 
        schedule.end_time === timeSlot.end
    );
}
</script>
@endpush
