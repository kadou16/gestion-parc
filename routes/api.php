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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/me',      [AuthController::class, 'updateMe']);

    Route::middleware('admin')->group(function () {
        Route::get('documents/{id}/visualiser', [DocumentController::class, 'visualiser']);
        Route::get('documents/{id}/telecharger', [DocumentController::class, 'telecharger']);
        Route::apiResource('vehicules',    VehiculeController::class);
        Route::apiResource('administrateurs', AdministrateurController::class);
        Route::apiResource('conducteurs',  ConducteurController::class);
        Route::apiResource('affectations', AffectationController::class);
        Route::apiResource('maintenances', MaintenanceController::class);
        Route::apiResource('documents',    DocumentController::class);
        Route::apiResource('alertes',      AlerteController::class);
        Route::apiResource('evaluations', EvaluationConducteurController::class);
    });
});