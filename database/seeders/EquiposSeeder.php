<?php

namespace Database\Seeders;

use App\Models\Equipo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EquiposSeeder extends Seeder
{
    private const PRECIO_LITRO_CLP = 1150;

    public function run(): void
    {
        foreach ($this->equipos() as $datos) {
            // Idempotente: correr db:seed dos veces no duplica el historial
            if (Equipo::where('codigo_interno', $datos['codigo_interno'])->exists()) {
                continue;
            }

            $this->crearEquipoConHistorial($datos);
        }
    }

    /**
     * Datos deterministas (sin azar), para que los ejemplos del README
     * den siempre los mismos resultados.
     *
     * - cargas: una por semana desde el 2026-08-03, avanzando "horas_entre_cargas" cada vez
     * - mantenciones: [índice de la carga => tipo]; se registran el mismo día y con el mismo horómetro
     */
    private function equipos(): array
    {
        return [
            [   // Al día: última preventiva hace 160 h
                'codigo_interno' => 'CAM-01', 'tipo' => 'camión', 'patente' => 'ABCD12',
                'marca' => 'Volvo', 'modelo' => 'FH16', 'estado' => 'operativo',
                'horometro_inicial' => 12000, 'cargas' => 14, 'horas_entre_cargas' => 40, 'litros_por_hora' => 14,
                'mantenciones' => [4 => 'preventiva', 9 => 'preventiva'],
            ],
            [   // Atrasado: 495 h desde su única preventiva
                'codigo_interno' => 'CAM-02', 'tipo' => 'camión', 'patente' => 'EFGH34',
                'marca' => 'Mercedes-Benz', 'modelo' => 'Actros', 'estado' => 'operativo',
                'horometro_inicial' => 8000, 'cargas' => 14, 'horas_entre_cargas' => 45, 'litros_por_hora' => 13,
                'mantenciones' => [2 => 'preventiva'],
            ],
            [   // Atrasado (300 h) y en mantención; la correctiva no reinicia el conteo
                'codigo_interno' => 'CAM-03', 'tipo' => 'camión', 'patente' => 'IJKL56',
                'marca' => 'Scania', 'modelo' => 'R450', 'estado' => 'en_mantencion',
                'horometro_inicial' => 20000, 'cargas' => 10, 'horas_entre_cargas' => 50, 'litros_por_hora' => 15,
                'mantenciones' => [3 => 'preventiva', 7 => 'correctiva'],
            ],
            [   // Equipo nuevo: 240 h y nunca tuvo preventiva (justo bajo el límite de 250)
                'codigo_interno' => 'EXC-01', 'tipo' => 'excavadora', 'patente' => null,
                'marca' => 'Caterpillar', 'modelo' => '320', 'estado' => 'operativo',
                'horometro_inicial' => 0, 'cargas' => 12, 'horas_entre_cargas' => 20, 'litros_por_hora' => 18,
                'mantenciones' => [],
            ],
            [   // Fuera de servicio
                'codigo_interno' => 'EXC-02', 'tipo' => 'excavadora', 'patente' => null,
                'marca' => 'Komatsu', 'modelo' => 'PC200', 'estado' => 'fuera_de_servicio',
                'horometro_inicial' => 9000, 'cargas' => 8, 'horas_entre_cargas' => 35, 'litros_por_hora' => 17,
                'mantenciones' => [1 => 'preventiva', 6 => 'correctiva'],
            ],
            [   // Generador, sin patente
                'codigo_interno' => 'GEN-01', 'tipo' => 'generador', 'patente' => null,
                'marca' => 'Cummins', 'modelo' => 'C100', 'estado' => 'operativo',
                'horometro_inicial' => 2000, 'cargas' => 10, 'horas_entre_cargas' => 24, 'litros_por_hora' => 9,
                'mantenciones' => [5 => 'preventiva'],
            ],
        ];
    }

    private function crearEquipoConHistorial(array $d): void
    {
        $equipo = Equipo::create([
            'codigo_interno' => $d['codigo_interno'],
            'tipo' => $d['tipo'],
            'patente' => $d['patente'],
            'marca' => $d['marca'],
            'modelo' => $d['modelo'],
            'estado' => $d['estado'],
            'horometro_actual' => $d['horometro_inicial'],
        ]);

        $horometro = $d['horometro_inicial'];
        $inicio = Carbon::parse('2026-08-03 08:00:00');

        for ($i = 0; $i < $d['cargas']; $i++) {
            $horometro += $d['horas_entre_cargas'];
            $fecha = $inicio->copy()->addWeeks($i);
            $litros = round($d['horas_entre_cargas'] * $d['litros_por_hora'], 2);

            $equipo->cargasCombustible()->create([
                'fecha_hora' => $fecha,
                'litros' => $litros,
                'monto_clp' => (int) round($litros * self::PRECIO_LITRO_CLP),
                'horometro' => $horometro,
            ]);

            if (isset($d['mantenciones'][$i])) {
                $tipo = $d['mantenciones'][$i];

                $equipo->mantenciones()->create([
                    'tipo' => $tipo,
                    'fecha' => $fecha->toDateString(),
                    'horometro' => $horometro,
                    'costo_clp' => $tipo === 'preventiva' ? 250000 : 180000,
                    'descripcion' => $tipo === 'preventiva'
                        ? 'Mantención preventiva: aceite y filtros'
                        : 'Reparación correctiva',
                ]);
            }
        }

        // El horómetro del equipo queda en el último valor registrado
        $equipo->update(['horometro_actual' => $horometro]);
    }
}