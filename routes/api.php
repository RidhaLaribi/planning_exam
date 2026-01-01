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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

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
    
    Route::apiResource('examens', ExamenController::class);
});
