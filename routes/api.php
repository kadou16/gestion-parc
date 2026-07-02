<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\ConducteurController;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AlerteController;
use App\Http\Controllers\EvaluationConducteurController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/me',      [AuthController::class, 'updateMe']);

    Route::middleware('permission:voir vehicules')->group(function () {
        Route::get('vehicules', [VehiculeController::class, 'index']);
        Route::get('vehicules/{vehicule}', [VehiculeController::class, 'show']);
    });
    Route::post('vehicules', [VehiculeController::class, 'store'])->middleware('permission:ajouter vehicule');
    Route::match(['put', 'patch'], 'vehicules/{vehicule}', [VehiculeController::class, 'update'])->middleware('permission:modifier vehicule');
    Route::delete('vehicules/{vehicule}', [VehiculeController::class, 'destroy'])->middleware('permission:supprimer vehicule');

    Route::middleware('permission:voir rapports')->group(function () {
        Route::get('conducteurs', [ConducteurController::class, 'index']);
        Route::get('affectations', [AffectationController::class, 'index']);
        Route::get('maintenances', [MaintenanceController::class, 'index']);
        Route::get('documents', [DocumentController::class, 'index']);
        Route::get('documents/{id}/visualiser', [DocumentController::class, 'visualiser']);
        Route::get('documents/{id}/telecharger', [DocumentController::class, 'telecharger']);
    });

    Route::middleware(['role:Admin', 'permission:gerer utilisateurs'])->group(function () {
        Route::apiResource('administrateurs', AdministrateurController::class);
        Route::apiResource('conducteurs',  ConducteurController::class)->except(['index']);
    });

    Route::middleware('role:Admin')->group(function () {
        Route::apiResource('affectations', AffectationController::class)->except(['index']);
        Route::apiResource('maintenances', MaintenanceController::class)->except(['index']);
        Route::apiResource('documents',    DocumentController::class)->except(['index']);
        Route::apiResource('alertes',      AlerteController::class);
        Route::apiResource('evaluations', EvaluationConducteurController::class);
    });
});
