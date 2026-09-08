<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\Api\PaymentApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route de vérification de santé pour les problèmes de connexion
Route::get('/health-check', function (Request $request) {
    try {
        // Tester la connexion à la base de données
        DB::connection()->getPdo();
        
        // Tester une requête simple
        DB::table('school_settings')->where('is_active', 1)->limit(1)->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Connexion à la base de données réussie',
            'timestamp' => now()->toISOString()
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Problème de connexion à la base de données',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
});

// Route pour vérifier le statut d'un élève lors de la réinscription
Route::post('/enrollments/check-student-status', [\App\Http\Controllers\EnrollmentController::class, 'checkStudentStatus']);

// Route pour rechercher un élève et suggérer la classe pour réinscription
Route::post('/enrollments/search-student-for-reinscription', [\App\Http\Controllers\EnrollmentController::class, 'searchStudentForReinscription']);

// Routes API publiques pour les emplois du temps
Route::prefix('schedules')->name('schedules.')->group(function () {
    Route::get('/classes/by-cycle', [ScheduleController::class, 'getClassesByCycle'])->name('classes.by-cycle');
    Route::get('/class-subjects-teachers', [ScheduleController::class, 'getClassSubjectsAndTeachers'])->name('class-subjects-teachers');
});

// Route API pour récupérer les frais par cycle
Route::get('/level-fees', function(Request $request) {
    $levelId = $request->input('level_id');
    $cycle = $request->input('cycle');
    $academicYearId = $request->input('academic_year_id');
    
    // Récupérer l'année en cours si non spécifiée
    if (!$academicYearId) {
        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        $academicYearId = $currentYear ? $currentYear->id : null;
    }
    
    // Si un niveau est fourni, récupérer son cycle
    if ($levelId && !$cycle) {
        $level = \App\Models\Level::find($levelId);
        $cycle = $level ? $level->cycle : null;
    }
    
    if (!$cycle) {
        return response()->json(['error' => 'Cycle is required'], 400);
    }
    
    // Récupérer les frais de scolarité de base pour ce cycle
    $baseTuitionFee = \App\Models\LevelFee::where('cycle', $cycle)
        ->where('is_base_tuition', true)
        ->where('is_active', true)
        ->when($academicYearId, function($query) use ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId);
        })
        ->first(['id', 'name', 'amount', 'fee_type', 'is_mandatory', 'description', 'frequency', 'is_base_tuition']);
    
    // Récupérer les frais optionnels du cycle (non scolarité de base)
    $cycleFees = \App\Models\LevelFee::where('cycle', $cycle)
        ->where('is_general', false)
        ->where('is_base_tuition', false)
        ->where('is_active', true)
        ->when($academicYearId, function($query) use ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId);
        })
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'amount', 'fee_type', 'is_mandatory', 'description', 'frequency', 'is_base_tuition']);
    
    // Récupérer les frais généraux (non liés au cycle)
    $generalFees = \App\Models\LevelFee::where('is_general', true)
        ->where('is_active', true)
        ->when($academicYearId, function($query) use ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId);
        })
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'amount', 'fee_type', 'is_mandatory', 'description', 'frequency', 'is_base_tuition']);
    
    // Combiner les frais optionnels
    $optionalFees = $cycleFees->concat($generalFees);
    
    // Calculer les totaux
    $baseTuitionAmount = $baseTuitionFee ? $baseTuitionFee->amount : 0;
    $optionalTotal = $optionalFees->sum('amount');
    $grandTotal = $baseTuitionAmount + $optionalTotal;
    
    return response()->json([
        'base_tuition' => $baseTuitionFee,
        'base_tuition_amount' => $baseTuitionAmount,
        'optional_fees' => $optionalFees,
        'cycle_fees' => $cycleFees,
        'general_fees' => $generalFees,
        'optional_total' => $optionalTotal,
        'grand_total' => $grandTotal,
        'cycle' => $cycle
    ]);
})->name('levelFees');

// Routes API pour les paiements
Route::prefix('v1')->name('api.')->group(function () {
    Route::apiResource('payments', PaymentApiController::class)->names([
        'index' => 'api.payments.index',
        'store' => 'api.payments.store',
        'show' => 'api.payments.show',
        'update' => 'api.payments.update',
        'destroy' => 'api.payments.destroy',
    ]);
    
    // Routes supplémentaires pour les paiements
    Route::post('payments/{payment}/complete', [PaymentApiController::class, 'complete'])->name('payments.complete');
    Route::post('payments/{payment}/cancel', [PaymentApiController::class, 'cancel'])->name('payments.cancel');
    Route::post('payments/{payment}/refund', [PaymentApiController::class, 'refund'])->name('payments.refund');
    Route::get('payments/stats/overview', [PaymentApiController::class, 'stats'])->name('payments.stats');
});
