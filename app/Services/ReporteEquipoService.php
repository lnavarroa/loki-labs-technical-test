<?php

namespace App\Services;

use App\Models\CargaCombustible;
use App\Models\Equipo;
use App\Models\Mantencion;
use Carbon\Carbon;

class ReporteEquipoService
{
    /**
     * Reporte de un equipo entre dos fechas (ambas inclusivas, formato Y-m-d).
     *
     * Rendimiento (método de tanque lleno): cada carga repone lo consumido desde la
     * anterior, así que los litros de la PRIMERA carga del rango se excluyen del
     * cálculo (repusieron combustible gastado antes del rango).
     *   horas = horómetro de la última carga - horómetro de la primera
     *   rendimiento = (litros totales - litros de la primera carga) / horas
     */
    public function generar(Equipo $equipo, string $desde, string $hasta): array
    {
        $cargas = CargaCombustible::query()
            ->where('equipo_id', $equipo->id)
            ->whereBetween('fecha_hora', [
                Carbon::parse($desde)->startOfDay(),
                Carbon::parse($hasta)->endOfDay(),
            ]);

        $totales = (clone $cargas)
            ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(litros), 0) AS litros, COALESCE(SUM(monto_clp), 0) AS monto')
            ->first();

        $gastoMantenciones = Mantencion::query()
            ->where('equipo_id', $equipo->id)
            ->whereBetween('fecha', [$desde, $hasta])
            ->sum('costo_clp');

        $horas = null;
        $litrosRendimiento = null;
        $rendimiento = null;

        // Con menos de 2 cargas no hay un tramo completo que medir
        if ($totales->cantidad >= 2) {
            // Se ordena por horómetro: es no decreciente por la regla de validación
            $primera = (clone $cargas)->orderBy('horometro')->orderBy('id')->first(['litros', 'horometro']);
            $ultima = (clone $cargas)->orderByDesc('horometro')->orderByDesc('id')->first(['horometro']);

            $horas = bcsub((string) $ultima->horometro, (string) $primera->horometro, 1);
            $litrosRendimiento = bcsub((string) $totales->litros, (string) $primera->litros, 2);

            // Con 0 horas de diferencia no se puede dividir: rendimiento queda en null
            if (bccomp($horas, '0', 1) > 0) {
                $rendimiento = round((float) $litrosRendimiento / (float) $horas, 2);
            }
        }

        return [
            'equipo' => [
                'id' => $equipo->id,
                'codigo_interno' => $equipo->codigo_interno,
            ],
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
            'cargas_combustible' => (int) $totales->cantidad,
            'litros_totales' => (float) $totales->litros,
            'gasto_combustible_clp' => (int) $totales->monto,
            'gasto_mantenciones_clp' => (int) $gastoMantenciones,
            'horas_de_uso' => $horas !== null ? (float) $horas : null,
            'litros_considerados_rendimiento' => $litrosRendimiento !== null ? (float) $litrosRendimiento : null,
            'rendimiento_l_por_h' => $rendimiento,
        ];
    }
}