<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMantencionRequest;
use App\Http\Resources\MantencionResource;
use App\Models\Equipo;
use App\Services\HorometroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MantencionController extends Controller
{
    public function index(Equipo $equipo): AnonymousResourceCollection
    {
        return MantencionResource::collection(
            $equipo->mantenciones()->orderByDesc('fecha')->orderByDesc('id')->paginate(15)
        );
    }

    public function store(StoreMantencionRequest $request, Equipo $equipo, HorometroService $horometro): JsonResponse
    {
        $datos = $request->validated();

        $mantencion = $horometro->registrar(
            $equipo,
            $datos['horometro'],
            fn (Equipo $bloqueado) => $bloqueado->mantenciones()->create($datos),
        );

        return (new MantencionResource($mantencion))->response()->setStatusCode(201);
    }
}