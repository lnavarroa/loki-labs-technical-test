<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCargaCombustibleRequest;
use App\Http\Resources\CargaCombustibleResource;
use App\Models\Equipo;
use App\Services\HorometroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CargaCombustibleController extends Controller
{
    public function index(Equipo $equipo): AnonymousResourceCollection
    {
        return CargaCombustibleResource::collection(
            $equipo->cargasCombustible()->orderByDesc('fecha_hora')->orderByDesc('id')->paginate(15)
        );
    }

    public function store(StoreCargaCombustibleRequest $request, Equipo $equipo, HorometroService $horometro): JsonResponse
    {
        $datos = $request->validated();

        $carga = $horometro->registrar(
            $equipo,
            $datos['horometro'],
            fn (Equipo $bloqueado) => $bloqueado->cargasCombustible()->create($datos),
        );

        return (new CargaCombustibleResource($carga))->response()->setStatusCode(201);
    }
}