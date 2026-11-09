<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso ya lo controla auth:sanctum en las rutas
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }
}