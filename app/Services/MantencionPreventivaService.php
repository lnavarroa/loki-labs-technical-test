<?php

namespace App\Services;

use App\Enums\TipoMantencion;
use App\Models\Equipo;
use App\Models\Mantencion;
use Illuminate\Support\Collection;

class MantencionPreventivaService
{
    // Un equipo requiere preventiva si supera estas horas desde la última
    public const HORAS_LIMITE = 250;

    /**
     * Equipos con MÁS de 250 horas de horómetro desde su última mantención
     * preventiva (o desde cero, si nunca tuvieron una). Las correctivas no cuentan.
     */
    public function pendientes(): Collection
    {
        // Horómetro de la última preventiva de cada equipo. Usa el índice
        // (equipo_id, tipo, horometro): es una búsqueda directa, sin recorrer la tabla.
        $ultimaPreventiva = Mantencion::query()
            ->selectRaw('MAX(horometro)')
            ->whereColumn('mantenciones.equipo_id', 'equipos.id')
            ->where('tipo', TipoMantencion::Preventiva->value);

        $base = Equipo::query()
            ->select('equipos.*')
            ->selectSub($ultimaPreventiva, 'horometro_ultima_preventiva');

        return Equipo::query()
            ->fromSub($base, 'equipos')
            ->whereRaw('horometro_actual - COALESCE(horometro_ultima_preventiva, 0) > ?', [self::HORAS_LIMITE])
            ->orderByRaw('(horometro_actual - COALESCE(horometro_ultima_preventiva, 0)) DESC')
            ->get()
            ->map(function (Equipo $equipo) {
                $ultima = $equipo->horometro_ultima_preventiva; // null si nunca tuvo una

                return [
                    'id' => $equipo->id,
                    'codigo_interno' => $equipo->codigo_interno,
                    'tipo' => $equipo->tipo,
                    'patente' => $equipo->patente,
                    'estado' => $equipo->estado->value,
                    'horometro_actual' => (float) $equipo->horometro_actual,
                    'horometro_ultima_preventiva' => $ultima !== null ? (float) $ultima : null,
                    'horas_desde_ultima_preventiva' => (float) bcsub(
                        (string) $equipo->horometro_actual,
                        (string) ($ultima ?? 0),
                        1,
                    ),
                ];
            });
    }
}