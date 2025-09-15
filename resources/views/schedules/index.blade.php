@extends('layouts.app')

@section('title', 'Emplois du temps')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Emplois du temps des classes
                    </h5>
                    <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Créer un emploi du temps
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <form method="GET" action="{{ route('schedules.index') }}">
                                <div class="input-group">
                                    <label class="input-group-text">Année académique</label>
                                    <select name="academic_year_id" class="form-select" onchange="this.form.submit()">
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" 
                                                {{ $currentAcademicYear && $currentAcademicYear->id == $year->id ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Vue d'ensemble des emplois du temps -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="card-title">{{ $allSchedules->count() }}</h3>
                                    <p class="card-text">Total des créneaux</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="card-title">{{ $classesWithSchedules->count() }}</h3>
                                    <p class="card-text">Classes avec emplois</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="card-title">{{ $orphanSchedules->count() }}</h3>
                                    <p class="card-text">Créneaux orphelins</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des emplois du temps par classe -->
                    @if($classesWithSchedules->count() > 0)
                        <div class="schedules-container">
                            <h5 class="mb-3">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Emplois du temps par classe
                            </h5>
                            <div class="table-responsive shadow-sm">
                                <table class="table schedules-table">
                                    <thead class="schedules-header">
                                        <tr>
                                            <th class="class-header">
                                                <i class="fas fa-graduation-cap me-2"></i>
                                                Classe
                                            </th>
                                            <th class="level-header">
                                                <i class="fas fa-layer-group me-2"></i>
                                                Niveau
                                            </th>
                                            <th class="slots-header">
                                                <i class="fas fa-clock me-2"></i>
                                                Créneaux
                                            </th>
                                            <th class="date-header">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                Dernière modification
                                            </th>
                                            <th class="actions-header">
                                                <i class="fas fa-cogs me-2"></i>
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($classesWithSchedules as $class)
                                            @php
                                                $scheduleCount = $class->schedules->count();
                                                $lastUpdated = $class->schedules->max('updated_at');
                                            @endphp
                                            <tr class="schedule-row-item">
                                                <td class="class-cell">
                                                    <div class="class-info">
                                                        <div class="class-name">
                                                            <i class="fas fa-school me-2"></i>
                                                            <strong>{{ $class->name }}</strong>
                                                        </div>
                                                        @if($class->description)
                                                            <div class="class-description">{{ $class->description }}</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="level-cell">
                                                    <div class="level-badge">
                                                        <i class="fas fa-tag me-2"></i>
                                                        {{ $class->getSafeLevelName() }}
                                                    </div>
                                                </td>
                                                <td class="slots-cell">
                                                    <div class="slots-badge">
                                                        <i class="fas fa-clock me-2"></i>
                                                        <span class="slots-number">{{ $scheduleCount }}</span>
                                                        <span class="slots-text">créneaux</span>
                                                    </div>
                                                </td>
                                            <td class="date-cell">
                                                @if($lastUpdated)
                                                    <div class="date-info">
                                                        <i class="fas fa-calendar me-2 text-info"></i>
                                                        <span>{{ \Carbon\Carbon::parse($lastUpdated)->format('d/m/Y à H:i') }}</span>
                                                    </div>
                                                @else
                                                    <div class="date-info empty">
                                                        <i class="fas fa-minus me-2 text-muted"></i>
                                                        <span>Aucune modification</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="actions-cell">
                                                <div class="action-buttons">
                                                    <!-- Bouton Détails -->
                                                    @if($class->schedules->count() > 0)
                                                        <a href="{{ route('schedules.show', ['schedule' => $class->schedules->first()->id, 'academic_year_id' => $currentAcademicYear->id]) }}" 
                                                           class="action-btn view-btn" 
                                                           title="Voir les détails">
                                                            <i class="fas fa-eye"></i>
                                                            <span>Détails</span>
                                                        </a>
                                                    @else
                                                        <span class="action-btn view-btn disabled" title="Aucun emploi du temps">
                                                            <i class="fas fa-eye"></i>
                                                            <span>Détails</span>
                                                        </span>
                                                    @endif
                                                    
                                                    <!-- Bouton Modifier -->
                                                    <a href="{{ route('schedules.build', ['class_id' => $class->id, 'academic_year_id' => $currentAcademicYear->id]) }}" 
                                                       class="action-btn edit-btn" 
                                                       title="Modifier l'emploi du temps">
                                                        <i class="fas fa-edit"></i>
                                                        <span>Modifier</span>
                                                    </a>
                                                    
                                                    <!-- Bouton Supprimer -->
                                                    <button type="button" 
                                                            class="action-btn delete-btn" 
                                                            onclick="confirmDelete('{{ $class->id }}', '{{ $class->name }}')"
                                                            title="Supprimer l'emploi du temps">
                                                        <i class="fas fa-trash"></i>
                                                        <span>Supprimer</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                        <!-- Pagination supprimée car $classesWithSchedules est maintenant une Collection -->
                    @endif
                        
                        <!-- Section des emplois du temps orphelins (sans classe) -->
                        @if($orphanSchedules && $orphanSchedules->count() > 0)
                            <div class="mt-5">
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Emplois du temps sans classe assignée
                                    </h6>
                                    <p class="mb-0">Ces emplois du temps existent mais ne sont pas associés à une classe spécifique.</p>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-warning">
                                            <tr>
                                                <th>Matière</th>
                                                <th>Professeur</th>
                                                <th>Jour</th>
                                                <th>Heure</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($orphanSchedules as $schedule)
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-book me-2"></i>
                                                        {{ $schedule->subject ? $schedule->subject->name : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-user me-2"></i>
                                                        {{ $schedule->teacher ? $schedule->teacher->first_name . ' ' . $schedule->teacher->last_name : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            {{ $schedule->dayName }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-clock me-2"></i>
                                                        {{ $schedule->timeSlot }}
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('schedules.build', ['class_id' => $schedule->class_id, 'academic_year_id' => $schedule->academic_year_id]) }}" 
                                                               class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger btn-sm delete-schedule-btn"
                                                                    data-schedule-id="{{ $schedule->id }}" 
                                                                    data-schedule-type="Orphelin">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                        
                        @if($classesWithSchedules->count() === 0 && (!$orphanSchedules || $orphanSchedules->count() === 0))
                            <!-- Aucun emploi du temps -->
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun emploi du temps trouvé</h5>
                                <p class="text-muted mb-4">
                                    @if($currentAcademicYear)
                                        Aucune classe n'a encore d'emploi du temps pour l'année {{ $currentAcademicYear->name }}.
                                    @else
                                        Veuillez sélectionner une année académique.
                                    @endif
                                </p>
                                <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>
                                    Créer le premier emploi du temps
                                </a>
                            </div>
                        @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'emploi du temps de la classe <strong id="className"></strong> ?</p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Container principal */
.schedules-container {
    margin: 20px 0;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* Tableau principal */
.schedules-table {
    font-size: 0.95rem;
    margin-bottom: 0;
    border: none;
    background: #fff;
}

/* En-têtes */
.schedules-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.class-header, .level-header, .slots-header, .date-header, .actions-header {
    font-weight: 600;
    font-size: 1rem;
    text-align: center;
    padding: 18px 15px;
    border: none;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    color: white;
}

/* Lignes du tableau */
.schedule-row-item {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e9ecef;
}

.schedule-row-item:hover {
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Cellule classe */
.class-cell {
    padding: 20px 15px;
    vertical-align: middle;
}

.class-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.class-name {
    display: flex;
    align-items: center;
    font-size: 1.1rem;
    color: #2c3e50;
}

.class-description {
    font-size: 0.9rem;
    color: #6c757d;
    font-style: italic;
    margin-left: 25px;
}

/* Cellule niveau */
.level-cell {
    padding: 20px 15px;
    text-align: center;
    vertical-align: middle;
}

.level-badge {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    padding: 10px 15px;
    border-radius: 25px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
    transition: all 0.3s ease;
}

.level-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
}

/* Cellule créneaux */
.slots-cell {
    padding: 20px 15px;
    text-align: center;
    vertical-align: middle;
}

.slots-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 10px 15px;
    border-radius: 25px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    transition: all 0.3s ease;
}

.slots-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

.slots-number {
    font-size: 1.1rem;
    font-weight: 700;
}

.slots-text {
    font-size: 0.9rem;
}

/* Cellule date */
.date-cell {
    padding: 20px 15px;
    vertical-align: middle;
}

.date-info {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    font-size: 0.95rem;
}

.date-info.empty {
    color: #6c757d;
    font-style: italic;
}

/* Cellule actions */
.actions-cell {
    padding: 20px 15px;
    text-align: center;
    vertical-align: middle;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.view-btn {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
}

.view-btn:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
}

.edit-btn {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.edit-btn:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
}

.delete-btn {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.delete-btn:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* Responsive */
@media (max-width: 992px) {
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-btn {
        min-width: 100px;
    }
    
    .class-header, .level-header, .slots-header, .date-header, .actions-header {
        font-size: 0.9rem;
        padding: 12px 10px;
    }
}

@media (max-width: 768px) {
    .schedules-table {
        font-size: 0.85rem;
    }
    
    .class-cell, .level-cell, .slots-cell, .date-cell, .actions-cell {
        padding: 15px 10px;
    }
    
    .action-btn span {
        display: none;
    }
    
    .action-btn {
        min-width: auto;
        padding: 8px;
    }
}
</style>
@endpush

<div id="page-config" data-academic-year-id="{{ $currentAcademicYear ? $currentAcademicYear->id : 'null' }}" data-csrf-token="{{ csrf_token() }}" style="display:none;"></div>

@push('scripts')
<script>
const pageConfig = document.getElementById('page-config');
const academicYearId = pageConfig.dataset.academicYearId === 'null' ? null : parseInt(pageConfig.dataset.academicYearId);
const csrfToken = pageConfig.dataset.csrfToken;
let classToDelete = null;

function confirmDelete(classId, className) {
    classToDelete = classId;
    document.getElementById('className').textContent = className;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function confirmDeleteSchedule(scheduleId, scheduleType) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ce créneau ${scheduleType} ?`)) {
        fetch(`/schedules/${scheduleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la suppression.');
        });
    }
}

// Gestionnaire pour les boutons de suppression des créneaux orphelins
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-schedule-btn')) {
        const button = e.target.closest('.delete-schedule-btn');
        const scheduleId = button.dataset.scheduleId;
        const scheduleType = button.dataset.scheduleType;
        confirmDeleteSchedule(scheduleId, scheduleType);
    }
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!classToDelete) return;
    
    fetch(`/schedules/class/${classToDelete}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            academic_year_id: academicYearId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la suppression.');
    })
    .finally(() => {
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        classToDelete = null;
    });
});
</script>
@endpush
