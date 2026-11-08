<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Equipo */
class EquipoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo_interno' => $this->codigo_interno,
            'tipo' => $this->tipo,
            'patente' => $this->patente,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'horometro_actual' => (float) $this->horometro_actual,
            'estado' => $this->estado->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}