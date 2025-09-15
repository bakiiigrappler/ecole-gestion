@extends('layouts.app')

@section('title', 'Modification des Présences - ' . $class->name)

@section('content')
<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-edit me-2"></i>
                            Modification des Présences
                        </h1>
                        <p class="page-subtitle">
                            {{ $class->name }} - {{ $academicYear->name ?? 'Année non définie' }}
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-edit me-1"></i>
                                Modification du {{ $editDate->format('d/m/Y') }}
                            </span>
                        </p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('attendances.manage', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                           class="btn btn-info me-2">
                            <i class="fas fa-calendar-day me-2"></i>
                            Aujourd'hui
                        </a>
                        <a href="{{ route('attendances.index', ['academic_year_id' => $academicYearId]) }}" 
                           class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques de la semaine -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $weekStats->present ?? 0 }}</h3>
                    <p>Présents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $weekStats->absent ?? 0 }}</h3>
                    <p>Absents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $weekStats->late ?? 0 }}</h3>
                    <p>En Retard</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $weekStats->excused ?? 0 }}</h3>
                    <p>Excusés</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fiche de présence à modifier -->
    @php
        // Vérifier si des présences existent déjà pour ce jour
        $hasExistingAttendances = $todayAttendances->isNotEmpty();
    @endphp
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card attendance-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Modification des Présences - {{ $editDate->format('d/m/Y') }}
                        @if($hasExistingAttendances)
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-edit me-1"></i>
                                Données existantes chargées
                            </span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <form id="attendanceForm" 
                          data-update-url="{{ route('attendances.update', $class->id) }}"
                          data-delete-url="{{ route('attendances.delete', $class->id) }}"
                          data-current-date="{{ $editDate->format('Y-m-d') }}">
                        @csrf
                        <input type="hidden" name="attendance_date" value="{{ $editDate->format('Y-m-d') }}">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="attendance-actions">
                                    <button type="button" class="btn btn-success btn-sm" onclick="markAllPresent()">
                                        <i class="fas fa-check-double me-1"></i>
                                        Tout Présent
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="markAllAbsent()">
                                        <i class="fas fa-times me-1"></i>
                                        Tout Absent
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="day-navigation">
                                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="goToPreviousDay()">
                                        <i class="fas fa-chevron-left me-2"></i>
                                        <span class="d-none d-md-inline">Jour Précédent</span>
                                        <span class="d-md-none">Précédent</span>
                                    </button>
                                    <span class="mx-3 fw-bold text-warning">
                                        {{ $editDate->format('d/m/Y') }}
                                        <small class="d-block text-warning">
                                            <i class="fas fa-edit me-1"></i>
                                            Modification
                                        </small>
                                    </span>
                                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="goToNextDay()">
                                        <span class="d-none d-md-inline">Jour Suivant</span>
                                        <span class="d-md-none">Suivant</span>
                                        <i class="fas fa-chevron-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="attendance-management">
                                    <button type="submit" class="btn btn-warning btn-lg" id="saveAttendancesBtn">
                                        <i class="fas fa-save me-2"></i>
                                        Mettre à Jour les Présences
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Spinner de chargement -->
                        <div id="loadingSpinner" class="d-none text-center mt-3">
                            <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <div class="mt-2">
                                <p class="text-muted mb-0">Mise à jour des présences en cours...</p>
                                <small class="text-muted">Veuillez patienter</small>
                            </div>
                        </div>

                        @php
                            // Définir les créneaux horaires de la journée (7h30 à 17h30)
                            $timeSlots = [
                                ['start' => '07:30', 'end' => '08:30', 'label' => '07h30-08h30'],
                                ['start' => '08:30', 'end' => '09:30', 'label' => '08h30-09h30'],
                                ['start' => '09:30', 'end' => '10:30', 'label' => '09h30-10h30'],
                                ['start' => '10:45', 'end' => '11:45', 'label' => '10h45-11h45'],
                                ['start' => '11:45', 'end' => '12:45', 'label' => '11h45-12h45'],
                                ['start' => '14:00', 'end' => '15:00', 'label' => '14h00-15h00'],
                                ['start' => '15:00', 'end' => '16:00', 'label' => '15h00-16h00'],
                                ['start' => '16:15', 'end' => '17:15', 'label' => '16h15-17h15']
                            ];
                        @endphp

                        <div class="attendance-by-student">
                            @foreach($students as $index => $student)
                                @php
                                    // Récupérer les présences pour cet étudiant (déjà groupées par student_id)
                                    $studentAttendances = $todayAttendances->get($student->id, collect());
                                    
                                @endphp
                                <div class="student-attendance-card mb-4">
                                    <div class="student-header">
                                        <div class="student-info">
                                            <h5 class="student-name">
                                                <i class="fas fa-user me-2"></i>
                                                {{ $student->first_name }} {{ $student->last_name }}
                                            </h5>
                                            <small class="text-muted">{{ $student->matricule }}</small>
                                        </div>
                                        <div class="student-actions">
                                            <button type="button" class="btn btn-success btn-sm mark-all-present-btn" data-student-id="{{ $student->id }}">
                                                <i class="fas fa-check-double me-1"></i>
                                                Tout Présent
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm mark-all-absent-btn" data-student-id="{{ $student->id }}">
                                                <i class="fas fa-times me-1"></i>
                                                Tout Absent
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="time-slots-grid">
                                        @foreach($timeSlots as $slotIndex => $slot)
                                            @php
                                                // Récupérer la présence pour ce créneau spécifique
                                                $slotAttendance = $studentAttendances->where('time_slot', $slot['start'])->first();
                                                
                                            @endphp
                                            <div class="time-slot-card" data-student-id="{{ $student->id }}" data-time-slot="{{ $slot['start'] }}">
                                                <div class="time-slot-header">
                                                    <div class="time-slot-label">
                                                        <i class="fas fa-clock me-2"></i>
                                                        <strong>{{ $slot['label'] }}</strong>
                                                    </div>
                                                    <div class="time-slot-status">
                                                        @if($slotAttendance)
                                                            @if($slotAttendance->status == 'present')
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check me-1"></i>
                                                                    Présent
                                                                </span>
                                                            @elseif($slotAttendance->status == 'absent')
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-times me-1"></i>
                                                                    Absent
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-secondary">
                                                                <i class="fas fa-question me-1"></i>
                                                                Non défini
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="time-slot-body">
                                                    <div class="attendance-controls">
                                                        <div class="status-radio-group">
                                                            <div class="form-check form-check-inline">
                                                                <input type="radio" 
                                                                       name="attendances[{{ $student->id }}][{{ $slot['start'] }}][status]" 
                                                                       class="form-check-input status-radio present-radio" 
                                                                       value="present"
                                                                       id="present_{{ $student->id }}_{{ $slot['start'] }}"
                                                                       {{ ($slotAttendance && $slotAttendance->status == 'present') ? 'checked' : '' }}
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}"
                                                                       onchange="toggleTimeSlotFields({{ $student->id }}, '{{ $slot['start'] }}')">
                                                                <label class="form-check-label present-label" for="present_{{ $student->id }}_{{ $slot['start'] }}">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    Présent
                                                                </label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input type="radio" 
                                                                       name="attendances[{{ $student->id }}][{{ $slot['start'] }}][status]" 
                                                                       class="form-check-input status-radio absent-radio" 
                                                                       value="absent"
                                                                       id="absent_{{ $student->id }}_{{ $slot['start'] }}"
                                                                       {{ ($slotAttendance && $slotAttendance->status == 'absent') ? 'checked' : '' }}
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}"
                                                                       onchange="toggleTimeSlotFields({{ $student->id }}, '{{ $slot['start'] }}')">
                                                                <label class="form-check-label absent-label" for="absent_{{ $student->id }}_{{ $slot['start'] }}">
                                                                    <i class="fas fa-times-circle me-1"></i>
                                                                    Absent
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="time-slot-details">
                                                        @if($slotIndex === 0)
                                                        {{-- Seul le premier créneau a un champ d'heure modifiable --}}
                                                        <div class="arrival-time-section" id="arrival_time_{{ $student->id }}_{{ $slot['start'] }}" data-display="{{ ($slotAttendance && $slotAttendance->status == 'present') ? 'block' : 'none' }}">
                                                            <label class="form-label">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Heure d'arrivée
                                                            </label>
                                                            <input type="time" 
                                                                   name="attendances[{{ $student->id }}][{{ $slot['start'] }}][arrival_time]" 
                                                                   class="form-control arrival-time" 
                                                                   value="{{ $slotAttendance && $slotAttendance->arrival_time ? \Carbon\Carbon::parse($slotAttendance->arrival_time)->format('H:i') : '' }}" 
                                                                   data-student-id="{{ $student->id }}" 
                                                                   data-time-slot="{{ $slot['start'] }}"
                                                                   min="{{ $slot['start'] }}"
                                                                   max="{{ $slot['end'] }}"
                                                                   data-min-time="{{ $slot['start'] }}" 
                                                                   data-max-time="{{ $slot['end'] }}"
                                                                   placeholder="HH:MM">
                                                        </div>
                                                        @else
                                                        {{-- Les autres créneaux utilisent l'heure de début par défaut --}}
                                                        <input type="hidden" 
                                                               name="attendances[{{ $student->id }}][{{ $slot['start'] }}][arrival_time]" 
                                                               value="{{ $slot['start'] }}"
                                                               data-student-id="{{ $student->id }}" 
                                                               data-time-slot="{{ $slot['start'] }}">
                                                        @endif
                                                        
                                                        <div class="absence-details" id="absence_fields_{{ $student->id }}_{{ $slot['start'] }}" data-display="{{ ($slotAttendance && $slotAttendance->status == 'absent') ? 'block' : 'none' }}">
                                                            <div class="justified-section">
                                                                <div class="form-check justified-checkbox-container" data-checkbox-id="justified_{{ $student->id }}_{{ $slot['start'] }}">
                                                                    <input type="checkbox" 
                                                                           name="attendances[{{ $student->id }}][{{ $slot['start'] }}][justified]" 
                                                                           class="form-check-input justified-check" 
                                                                           value="1"
                                                                           id="justified_{{ $student->id }}_{{ $slot['start'] }}" 
                                                                           {{ ($slotAttendance && $slotAttendance->justified) ? 'checked' : '' }} 
                                                                           data-student-id="{{ $student->id }}"
                                                                           data-time-slot="{{ $slot['start'] }}">
                                                                    <label class="form-check-label" for="justified_{{ $student->id }}_{{ $slot['start'] }}">
                                                                        <i class="fas fa-shield-alt me-1"></i>
                                                                        Absence justifiée
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="reason-section" id="reason_{{ $student->id }}_{{ $slot['start'] }}" data-display="{{ ($slotAttendance && $slotAttendance->status == 'absent') ? 'block' : 'none' }}">
                                                                <label class="form-label">
                                                                    <i class="fas fa-comment me-1"></i>
                                                                    Raison (optionnel)
                                                                </label>
                                                                <input type="text" 
                                                                       name="attendances[{{ $student->id }}][{{ $slot['start'] }}][reason]" 
                                                                       class="form-control reason-input" 
                                                                       placeholder="Ex: Maladie, rendez-vous médical..." 
                                                                       value="{{ $slotAttendance ? $slotAttendance->reason : '' }}" 
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique de la semaine -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique de la Semaine
                    </h5>
                </div>
                <div class="card-body">
                    @if($weekAttendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Jour</th>
                                        <th>Présents</th>
                                        <th>Absents</th>
                                        <th>En Retard</th>
                                        <th>Excusés</th>
                                        <th>Taux</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weekAttendances as $date => $attendances)
                                        @php
                                            $dateObj = \Carbon\Carbon::parse($date);
                                            
                                            // Grouper par étudiant pour appliquer la nouvelle logique
                                            $studentsByDay = $attendances->groupBy('student_id');
                                            $presentCount = 0;
                                            $absentCount = 0;
                                            $lateCount = 0;
                                            $excusedCount = 0;
                                            
                                            foreach ($studentsByDay as $studentId => $studentAttendances) {
                                                $hasPresent = false;
                                                $hasLate = false;
                                                $hasJustifiedAbsence = false;
                                                $hasUnjustifiedAbsence = false;
                                                $firstArrivalTime = null;
                                                $firstPresenceTime = null;
                                                
                                                // Analyser les créneaux de l'étudiant pour ce jour
                                                $firstArrivalTime = null;
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
                                            
                                            $total = $studentsByDay->count();
                                            $rate = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0;
                                            
                                            // Pré-calculer la classe CSS pour éviter les erreurs de syntaxe
                                            $progressBarClass = $rate >= 90 ? 'bg-success' : ($rate >= 70 ? 'bg-warning' : 'bg-danger');
                                            $progressBarStyle = 'width: ' . $rate . '%';
                                        @endphp
                                        <tr>
                                            <td>{{ $dateObj->format('d/m/Y') }}</td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $dateObj->locale('fr')->dayName }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $presentCount }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ $absentCount }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $lateCount }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $excusedCount }}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar {{ $progressBarClass }}" role="progressbar" data-width="{{ $rate }}">
                                                        {{ $rate }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('attendances.show', ['class' => $class->id, 'date' => $date, 'academic_year_id' => $academicYearId]) }}" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>
                                                        Voir
                                                    </a>
                                                    @if($date === $editDate->format('Y-m-d'))
                                                        <span class="btn btn-warning btn-sm disabled">
                                                            <i class="fas fa-edit me-1"></i>
                                                            En cours
                                                        </span>
                                                    @else
                                                        <a href="{{ route('attendances.edit', ['class' => $class->id, 'date' => $date, 'academic_year_id' => $academicYearId]) }}" 
                                                           class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit me-1"></i>
                                                            Modifier
                                                        </a>
                                                    @endif
                                                    <button type="button" class="btn btn-info btn-sm generate-pdf-btn" 
                                                            data-date="{{ $date }}" 
                                                            data-formatted-date="{{ $dateObj->format('d/m/Y') }}" 
                                                            data-class-name="{{ $class->name }}"
                                                            title="Télécharger la fiche de présence en PDF">
                                                        <i class="fas fa-file-pdf me-1"></i>
                                                        PDF
                                                    </button>
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
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h4>Aucune présence enregistrée</h4>
                            <p>Aucune présence n'a été enregistrée pour cette semaine.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal pour les détails d'un élève -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-labelledby="studentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailsModalLabel">
                    <i class="fas fa-user-graduate me-2"></i>
                    Détails de Présence
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="studentDetailsContent">
                    <!-- Le contenu sera chargé dynamiquement -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour la suppression -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation de Suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center mb-3">Êtes-vous sûr de vouloir supprimer toutes les présences ?</h6>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Date concernée :</strong> <span id="deleteDateDisplay"></span>
                </div>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible et supprimera définitivement toutes les données de présence pour ce jour.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>
                    Supprimer Définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inclure les styles et scripts de la page manage -->
@include('attendances.manage-styles')

<script>
// Variables globales pour les IDs
const classId = '{{ $class->id }}';
const academicYearId = '{{ $academicYearId }}';

document.addEventListener('DOMContentLoaded', function() {
    // Gérer les barres de progression
    document.querySelectorAll('.progress-bar[data-width]').forEach(function(bar) {
        const width = bar.getAttribute('data-width');
        bar.style.width = width + '%';
    });
    
    // Gérer l'affichage initial des conteneurs
    document.querySelectorAll('.arrival-time-section[data-display], .absence-details[data-display]').forEach(function(container) {
        const display = container.getAttribute('data-display');
        if (display === 'block') {
            if (container.classList.contains('arrival-time-section')) {
                const timeSlot = container.id.split('_').pop();
                if (timeSlot === '07:30') {
                    container.classList.add('show');
                }
            } else {
                container.classList.add('show');
            }
        }
    });
    
    // Gérer les boutons "Tout Présent" et "Tout Absent" par élève
    document.querySelectorAll('.mark-all-present-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            markStudentAllPresent(studentId);
        });
    });
    
    document.querySelectorAll('.mark-all-absent-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            markStudentAllAbsent(studentId);
        });
    });
    
    // Gérer les clics sur les conteneurs de checkbox "Absence justifiée"
    document.querySelectorAll('.justified-checkbox-container').forEach(function(container) {
        container.addEventListener('click', function() {
            const checkboxId = this.getAttribute('data-checkbox-id');
            toggleJustifiedCheckbox(checkboxId);
        });
    });
    
    // Gérer la validation des heures d'arrivée
    document.querySelectorAll('.arrival-time').forEach(function(input) {
        input.addEventListener('change', function() {
            validateArrivalTime(this);
        });
    });
    
    // Gérer les boutons de génération PDF
    const generatePdfBtns = document.querySelectorAll('.generate-pdf-btn');
    if (generatePdfBtns.length > 0) {
        generatePdfBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                const formattedDate = this.getAttribute('data-formatted-date');
                const className = this.getAttribute('data-class-name');
                generateAttendancePDF(date, formattedDate, className);
            });
        });
    }
    
    // Gestion du formulaire de présence
    const attendanceForm = document.getElementById('attendanceForm');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmission();
        });
    }
});

// Fonctions pour gérer le spinner overlay global
function showSpinnerOverlay(text, subtext = '') {
    hideSpinnerOverlay();
    
    const overlay = document.createElement('div');
    overlay.className = 'spinner-overlay';
    overlay.id = 'globalSpinnerOverlay';
    
    overlay.innerHTML = `
        <div class="spinner-content">
            <div class="spinner-border text-warning" role="status">
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

// Fonction pour basculer le checkbox "Absence justifiée"
function toggleJustifiedCheckbox(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change'));
    }
}

// Fonction pour valider l'heure d'arrivée
function validateArrivalTime(input) {
    const value = input.value;
    const minTime = input.getAttribute('data-min-time');
    const maxTime = input.getAttribute('data-max-time');
    
    if (value && minTime && maxTime) {
        if (value < minTime || value > maxTime) {
            showAlert('warning', `L'heure d'arrivée doit être entre ${minTime} et ${maxTime}`);
            input.value = minTime;
        }
    }
}

// Fonction pour basculer les champs selon le statut
function toggleTimeSlotFields(studentId, timeSlot) {
    const presentRadio = document.getElementById('present_' + studentId + '_' + timeSlot);
    const absentRadio = document.getElementById('absent_' + studentId + '_' + timeSlot);
    const arrivalContainer = document.getElementById('arrival_time_' + studentId + '_' + timeSlot);
    const absenceContainer = document.getElementById('absence_fields_' + studentId + '_' + timeSlot);
    const reasonContainer = document.getElementById('reason_' + studentId + '_' + timeSlot);
    
    if (arrivalContainer) arrivalContainer.classList.remove('show');
    if (absenceContainer) absenceContainer.classList.remove('show');
    if (reasonContainer) reasonContainer.classList.remove('show');
    
    if (presentRadio && presentRadio.checked) {
        if (timeSlot === '07:30' && arrivalContainer) {
            arrivalContainer.classList.add('show');
        }
    } else if (absentRadio && absentRadio.checked) {
        if (absenceContainer) absenceContainer.classList.add('show');
        if (reasonContainer) reasonContainer.classList.add('show');
    }
}

// Fonctions pour marquer tous les créneaux d'un élève
function markStudentAllPresent(studentId) {
    const timeSlots = ['07:30', '08:30', '09:30', '10:45', '11:45', '14:00', '15:00', '16:15'];
    timeSlots.forEach(timeSlot => {
        const presentRadio = document.getElementById('present_' + studentId + '_' + timeSlot);
        if (presentRadio) {
            presentRadio.checked = true;
            toggleTimeSlotFields(studentId, timeSlot);
        }
    });
}

function markStudentAllAbsent(studentId) {
    const timeSlots = ['07:30', '08:30', '09:30', '10:45', '11:45', '14:00', '15:00', '16:15'];
    timeSlots.forEach(timeSlot => {
        const absentRadio = document.getElementById('absent_' + studentId + '_' + timeSlot);
        if (absentRadio) {
            absentRadio.checked = true;
            toggleTimeSlotFields(studentId, timeSlot);
        }
    });
}

// Fonctions pour marquer tous les élèves
function markAllPresent() {
    document.querySelectorAll('.student-attendance-card').forEach(card => {
        const studentId = card.querySelector('[data-student-id]').dataset.studentId;
        markStudentAllPresent(studentId);
    });
}

function markAllAbsent() {
    document.querySelectorAll('.student-attendance-card').forEach(card => {
        const studentId = card.querySelector('[data-student-id]').dataset.studentId;
        markStudentAllAbsent(studentId);
    });
}

// Fonction pour afficher les alertes
function showAlert(type, message) {
    let alertClass = 'alert-info';
    if (type === 'success') {
        alertClass = 'alert-success';
    } else if (type === 'error') {
        alertClass = 'alert-danger';
    } else if (type === 'warning') {
        alertClass = 'alert-warning';
    }
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Fonction pour gérer la soumission du formulaire
function handleFormSubmission() {
    const formData = new FormData(document.getElementById('attendanceForm'));
    const attendances = [];
    
    document.querySelectorAll('.student-attendance-card').forEach(card => {
        const studentId = card.querySelector('[data-student-id]').dataset.studentId;
        const timeSlots = ['07:30', '08:30', '09:30', '10:45', '11:45', '14:00', '15:00', '16:15'];
        
        timeSlots.forEach(timeSlot => {
            const statusRadio = card.querySelector(`input[name*="[${timeSlot}][status]"]:checked`);
            const status = statusRadio ? statusRadio.value : null;
            
            let arrivalTime = '';
            let reason = '';
            let justified = false;
            
            if (status === 'present') {
                const arrivalInput = card.querySelector(`input[name*="[${timeSlot}][arrival_time]"]`);
                if (timeSlot === '07:30') {
                    arrivalTime = arrivalInput ? arrivalInput.value : '';
                } else {
                    arrivalTime = timeSlot;
                }
            } else if (status === 'absent') {
                const reasonInput = card.querySelector(`input[name*="[${timeSlot}][reason]"]`);
                const justifiedCheck = card.querySelector(`input[name*="[${timeSlot}][justified]"]`);
                reason = reasonInput ? reasonInput.value : '';
                justified = justifiedCheck ? justifiedCheck.checked : false;
            }
            
            if (status) {
                attendances.push({
                    student_id: studentId,
                    time_slot: timeSlot,
                    status: status,
                    arrival_time: arrivalTime,
                    reason: reason,
                    justified: justified
                });
            }
        });
    });
    
    showSpinnerOverlay('Mise à jour en cours...', 'Sauvegarde des modifications');
    
    fetch('{{ route("attendances.update", $class->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            attendance_date: formData.get('attendance_date'),
            attendances: attendances
        })
    })
    .then(response => response.json())
    .then(data => {
        hideSpinnerOverlay();
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        hideSpinnerOverlay();
        console.error('Error:', error);
        showAlert('error', 'Erreur lors de l\'enregistrement des présences');
    });
}
</script>

@endsection
