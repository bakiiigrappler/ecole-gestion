<script>
// Variables globales pour les IDs (éviter les conflits)
const viewClassId = '{{ $class->id }}';
const viewAcademicYearId = '{{ $academicYearId }}';

// Fonctions nécessaires pour la page de consultation
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

function generateAttendancePDF(date, formattedDate, className) {
    if (!date || !formattedDate || !className) {
        showAlert('error', 'Paramètres manquants pour la génération du PDF');
        return;
    }
    
    showSpinnerOverlay('Génération du PDF en cours...', 'Récupération des données de présence');
    
    fetch(`/attendances/${viewClassId}/show/${date}?academic_year_id=${viewAcademicYearId}&format=json`)
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

function createAttendancePDF(attendances, date, className, students) {
    if (!attendances || !students || !Array.isArray(attendances) || !Array.isArray(students)) {
        showAlert('error', 'Données invalides pour la génération du PDF');
        return;
    }
    
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    script.onload = function() {
        try {
            showSpinnerOverlay('Création du PDF...', 'Génération du document avec jsPDF');
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4'
            });
        
            const primaryColor = [52, 152, 219];
            const successColor = [40, 167, 69];
            const dangerColor = [220, 53, 69];
            const warningColor = [255, 193, 7];
            const grayColor = [108, 117, 125];
            
            doc.setFillColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.rect(0, 0, 297, 35, 'F');
            
            doc.setDrawColor(255, 255, 255);
            doc.setLineWidth(3);
            doc.rect(5, 8, 287, 23);
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.text('FICHE DE PRÉSENCE', 20, 20);
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Système de Gestion Scolaire', 20, 28);
            
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
            
            const totalStudents = students.length;
            const presentCount = attendances.filter(a => a.status === 'present' || a.status === 'late').length;
            const lateCount = attendances.filter(a => {
                if (a.status === 'present' && a.arrival_time) {
                    const arrivalMinutes = timeToMinutes(a.arrival_time);
                    const startMinutes = timeToMinutes(a.time_slot);
                    return arrivalMinutes > startMinutes;
                }
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
            
            const startY = 70;
            const colWidths = [25, 50, 20, 20, 20, 20, 20, 20, 20, 20, 20];
            const headers = ['N°', 'Nom de l\'élève', '07:30', '08:30', '09:30', '10:45', '11:45', '14:00', '15:00', '16:15', 'Total'];
            
            doc.setFillColor(52, 152, 219);
            doc.rect(20, startY, 257, 12, 'F');
            
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
            
            let currentY = startY + 12;
            students.forEach((student, index) => {
                if (currentY > 180) {
                    doc.addPage();
                    currentY = 20;
                }
                
                if (index % 2 === 0) {
                    doc.setFillColor(248, 249, 250);
                    doc.rect(20, currentY, 257, 10, 'F');
                }
                
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
                    if (dataIndex >= 2 && dataIndex <= 9) {
                        if (data === 'P') {
                            doc.setTextColor(successColor[0], successColor[1], successColor[2]);
                        } else if (data === 'A') {
                            doc.setTextColor(dangerColor[0], dangerColor[1], dangerColor[2]);
                        } else if (data === 'R') {
                            doc.setTextColor(warningColor[0], warningColor[1], warningColor[2]);
                        } else {
                            doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
                        }
                    } else if (dataIndex === 10) {
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
            
            const footerY = 190;
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.5);
            doc.line(20, footerY - 5, 277, footerY - 5);
            
            doc.setFillColor(248, 249, 250);
            doc.rect(20, footerY - 3, 257, 8, 'F');
            
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(grayColor[0], grayColor[1], grayColor[2]);
            doc.text('Fiche générée automatiquement le ' + new Date().toLocaleDateString('fr-FR'), 25, footerY + 2);
            
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(primaryColor[0], primaryColor[1], primaryColor[2]);
            doc.text('Système de Gestion Scolaire', 200, footerY + 2);
            
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

function getAttendanceStatus(attendances, studentId, timeSlot) {
    if (!attendances || !Array.isArray(attendances)) return '-';
    
    const attendance = attendances.find(a => a && a.student_id == studentId && a.time_slot === timeSlot);
    if (!attendance || !attendance.status) return '-';
    
    if (timeSlot === '07:30' || timeSlot === '08:30') {
        if (attendance.status === 'absent' && !attendance.justified) {
            return 'R';
        }
    }
    
    if (attendance.status === 'present') {
        if (attendance.arrival_time) {
            const arrivalTime = attendance.arrival_time;
            const startTime = timeSlot;
            
            const arrivalMinutes = timeToMinutes(arrivalTime);
            const startMinutes = timeToMinutes(startTime);
            
            if (arrivalMinutes > startMinutes) {
                return 'R';
            }
        }
        
        const studentAttendances = attendances.filter(a => a && a.student_id == studentId);
        const hasFirstCreneauAbsent = studentAttendances.some(a => a.time_slot === '07:30' && a.status === 'absent' && !a.justified);
        const hasSecondCreneauAbsent = studentAttendances.some(a => a.time_slot === '08:30' && a.status === 'absent' && !a.justified);
        
        if (hasFirstCreneauAbsent || hasSecondCreneauAbsent) {
            return 'R';
        }
        
        return 'P';
    }
    
    switch(attendance.status) {
        case 'absent': return 'A';
        case 'late': return 'R';
        case 'excused': return 'E';
        default: return '-';
    }
}

function timeToMinutes(timeString) {
    if (!timeString) return 0;
    const [hours, minutes] = timeString.split(':').map(Number);
    return hours * 60 + minutes;
}

function getTotalAttendance(attendances, studentId) {
    if (!attendances || !Array.isArray(attendances)) return '0/8';
    
    const studentAttendances = attendances.filter(a => a && a.student_id == studentId);
    let presentCount = 0;
    
    studentAttendances.forEach(attendance => {
        if (attendance.status === 'present') {
            if (attendance.arrival_time) {
                const arrivalMinutes = timeToMinutes(attendance.arrival_time);
                const startMinutes = timeToMinutes(attendance.time_slot);
                
                if (arrivalMinutes > startMinutes) {
                    presentCount++;
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
    
    const totalSlots = 8;
    return `${presentCount}/${totalSlots}`;
}

function showDeleteConfirmationModal(date, formattedDate) {
    const modalElement = document.getElementById('deleteConfirmationModal');
    const dateDisplay = document.getElementById('deleteDateDisplay');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    if (!modalElement || !dateDisplay || !confirmBtn) {
        console.error('Éléments du modal de suppression non trouvés');
        showAlert('error', 'Erreur: Éléments du modal non trouvés');
        return;
    }
    
    const modal = new bootstrap.Modal(modalElement);
    
    dateDisplay.textContent = formattedDate;
    confirmBtn.setAttribute('data-date-to-delete', date);
    
    modal.show();
}

function deleteDayAttendances(date) {
    const modalElement = document.getElementById('deleteConfirmationModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
    
    showSpinnerOverlay('Suppression en cours...', 'Suppression des présences du ' + date);
    
    fetch(`/attendances/${viewClassId}/delete`, {
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

document.addEventListener('DOMContentLoaded', function() {
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
    }
});
</script>
