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
    
    // Gérer les boutons de détails d'élève
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
    
    showSpinnerOverlay('Enregistrement en cours...', 'Sauvegarde des modifications');
    
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
