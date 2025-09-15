@extends('layouts.app')

@section('title', 'Gestion des Présences - ' . $class->name)

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
                            @if(request()->get('date') && request()->get('date') !== $today->format('Y-m-d'))
                                Modification des Présences
                            @else
                            Gestion des Présences
                            @endif
                        </h1>
                        <p class="page-subtitle">
                            {{ $class->name }} - {{ $academicYear->name ?? 'Année non définie' }}
                            @if(request()->get('date') && request()->get('date') !== $today->format('Y-m-d'))
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-edit me-1"></i>
                                    Modification du {{ $today->format('d/m/Y') }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="page-actions">
                        @if(request()->get('date') && request()->get('date') !== $today->format('Y-m-d'))
                            <a href="{{ route('attendances.manage', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                               class="btn btn-info me-2">
                                <i class="fas fa-calendar-day me-2"></i>
                                Aujourd'hui
                            </a>
                        @endif
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

    <!-- Fiche de présence du jour -->
    @php
        // Vérifier si des présences existent déjà pour ce jour
        $hasExistingAttendances = $todayAttendances->isNotEmpty();
    @endphp
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card attendance-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        @if(request()->get('date') && request()->get('date') !== $today->format('Y-m-d'))
                            Fiche de Présence - {{ $today->format('d/m/Y') }} (Modification)
                        @else
                        Fiche de Présence - {{ $today->format('d/m/Y') }}
                        @endif
                        @if($hasExistingAttendances)
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check me-1"></i>
                                Déjà enregistrée
                            </span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <form id="attendanceForm" 
                          data-update-url="{{ route('attendances.update', $class->id) }}"
                          data-delete-url="{{ route('attendances.delete', $class->id) }}"
                          data-current-date="{{ $today->format('Y-m-d') }}">
                        @csrf
                        <input type="hidden" name="attendance_date" value="{{ $today->format('Y-m-d') }}">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                @if(!$hasExistingAttendances)
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
                                @else
                                <div class="attendance-actions">
                                    <span class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Actions désactivées - Présences déjà enregistrées
                                    </span>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="day-navigation">
                                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="goToPreviousDay()">
                                        <i class="fas fa-chevron-left me-2"></i>
                                        <span class="d-none d-md-inline">Jour Précédent</span>
                                        <span class="d-md-none">Précédent</span>
                                    </button>
                                    <span class="mx-3 fw-bold text-primary">
                                        @if(request()->get('date'))
                                            {{ \Carbon\Carbon::parse(request()->get('date'))->format('d/m/Y') }}
                                        @else
                                            {{ $today->format('d/m/Y') }}
                                            <small class="d-block text-success">
                                                <i class="fas fa-calendar-day me-1"></i>
                                                Aujourd'hui
                                            </small>
                                        @endif
                                    </span>
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-lg {{ request()->get('date') && request()->get('date') !== $today->format('Y-m-d') ? 'disabled' : '' }}" 
                                            onclick="goToNextDay()"
                                            {{ request()->get('date') && request()->get('date') !== $today->format('Y-m-d') ? 'disabled' : '' }}
                                            title="{{ request()->get('date') && request()->get('date') !== $today->format('Y-m-d') ? 'Bouton désactivé pour les jours précédents' : 'Aller au jour suivant' }}">
                                        <span class="d-none d-md-inline">Jour Suivant</span>
                                        <span class="d-md-none">Suivant</span>
                                        <i class="fas fa-chevron-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                @if(!$hasExistingAttendances)
                                <div class="attendance-management">
                                    <button type="submit" class="btn btn-primary btn-lg" id="saveAttendancesBtn">
                                        <i class="fas fa-save me-2"></i>
                                        Enregistrer les Présences
                                    </button>
                                </div>
                                @else
                                <div class="attendance-management">
                                    <span class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Présences enregistrées
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>

                                <!-- Spinner de chargement -->
                                <div id="loadingSpinner" class="d-none text-center mt-3">
                                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-muted mb-0">Enregistrement des présences en cours...</p>
                                        <small class="text-muted">Veuillez patienter</small>
                                    </div>
                                </div>
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
                                            @if(!$hasExistingAttendances)
                                            <button type="button" class="btn btn-success btn-sm mark-all-present-btn" data-student-id="{{ $student->id }}">
                                                <i class="fas fa-check-double me-1"></i>
                                                Tout Présent
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm mark-all-absent-btn" data-student-id="{{ $student->id }}">
                                                <i class="fas fa-times me-1"></i>
                                                Tout Absent
                                            </button>
                                            @else
                                            <span class="text-muted small">
                                                <i class="fas fa-lock me-1"></i>
                                                Lecture seule
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="time-slots-grid">
                                        @foreach($timeSlots as $slotIndex => $slot)
                                            @php
                                                $slotAttendance = $studentAttendances->where('time_slot', $slot['start'])->first();
                                            @endphp
                                            <div class="time-slot-card {{ $hasExistingAttendances ? 'readonly' : '' }}" data-student-id="{{ $student->id }}" data-time-slot="{{ $slot['start'] }}">
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
                                                            @elseif($slotAttendance->status == 'late')
                                                                <span class="badge bg-warning">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    En Retard
                                                                </span>
                                                            @endif
                                                        @else
                                                            @if($hasExistingAttendances)
                                                                <span class="badge bg-info">
                                                                    <i class="fas fa-lock me-1"></i>
                                                                    Créneau enregistré
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-question me-1"></i>
                                                                    Non défini
                                                                </span>
                                                            @endif
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
                                                                       {{ $hasExistingAttendances ? 'disabled' : '' }}
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}"
                                                                       onchange="toggleTimeSlotFields({{ $student->id }}, '{{ $slot['start'] }}')">
                                                                <label class="form-check-label present-label {{ $hasExistingAttendances ? 'text-muted' : '' }}" for="present_{{ $student->id }}_{{ $slot['start'] }}">
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
                                                                       {{ $hasExistingAttendances ? 'disabled' : '' }}
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}"
                                                                       onchange="toggleTimeSlotFields({{ $student->id }}, '{{ $slot['start'] }}')">
                                                                <label class="form-check-label absent-label {{ $hasExistingAttendances ? 'text-muted' : '' }}" for="absent_{{ $student->id }}_{{ $slot['start'] }}">
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
                                                                   value="{{ $slotAttendance ? $slotAttendance->arrival_time : '' }}" 
                                                                   {{ $hasExistingAttendances ? 'disabled' : '' }}
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
                                                                           {{ $hasExistingAttendances ? 'disabled' : '' }}
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
                                                                       {{ $hasExistingAttendances ? 'disabled' : '' }}
                                                                       data-student-id="{{ $student->id }}"
                                                                       data-time-slot="{{ $slot['start'] }}">
                                                            </div>
                                                        </div>
                                                        
                                                        {{-- Affichage des détails en mode lecture seule pour les créneaux enregistrés --}}
                                                        @if($hasExistingAttendances && $slotAttendance)
                                                            <div class="readonly-details mt-2">
                                                                @if($slotAttendance->status == 'present' && $slotAttendance->arrival_time)
                                                                    <div class="alert alert-info py-2">
                                                                        <small>
                                                                            <i class="fas fa-clock me-1"></i>
                                                                            <strong>Heure d'arrivée :</strong> 
                                                                            {{ \Carbon\Carbon::parse($slotAttendance->arrival_time)->format('H:i') }}
                                                                        </small>
                                                                    </div>
                                                                @endif
                                                                
                                                                @if($slotAttendance->status == 'absent')
                                                                    <div class="alert alert-warning py-2">
                                                                        <small>
                                                                            <i class="fas fa-info-circle me-1"></i>
                                                                            <strong>Absence :</strong> 
                                                                            {{ $slotAttendance->justified ? 'Justifiée' : 'Non justifiée' }}
                                                                            @if($slotAttendance->reason)
                                                                                <br><strong>Raison :</strong> {{ $slotAttendance->reason }}
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                @endif
                                                                
                                                                @if($slotAttendance->status == 'late')
                                                                    <div class="alert alert-warning py-2">
                                                                        <small>
                                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                                            <strong>Retard :</strong> 
                                                                            @if($slotAttendance->arrival_time)
                                                                                Arrivé à {{ \Carbon\Carbon::parse($slotAttendance->arrival_time)->format('H:i') }}
                                                                            @else
                                                                                Retard non spécifié
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
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

    <!-- Filtres pour les semaines précédentes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtres et Historique
                    </h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_type" class="form-label">Période</label>
                            <select name="filter_type" id="filter_type" class="form-select">
                                <option value="week">Semaine courante</option>
                                <option value="month">Mois courant</option>
                                <option value="custom">Semaine personnalisée</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="customDateContainer" style="display: none;">
                            <label for="custom_date" class="form-label">Date de la semaine</label>
                            <input type="date" name="custom_date" id="custom_date" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>
                                Filtrer
                            </button>
                        </div>
                    </form>
                    
                    <div id="filterResults" class="mt-4" style="display: none;">
                        <!-- Les résultats du filtre seront affichés ici -->
                    </div>
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
                                                <a href="{{ route('attendances.view', ['class' => $class->id, 'date' => $date, 'academic_year_id' => $academicYearId]) }}" 
                                                       class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>
                                                    Voir
                                                </a>
                                                    <button type="button" class="btn btn-warning btn-sm edit-attendance-btn" 
                                                            data-date="{{ $date }}" 
                                                            data-formatted-date="{{ $dateObj->format('d/m/Y') }}"
                                                            title="Modifier les présences de ce jour">
                                                        <i class="fas fa-edit me-1"></i>
                                                        Modifier
                                                    </button>
                                                    <button type="button" class="btn btn-info btn-sm generate-pdf-btn" 
                                                            data-date="{{ $date }}" 
                                                            data-formatted-date="{{ $dateObj->format('d/m/Y') }}" 
                                                            data-class-name="{{ $class->name }}"
                                                            title="Télécharger la fiche de présence en PDF">
                                                        <i class="fas fa-file-pdf me-1"></i>
                                                        PDF
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm delete-attendance-btn" 
                                                            data-date="{{ $date }}" 
                                                            data-formatted-date="{{ $dateObj->format('d/m/Y') }}"
                                                            title="Supprimer toutes les présences de ce jour">
                                                        <i class="fas fa-trash me-1"></i>
                                                        Supprimer
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

    <!-- Récapitulatif par élève -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-graduate me-2"></i>
                        Récapitulatif par Élève
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Élève</th>
                                    <th>Présent</th>
                                    <th>Absent</th>
                                    <th>En Retard</th>
                                    <th>Excusé</th>
                                    <th>Taux de Présence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        // Calculer les statistiques de l'élève pour la semaine
                                        $studentWeekAttendances = $weekAttendances->flatten()->where('student_id', $student->id);
                                        
                                        // Grouper par date pour appliquer la logique intelligente
                                        $studentAttendancesByDate = $studentWeekAttendances->groupBy(function($attendance) {
                                            return $attendance->attendance_date->format('Y-m-d');
                                        });
                                        
                                        $presentCount = 0;
                                        $absentCount = 0;
                                        $lateCount = 0;
                                        $excusedCount = 0;
                                        
                                        foreach ($studentAttendancesByDate as $date => $dayAttendances) {
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
                                            
                                            foreach ($dayAttendances as $attendance) {
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
                                        
                                        // Le total est le nombre de jours où l'élève a été présent (avec ou sans retard) + absents + excusés
                                        $total = $presentCount + $absentCount + $excusedCount;
                                        $rate = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0;
                                        
                                        // Pré-calculer la classe CSS pour éviter les erreurs de syntaxe
                                        $progressBarClass = $rate >= 90 ? 'bg-success' : ($rate >= 70 ? 'bg-warning' : 'bg-danger');
                                        $progressBarStyle = 'width: ' . $rate . '%';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-name">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </div>
                                                <small class="text-muted">{{ $student->matricule }}</small>
                                            </div>
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
                                            <button type="button" class="btn btn-primary btn-sm" data-student-id="{{ $student->id }}" data-student-name="{{ $student->first_name }} {{ $student->last_name }}">
                                                <i class="fas fa-eye me-1"></i>
                                                Détails
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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

.attendance-card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.attendance-actions .btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.attendance-table {
    border-radius: 10px;
    overflow: hidden;
}

.student-row {
    transition: all 0.3s ease;
}

.student-row:hover {
    background-color: #f8f9fa;
}

.student-info {
    display: flex;
    flex-direction: column;
}

.student-name {
    font-weight: 600;
    color: #2c3e50;
}

.attendance-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.attendance-status .form-check {
    margin-bottom: 0;
}

.arrival-time, .reason-input {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.arrival-time:focus, .reason-input:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.arrival-time-container, .absence-fields-container {
    transition: all 0.3s ease;
}

.attendance-summary .badge {
    font-size: 0.8rem;
    padding: 0.5rem;
}

.justified-check {
    margin-right: 0.5rem;
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

.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 10px;
    font-size: 0.8rem;
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

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    background-color: #e0a800;
    border-color: #e0a800;
    color: #212529;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
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

/* Styles pour le nouveau système de présence par élève */
.attendance-by-student {
    margin-top: 2rem;
}

.student-attendance-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.student-attendance-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.student-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f8f9fa;
}

.student-info .student-name {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
}

.student-info .text-muted {
    font-size: 0.9rem;
}

.student-actions .btn {
    margin-left: 0.5rem;
}

/* Nouveau design en grille pour les créneaux horaires */
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.time-slot-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.time-slot-card:hover {
    border-color: #3498db;
    box-shadow: 0 4px 20px rgba(52, 152, 219, 0.15);
    transform: translateY(-2px);
}

.time-slot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f8f9fa;
}

.time-slot-label {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.1rem;
}

.time-slot-label i {
    color: #3498db;
}

.time-slot-status .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
}

.time-slot-body {
    padding: 0;
}

.attendance-controls {
    margin-bottom: 1rem;
}

.status-radio-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.status-radio-group .form-check {
    margin-bottom: 0;
}

.status-radio-group .form-check-input {
    margin-right: 0.5rem;
    transform: scale(1.2);
}

.status-radio-group .form-check-label {
    font-weight: 500;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.present-label {
    color: #28a745;
}

.present-label:hover {
    background-color: rgba(40, 167, 69, 0.1);
    border-color: #28a745;
}

.absent-label {
    color: #dc3545;
}

.absent-label:hover {
    background-color: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
}

.status-radio-group .form-check-input:checked + .present-label {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

.status-radio-group .form-check-input:checked + .absent-label {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.time-slot-details {
    margin-top: 1rem;
}

.arrival-time-section, .absence-details {
    transition: all 0.3s ease;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
}

.arrival-time-section.show, .absence-details.show {
    opacity: 1;
    max-height: 200px;
}

.arrival-time-section .form-label,
.reason-section .form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.arrival-time-section .form-label i,
.reason-section .form-label i {
    color: #3498db;
}

.arrival-time-section .form-control,
.reason-section .form-control {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.arrival-time-section .form-control:focus,
.reason-section .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.justified-section {
    margin-bottom: 1rem;
}

.justified-section .form-check {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border-radius: 12px;
    border: 2px solid #ffc107;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
    cursor: pointer;
}

.justified-checkbox-container {
    cursor: pointer;
}

.justified-section .form-check:hover {
    background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
    border-color: #f39c12;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.justified-section .form-check-input {
    margin-right: 0.75rem;
    transform: scale(1.5);
    width: 20px;
    height: 20px;
    border: 2px solid #ffc107;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
}

.justified-section .form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' view='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
}

.justified-section .form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
    border-color: #f39c12;
}

.justified-section .form-check-label {
    font-weight: 600;
    color: #856404;
    cursor: pointer;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}

.justified-section .form-check-label i {
    color: #f39c12;
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

/* Spinner de chargement */
#loadingSpinner {
    animation: fadeIn 0.3s ease-in;
}

/* Spinner overlay global */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}

.spinner-overlay .spinner-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    text-align: center;
    max-width: 400px;
    margin: 0 1rem;
}

.spinner-overlay .spinner-border {
    width: 4rem;
    height: 4rem;
    border-width: 0.4em;
    margin-bottom: 1rem;
}

.spinner-overlay .spinner-text {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.spinner-overlay .spinner-subtext {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}

/* Styles pour le tableau de récapitulatif par élève */
.student-info {
    display: flex;
    flex-direction: column;
}

.student-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.student-info small {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Styles pour les cartes de statistiques dans la modal */
.card.bg-success,
.card.bg-danger,
.card.bg-warning,
.card.bg-info {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.card.bg-success:hover,
.card.bg-danger:hover,
.card.bg-warning:hover,
.card.bg-info:hover {
    transform: translateY(-2px);
}

/* Amélioration de la modal */
.modal-lg {
    max-width: 800px;
}

.modal-body {
    padding: 2rem;
}

.modal-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-bottom: none;
}

.modal-header .btn-close {
    filter: invert(1);
}

/* Styles pour le modal de suppression */
.modal-header.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.modal-header.bg-danger .btn-close {
    filter: invert(1);
}

#deleteConfirmationModal .modal-body {
    text-align: center;
}

#deleteConfirmationModal .alert {
    text-align: left;
    margin-bottom: 1rem;
}

#deleteConfirmationModal .fa-trash-alt {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Styles pour les nouveaux contrôles */
.day-navigation {
    display: flex;
    gap: 1rem;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}

.day-navigation .btn {
    min-width: 120px;
    font-weight: 600;
}

.day-navigation span {
    font-size: 1.1rem;
    min-width: 100px;
    text-align: center;
}

.attendance-management {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: flex-end;
}

.attendance-actions {
    display: flex;
    gap: 0.5rem;
}

/* Styles pour les champs désactivés */
input:disabled, 
select:disabled, 
textarea:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #f8f9fa;
}

/* Styles pour les boutons désactivés */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Styles pour les labels désactivés */
.text-muted {
    opacity: 0.7;
}

/* Indicateur visuel pour le mode lecture seule */
.time-slot-card.readonly {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.time-slot-card.readonly .time-slot-header {
    background-color: #e9ecef;
    border-radius: 10px 10px 0 0;
}

/* Styles pour les détails en mode lecture seule */
.readonly-details {
    margin-top: 0.5rem;
}

.readonly-details .alert {
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    border-radius: 8px;
}

.readonly-details .alert small {
    font-size: 0.8rem;
    line-height: 1.3;
}

.readonly-details .alert i {
    width: 14px;
    text-align: center;
}

/* Indicateur visuel pour le mode édition */
.editing-mode {
    border: 2px solid #ffc107 !important;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
}

/* Animation pour les boutons */
.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#loadingSpinner .spinner-border {
    border-width: 0.3em;
    animation: spin 1s linear infinite;
}

#loadingSpinner p {
    font-weight: 500;
    font-size: 1rem;
}

#loadingSpinner small {
    font-size: 0.875rem;
    opacity: 0.8;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive design */
@media (max-width: 768px) {
    .student-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .student-actions {
        width: 100%;
    }
    
    .student-actions .btn {
        margin-left: 0;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .time-slots-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .time-slot-card {
        padding: 1rem;
    }
    
    .time-slot-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .status-radio-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .status-radio-group .form-check-label {
        padding: 0.75rem 1rem;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .time-slot-card {
        padding: 0.75rem;
    }
    
    .time-slot-label {
        font-size: 1rem;
    }
    
    .status-radio-group .form-check-label {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
}

/* Style pour le conteneur de statut des présences */
.presence-status-container {
    padding: 1.5rem;
    border-radius: 10px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #28a745;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
}

.bg-light-success {
    background-color: #d4edda !important;
    border-left: 4px solid #28a745;
}

.presence-status-container h4 {
    font-weight: 600;
    margin-bottom: 1rem;
}

.presence-status-container p {
    font-size: 1rem;
    line-height: 1.5;
}

.presence-status-container .text-success {
    color: #155724 !important;
}
</style>

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
    
    // Les présences sont gérées côté serveur avec la condition if(!$hasExistingAttendances)
    
    // Gérer l'affichage initial des conteneurs
    document.querySelectorAll('.arrival-time-section[data-display], .absence-details[data-display]').forEach(function(container) {
        const display = container.getAttribute('data-display');
        if (display === 'block') {
            // Pour les champs d'arrivée, ne les afficher que pour le premier créneau (07:30)
            if (container.classList.contains('arrival-time-section')) {
                const timeSlot = container.id.split('_').pop();
                if (timeSlot === '07:30') {
                    container.classList.add('show');
                }
            } else {
                // Pour les autres conteneurs (absence-details), les afficher normalement
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
    
    // Gérer les boutons de détails d'élève (seulement ceux avec data-student-name)
    document.querySelectorAll('button[data-student-id][data-student-name]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            showStudentDetails(studentId, studentName);
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
    
    // Gérer les boutons de modification
    const editAttendanceBtns = document.querySelectorAll('.edit-attendance-btn');
    if (editAttendanceBtns.length > 0) {
        editAttendanceBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                const formattedDate = this.getAttribute('data-formatted-date');
                editAttendanceForDate(date, formattedDate);
            });
        });
    }
    
    // Gérer les boutons de suppression
    const deleteAttendanceBtns = document.querySelectorAll('.delete-attendance-btn');
    if (deleteAttendanceBtns.length > 0) {
        deleteAttendanceBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                const formattedDate = this.getAttribute('data-formatted-date');
                showDeleteConfirmationModal(date, formattedDate);
            });
        });
    }
    
    // Gérer le bouton de confirmation de suppression
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const date = this.getAttribute('data-date-to-delete');
            if (date) {
                deleteDayAttendances(date);
            } else {
                console.error('Date à supprimer non trouvée');
                showAlert('error', 'Erreur: Date à supprimer non trouvée');
            }
        });
    } else {
        console.warn('Bouton de confirmation de suppression non trouvé');
    }
    
    document.querySelectorAll('.mark-all-absent-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            markStudentAllAbsent(studentId);
        });
    });
    
    // Gestion du formulaire de présence
    const attendanceForm = document.getElementById('attendanceForm');
    const filterForm = document.getElementById('filterForm');
    const filterType = document.getElementById('filter_type');
    const customDateContainer = document.getElementById('customDateContainer');
    
    // Afficher/masquer le champ de date personnalisée
    filterType.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateContainer.style.display = 'block';
        } else {
            customDateContainer.style.display = 'none';
        }
    });
    
    // Soumission du formulaire de présence
    attendanceForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const attendances = [];
        
        // Collecter les données de présence par créneaux horaires
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
                        // Pour le premier créneau, utiliser la valeur saisie
                        arrivalTime = arrivalInput ? arrivalInput.value : '';
                    } else {
                        // Pour les autres créneaux, utiliser l'heure de début du créneau
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
        
        // Afficher le spinner de chargement
        showLoadingSpinner();
        
        // Envoyer les données
        fetch('{{ route("attendances.store", $class->id) }}', {
            method: 'POST',
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
            hideLoadingSpinner();
            if (data.success) {
                showAlert('success', data.message);
                
                // Proposer d'aller au jour suivant
                setTimeout(() => {
                    if (confirm('Voulez-vous passer au jour suivant pour enregistrer les présences ?')) {
                        goToNextDay();
                    } else {
                        // Recharger la page pour voir les changements
                    location.reload();
                    }
                }, 1500);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            hideLoadingSpinner();
            console.error('Error:', error);
            showAlert('error', 'Erreur lors de l\'enregistrement des présences');
        });
    });
    
    // Soumission du formulaire de filtre
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Afficher le spinner overlay
        showSpinnerOverlay('Filtrage en cours...', 'Recherche des présences selon les critères');
        
        const formData = new FormData(this);
        
        fetch('{{ route("attendances.filter", $class->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                filter_type: formData.get('filter_type'),
                custom_date: formData.get('custom_date'),
                academic_year_id: '{{ $academicYearId }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            hideSpinnerOverlay();
            if (data.success) {
                displayFilterResults(data.attendances, data.start_date, data.end_date);
            } else {
                showAlert('error', 'Erreur lors du filtrage');
            }
        })
        .catch(error => {
            hideSpinnerOverlay();
            console.error('Error:', error);
            showAlert('error', 'Erreur lors du filtrage');
        });
    });
});

// Fonctions pour gérer le spinner de chargement
function showLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    const saveBtn = document.getElementById('saveAttendancesBtn');
    
    if (spinner && saveBtn) {
        spinner.classList.remove('d-none');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
    }
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    const saveBtn = document.getElementById('saveAttendancesBtn');
    
    if (spinner && saveBtn) {
        spinner.classList.add('d-none');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer les Présences';
    }
}

// Fonctions pour gérer le spinner overlay global
function showSpinnerOverlay(text, subtext = '') {
    // Supprimer l'overlay existant s'il y en a un
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

// Fonction pour basculer le checkbox "Absence justifiée" en cliquant sur le cadre
function toggleJustifiedCheckbox(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        // Déclencher l'événement change pour que les autres scripts puissent réagir
        checkbox.dispatchEvent(new Event('change'));
    }
}

// Fonction pour afficher les détails d'un élève
function showStudentDetails(studentId, studentName) {
    const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
    const modalTitle = document.getElementById('studentDetailsModalLabel');
    const modalContent = document.getElementById('studentDetailsContent');
    
    // Mettre à jour le titre
    modalTitle.innerHTML = `<i class="fas fa-user-graduate me-2"></i>Détails de Présence - ${studentName}`;
    
    // Afficher un spinner de chargement
    modalContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3 mb-2 fw-bold">Chargement des détails...</p>
            <small class="text-muted">Analyse des données de présence</small>
        </div>
    `;
    
    // Afficher la modal
    modal.show();
    
    // Simuler le chargement des données (vous pouvez remplacer par un appel AJAX réel)
    setTimeout(() => {
        // Récupérer les données de l'élève depuis le tableau
        const studentButton = document.querySelector(`button[data-student-id="${studentId}"][data-student-name]`);
        if (!studentButton) {
            modalContent.innerHTML = '<div class="alert alert-danger">Erreur: Impossible de trouver les données de l\'élève (ID: ' + studentId + ').</div>';
            return;
        }
        
        const studentRow = studentButton.closest('tr');
        if (!studentRow) {
            modalContent.innerHTML = '<div class="alert alert-danger">Erreur: Impossible de trouver la ligne de l\'élève.</div>';
            return;
        }
        
        // Récupérer les données depuis les badges
        const presentBadge = studentRow.querySelector('.badge.bg-success');
        const absentBadge = studentRow.querySelector('.badge.bg-danger');
        const lateBadge = studentRow.querySelector('.badge.bg-warning');
        const excusedBadge = studentRow.querySelector('.badge.bg-info');
        const progressBar = studentRow.querySelector('.progress-bar');
        
        const presentCount = presentBadge ? presentBadge.textContent.trim() : '0';
        const absentCount = absentBadge ? absentBadge.textContent.trim() : '0';
        const lateCount = lateBadge ? lateBadge.textContent.trim() : '0';
        const excusedCount = excusedBadge ? excusedBadge.textContent.trim() : '0';
        const rate = progressBar ? progressBar.textContent.trim() : '0%';
        
        // Debug: afficher les valeurs trouvées
        console.log('Données trouvées:', {
            presentCount, absentCount, lateCount, excusedCount, rate,
            presentBadge: !!presentBadge, absentBadge: !!absentBadge, 
            lateBadge: !!lateBadge, excusedBadge: !!excusedBadge, progressBar: !!progressBar
        });
        
        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-pie me-2"></i>Statistiques de la Semaine</h6>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>${presentCount}</h4>
                                    <small>Présent</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4>${absentCount}</h4>
                                    <small>Absent</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>${lateCount}</h4>
                                    <small>En Retard</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>${excusedCount}</h4>
                                    <small>Excusé</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-percentage me-2"></i>Taux de Présence</h6>
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar ${progressBar.className}" role="progressbar" style="width: ${rate}">
                            ${rate}
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Taux de présence :</strong> ${rate}<br>
                        <small>Basé sur la logique intelligente de présence par élève</small>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h6><i class="fas fa-calendar-week me-2"></i>Détails par Jour</h6>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note :</strong> Les détails par jour seront disponibles dans une prochaine version.
                        Pour l'instant, vous pouvez voir le récapitulatif global de la semaine.
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-primary btn-lg" onclick="generateStudentReport(${studentId}, '${studentName}')">
                        <i class="fas fa-file-alt me-2"></i>
                        Fiche de l'élève
                    </button>
                    <p class="mt-2 text-muted">
                        <small>Générer un rapport détaillé avec les informations sur les retards et la présence</small>
                    </p>
                </div>
            </div>
        `;
    }, 1000);
}

// Fonction pour générer le rapport détaillé de l'élève
function generateStudentReport(studentId, studentName) {
    // Afficher le spinner overlay
    showSpinnerOverlay('Génération du rapport élève...', 'Création de la fiche détaillée de ' + studentName);
    
    // Récupérer les données depuis le tableau de la page
    const studentButton = document.querySelector(`button[data-student-id="${studentId}"][data-student-name]`);
    if (!studentButton) {
        hideSpinnerOverlay();
        showAlert('error', 'Impossible de trouver les données de l\'élève');
        return;
    }
    
    const studentRow = studentButton.closest('tr');
    if (!studentRow) {
        hideSpinnerOverlay();
        showAlert('error', 'Impossible de trouver la ligne de l\'élève');
        return;
    }
    
    // Récupérer les statistiques depuis le tableau
    const presentBadge = studentRow.querySelector('.badge.bg-success');
    const absentBadge = studentRow.querySelector('.badge.bg-danger');
    const lateBadge = studentRow.querySelector('.badge.bg-warning');
    const excusedBadge = studentRow.querySelector('.badge.bg-info');
    const progressBar = studentRow.querySelector('.progress-bar');
    
    const weekStats = {
        present: presentBadge ? parseInt(presentBadge.textContent.trim()) : 0,
        absent: absentBadge ? parseInt(absentBadge.textContent.trim()) : 0,
        late: lateBadge ? parseInt(lateBadge.textContent.trim()) : 0,
        excused: excusedBadge ? parseInt(excusedBadge.textContent.trim()) : 0
    };
    
    // Récupérer les informations de l'élève
    const studentInfo = studentRow.querySelector('.student-info');
    const matricule = studentInfo ? studentInfo.querySelector('small').textContent.trim() : 'N/A';
    
    // Créer des données simulées pour les retards (en attendant l'implémentation serveur)
    const mockAttendances = [];
    if (weekStats.late > 0) {
        for (let i = 0; i < weekStats.late; i++) {
            mockAttendances.push({
                attendance_date: new Date().toISOString().split('T')[0],
                time_slot: '07:30',
                status: 'present',
                arrival_time: '08:00',
                reason: 'Retard non justifié'
            });
        }
    }
    
    const student = {
        matricule: matricule,
        class_name: '{{ $class->name }}'
    };
    
    // Générer le PDF avec les données récupérées
    createStudentReportPDF(mockAttendances, studentName, student, weekStats);
}

// Fonction pour créer le PDF du rapport élève
function createStudentReportPDF(attendances, studentName, student, weekStats) {
    // Charger jsPDF depuis CDN
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    script.onload = function() {
        try {
            // Mettre à jour le spinner
            showSpinnerOverlay('Création du rapport...', 'Génération du document PDF pour ' + studentName);
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
        
            // Configuration des couleurs
            const primaryColor = [52, 152, 219]; // Bleu
            const successColor = [40, 167, 69];  // Vert
            const dangerColor = [220, 53, 69];   // Rouge
            const warningColor = [255, 193, 7];  // Jaune
            const grayColor = [108, 117, 125];   // Gris
            
            // En-tête
            doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.rect(0, 0, 210, 30, 'F');
            
            // Bordure décorative
            doc.setDrawColor(255, 255, 255);
            doc.setLineWidth(2);
            doc.rect(5, 5, 200, 20);
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('FICHE DE PRÉSENCE ÉLÈVE', 20, 15);
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Rapport détaillé de présence', 20, 22);
            
            // Informations de l'élève
            doc.setFillColor(248, 249, 250);
            doc.rect(15, 35, 180, 25, 'F');
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.5);
            doc.rect(15, 35, 180, 25);
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Informations de l\'élève', 20, 45);
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.text('Nom: ' + studentName, 20, 52);
            doc.text('Matricule: ' + (student.matricule || 'N/A'), 20, 58);
            doc.text('Classe: ' + (student.class_name || 'N/A'), 110, 52);
            doc.text('Période: Semaine courante', 110, 58);
            
            // Statistiques de la semaine
            const startY = 70;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Statistiques de la semaine', 20, startY);
            
            // Cartes de statistiques
            const stats = [
                { label: 'Présents', value: weekStats.present || 0, color: successColor },
                { label: 'Absents', value: weekStats.absent || 0, color: dangerColor },
                { label: 'En Retard', value: weekStats.late || 0, color: warningColor },
                { label: 'Excusés', value: weekStats.excused || 0, color: [52, 152, 219] }
            ];
            
            let xPos = 20;
            stats.forEach((stat, index) => {
                // Fond de la carte
                doc.setFillColor(stat.color[0], stat.color[1], stat.color[2]);
                doc.rect(xPos, startY + 5, 35, 20, 'F');
                
                // Bordure
                doc.setDrawColor(255, 255, 255);
                doc.setLineWidth(1);
                doc.rect(xPos, startY + 5, 35, 20);
                
                // Texte
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(16);
                doc.setFont('helvetica', 'bold');
                doc.text(stat.value.toString(), xPos + 17.5, startY + 12, { align: 'center' });
                
                doc.setFontSize(8);
                doc.setFont('helvetica', 'normal');
                doc.text(stat.label, xPos + 17.5, startY + 18, { align: 'center' });
                
                xPos += 40;
            });
            
            // Détails des retards
            const lateDetailsY = startY + 35;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text('Détails des retards', 20, lateDetailsY);
            
            // Analyser les retards
            const lateAttendances = attendances.filter(a => {
                if (a.status === 'present' && a.arrival_time) {
                    const arrivalMinutes = timeToMinutes(a.arrival_time);
                    const startMinutes = timeToMinutes(a.time_slot);
                    return arrivalMinutes > startMinutes;
                }
                return a.status === 'late' || (a.status === 'absent' && !a.justified && (a.time_slot === '07:30' || a.time_slot === '08:30'));
            });
            
            if (lateAttendances.length > 0) {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                let yPos = lateDetailsY + 8;
                
                lateAttendances.forEach((attendance, index) => {
                    if (yPos > 250) {
                        doc.addPage();
                        yPos = 20;
                    }
                    
                    const date = new Date(attendance.attendance_date).toLocaleDateString('fr-FR');
                    const timeSlot = attendance.time_slot;
                    const reason = attendance.reason || 'Non spécifié';
                    
                    doc.text(`• ${date} - Créneau ${timeSlot}: ${reason}`, 25, yPos);
                    yPos += 5;
                });
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(successColor[0], successColor[1], successColor[2]);
                doc.text('Aucun retard enregistré cette semaine', 25, lateDetailsY + 8);
            }
            
            // Pied de page
            const footerY = 280;
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.5);
            doc.line(20, footerY - 5, 190, footerY - 5);
            
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
            doc.text('Rapport généré automatiquement le ' + new Date().toLocaleDateString('fr-FR'), 20, footerY);
            doc.text('Système de Gestion Scolaire', 150, footerY);
            
            // Télécharger le PDF
            const fileName = 'Fiche_Eleve_' + studentName.replace(/\s+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(fileName);
            
            hideSpinnerOverlay();
            showAlert('success', 'Rapport élève généré avec succès !');
        } catch (error) {
            hideSpinnerOverlay();
            console.error('Erreur lors de la création du rapport:', error);
            showAlert('error', 'Erreur lors de la création du rapport: ' + error.message);
        }
    };
    document.head.appendChild(script);
}

// Fonction pour naviguer vers le jour précédent
function goToPreviousDay() {
    const form = document.getElementById('attendanceForm');
    const currentDate = new Date(form.getAttribute('data-current-date'));
    currentDate.setDate(currentDate.getDate() - 1);
    const previousDate = currentDate.toISOString().split('T')[0];
    
    const url = new URL(window.location);
    url.searchParams.set('date', previousDate);
    window.location.href = url.toString();
}

// Fonction pour naviguer vers le jour suivant
function goToNextDay() {
    const form = document.getElementById('attendanceForm');
    const currentDate = new Date(form.getAttribute('data-current-date'));
    currentDate.setDate(currentDate.getDate() + 1);
    const nextDate = currentDate.toISOString().split('T')[0];
    
    const url = new URL(window.location);
    url.searchParams.set('date', nextDate);
    window.location.href = url.toString();
}

// Fonction pour modifier les présences (redirection vers la page de modification)
function editAttendances() {
    const form = document.getElementById('attendanceForm');
    const currentDate = form.getAttribute('data-current-date');
    const url = new URL(window.location);
    url.searchParams.set('date', currentDate);
    window.location.href = url.toString();
}

// Fonction pour modifier les présences d'une date spécifique
function editAttendanceForDate(date, formattedDate) {
    // Afficher le spinner overlay
    showSpinnerOverlay('Redirection vers la page de modification...', 'Chargement des présences du ' + formattedDate);
    
    // Rediriger directement vers la page de modification
    setTimeout(() => {
        const url = `/attendances/${classId}/edit/${date}?academic_year_id=${academicYearId}`;
        window.location.href = url;
    }, 1000);
}

// Fonction pour afficher le modal de confirmation de suppression
function showDeleteConfirmationModal(date, formattedDate) {
    const modalElement = document.getElementById('deleteConfirmationModal');
    const dateDisplay = document.getElementById('deleteDateDisplay');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Vérifier que tous les éléments existent
    if (!modalElement || !dateDisplay || !confirmBtn) {
        console.error('Éléments du modal de suppression non trouvés');
        showAlert('error', 'Erreur: Éléments du modal non trouvés');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    
    // Mettre à jour l'affichage de la date
    dateDisplay.textContent = formattedDate;
    
    // Stocker la date à supprimer dans le bouton de confirmation
    confirmBtn.setAttribute('data-date-to-delete', date);
    
    // Afficher le modal
    modal.show();
}

// Fonction pour supprimer les présences d'un jour spécifique
function deleteDayAttendances(date) {
    // Fermer le modal de confirmation
    const modalElement = document.getElementById('deleteConfirmationModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
    
    // Afficher le spinner overlay
    showSpinnerOverlay('Suppression en cours...', 'Suppression des présences du ' + date);
    
    const form = document.getElementById('attendanceForm');
    const deleteUrl = form.getAttribute('data-delete-url');
    
    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            attendance_date: date
        })
    })
    .then(response => response.json())
    .then(data => {
        hideSpinnerOverlay();
        if (data.success) {
            showAlert('success', 'Présences supprimées avec succès !');
            // Recharger la page après un délai
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('error', 'Erreur lors de la suppression : ' + data.message);
        }
    })
    .catch(error => {
        hideSpinnerOverlay();
        console.error('Erreur:', error);
        showAlert('error', 'Erreur lors de la suppression des présences.');
    });
}



// Fonction pour valider l'heure d'arrivée dans l'intervalle du créneau
function validateArrivalTime(input) {
    const value = input.value;
    const minTime = input.getAttribute('data-min-time');
    const maxTime = input.getAttribute('data-max-time');
    
    if (value && minTime && maxTime) {
        if (value < minTime || value > maxTime) {
            // Afficher un message d'erreur
            showAlert('warning', `L'heure d'arrivée doit être entre ${minTime} et ${maxTime}`);
            // Remettre l'heure de début du créneau par défaut
            input.value = minTime;
        }
    }
}

// Fonction pour basculer les champs selon le statut pour un créneau horaire
function toggleTimeSlotFields(studentId, timeSlot) {
    const presentRadio = document.getElementById('present_' + studentId + '_' + timeSlot);
    const absentRadio = document.getElementById('absent_' + studentId + '_' + timeSlot);
    const arrivalContainer = document.getElementById('arrival_time_' + studentId + '_' + timeSlot);
    const absenceContainer = document.getElementById('absence_fields_' + studentId + '_' + timeSlot);
    const reasonContainer = document.getElementById('reason_' + studentId + '_' + timeSlot);
    
    // Supprimer toutes les classes show
    if (arrivalContainer) arrivalContainer.classList.remove('show');
    if (absenceContainer) absenceContainer.classList.remove('show');
    if (reasonContainer) reasonContainer.classList.remove('show');
    
    if (presentRadio && presentRadio.checked) {
        // Seul le premier créneau (07:30) affiche le champ d'heure d'arrivée
        if (timeSlot === '07:30' && arrivalContainer) {
            arrivalContainer.classList.add('show');
        }
    } else if (absentRadio && absentRadio.checked) {
        if (absenceContainer) absenceContainer.classList.add('show');
        if (reasonContainer) reasonContainer.classList.add('show');
    }
}

// Fonction pour mettre à jour le résumé
function updateSummary(studentId, status) {
    const summaryContainer = document.getElementById('summary_' + studentId);
    
    if (status === 'present') {
        summaryContainer.innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Présent</span>';
    } else if (status === 'absent') {
        summaryContainer.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Absent</span>';
    } else {
        summaryContainer.innerHTML = '<span class="text-muted">Non défini</span>';
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

// Fonctions pour marquer tous les élèves (conservées pour compatibilité)
function markAllPresent() {
    // Cette fonction peut être utilisée pour marquer tous les élèves présents pour tous les créneaux
    document.querySelectorAll('.student-attendance-card').forEach(card => {
        const studentId = card.querySelector('[data-student-id]').dataset.studentId;
        markStudentAllPresent(studentId);
    });
}

function markAllAbsent() {
    // Cette fonction peut être utilisée pour marquer tous les élèves absents pour tous les créneaux
    document.querySelectorAll('.student-attendance-card').forEach(card => {
        const studentId = card.querySelector('[data-student-id]').dataset.studentId;
        markStudentAllAbsent(studentId);
    });
}

// Fonction pour afficher les résultats du filtre
function displayFilterResults(attendances, startDate, endDate) {
    const resultsContainer = document.getElementById('filterResults');
    
    if (Object.keys(attendances).length === 0) {
        resultsContainer.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h4>Aucune présence trouvée</h4>
                <p>Aucune présence n'a été enregistrée pour la période sélectionnée.</p>
            </div>
        `;
    } else {
        let html = `
            <h6>Résultats du filtre (${startDate} - ${endDate})</h6>
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
        `;
        
        Object.keys(attendances).forEach(date => {
            const dateObj = new Date(date);
            const dayName = dateObj.toLocaleDateString('fr-FR', { weekday: 'long' });
            const presentCount = attendances[date].filter(a => a.status === 'present').length;
            const absentCount = attendances[date].filter(a => a.status === 'absent').length;
            const lateCount = attendances[date].filter(a => a.status === 'late').length;
            const excusedCount = attendances[date].filter(a => a.status === 'excused').length;
            const total = attendances[date].length;
            const rate = total > 0 ? Math.round((presentCount + lateCount) / total * 100) : 0;
            
            html += `
                <tr>
                    <td>${dateObj.toLocaleDateString('fr-FR')}</td>
                    <td><span class="badge bg-secondary">${dayName}</span></td>
                    <td><span class="badge bg-success">${presentCount}</span></td>
                    <td><span class="badge bg-danger">${absentCount}</span></td>
                    <td><span class="badge bg-warning">${lateCount}</span></td>
                    <td><span class="badge bg-info">${excusedCount}</span></td>
                    <td>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${rate >= 90 ? 'bg-success' : rate >= 70 ? 'bg-warning' : 'bg-danger'}" 
                                 role="progressbar" style="width: ${rate}%" aria-valuenow="${rate}" aria-valuemin="0" aria-valuemax="100">
                                ${rate}%
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="/attendances/{{ $class->id }}/show/${date}?academic_year_id={{ $academicYearId }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            Voir
                        </a>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        resultsContainer.innerHTML = html;
    }
    
    resultsContainer.style.display = 'block';
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
    
    // Insérer l'alerte en haut de la page
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Supprimer l'alerte après 5 secondes
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Fonction pour générer le PDF de la fiche de présence
function generateAttendancePDF(date, formattedDate, className) {
    // Vérifier que tous les paramètres sont valides
    if (!date || !formattedDate || !className) {
        showAlert('error', 'Paramètres manquants pour la génération du PDF');
        return;
    }
    
    // Afficher le spinner overlay
    showSpinnerOverlay('Génération du PDF en cours...', 'Récupération des données de présence');
    
    // Récupérer les données de présence pour cette date
    fetch(`/attendances/${classId}/show/${date}?academic_year_id=${academicYearId}&format=json`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createAttendancePDF(data.attendances, formattedDate, className, data.students);
            } else {
                hideSpinnerOverlay();
                showAlert('error', 'Erreur lors de la récupération des données');
            }
        })
        .catch(error => {
            hideSpinnerOverlay();
            console.error('Erreur:', error);
            showAlert('error', 'Erreur lors de la génération du PDF');
        });
}

// Fonction pour créer le PDF avec jsPDF
function createAttendancePDF(attendances, date, className, students) {
    // Vérifier que les données sont valides
    if (!attendances || !students || !Array.isArray(attendances) || !Array.isArray(students)) {
        showAlert('error', 'Données invalides pour la génération du PDF');
        return;
    }
    
    // Charger jsPDF depuis CDN
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    script.onload = function() {
        try {
            // Mettre à jour le spinner
            showSpinnerOverlay('Création du PDF...', 'Génération du document avec jsPDF');
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });
        
        // Configuration des couleurs et styles
        const primaryColor = [52, 152, 219]; // Bleu
        const successColor = [40, 167, 69];  // Vert
        const dangerColor = [220, 53, 69];   // Rouge
        const warningColor = [255, 193, 7];  // Jaune
        const grayColor = [108, 117, 125];   // Gris
        
        // En-tête avec dégradé
        doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
        doc.rect(0, 0, 297, 35, 'F');
        
        // Bordure décorative plus large
        doc.setDrawColor(255, 255, 255);
        doc.setLineWidth(3);
        doc.rect(5, 8, 287, 23);
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text('FICHE DE PRÉSENCE', 20, 20);
        
        // Sous-titre
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Système de Gestion Scolaire', 20, 28);
        
        // Informations de l'établissement avec encadré
        doc.setFillColor(248, 249, 250);
        doc.rect(20, 40, 257, 20, 'F');
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.5);
        doc.rect(20, 40, 257, 20);
        
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('École de Gestion', 25, 47);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Classe: ' + className, 25, 53);
        doc.text('Date: ' + date, 25, 57);
        
        // Statistiques rapides
        const totalStudents = students.length;
        const presentCount = attendances.filter(a => a.status === 'present' || a.status === 'late').length;
        const lateCount = attendances.filter(a => {
            // Compter les retards basés sur l'heure d'arrivée
            if (a.status === 'present' && a.arrival_time) {
                const arrivalMinutes = timeToMinutes(a.arrival_time);
                const startMinutes = timeToMinutes(a.time_slot);
                return arrivalMinutes > startMinutes;
            }
            // Compter les absences non justifiées aux premiers créneaux comme retards
            if (a.status === 'absent' && !a.justified && (a.time_slot === '07:30' || a.time_slot === '08:30')) {
                return true;
            }
            return a.status === 'late';
        }).length;
        const absentCount = attendances.filter(a => a.status === 'absent').length;
        const rate = totalStudents > 0 ? Math.round(((presentCount + lateCount) / totalStudents) * 100) : 0;
        
        doc.text('Total élèves: ' + totalStudents, 200, 47);
        doc.text('Présents: ' + presentCount, 200, 53);
        doc.text('Taux: ' + rate + '%', 200, 57);
        
        // Tableau des présences
        const startY = 70;
        const colWidths = [25, 50, 20, 20, 20, 20, 20, 20, 20, 20, 20];
        const headers = ['N°', 'Nom de l\'élève', '07:30', '08:30', '09:30', '10:45', '11:45', '14:00', '15:00', '16:15', 'Total'];
        
        // En-tête du tableau avec style amélioré
        doc.setFillColor(52, 152, 219);
        doc.rect(20, startY, 257, 12, 'F');
        
        // Bordure de l'en-tête
        doc.setDrawColor(255, 255, 255);
        doc.setLineWidth(1);
        doc.rect(20, startY, 257, 12);
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(9);
        doc.setFont('helvetica', 'bold');
        
        let xPos = 20;
        headers.forEach((header, index) => {
            doc.text(header, xPos + 2, startY + 7);
            xPos += colWidths[index];
        });
        
        // Lignes des étudiants
        let currentY = startY + 12;
        students.forEach((student, index) => {
            if (currentY > 180) {
                doc.addPage();
                currentY = 20;
            }
            
            // Alternance des couleurs de fond avec bordures
            if (index % 2 === 0) {
                doc.setFillColor(248, 249, 250);
                doc.rect(20, currentY, 257, 10, 'F');
            }
            
            // Bordures des lignes
            doc.setDrawColor(220, 220, 220);
            doc.setLineWidth(0.3);
            doc.rect(20, currentY, 257, 10);
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            
            xPos = 20;
            const studentData = [
                (index + 1).toString(),
                (student.first_name + ' ' + student.last_name).substring(0, 20),
                getAttendanceStatus(attendances, student.id, '07:30'),
                getAttendanceStatus(attendances, student.id, '08:30'),
                getAttendanceStatus(attendances, student.id, '09:30'),
                getAttendanceStatus(attendances, student.id, '10:45'),
                getAttendanceStatus(attendances, student.id, '11:45'),
                getAttendanceStatus(attendances, student.id, '14:00'),
                getAttendanceStatus(attendances, student.id, '15:00'),
                getAttendanceStatus(attendances, student.id, '16:15'),
                getTotalAttendance(attendances, student.id)
            ];
            
            studentData.forEach((data, dataIndex) => {
                // Couleur spéciale pour les statuts
                if (dataIndex >= 2 && dataIndex <= 9) { // Colonnes des créneaux horaires
                    if (data === 'P') {
                        doc.setTextColor(successColor[0], successColor[1], successColor[2]);
                    } else if (data === 'A') {
                        doc.setTextColor(dangerColor[0], dangerColor[1], dangerColor[2]);
                    } else if (data === 'R') {
                        doc.setTextColor(warningColor[0], warningColor[1], warningColor[2]);
                    } else {
                        doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
                    }
                } else if (dataIndex === 10) { // Colonne Total
                    doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
                    doc.setFont('helvetica', 'bold');
                } else {
                    doc.setTextColor(0, 0, 0);
                }
                
                doc.text(data, xPos + 1, currentY + 7);
                xPos += colWidths[dataIndex];
            });
            
            currentY += 10;
        });
        
        // Pied de page avec style
        const footerY = 190;
        
        // Ligne de séparation
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.5);
        doc.line(20, footerY - 5, 277, footerY - 5);
        
        // Fond du pied de page
        doc.setFillColor(248, 249, 250);
        doc.rect(20, footerY - 3, 257, 8, 'F');
        
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
        doc.text('Fiche générée automatiquement le ' + new Date().toLocaleDateString('fr-FR'), 25, footerY + 2);
        
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
        doc.text('Système de Gestion Scolaire', 200, footerY + 2);
        
        // Légende des statuts
        doc.setFontSize(7);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(0, 0, 0);
        doc.text('Légende: ', 25, footerY + 5);
        
        doc.setTextColor(successColor[0], successColor[1], successColor[2]);
        doc.text('P = Présent', 50, footerY + 5);
        
        doc.setTextColor(dangerColor[0], dangerColor[1], dangerColor[2]);
        doc.text('A = Absent', 80, footerY + 5);
        
        doc.setTextColor(warningColor[0], warningColor[1], warningColor[2]);
        doc.text('R = Retard', 110, footerY + 5);
        
        doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
        doc.text('- = Non défini', 140, footerY + 5);
        
        // Télécharger le PDF
        const fileName = 'Fiche_Presence_' + className.replace(/\s+/g, '_') + '_' + date.replace(/\//g, '_') + '.pdf';
        doc.save(fileName);
        
        hideSpinnerOverlay();
        showAlert('success', 'PDF généré avec succès !');
        } catch (error) {
            hideSpinnerOverlay();
            console.error('Erreur lors de la création du PDF:', error);
            showAlert('error', 'Erreur lors de la création du PDF: ' + error.message);
        }
    };
    document.head.appendChild(script);
}

// Fonction pour obtenir le statut de présence
function getAttendanceStatus(attendances, studentId, timeSlot) {
    if (!attendances || !Array.isArray(attendances)) return '-';
    
    const attendance = attendances.find(a => a && a.student_id == studentId && a.time_slot === timeSlot);
    if (!attendance || !attendance.status) return '-';
    
    // Logique spéciale pour les premiers créneaux (07:30 et 08:30)
    if (timeSlot === '07:30' || timeSlot === '08:30') {
        // Vérifier si l'élève est absent aux premiers créneaux (non justifié)
        if (attendance.status === 'absent' && !attendance.justified) {
            return 'R'; // Absence non justifiée aux premiers créneaux = retard
        }
    }
    
    // Si l'élève est présent, vérifier s'il est en retard
    if (attendance.status === 'present') {
        // Vérifier le retard basé sur l'heure d'arrivée
        if (attendance.arrival_time) {
            const arrivalTime = attendance.arrival_time;
            const startTime = timeSlot;
            
            // Convertir les heures en minutes pour faciliter la comparaison
            const arrivalMinutes = timeToMinutes(arrivalTime);
            const startMinutes = timeToMinutes(startTime);
            
            // Si l'arrivée est après l'heure de début, c'est un retard
            if (arrivalMinutes > startMinutes) {
                return 'R';
            }
        }
        
        // Vérifier le retard basé sur la logique globale (premiers créneaux absents)
        const studentAttendances = attendances.filter(a => a && a.student_id == studentId);
        const hasFirstCreneauAbsent = studentAttendances.some(a => a.time_slot === '07:30' && a.status === 'absent' && !a.justified);
        const hasSecondCreneauAbsent = studentAttendances.some(a => a.time_slot === '08:30' && a.status === 'absent' && !a.justified);
        
        if (hasFirstCreneauAbsent || hasSecondCreneauAbsent) {
            return 'R'; // Retard si absences non justifiées aux premiers créneaux
        }
        
        return 'P'; // Présent sans retard
    }
    
    switch(attendance.status) {
        case 'absent': return 'A';
        case 'late': return 'R';
        case 'excused': return 'E';
        default: return '-';
    }
}

// Fonction utilitaire pour convertir une heure (HH:MM) en minutes
function timeToMinutes(timeString) {
    if (!timeString) return 0;
    const [hours, minutes] = timeString.split(':').map(Number);
    return hours * 60 + minutes;
}

// Fonction pour calculer le total de présence
function getTotalAttendance(attendances, studentId) {
    if (!attendances || !Array.isArray(attendances)) return '0/8';
    
    const studentAttendances = attendances.filter(a => a && a.student_id == studentId);
    let presentCount = 0;
    
    studentAttendances.forEach(attendance => {
        if (attendance.status === 'present') {
            // Vérifier si c'est un retard basé sur l'heure d'arrivée
            if (attendance.arrival_time) {
                const arrivalMinutes = timeToMinutes(attendance.arrival_time);
                const startMinutes = timeToMinutes(attendance.time_slot);
                
                if (arrivalMinutes > startMinutes) {
                    presentCount++; // Compter comme présent même si en retard
                } else {
                    presentCount++;
                }
            } else {
                presentCount++;
            }
        } else if (attendance.status === 'late') {
            presentCount++;
        }
    });
    
    const totalSlots = 8; // Nombre total de créneaux
    return `${presentCount}/${totalSlots}`;
}

</script>
@endsection
