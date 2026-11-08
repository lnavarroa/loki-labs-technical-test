<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCargaCombustibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo controla auth:sanctum en las rutas
    }

    public function rules(): array
    {
        return [
            'fecha_hora' => ['required', 'date'],
            'litros' => ['required', 'numeric', 'gt:0', 'max:999999.99', 'decimal:0,2'],
            'monto_clp' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'horometro' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,1'],
        ];
    }
}