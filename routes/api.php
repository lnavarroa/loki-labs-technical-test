<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CargaCombustibleController;
use App\Http\Controllers\Api\EquipoController;
use App\Http\Controllers\Api\MantencionController;
use App\Http\Controllers\Api\ReporteEquipoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ojo: cuando agregue /equipos/mantencion-preventiva, debe declararse
    // ANTES de este apiResource, o {equipo} la interpretará como un id.
    Route::apiResource('equipos', EquipoController::class);

    Route::apiResource('equipos.cargas-combustible', CargaCombustibleController::class)
        ->only(['index', 'store']);
    Route::apiResource('equipos.mantenciones', MantencionController::class)
        ->only(['index', 'store']);

    Route::get('equipos/{equipo}/reporte', ReporteEquipoController::class);
});