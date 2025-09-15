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
                            <i class="fas fa-calendar-day me-2"></i>
                            Détails des Présences
                        </h1>
                        <p class="page-subtitle">{{ $class->name }} - {{ $attendanceDate->format('d/m/Y') }}</p>
                    </div>
                    <div class="page-actions">
                        <a href="{{ route('attendances.manage', ['class' => $class->id, 'academic_year_id' => $academicYearId]) }}" 
                           class="btn btn-outline-light">
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
        @php
            $presentCount = $attendances->where('status', 'present')->count();
            $absentCount = $attendances->where('status', 'absent')->count();
            $lateCount = $attendances->where('status', 'late')->count();
            $excusedCount = $attendances->where('status', 'excused')->count();
            $total = $students->count();
            $attendanceRate = $total > 0 ? round((($presentCount + $lateCount) / $total) * 100, 1) : 0;
        @endphp
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $presentCount }}</h3>
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
                    <h3>{{ $absentCount }}</h3>
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
                    <h3>{{ $lateCount }}</h3>
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
                    <h3>{{ $excusedCount }}</h3>
                    <p>Excusés</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Taux de présence -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Taux de Présence du Jour</h5>
                    <div class="attendance-rate-display">
                        <div class="rate-circle">
                            <span class="rate-percentage">{{ $attendanceRate }}%</span>
                        </div>
                        <div class="rate-details">
                            <p>{{ $presentCount + $lateCount }} / {{ $total }} élèves</p>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar 
                                    @if($attendanceRate >= 90) bg-success
                                    @elseif($attendanceRate >= 70) bg-warning
                                    @else bg-danger
                                    @endif" 
                                    role="progressbar" 
                                    style="width: {{ $attendanceRate }}%"
                                    aria-valuenow="{{ $attendanceRate }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails des présences -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Détail des Présences
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Élève</th>
                                    <th width="15%">Statut</th>
                                    <th width="15%">Heure d'Arrivée</th>
                                    <th width="25%">Raison</th>
                                    <th width="15%">Justifié</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    @php
                                        $attendance = $attendances->get($student->id);
                                    @endphp
                                    <tr class="student-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-name">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </div>
                                                <small class="text-muted">{{ $student->matricule }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($attendance)
                                                <span class="badge bg-{{ $attendance->status_color }}">
                                                    {{ $attendance->status_label }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Non enregistré</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance && $attendance->arrival_time)
                                                <span class="arrival-time">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $attendance->arrival_time }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance && $attendance->reason)
                                                <span class="reason-text">{{ $attendance->reason }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance)
                                                @if($attendance->justified)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        Oui
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>
                                                        Non
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
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

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
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

.attendance-rate-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    margin: 2rem 0;
}

.rate-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: 700;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.rate-percentage {
    font-size: 2.5rem;
    font-weight: 700;
}

.rate-details p {
    font-size: 1.2rem;
    color: #495057;
    margin-bottom: 1rem;
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

.arrival-time {
    color: #495057;
    font-weight: 500;
}

.reason-text {
    color: #495057;
    font-style: italic;
}

.card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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
}

.badge {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
}
</style>
@endsection
