@extends('layouts.app')

@section('title', 'Constitution de l\'emploi du temps')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Constitution de l'emploi du temps
                    </h5>
                    <div>
                        <a href="{{ route('schedules.create') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>
                            Retour
                        </a>
                        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>
                            Liste
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Informations de la classe -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Classe :</strong> {{ $class->name }}
                                @if($class->getSafeLevelName() !== 'Non défini')
                                    <span class="badge bg-primary ms-2">{{ $class->getSafeLevelName() }}</span>
                                @endif
                                <span class="ms-3">
                                    <strong>Année académique :</strong> {{ $academicYear->name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire d'emploi du temps -->
                    <form id="scheduleForm">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                        <input type="hidden" name="academic_year_id" value="{{ $academicYear->id }}">

                        <!-- Grille d'emploi du temps -->
                        <div class="table-responsive">
                            <table class="table table-bordered schedule-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 120px;">Horaires</th>
                                        @foreach($days as $dayNumber => $dayName)
                                            <th class="text-center">{{ $dayName }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="scheduleTableBody">
                                    @foreach($defaultTimeSlots as $index => $timeSlot)
                                        <tr data-time-slot="{{ $index }}">
                                            <td class="text-center align-middle">
                                                <div class="time-slot-controls">
                                                    <input type="time" 
                                                           name="time_slots[{{ $index }}][start]" 
                                                           value="{{ $timeSlot['start'] }}" 
                                                           class="form-control form-control-sm mb-1 start-time">
                                                    <input type="time" 
                                                           name="time_slots[{{ $index }}][end]" 
                                                           value="{{ $timeSlot['end'] }}" 
                                                           class="form-control form-control-sm end-time">
                                                </div>
                                            </td>
                                            @foreach($days as $dayNumber => $dayName)
                                                <td class="schedule-cell" data-day="{{ $dayNumber }}" data-slot="{{ $index }}">
                                                    <div class="schedule-content">
                                                        <!-- Type de créneau -->
                                                        <select name="schedule[{{ $index }}][{{ $dayNumber }}][type]" 
                                                                class="form-select form-select-sm mb-2 slot-type">
                                                            <option value="">-- Vide --</option>
                                                            <option value="course">Cours</option>
                                                            <option value="break">Pause</option>
                                                        </select>

                                                        <!-- Pour les cours -->
                                                        <div class="course-fields d-none">
                                                            <select name="schedule[{{ $index }}][{{ $dayNumber }}][subject_id]" 
                                                                    class="form-select form-select-sm mb-1 subject-select">
                                                                <option value="">-- Matière --</option>
                                                                @foreach($subjects as $subject)
                                                                    <option value="{{ $subject->id }}">
                                                                        {{ $subject->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>

                                                                                                                                                                    <select name="schedule[{{ $index }}][{{ $dayNumber }}][teacher_id]" 
                                                                    class="form-select form-select-sm mb-1 teacher-select">
                                                                <option value="">-- Enseignant --</option>
                                                                <!-- Les options seront mises à jour dynamiquement selon la matière -->
                                                            </select>

                                                            <!-- Affichage du nom du professeur sélectionné -->
                                                            <div class="selected-teacher-info d-none">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-user-tie me-1"></i>
                                                                    <span class="teacher-name"></span>
                                                                </small>
                                                            </div>

                                                            <input type="text" 
                                                                   name="schedule[{{ $index }}][{{ $dayNumber }}][room]" 
                                                                   placeholder="Salle" 
                                                                   class="form-control form-control-sm">
                                                        </div>

                                                        <!-- Pour les pauses -->
                                                        <div class="break-fields d-none">
                                                            <input type="text" 
                                                                   name="schedule[{{ $index }}][{{ $dayNumber }}][title]" 
                                                                   placeholder="Nom de la pause" 
                                                                   class="form-control form-control-sm"
                                                                   value="Pause">
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <button type="button" id="addTimeSlotBtn" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i>
                                    Ajouter un créneau
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" id="previewBtn" class="btn btn-info me-2">
                                    <i class="fas fa-eye me-1"></i>
                                    Aperçu
                                </button>
                                <button type="submit" id="saveBtn" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>
                                    Enregistrer l'emploi du temps
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'aperçu -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aperçu de l'emploi du temps - {{ $class->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Le contenu sera généré par JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@endsection



@push('styles')
<style>
.schedule-table {
    font-size: 0.875rem;
}

.schedule-cell {
    min-width: 180px;
    padding: 8px;
    vertical-align: top;
}

.schedule-content {
    min-height: 120px;
}

.time-slot-controls {
    width: 100px;
}

.form-select-sm, .form-control-sm {
    font-size: 0.8rem;
}

.conflict-warning {
    background-color: #fff3cd !important;
    border-color: #ffeaa7 !important;
}

.conflict-error {
    background-color: #f8d7da !important;
    border-color: #f5c6cb !important;
}
</style>
@endpush

@php
$scheduleConfigJson = json_encode([
    'timeSlotCount' => count($defaultTimeSlots),
    'classId' => $class->id,
    'academicYearId' => $academicYear->id,
    'storeUrl' => route('schedules.store'),
    'indexUrl' => route('schedules.index'),
    'csrfToken' => csrf_token(),
    'days' => $days,
    'subjects' => $subjects->map(function($s) { return ['id' => $s->id, 'name' => $s->name]; }),
    'teachers' => $teachers->map(function($t) { return ['id' => $t->id, 'name' => $t->first_name . ' ' . $t->last_name]; }),
    'teachersBySubject' => $teachersBySubject,
    'existingSchedules' => $existingSchedules->count() > 0 ? $existingSchedules : null
]);
@endphp

<div id="schedule-config" data-config="{{ base64_encode($scheduleConfigJson) }}" style="display:none;"></div>

@push('scripts')
<script>

// Configuration depuis l'attribut data
const configElement = document.getElementById('schedule-config');
const scheduleConfig = JSON.parse(atob(configElement.dataset.config));
const { timeSlotCount, classId, academicYearId, storeUrl, indexUrl, csrfToken, days, subjects, teachers, teachersBySubject } = scheduleConfig;
let timeSlotCounter = timeSlotCount;

document.addEventListener('DOMContentLoaded', function() {
    // Gestion des types de créneaux
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('slot-type')) {
            const cell = e.target.closest('.schedule-cell');
            const courseFields = cell.querySelector('.course-fields');
            const breakFields = cell.querySelector('.break-fields');
            
            if (e.target.value === 'course') {
                courseFields.classList.remove('d-none');
                breakFields.classList.add('d-none');
            } else if (e.target.value === 'break') {
                courseFields.classList.add('d-none');
                breakFields.classList.remove('d-none');
            } else {
                courseFields.classList.add('d-none');
                breakFields.classList.add('d-none');
            }
        }
        
        // Gestion du changement de matière - filtrer les enseignants
        if (e.target.classList.contains('subject-select')) {
            updateTeachersForSubject(e.target);
        }
        
        // Gestion du changement d'enseignant - afficher le nom
        if (e.target.classList.contains('teacher-select')) {
            updateTeacherInfo(e.target);
        }
    });

    // Ajouter un nouveau créneau horaire
    document.getElementById('addTimeSlotBtn').addEventListener('click', function() {
        const tbody = document.getElementById('scheduleTableBody');
        const newRow = createNewTimeSlotRow(timeSlotCounter);
        tbody.appendChild(newRow);
        timeSlotCounter++;
    });

    // Aperçu
    document.getElementById('previewBtn').addEventListener('click', function() {
        generatePreview();
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    });

    // Sauvegarde
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSchedule();
    });

    // Charger les données existantes si disponibles
    if (scheduleConfig.existingSchedules) {
        loadExistingSchedule();
    }
});

function createNewTimeSlotRow(index) {
    const row = document.createElement('tr');
    row.setAttribute('data-time-slot', index);
    
    let rowHTML = `
        <td class="text-center align-middle">
            <div class="time-slot-controls">
                <input type="time" name="time_slots[${index}][start]" value="08:00" class="form-control form-control-sm mb-1 start-time">
                <input type="time" name="time_slots[${index}][end]" value="09:00" class="form-control form-control-sm end-time">
                <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeTimeSlot(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;

    // Utiliser les données JS au lieu de PHP
    Object.keys(days).forEach(dayNumber => {
        rowHTML += `
            <td class="schedule-cell" data-day="${dayNumber}" data-slot="${index}">
                <div class="schedule-content">
                    <select name="schedule[${index}][${dayNumber}][type]" class="form-select form-select-sm mb-2 slot-type">
                        <option value="">-- Vide --</option>
                        <option value="course">Cours</option>
                        <option value="break">Pause</option>
                    </select>

                    <div class="course-fields d-none">
                        <select name="schedule[${index}][${dayNumber}][subject_id]" class="form-select form-select-sm mb-1 subject-select">
                            <option value="">-- Matière --</option>
                            ${subjects.map(subject => `<option value="${subject.id}">${subject.name}</option>`).join('')}
                        </select>

                        <select name="schedule[${index}][${dayNumber}][teacher_id]" class="form-select form-select-sm mb-1 teacher-select">
                            <option value="">-- Enseignant --</option>
                            <!-- Les options seront mises à jour dynamiquement selon la matière -->
                        </select>

                        <input type="text" name="schedule[${index}][${dayNumber}][room]" placeholder="Salle" class="form-control form-control-sm">
                    </div>

                    <div class="break-fields d-none">
                        <input type="text" name="schedule[${index}][${dayNumber}][title]" placeholder="Nom de la pause" class="form-control form-control-sm" value="Pause">
                    </div>
                </div>
            </td>
        `;
    });

    row.innerHTML = rowHTML;
    return row;
}

// Fonction pour mettre à jour la liste des enseignants selon la matière sélectionnée
function updateTeachersForSubject(subjectSelect) {
    const cell = subjectSelect.closest('.schedule-cell');
    const teacherSelect = cell.querySelector('.teacher-select');
    const subjectId = subjectSelect.value;
    
    // Vider la liste des enseignants
    teacherSelect.innerHTML = '<option value="">-- Enseignant --</option>';
    
    if (subjectId && teachersBySubject[subjectId]) {
        // Ajouter les enseignants spécialisés dans cette matière
        teachersBySubject[subjectId].forEach(teacher => {
            const option = document.createElement('option');
            option.value = teacher.id;
            option.textContent = `${teacher.first_name} ${teacher.last_name}`;
            teacherSelect.appendChild(option);
        });
        
        // Si aucun enseignant spécialisé, afficher tous les enseignants avec mention
        if (teachersBySubject[subjectId].length === 0) {
            const optionInfo = document.createElement('option');
            optionInfo.value = '';
            optionInfo.textContent = '-- Aucun enseignant spécialisé, tous les enseignants --';
            optionInfo.disabled = true;
            teacherSelect.appendChild(optionInfo);
            
            // Ajouter tous les enseignants disponibles
            teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = `${teacher.name} (non spécialisé)`;
                teacherSelect.appendChild(option);
            });
        }
    }
}

function removeTimeSlot(index) {
    const row = document.querySelector(`tr[data-time-slot="${index}"]`);
    if (row) {
        row.remove();
    }
}

function generatePreview() {
    const previewContent = document.getElementById('previewContent');
    
    // Générer un aperçu HTML de l'emploi du temps
    let previewHTML = '<div class="table-responsive"><table class="table table-bordered"><thead class="table-primary"><tr><th>Horaires</th>';
    
    // Ajouter les en-têtes des jours
    Object.values(days).forEach(dayName => {
        previewHTML += `<th class="text-center">${dayName}</th>`;
    });
    
    previewHTML += '</tr></thead><tbody>';
    
    // Parcourir les créneaux et afficher le contenu
    const rows = document.querySelectorAll('#scheduleTableBody tr');
    rows.forEach((row, index) => {
        const startTime = row.querySelector('.start-time').value;
        const endTime = row.querySelector('.end-time').value;
        
        previewHTML += `<tr><td class="text-center align-middle"><strong>${startTime} - ${endTime}</strong></td>`;
        
        Object.keys(days).forEach(dayNumber => {
            const cell = row.querySelector(`[data-day="${dayNumber}"]`);
            const type = cell.querySelector('.slot-type').value;
            
            previewHTML += '<td class="text-center align-middle">';
            
            if (type === 'course') {
                const subjectSelect = cell.querySelector('.subject-select');
                const teacherSelect = cell.querySelector('.teacher-select');
                const room = cell.querySelector('input[name*="[room]"]').value;
                
                const subjectText = subjectSelect.options[subjectSelect.selectedIndex]?.text || '';
                const teacherText = teacherSelect.options[teacherSelect.selectedIndex]?.text || '';
                
                if (subjectText && teacherText) {
                    previewHTML += `<div class="bg-light p-2 rounded"><strong>${subjectText}</strong><br><small>${teacherText}</small>`;
                    if (room) previewHTML += `<br><em>${room}</em>`;
                    previewHTML += '</div>';
                }
            } else if (type === 'break') {
                const title = cell.querySelector('input[name*="[title]"]').value || 'Pause';
                previewHTML += `<div class="bg-warning p-2 rounded text-dark"><strong>${title}</strong></div>`;
            }
            
            previewHTML += '</td>';
        });
        
        previewHTML += '</tr>';
    });
    
    previewHTML += '</tbody></table></div>';
    previewContent.innerHTML = previewHTML;
}

function saveSchedule() {
    const scheduleData = [];
    
    // Collecter les données du formulaire
    const rows = document.querySelectorAll('#scheduleTableBody tr');
    rows.forEach((row, rowIndex) => {
        const startTime = row.querySelector('.start-time').value;
        const endTime = row.querySelector('.end-time').value;
        
        if (!startTime || !endTime) return;
        
        Object.keys(days).forEach(dayNumber => {
            const cell = row.querySelector(`[data-day="${dayNumber}"]`);
            const type = cell.querySelector('.slot-type').value;
            
            if (type) {
                const scheduleItem = {
                    day: parseInt(dayNumber),
                    start_time: startTime,
                    end_time: endTime,
                    type: type
                };
                
                if (type === 'course') {
                    scheduleItem.subject_id = cell.querySelector('.subject-select').value;
                    scheduleItem.teacher_id = cell.querySelector('.teacher-select').value;
                    scheduleItem.room = cell.querySelector('input[name*="[room]"]').value;
                } else if (type === 'break') {
                    scheduleItem.title = cell.querySelector('input[name*="[title]"]').value;
                }
                
                scheduleData.push(scheduleItem);
            }
        });
    });
    
    // Envoyer les données
    fetch(storeUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            class_id: classId,
            academic_year_id: academicYearId,
            schedule_data: scheduleData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = indexUrl;
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'enregistrement.');
    });
}

// Fonction pour charger les emplois du temps existants
function loadExistingSchedule() {
    if (scheduleConfig.existingSchedules) {
        scheduleConfig.existingSchedules.forEach(schedule => {
            // Trouver la cellule correspondante et remplir les données
            console.log('Schedule à charger:', schedule);
        });
    }
}

// Gestion des matières et enseignants
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('subject-select')) {
        const subjectId = e.target.value;
        const teacherSelect = e.target.closest('.course-fields').querySelector('.teacher-select');
        const teacherInfo = e.target.closest('.course-fields').querySelector('.selected-teacher-info');
        
        // Réinitialiser le select des enseignants
        teacherSelect.innerHTML = '<option value="">-- Enseignant --</option>';
        teacherInfo.classList.add('d-none');
        
        if (subjectId) {
            // Récupérer les enseignants pour cette matière
            const teachers = teachersBySubject[subjectId] || [];
            
            teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = teacher.name;
                teacherSelect.appendChild(option);
            });
            
            // Si un seul enseignant, le sélectionner automatiquement
            if (teachers.length === 1) {
                teacherSelect.value = teachers[0].id;
                updateTeacherInfo(teacherSelect);
            }
        }
    }
    
    if (e.target.classList.contains('teacher-select')) {
        updateTeacherInfo(e.target);
    }
});

// Mettre à jour l'affichage des informations du professeur
function updateTeacherInfo(teacherSelect) {
    const teacherInfo = teacherSelect.closest('.course-fields').querySelector('.selected-teacher-info');
    const teacherNameSpan = teacherInfo.querySelector('.teacher-name');
    
    if (teacherSelect.value) {
        const selectedOption = teacherSelect.options[teacherSelect.selectedIndex];
        teacherNameSpan.textContent = selectedOption.textContent;
        teacherInfo.classList.remove('d-none');
    } else {
        teacherInfo.classList.add('d-none');
    }
}
</script>
@endpush
