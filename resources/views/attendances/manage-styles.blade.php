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
