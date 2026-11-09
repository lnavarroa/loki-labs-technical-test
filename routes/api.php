<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CargaCombustibleController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\MantencionController;
use App\Http\Controllers\Api\MantencionPreventivaController;
use App\Http\Controllers\Api\ReporteEquipoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Debe declararse ANTES del apiResource de equipos: si no, {equipo}
    // interpretaría "mantencion-preventiva" como un id.
    Route::get('equipos/mantencion-preventiva', MantencionPreventivaController::class);
    
    Route::apiResource('equipos', EquipoController::class);

    Route::apiResource('equipos.cargas-combustible', CargaCombustibleController::class)
        ->only(['index', 'store']);
    Route::apiResource('equipos.mantenciones', MantencionController::class)
        ->only(['index', 'store']);

    Route::get('equipos/{equipo}/reporte', ReporteEquipoController::class);
});