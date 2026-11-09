<?php

namespace Tests\Concerns;

use App\Models\Equipo;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreaEquipos
{
    protected function autenticar(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    protected function crearEquipo(array $atributos = []): Equipo
    {
        static $contador = 0;
        $contador++;

        return Equipo::create(array_merge([
            'codigo_interno' => "EQ-{$contador}",
            'tipo' => 'camión',
            'marca' => 'Volvo',
            'modelo' => 'FH16',
            'horometro_actual' => 0,
        ], $atributos))->refresh();
    }
}