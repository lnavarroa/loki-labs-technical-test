<?php

namespace App\Http\Requests;

use App\Enums\EstadoEquipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo controla auth:sanctum en las rutas
    }

    protected function prepareForValidation(): void
    {
        // Normaliza para que "abcd12" y "ABCD12" cuenten como la misma patente
        foreach (['codigo_interno', 'patente'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => strtoupper(trim($this->input($campo)))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'codigo_interno' => ['required', 'string', 'max:50', 'unique:equipos,codigo_interno'],
            'tipo' => ['required', 'string', 'max:50'],
            'patente' => ['nullable', 'string', 'max:10', 'unique:equipos,patente'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            // Un equipo existente puede llegar con horas acumuladas
            'horometro_actual' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,1'],
            'estado' => ['nullable', Rule::enum(EstadoEquipo::class)],
        ];
    }
}