<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CargaCombustible */
class CargaCombustibleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'fecha_hora' => $this->fecha_hora->toIso8601String(),
            'litros' => (float) $this->litros,
            'monto_clp' => $this->monto_clp,
            'horometro' => (float) $this->horometro,
            'created_at' => $this->created_at,
        ];
    }
}