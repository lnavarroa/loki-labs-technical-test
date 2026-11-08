<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Mantencion */
class MantencionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipo_id' => $this->equipo_id,
            'tipo' => $this->tipo->value,
            'fecha' => $this->fecha->toDateString(),
            'horometro' => (float) $this->horometro,
            'costo_clp' => $this->costo_clp,
            'descripcion' => $this->descripcion,
            'created_at' => $this->created_at,
        ];
    }
}