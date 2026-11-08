<?php

namespace App\Http\Requests;

use App\Enums\EstadoEquipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo controla auth:sanctum en las rutas
    }

    protected function prepareForValidation(): void
    {
        foreach (['codigo_interno', 'patente'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => strtoupper(trim($this->input($campo)))]);
            }
        }
    }

    public function rules(): array
    {
        $equipo = $this->route('equipo');

        // 'sometimes': solo se valida lo que viene en la petición,
        // así PUT y PATCH aceptan envíos parciales
        return [
            'codigo_interno' => ['sometimes', 'required', 'string', 'max:50',
                Rule::unique('equipos', 'codigo_interno')->ignore($equipo)],
            'tipo' => ['sometimes', 'required', 'string', 'max:50'],
            'patente' => ['sometimes', 'nullable', 'string', 'max:10',
                Rule::unique('equipos', 'patente')->ignore($equipo)],
            'marca' => ['sometimes', 'required', 'string', 'max:100'],
            'modelo' => ['sometimes', 'required', 'string', 'max:100'],
            'estado' => ['sometimes', 'required', Rule::enum(EstadoEquipo::class)],
            // El horómetro solo avanza registrando cargas o mantenciones
            'horometro_actual' => ['prohibited'],
        ];
    }
}