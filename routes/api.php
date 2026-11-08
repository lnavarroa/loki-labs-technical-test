<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EquipoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ojo: cuando agregue /equipos/mantencion-preventiva, debe declararse
    // ANTES de este apiResource, o {equipo} la interpretará como un id.
    Route::apiResource('equipos', EquipoController::class);
});