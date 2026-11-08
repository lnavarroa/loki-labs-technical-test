<?php

namespace App\Models;

use App\Enums\TipoMantencion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mantencion extends Model
{
    // Laravel pluraliza en inglés, así que declaramos la tabla a mano
    protected $table = 'mantenciones';

    protected $fillable = [
        'equipo_id',
        'tipo',
        'fecha',
        'horometro',
        'costo_clp',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMantencion::class,
            'fecha' => 'date',
            'horometro' => 'decimal:1',
            'costo_clp' => 'integer',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}