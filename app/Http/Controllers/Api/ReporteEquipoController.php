<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteEquipoRequest;
use App\Models\Equipo;
use App\Services\ReporteEquipoService;
use Illuminate\Http\JsonResponse;

class ReporteEquipoController extends Controller
{
    public function __invoke(ReporteEquipoRequest $request, Equipo $equipo, ReporteEquipoService $reporte): JsonResponse
    {
        $datos = $request->validated();

        return response()->json([
            'data' => $reporte->generar($equipo, $datos['desde'], $datos['hasta']),
        ]);
    }
}