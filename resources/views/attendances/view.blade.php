@extends('layouts.app')

@section('title', 'Détails des Présences - ' . $class->name)

@section('content')
<div class="container-fluid">
    <!-- En-tête de la page -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-eye me-2"></i>
                            Détails des Présences
                        </h1>
                        <p class="page-subtitle">
                            {{ $class->name }} - {{ $academicYear->name ?? 'Année non définie' }}
                            <span class="badge bg-info ms-2">
                                <i class="fas fa-calendar-day me-1"></i>
                                {{ $viewDate->format('d/m/Y') }}
                            </span>
                        </p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('attendances.manage', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                           class="btn btn-primary me-2">
                            <i class="fas fa-edit me-2"></i>
                            Gestion
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

    <!-- Statistiques du jour -->
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

    <!-- Détails des présences par élève -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card attendance-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        Fiche de Présence - {{ $viewDate->format('d/m/Y') }}
                        <span class="badge bg-info ms-2">
                            <i class="fas fa-eye me-1"></i>
                            Mode Consultation
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        // Définir les créneaux horaires de la journée
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
                                // Récupérer les présences pour cet étudiant
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
                                        <span class="badge bg-info">
                                            <i class="fas fa-eye me-1"></i>
                                            Consultation
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="time-slots-grid">
                                    @foreach($timeSlots as $slotIndex => $slot)
                                        @php
                                            // Récupérer la présence pour ce créneau spécifique
                                            $slotAttendance = $studentAttendances->where('time_slot', $slot['start'])->first();
                                        @endphp
                                        <div class="time-slot-card readonly" data-student-id="{{ $student->id }}" data-time-slot="{{ $slot['start'] }}">
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
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-question me-1"></i>
                                                            Non défini
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="time-slot-body">
                                                {{-- Affichage des détails en mode lecture seule --}}
                                                @if($slotAttendance)
                                                    <div class="readonly-details">
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
                                                @else
                                                    <div class="alert alert-light py-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Aucune donnée enregistrée pour ce créneau
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="fas fa-tools me-2"></i>
                        Actions Disponibles
                    </h5>
                    <div class="btn-group" role="group">
                        <a href="{{ route('attendances.edit', ['class' => $class->id, 'date' => $viewDate->format('Y-m-d'), 'academic_year_id' => $academicYearId]) }}" 
                           class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>
                            Modifier les Présences
                        </a>
                        <button type="button" class="btn btn-info generate-pdf-btn" 
                                data-date="{{ $viewDate->format('Y-m-d') }}" 
                                data-formatted-date="{{ $viewDate->format('d/m/Y') }}" 
                                data-class-name="{{ $class->name }}">
                            <i class="fas fa-file-pdf me-2"></i>
                            Télécharger PDF
                        </button>
                        <button type="button" class="btn btn-danger delete-attendance-btn" 
                                data-date="{{ $viewDate->format('Y-m-d') }}" 
                                data-formatted-date="{{ $viewDate->format('d/m/Y') }}">
                            <i class="fas fa-trash me-2"></i>
                            Supprimer
                        </button>
                    </div>
                </div>
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

@include('attendances.manage-styles')
@include('attendances.view-scripts')

@endsection
