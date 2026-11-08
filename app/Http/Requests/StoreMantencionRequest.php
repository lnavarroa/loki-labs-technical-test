<?php

namespace App\Http\Requests;

use App\Enums\TipoMantencion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMantencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo controla auth:sanctum en las rutas
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoMantencion::class)],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'horometro' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,1'],
            'costo_clp' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}