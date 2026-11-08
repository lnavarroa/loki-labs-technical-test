<?php

namespace App\Services;

use App\Models\Equipo;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HorometroService
{
    /**
     * Crea un registro (carga o mantención) y mantiene al día el horómetro del equipo.
     * $crear recibe el equipo ya bloqueado y devuelve el registro creado.
     */
    public function registrar(Equipo $equipo, string|int|float $horometro, Closure $crear): Model
    {
        return DB::transaction(function () use ($equipo, $horometro, $crear) {
            // Bloquea la fila del equipo hasta terminar la transacción: dos registros
            // simultáneos se procesan uno tras otro y siempre ven el horómetro actualizado
            $equipo = Equipo::lockForUpdate()->findOrFail($equipo->id);

            $nuevo = (string) $horometro;
            $actual = (string) $equipo->horometro_actual;

            // bccomp compara decimales exactos (1 decimal), sin errores de punto flotante
            if (bccomp($nuevo, $actual, 1) < 0) {
                throw ValidationException::withMessages([
                    'horometro' => "El horómetro ($nuevo) no puede ser menor al último registrado ($actual).",
                ]);
            }

            $registro = $crear($equipo);

            if (bccomp($nuevo, $actual, 1) > 0) {
                $equipo->update(['horometro_actual' => $nuevo]);
            }

            return $registro;
        });
    }
}