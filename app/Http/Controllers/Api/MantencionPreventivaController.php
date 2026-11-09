<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MantencionPreventivaService;
use Illuminate\Http\JsonResponse;

class MantencionPreventivaController extends Controller
{
    public function __invoke(MantencionPreventivaService $servicio): JsonResponse
    {
        return response()->json([
            'horas_limite' => MantencionPreventivaService::HORAS_LIMITE,
            'data' => $servicio->pendientes()->values(),
        ]);
    }
}