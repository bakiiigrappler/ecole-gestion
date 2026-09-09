<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FeeManagementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CompetencyEvaluationController;
use App\Http\Controllers\Admin\SchoolSettingsController;
use App\Http\Controllers\SeriesController;

// Routes d'authentification (publiques)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Routes publiques pour le portail parent et paiements en ligne
Route::prefix('parent')->name('parent-portal.')->group(function () {
    // Connexion du portail parent
    Route::get('/login', [ParentPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [ParentPortalController::class, 'login']);
    
    // Inscription en ligne (publique)
    Route::get('/enrollment', [ParentPortalController::class, 'showOnlineEnrollment'])->name('online-enrollment');
    Route::post('/enrollment', [ParentPortalController::class, 'processOnlineEnrollment'])->name('process-enrollment');
    
    // Paiements en ligne (publiques)
    Route::get('/payment/{transactionId}', [ParentPortalController::class, 'showPayment'])->name('payment');
    Route::post('/payment/{transactionId}', [ParentPortalController::class, 'processPayment'])->name('process-payment');
    Route::get('/payment/{transactionId}/success', [ParentPortalController::class, 'paymentSuccess'])->name('payment-success');
});

// Routes publiques pour les paiements en ligne
Route::prefix('payment')->name('online-payment.')->group(function () {
    Route::get('/form', [OnlinePaymentController::class, 'showPaymentForm'])->name('form');
    Route::post('/process', [OnlinePaymentController::class, 'processPayment'])->name('process');
    Route::get('/{transactionId}', [OnlinePaymentController::class, 'showPayment'])->name('show');
    Route::post('/{transactionId}/process', [OnlinePaymentController::class, 'processGatewayPayment'])->name('process-gateway');
    Route::get('/{transactionId}/success', [OnlinePaymentController::class, 'paymentSuccess'])->name('success');
    Route::get('/{transactionId}/failed', [OnlinePaymentController::class, 'paymentFailed'])->name('failed');
    Route::get('/{transactionId}/status', [OnlinePaymentController::class, 'checkPaymentStatus'])->name('status');
});

// Callbacks des passerelles de paiement (publiques)
Route::prefix('api/payments')->name('payment-callback.')->group(function () {
    Route::post('/moov/callback', [OnlinePaymentController::class, 'gatewayCallback'])->name('moov');
    Route::post('/airtel/callback', [OnlinePaymentController::class, 'gatewayCallback'])->name('airtel');
});

// Routes API publiques pour l'inscription
Route::prefix('api')->name('api.')->group(function () {
    Route::post('/students/check-matricule', [StudentController::class, 'checkMatricule'])->name('students.checkMatricule');
    Route::get('/teachers/by-level/{levelId}', [ClassController::class, 'getTeachersByLevel'])->name('teachers.byLevel');
    Route::get('/subjects/by-level/{levelId}', [ClassController::class, 'getSubjectsByLevel'])->name('subjects.byLevel');
    
    // Routes API pour le formulaire de notes dynamique
    Route::get('/students/with-classes', [GradeController::class, 'getStudentsWithClasses'])->name('students.withClasses');
    Route::get('/subjects/with-teachers', [GradeController::class, 'getSubjectsWithTeachers'])->name('subjects.withTeachers');
    Route::get('/students/{studentId}/info', [GradeController::class, 'getStudentInfo'])->name('students.info');
    Route::get('/classes/{classId}/subjects', [GradeController::class, 'getClassSubjects'])->name('classes.subjects');
    Route::get('/subjects/{subjectId}/teacher/{classId}', [GradeController::class, 'getSubjectTeacherForClass'])->name('subjects.teacherForClass');
    
    // Routes API pour la sélection hiérarchique
    Route::get('/levels/{levelId}/classes', [GradeController::class, 'getClassesForLevel'])->name('levels.classes');
    Route::get('/classes/{classId}/students', [GradeController::class, 'getStudentsForClass'])->name('classes.students');
    
    // Route de test pour diagnostiquer
    Route::get('/test-api', function() {
        return response()->json([
            'message' => 'API fonctionne',
            'timestamp' => now(),
            'levels_count' => \App\Models\Level::count(),
            'classes_count' => \App\Models\SchoolClass::count(),
            'students_count' => \App\Models\Student::count()
        ]);
    })->name('api.test');
    
    // Route de test pour les données de grades
    Route::post('/test-grades', function(\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info('Test données grades', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'all_data' => $request->all(),
            'json_data' => $request->json() ? $request->json()->all() : null
        ]);
        
        return response()->json([
            'message' => 'Données reçues',
            'data' => $request->all(),
            'json' => $request->json() ? $request->json()->all() : null
        ]);
    })->name('api.testGrades');
    
    // Route de test pour les niveaux
    Route::get('/test-levels', function() {
        $levels = \App\Models\Level::active()->orderBy('order')->get(['id', 'name', 'cycle']);
        return response()->json($levels);
    })->name('api.testLevels');
});

// Route de déconnexion
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Redirection de la page d'accueil vers le dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Route de test pour l'export PDF (sans authentification)
Route::get('/test-export-pdf', [App\Http\Controllers\TestExportController::class, 'testPDF']);

// Route de test pour l'export Excel (sans authentification)
Route::get('/test-export-excel', [App\Http\Controllers\TestExcelController::class, 'testExcel']);

// Routes protégées par authentification
Route::middleware('auth')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route pour les statistiques
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
    
    // API pour les statistiques dynamiques
    Route::post('/api/statistics', [StatisticsController::class, 'api'])->name('api.statistics');
    
    // Routes d'export des statistiques
    Route::get('/statistics/export/pdf', [StatisticsController::class, 'exportPDF'])->name('statistics.export.pdf');
    Route::get('/statistics/export/excel', [StatisticsController::class, 'exportExcel'])->name('statistics.export.excel');
    Route::get('/statistics/export/csv', [StatisticsController::class, 'exportCSV'])->name('statistics.export.csv');
    
    // Routes d'export du dashboard
    Route::get('/dashboard/export/general', [DashboardController::class, 'exportGeneralReport'])->name('dashboard.export.general');
    Route::get('/dashboard/export/financial', [DashboardController::class, 'exportFinancialReport'])->name('dashboard.export.financial');
    Route::get('/dashboard/export/enrollment', [DashboardController::class, 'exportEnrollmentReport'])->name('dashboard.export.enrollment');
    Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');

    // Routes pour les bulletins
    Route::get('/bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
    Route::get('/bulletins/student/{studentId}', [BulletinController::class, 'studentGrades'])->name('bulletins.student');
    Route::get('/bulletins/class/{id}', [BulletinController::class, 'show'])->name('bulletins.class');
    Route::get('/bulletins/level/{levelId}', [BulletinController::class, 'byLevel'])->name('bulletins.byLevel');
    Route::get('/bulletins/cycle/{cycle}', [BulletinController::class, 'byCycle'])->name('bulletins.byCycle');

    // Routes pour les évaluations par compétences (Primaire)
    Route::prefix('competency-evaluations')->name('competency-evaluations.')->group(function () {
        Route::get('/', [CompetencyEvaluationController::class, 'index'])->name('index');
        Route::get('/create', [CompetencyEvaluationController::class, 'create'])->name('create');
        Route::post('/', [CompetencyEvaluationController::class, 'store'])->name('store');
        Route::post('/single-student', [CompetencyEvaluationController::class, 'storeSingleStudent'])->name('store-single-student');
        Route::get('/{classId}/bulletins', [CompetencyEvaluationController::class, 'showBulletins'])->name('bulletins');
        Route::get('/student/{student}/bulletin', [CompetencyEvaluationController::class, 'generateStudentBulletin'])->name('student-bulletin');
        Route::get('/api/competencies', [CompetencyEvaluationController::class, 'getCompetenciesBySubjectArea'])->name('api.competencies');
        Route::get('/api/student-competency-data/{studentId}/{palier}', [CompetencyEvaluationController::class, 'getStudentCompetencyData'])->name('api.student-competency-data');
        Route::get('/api/student-passage-conditions/{studentId}', [CompetencyEvaluationController::class, 'checkStudentPassageConditions'])->name('api.student-passage-conditions');
    });

    // Routes pour les évaluations du préprimaire (Maternelle)
    Route::prefix('pre-primary-evaluations')->name('pre-primary-evaluations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'store'])->name('store');
        Route::get('/{classId}/bulletins', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'showBulletins'])->name('bulletins');
        Route::get('/student/{student}/bulletin', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'generateStudentBulletin'])->name('student-bulletin');
        Route::delete('/{id}', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'destroy'])->name('destroy');
        Route::delete('/student/destroy-all', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'destroyStudentEvaluations'])->name('destroy-student');
        Route::post('/reset-trimester', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'resetTrimester'])->name('reset-trimester');
        Route::get('/api/student-data/{studentId}', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'getStudentData'])->name('api.student-data');
        Route::get('/api/class-statistics/{classId}', [\App\Http\Controllers\PrePrimaryCompetencyEvaluationController::class, 'getClassStatistics'])->name('api.class-statistics');
    });
});

// Routes API publiques pour les bulletins (sans authentification)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/student-competency-data/{studentId}/{palier}', [CompetencyEvaluationController::class, 'getStudentCompetencyData'])->name('student-competency-data');
});

/*
 * Le groupe `auth` precedent se refermait juste avant ce bloc : les routes qui
 * suivaient — eleves, enseignants, parents, classes, notes, bulletins,
 * paiements, presences, inscriptions, administration, portail parent — etaient
 * declarees hors de tout groupe malgre leur indentation, et repondaient donc a
 * n'importe quel visiteur non connecte.
 */
Route::middleware('auth')->group(function () {

// Routes pour la gestion des élèves
    Route::resource('students', StudentController::class)->names([
        'index' => 'students.index',
        'create' => 'students.create',
        'store' => 'students.store',
        'show' => 'students.show',
        'edit' => 'students.edit',
        'update' => 'students.update',
        'destroy' => 'students.destroy',
    ]);
    
    // Route AJAX pour les détails d'un élève
    Route::get('/students/{id}/details', [StudentController::class, 'getStudentDetails'])->name('students.details');

    // Routes pour la gestion des enseignants
    Route::resource('teachers', TeacherController::class)->names([
        'index' => 'teachers.index',
        'create' => 'teachers.create',
        'store' => 'teachers.store',
        'show' => 'teachers.show',
        'edit' => 'teachers.edit',
        'update' => 'teachers.update',
        'destroy' => 'teachers.destroy',
    ]);

    // Routes pour la gestion des parents
    Route::resource('parents', ParentController::class)->names([
        'index' => 'parents.index',
        'create' => 'parents.create',
        'store' => 'parents.store',
        'show' => 'parents.show',
        'edit' => 'parents.edit',
        'update' => 'parents.update',
        'destroy' => 'parents.destroy',
    ]);

    // Routes pour la gestion des classes
    // Avant la resource : sinon `/classes/{class}` capterait ces segments.
    Route::get('/classes/proposer-nom', [ClassController::class, 'proposerNom'])->name('classes.proposerNom');
    Route::get('/classes/series-du-niveau', [ClassController::class, 'seriesDuNiveau'])->name('classes.seriesDuNiveau');

    Route::resource('classes', ClassController::class)->names([
        'index' => 'classes.index',
        'create' => 'classes.create',
        'store' => 'classes.store',
        'show' => 'classes.show',
        'edit' => 'classes.edit',
        'update' => 'classes.update',
        'destroy' => 'classes.destroy',
    ]);
    
    // Route pour gérer les élèves d'une classe
    Route::get('/classes/{class}/students', [ClassController::class, 'students'])->name('classes.students');

    // La fiche de classe : liste nominative imprimable des eleves.
    Route::get('/classes/{class}/fiche', [ClassController::class, 'fiche'])->name('classes.fiche');
    
    // Route pour gérer les professeurs d'une classe
    Route::get('/classes/{class}/teachers', [ClassController::class, 'teachers'])->name('classes.teachers');
    Route::post('/classes/{class}/teachers', [ClassController::class, 'updateTeachers'])->name('classes.updateTeachers');
    Route::post('/classes/{class}/teachers/{teacher}/principal', [ClassController::class, 'setPrincipalTeacher'])->name('classes.setPrincipalTeacher');
    Route::post('/classes/{class}/teachers/{teacher}/remove-principal', [ClassController::class, 'removePrincipalTeacher'])->name('classes.removePrincipalTeacher');
    
    // API pour récupérer les classes existantes et suggérer le prochain nom
    Route::get('/api/levels/{levelId}/existing-classes', [ClassController::class, 'getExistingClassesForLevel'])->name('api.level.existingClasses');
    
    // API pour récupérer les enseignants par niveau
    Route::get('/api/levels/{levelId}/teachers', [ClassController::class, 'getTeachersForLevel'])->name('api.level.teachers');

    // Routes pour la gestion des séries
    // Avant la resource : sinon `/series/{series}` capterait ce segment.
    Route::get('/series/proposer-code', [SeriesController::class, 'proposerCode'])->name('series.proposerCode');

    Route::resource('series', SeriesController::class)->names([
        'index' => 'series.index',
        'create' => 'series.create',
        'store' => 'series.store',
        'show' => 'series.show',
        'edit' => 'series.edit',
        'update' => 'series.update',
        'destroy' => 'series.destroy',
    ]);

    // Routes pour la gestion des notes
    Route::resource('grades', GradeController::class)->names([
        'index' => 'grades.index',
        'create' => 'grades.create',
        'store' => 'grades.store',
        'show' => 'grades.show',
        'edit' => 'grades.edit',
        'update' => 'grades.update',
        'destroy' => 'grades.destroy',
    ]);
    
    // Route spécifique pour le bulletin d'un élève
    Route::get('/grades/student/{studentId}/bulletin', [GradeController::class, 'showBulletin'])->name('grades.bulletin');
    Route::get('/grades/student/{studentId}/bulletin-simple', [App\Http\Controllers\SimpleGradeController::class, 'showBulletin'])->name('grades.bulletin.simple');
    Route::get('/grades/student/{studentId}/bulletin/pdf', [GradeController::class, 'downloadBulletinPDF'])->name('grades.bulletin.pdf');
Route::get('/grades/student/{studentId}/bulletin-carte', [GradeController::class, 'showBulletinCarte'])->name('grades.bulletin.carte');
    Route::get('/api/grades/student/{studentId}/trimesters', [GradeController::class, 'getStudentTrimesters'])->name('api.grades.student.trimesters');
    Route::get('/grades-simple', [App\Http\Controllers\SimpleGradesController::class, 'index'])->name('grades.simple');
    
    // Route pour supprimer toutes les notes d'un élève
    Route::delete('/grades/student/{studentId}/delete-all', [GradeController::class, 'deleteAllGradesForStudent'])->name('grades.delete-all-for-student');
    
    // Route pour gérer les notes d'un élève
    Route::get('/grades/student/{studentId}/manage', [GradeController::class, 'manageStudentGrades'])->name('grades.manage-student');

    // Routes pour la gestion des présences (définies plus bas avec plus de détails)

    // Routes pour la gestion des frais (ancien système) - Redirigé vers le nouveau système
    Route::get('fees', [FeeController::class, 'index'])->name('fees.index');

    // Les vues fees/create, edit et show existaient sans aucune route : le
    // module ne permettait ni de creer ni de modifier un frais.
    Route::get('fees/create', [FeeController::class, 'create'])->name('fees.create');
    Route::post('fees', [FeeController::class, 'store'])->name('fees.store');

    // Routes pour la nouvelle gestion des frais
    Route::prefix('fees')->name('fees.')->group(function () {
        // Tableau de bord
        Route::get('dashboard', [App\Http\Controllers\FeeManagementController::class, 'dashboard'])->name('dashboard');
        
        // Frais de niveau
        Route::get('level-fees', [App\Http\Controllers\FeeManagementController::class, 'levelFees'])->name('level-fees');
        Route::post('level-fee', [App\Http\Controllers\FeeManagementController::class, 'createLevelFee'])->name('level-fee.store');
        Route::get('level-fee/{levelFee}', [App\Http\Controllers\FeeManagementController::class, 'showLevelFee'])->name('level-fee.show');
        Route::put('level-fee/{levelFee}', [App\Http\Controllers\FeeManagementController::class, 'updateLevelFee'])->name('level-fee.update');
        Route::delete('level-fee/{levelFee}', [App\Http\Controllers\FeeManagementController::class, 'destroyLevelFee'])->name('level-fee.destroy');
        
        // Frais de classe
        Route::get('class-fees', [App\Http\Controllers\FeeManagementController::class, 'classFees'])->name('class-fees');
        Route::post('class-fee', [App\Http\Controllers\FeeManagementController::class, 'createClassFee'])->name('class-fee.store');
        Route::get('class-fee/{classFee}', [App\Http\Controllers\FeeManagementController::class, 'showClassFee'])->name('class-fee.show');
        Route::put('class-fee/{classFee}', [App\Http\Controllers\FeeManagementController::class, 'updateClassFee'])->name('class-fee.update');
        Route::delete('class-fee/{classFee}', [App\Http\Controllers\FeeManagementController::class, 'destroyClassFee'])->name('class-fee.destroy');
        
        // Frais d'inscription
        Route::get('enrollment-fees', [App\Http\Controllers\FeeManagementController::class, 'enrollmentFees'])->name('enrollment-fees');
        Route::get('enrollment-fee/{enrollmentFee}', [App\Http\Controllers\FeeManagementController::class, 'showEnrollmentFee'])->name('enrollment-fee.show');
        Route::post('enrollment-fee/{enrollmentFee}/mark-paid', [App\Http\Controllers\FeeManagementController::class, 'markAsPaid'])->name('enrollment-fee.mark-paid');
        Route::post('enrollment-fee/{enrollmentFee}/mark-unpaid', [App\Http\Controllers\FeeManagementController::class, 'markAsUnpaid'])->name('enrollment-fee.mark-unpaid');
        
        // Rapports
        Route::get('report', [App\Http\Controllers\FeeManagementController::class, 'generateReport'])->name('report');
        Route::post('duplicate', [App\Http\Controllers\FeeManagementController::class, 'duplicateFees'])->name('duplicate');
    });

    // Apres le groupe : « fees/{fee} » capterait sinon « dashboard »,
    // « level-fees » ou « class-fees » et tenterait de les lire comme un id.
    Route::get('fees/{fee}', [FeeController::class, 'show'])->name('fees.show');
    Route::get('fees/{fee}/edit', [FeeController::class, 'edit'])->name('fees.edit');
    Route::put('fees/{fee}', [FeeController::class, 'update'])->name('fees.update');
    Route::delete('fees/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');

    // Routes pour la gestion des paiements
    /*
     * Avant la ressource : « payments/declarations » serait sinon capte par
     * « payments/{payment} », qui tenterait de lire « declarations » comme un
     * identifiant.
     */
    Route::get('payments/declarations', [PaymentController::class, 'declarations'])
        ->name('payments.declarations');

    Route::resource('payments', PaymentController::class)->except(['create'])->names([
        'index' => 'payments.index',
        'store' => 'payments.store',
        'show' => 'payments.show',
        'edit' => 'payments.edit',
        'update' => 'payments.update',
        'destroy' => 'payments.destroy',
    ]);
    
    // Routes supplémentaires pour les paiements
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('payments/{payment}/complete', [PaymentController::class, 'complete'])->name('payments.complete');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
Route::get('payments/{payment}/edit-modal', [PaymentController::class, 'editModal'])->name('payments.edit-modal');
Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');

    // Routes pour la gestion des matières
    // Avant la resource : sinon `/subjects/{subject}` capterait ce segment.
    Route::get('/subjects/proposer-code', [SubjectController::class, 'proposerCode'])->name('subjects.proposerCode');

    Route::resource('subjects', SubjectController::class)->names([
        'index' => 'subjects.index',
        'create' => 'subjects.create',
        'store' => 'subjects.store',
        'show' => 'subjects.show',
        'edit' => 'subjects.edit',
        'update' => 'subjects.update',
        'destroy' => 'subjects.destroy',
    ]);

    // Route pour obtenir les matières par niveau
    Route::get('/levels/{level}/subjects', [SubjectController::class, 'byLevel'])->name('subjects.byLevel');

    // Routes pour la gestion des emplois du temps
    // Avant la resource : sinon `/schedules/{schedule}` capte « build » et
    // « check-existing », et le binding cherche un emploi du temps nomme ainsi.
    Route::get('/schedules/build', [ScheduleController::class, 'build'])->name('schedules.build');
    Route::get('/schedules/check-existing', [ScheduleController::class, 'checkExisting'])->name('schedules.check-existing');

    Route::resource('schedules', ScheduleController::class)->names([
        'index' => 'schedules.index',
        'create' => 'schedules.create',
        'store' => 'schedules.store',
        'show' => 'schedules.show',
        'edit' => 'schedules.edit',
        'destroy' => 'schedules.destroy',
    ]);
    Route::get('/schedules/{class}/print', [ScheduleController::class, 'print'])->name('schedules.print');

    // Routes pour la gestion des présences
    Route::get('/attendances', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/{class}/manage', [App\Http\Controllers\AttendanceController::class, 'manage'])->name('attendances.manage');
        Route::get('/attendances/{class}/edit/{date}', [App\Http\Controllers\AttendanceController::class, 'edit'])->name('attendances.edit-date');
        Route::get('/attendances/{class}/view/{date}', [App\Http\Controllers\AttendanceController::class, 'view'])->name('attendances.view');
    Route::post('/attendances/{class}/store', [App\Http\Controllers\AttendanceController::class, 'store'])->name('attendances.store');
    Route::put('/attendances/{class}/update', [App\Http\Controllers\AttendanceController::class, 'update'])->name('attendances.update');
    Route::delete('/attendances/{class}/delete', [App\Http\Controllers\AttendanceController::class, 'delete'])->name('attendances.delete');
    Route::get('/attendances/{class}/show/{date}', [App\Http\Controllers\AttendanceController::class, 'show'])->name('attendances.show');
    Route::post('/attendances/{class}/filter', [App\Http\Controllers\AttendanceController::class, 'filter'])->name('attendances.filter');
    Route::get('/attendances/{class}/reports', [App\Http\Controllers\AttendanceController::class, 'reports'])->name('attendances.reports');

    /*
     * Segments fixes declares AVANT la resource : sinon `/enrollments/{enrollment}`
     * les capte et le binding cherche une inscription nommee « pending-students ».
     * Les deux URL renvoyaient une erreur SQL.
     */
    Route::get('/enrollments/suggerer-eleves', [EnrollmentController::class, 'suggererEleves'])->name('enrollments.suggererEleves');
    Route::get('/enrollments/rechercher-eleve', [EnrollmentController::class, 'rechercherEleve'])->name('enrollments.rechercherEleve');
    Route::get('/enrollments/frais-du-niveau', [EnrollmentController::class, 'fraisDuNiveau'])->name('enrollments.fraisDuNiveau');
    Route::get('/enrollments/unenrolled-students', [EnrollmentController::class, 'getUnEnrolledStudents'])->name('enrollments.unenrolled');
    Route::get('/enrollments/pending-students', [EnrollmentController::class, 'pendingStudentCreations'])->name('enrollments.pending-students');

    // Routes pour la gestion des inscriptions
    Route::resource('enrollments', EnrollmentController::class)->names([
        'index' => 'enrollments.index',
        'create' => 'enrollments.create',
        'store' => 'enrollments.store',
        'show' => 'enrollments.show',
        'edit' => 'enrollments.edit',
        'update' => 'enrollments.update',
        'destroy' => 'enrollments.destroy',
    ]);
    
    // Routes spéciales pour les réinscriptions
    Route::get('/students/{student}/re-enroll', [EnrollmentController::class, 'reEnroll'])->name('enrollments.re-enroll');
    Route::post('/students/{student}/re-enroll', [EnrollmentController::class, 'processReEnrollment'])->name('enrollments.process-re-enrollment');
    
    // Routes pour le nouveau workflow inscription-première
    Route::get('/enrollments/{enrollment}/create-student', [EnrollmentController::class, 'showStudentCreationForm'])->name('enrollments.create-student');
    Route::post('/enrollments/{enrollment}/create-student', [EnrollmentController::class, 'createStudentFromEnrollment'])->name('enrollments.store-student');
    Route::post('/enrollments/{enrollment}/mark-pending', [EnrollmentController::class, 'markAsPending'])->name('enrollments.mark-pending');
    
    // Routes pour la génération de reçus
    Route::get('/enrollments/{enrollment}/receipt', [EnrollmentController::class, 'generateReceipt'])->name('enrollments.receipt');
    Route::get('/enrollments/{enrollment}/receipt/download', [EnrollmentController::class, 'downloadReceipt'])->name('enrollments.download-receipt');
    
    // Route pour l'autorisation d'entrée avec QR code
    Route::get('/enrollments/{enrollment}/entry-authorization/download', [EnrollmentController::class, 'downloadEntryAuthorization'])->name('enrollments.download-entry-authorization');

    // Espace de l'eleve : il n'administre rien, il consulte ce qui le concerne.
    // Chaque rubrique a sa page : tout empiler sur une seule ne donnait ni
    // tableau de bord ni espace de notes, seulement une page a derouler.
    Route::controller(\App\Http\Controllers\MonEspaceController::class)->group(function () {
        Route::get('/mon-espace', 'index')->name('mon-espace');
        Route::get('/mon-espace/notes', 'notes')->name('mon-espace.notes');
        Route::get('/mon-espace/emploi-du-temps', 'emploiDuTemps')->name('mon-espace.emploi-du-temps');
        Route::get('/mon-espace/absences', 'absences')->name('mon-espace.absences');
        Route::get('/mon-espace/fiche', 'fiche')->name('mon-espace.fiche');
    });

    // Routes d'administration (pour admins et superadmins)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Etablissements : le superadmin en gere plusieurs. La bascule doit
        // preceder la resource, sinon « basculer » serait pris pour un id.
        Route::post('/schools/basculer', [\App\Http\Controllers\Admin\SchoolController::class, 'basculer'])->name('schools.basculer');
        Route::post('/schools/{school}/toggle-status', [\App\Http\Controllers\Admin\SchoolController::class, 'toggleStatus'])->name('schools.toggle-status');
        Route::resource('schools', \App\Http\Controllers\Admin\SchoolController::class)->except(['destroy']);

        /*
         * La plateforme : ce qui surplombe les etablissements. Reserve au
         * super administrateur — le controleur le verifie lui-meme.
         */
        Route::prefix('plateforme')->name('plateforme.')->controller(\App\Http\Controllers\Admin\PlateformeController::class)->group(function () {
            Route::get('/personnalisation', 'personnalisation')->name('personnalisation');
            Route::post('/personnalisation', 'personnalisationSave')->name('personnalisation.save');
            Route::post('/personnalisation/reinitialiser', 'personnalisationReset')->name('personnalisation.reset');

            Route::get('/parametres', 'parametres')->name('parametres');
            Route::post('/parametres', 'parametresSave')->name('parametres.save');

            Route::get('/corbeille', 'corbeille')->name('corbeille');
            Route::post('/corbeille/{entite}/{id}/restaurer', 'restaurer')->whereNumber('id')->name('corbeille.restaurer');
            Route::delete('/corbeille/{entite}/{id}', 'supprimer')->whereNumber('id')->name('corbeille.supprimer');
            Route::post('/corbeille/{entite}/vider', 'vider')->name('corbeille.vider');
        });

        // Paramètres généraux (admin/superadmin)
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        
        // Paramètres de l'établissement (admin/superadmin)
        Route::resource('school-settings', SchoolSettingsController::class)->except(['show', 'destroy']);
        Route::get('/school-settings/preview', [SchoolSettingsController::class, 'preview'])->name('school-settings.preview');
        
        // Gestion des utilisateurs (admin/superadmin)
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Le mot de passe n'est pas conserve en clair : le seul recours quand
        // il est perdu est d'en engendrer un nouveau, affiche une fois.
        Route::post('/users/{user}/mot-de-passe', [UserController::class, 'reinitialiserMotDePasse'])
            ->name('users.mot-de-passe');
        
        // Informations système (superadmin uniquement)
        Route::get('/system-info', [SettingsController::class, 'systemInfo'])->name('system-info');
        
        // Sécurité et journaux (superadmin uniquement)
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::get('/logs/download', [SettingsController::class, 'downloadLogs'])->name('logs.download');
        Route::post('/logs/clear', [SettingsController::class, 'clearLogs'])->name('logs.clear');
        
        // Maintenance (superadmin uniquement)
        Route::get('/maintenance', [SettingsController::class, 'maintenance'])->name('maintenance');
        Route::post('/maintenance/clear-cache', [SettingsController::class, 'clearCache'])->name('maintenance.clear-cache');
        Route::post('/maintenance/optimize', [SettingsController::class, 'optimize'])->name('maintenance.optimize');
        Route::post('/maintenance/backup', [SettingsController::class, 'backup'])->name('maintenance.backup');
    });

    // Routes API pour les données dynamiques (optionnel)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/students/search', [StudentController::class, 'search'])->name('students.search');
        Route::get('/students/classes-by-level', [StudentController::class, 'getClassesByLevel'])->name('students.classesByLevel');
        Route::get('/students/classes-by-cycle', [StudentController::class, 'getClassesByCycle'])->name('students.classesByCycle');
        Route::get('/teachers/search', [TeacherController::class, 'search'])->name('teachers.search');
        Route::get('/teachers/classes-by-cycle', [TeacherController::class, 'getClassesByCycle'])->name('teachers.classesByCycle');
        
        // Routes API pour les parents
        Route::get('/parents/search', [ParentController::class, 'search'])->name('parents.search');
        Route::post('/parents/create-and-link', [ParentController::class, 'createAndLink'])->name('parents.createAndLink');
        Route::post('/students/{studentId}/parents/{parentId}/link', [ParentController::class, 'linkToStudent'])->name('students.parents.link');
        Route::delete('/students/{studentId}/parents/{parentId}/unlink', [ParentController::class, 'unlinkFromStudent'])->name('students.parents.unlink');
        Route::get('/levels-by-cycle', function(\Illuminate\Http\Request $request) {
            $levels = \App\Models\Level::where('cycle', $request->cycle)
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'name', 'cycle']);
            return response()->json($levels);
        })->name('levelsByCycle');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        
        // Routes pour obtenir le prochain matricule disponible
        Route::get('/next-student-matricule', function() {
            return response()->json(['matricule' => \App\Models\Student::generateStudentId()]);
        })->name('nextStudentMatricule');
        
        Route::get('/next-teacher-matricule', function() {
            return response()->json(['matricule' => \App\Models\Teacher::generateEmployeeId()]);
        })->name('nextTeacherMatricule');
        
        // Routes API pour les emplois du temps
        Route::get('/schedules/subjects-by-level', [ScheduleController::class, 'getSubjectsByLevel'])->name('schedules.subjectsByLevel');
        Route::post('/schedules/check-conflicts', [ScheduleController::class, 'checkConflicts'])->name('schedules.checkConflicts');
        Route::get('/schedules/check-existing', function(\Illuminate\Http\Request $request) {
            $exists = \App\Models\Schedule::where('class_id', $request->class_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->exists();
            return response()->json(['exists' => $exists]);
        })->name('schedules.checkExisting');
    });
    
    // Routes protégées du portail parent
    Route::prefix('parent')->name('parent-portal.')->group(function () {
        Route::get('/dashboard', [ParentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/children', [ParentPortalController::class, 'children'])->name('children');
        Route::get('/logout', [ParentPortalController::class, 'logout'])->name('logout');
        
        // Gestion des enfants
        Route::get('/child/{studentId}', [ParentPortalController::class, 'childDetails'])->name('child-details');
        Route::get('/child/{studentId}/grades', [ParentPortalController::class, 'childGrades'])->name('child-grades');
        Route::get('/child/{studentId}/attendance', [ParentPortalController::class, 'childAttendance'])->name('child-attendance');
        
        // Paiements et profil
        Route::get('/payments', [ParentPortalController::class, 'paymentHistory'])->name('payment-history');

        /*
         * Regler la scolarite par telephone : l'ecran qui montre ou payer, et
         * la declaration que le secretariat viendra verifier.
         */
        Route::get('/paiement', [ParentPortalController::class, 'paiement'])->name('paiement');
        Route::post('/paiement', [ParentPortalController::class, 'declarerLePaiement'])->name('declarer-paiement');

        // Le parent accuse lecture d'un versement refuse : l'alerte s'efface.
        Route::post('/paiement/{payment}/lu', [ParentPortalController::class, 'accuserLeRejet'])
            ->name('rejet-lu');
        Route::get('/profile', [ParentPortalController::class, 'profile'])->name('profile');
        Route::post('/profile', [ParentPortalController::class, 'updateProfile'])->name('update-profile');
        Route::post('/change-password', [ParentPortalController::class, 'changePassword'])->name('change-password');
    });
});


// Route pour la page d'erreur de connexion à la base de données
Route::get('/database-connection-error', function () {
    $previousUrl = session('database_error_previous_url', '/');
    return view('errors.database-connection', compact('previousUrl'));
})->name('database.connection.error');

