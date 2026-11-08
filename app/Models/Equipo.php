<?php

namespace App\Models;

use App\Enums\EstadoEquipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    protected $fillable = [
        'codigo_interno',
        'tipo',
        'patente',
        'marca',
        'modelo',
        'horometro_actual',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'horometro_actual' => 'decimal:1',
            'estado' => EstadoEquipo::class,
        ];
    }

    public function cargasCombustible(): HasMany
    {
        return $this->hasMany(CargaCombustible::class);
    }

    public function mantenciones(): HasMany
    {
        return $this->hasMany(Mantencion::class);
    }
}