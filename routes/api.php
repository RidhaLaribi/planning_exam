<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\EtudiantController;
use App\Http\Controllers\ProfesseurController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LieuExamenController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\DoyenController;
use App\Http\Controllers\ChefDepartementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes - TODO: Re-enable auth:sanctum middleware in production
// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // User Management
    Route::apiResource('users', UserController::class);

    // Resources
    Route::apiResource('departements', DepartementController::class);
    Route::apiResource('formations', FormationController::class);
    Route::apiResource('modules', ModuleController::class);
    Route::apiResource('etudiants', EtudiantController::class);
    Route::apiResource('professeurs', ProfesseurController::class);
    Route::apiResource('lieu_examen', LieuExamenController::class)->parameters(['lieu_examen' => 'lieuExamen']);

    // Examens & Advanced Queries
    Route::get('examens/schedule', [ExamenController::class, 'schedule']);
    Route::get('examens/conflicts', [ExamenController::class, 'detectConflicts']);
    Route::get('examens/departement/{id}', [ExamenController::class, 'byDepartement']);
    Route::get('examens/etudiant/{id}', [ExamenController::class, 'byEtudiant']);
    Route::get('examens/professeur/{id}', [ExamenController::class, 'byProfesseur']);

    // Doyen Routes
    Route::prefix('doyen')->group(function () {
        Route::get('dashboard', [DoyenController::class, 'dashboard']);
        Route::get('schedule', [DoyenController::class, 'schedule']);
        Route::post('validate', [DoyenController::class, 'validateSchedule']);
        Route::post('detect-conflicts', [DoyenController::class, 'detectConflicts']);
    });

    // Chef Departement Routes
    Route::prefix('chef-departement')->group(function () {
        Route::get('dashboard', [ChefDepartementController::class, 'dashboard']);
        Route::post('validate', [ChefDepartementController::class, 'validateSchedule']);
    });

    Route::post('/schedule/generate', [\App\Http\Controllers\AutoScheduleController::class, 'generate']);
    Route::get('/schedule/status/{jobId}', [\App\Http\Controllers\AutoScheduleController::class, 'status']);
    Route::get('/stats', [\App\Http\Controllers\StatsController::class, 'index']);

    Route::apiResource('examens', ExamenController::class);
});
