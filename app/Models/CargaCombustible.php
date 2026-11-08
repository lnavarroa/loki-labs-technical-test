<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargaCombustible extends Model
{
    // Laravel pluraliza en inglés, así que declaramos la tabla a mano
    protected $table = 'cargas_combustible';

    protected $fillable = [
        'equipo_id',
        'fecha_hora',
        'litros',
        'monto_clp',
        'horometro',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'litros' => 'decimal:2',
            'monto_clp' => 'integer',
            'horometro' => 'decimal:1',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}