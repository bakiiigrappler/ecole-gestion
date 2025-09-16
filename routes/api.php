<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// Routes API publiques pour les emplois du temps
Route::prefix('schedules')->name('schedules.')->group(function () {
    Route::get('/classes/by-cycle', [ScheduleController::class, 'getClassesByCycle'])->name('classes.by-cycle');
    Route::get('/class-subjects-teachers', [ScheduleController::class, 'getClassSubjectsAndTeachers'])->name('class-subjects-teachers');
});

// Routes API pour les paiements
Route::prefix('v1')->group(function () {
    Route::apiResource('payments', PaymentApiController::class);
    
    // Routes supplémentaires pour les paiements
    Route::post('payments/{payment}/complete', [PaymentApiController::class, 'complete']);
    Route::post('payments/{payment}/cancel', [PaymentApiController::class, 'cancel']);
    Route::post('payments/{payment}/refund', [PaymentApiController::class, 'refund']);
    Route::get('payments/stats/overview', [PaymentApiController::class, 'stats']);
});
