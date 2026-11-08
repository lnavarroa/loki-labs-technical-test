<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoEquipo;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Http\Resources\EquipoResource;
use App\Models\Equipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class EquipoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'estado' => ['sometimes', Rule::enum(EstadoEquipo::class)],
            'tipo' => ['sometimes', 'string', 'max:50'],
        ]);

        $equipos = Equipo::query()
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->orderBy('codigo_interno')
            ->paginate(15)
            ->withQueryString();

        return EquipoResource::collection($equipos);
    }

    public function store(StoreEquipoRequest $request): JsonResponse
    {
        // refresh() trae los valores por defecto que puso la base de datos (estado, horómetro)
        $equipo = Equipo::create($request->validated())->refresh();

        return (new EquipoResource($equipo))->response()->setStatusCode(201);
    }

    public function show(Equipo $equipo): EquipoResource
    {
        return new EquipoResource($equipo);
    }

    public function update(UpdateEquipoRequest $request, Equipo $equipo): EquipoResource
    {
        $equipo->update($request->validated());

        return new EquipoResource($equipo);
    }

    public function destroy(Equipo $equipo): JsonResponse
    {
        // El historial de gasto es dato contable: no se borra en silencio.
        // La clave foránea (RESTRICT) es la segunda barrera si algo se cuela.
        if ($equipo->cargasCombustible()->exists() || $equipo->mantenciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un equipo con cargas de combustible o mantenciones registradas.',
            ], 409);
        }

        $equipo->delete();

        return response()->json(null, 204);
    }
}