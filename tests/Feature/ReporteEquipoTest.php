<?php

namespace Tests\Feature;

use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEquipos;
use Tests\TestCase;

class ReporteEquipoTest extends TestCase
{
    use RefreshDatabase, CreaEquipos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autenticar();
    }

    private function crearCarga(Equipo $equipo, string $fechaHora, float $litros, int $monto, float $horometro): void
    {
        $equipo->cargasCombustible()->create([
            'fecha_hora' => $fechaHora,
            'litros' => $litros,
            'monto_clp' => $monto,
            'horometro' => $horometro,
        ]);
    }

    private function crearMantencion(Equipo $equipo, string $fecha, int $costo): void
    {
        $equipo->mantenciones()->create([
            'tipo' => 'preventiva',
            'fecha' => $fecha,
            'horometro' => 0,
            'costo_clp' => $costo,
        ]);
    }

    private function reporte(Equipo $equipo, string $desde = '2026-11-01', string $hasta = '2026-11-30')
    {
        return $this->getJson("/api/equipos/{$equipo->id}/reporte?desde={$desde}&hasta={$hasta}");
    }

    public function test_calcula_totales_y_rendimiento_por_tanque_lleno(): void
    {
        $equipo = $this->crearEquipo();
        $this->crearCarga($equipo, '2026-11-08 10:30:00', 120.5, 145000, 3050);
        $this->crearCarga($equipo, '2026-11-08 12:00:00', 80, 96000, 3100);
        $this->crearCarga($equipo, '2026-11-08 14:00:00', 90, 108000, 3150);
        $this->crearMantencion($equipo, '2026-11-08', 250000);
        $this->crearMantencion($equipo, '2026-10-15', 999999); // fuera del rango

        $datos = $this->reporte($equipo)->assertOk()->json('data');

        $this->assertEquals(3, $datos['cargas_combustible']);
        $this->assertEquals(290.5, $datos['litros_totales']);
        $this->assertEquals(349000, $datos['gasto_combustible_clp']);
        $this->assertEquals(250000, $datos['gasto_mantenciones_clp']);
        $this->assertEquals(100, $datos['horas_de_uso']);
        // Se excluyen los 120.5 L de la primera carga: (290.5 - 120.5) / 100
        $this->assertEquals(170, $datos['litros_considerados_rendimiento']);
        $this->assertEquals(1.7, $datos['rendimiento_l_por_h']);
    }

    public function test_rango_sin_datos_devuelve_ceros_y_rendimiento_nulo(): void
    {
        $equipo = $this->crearEquipo();

        $datos = $this->reporte($equipo)->assertOk()->json('data');

        $this->assertEquals(0, $datos['cargas_combustible']);
        $this->assertEquals(0, $datos['litros_totales']);
        $this->assertEquals(0, $datos['gasto_combustible_clp']);
        $this->assertEquals(0, $datos['gasto_mantenciones_clp']);
        $this->assertNull($datos['rendimiento_l_por_h']);
    }

    public function test_con_una_sola_carga_el_rendimiento_es_nulo(): void
    {
        $equipo = $this->crearEquipo();
        $this->crearCarga($equipo, '2026-11-08 10:00:00', 50, 60000, 100);

        $datos = $this->reporte($equipo)->assertOk()->json('data');

        $this->assertEquals(50, $datos['litros_totales']);
        $this->assertNull($datos['horas_de_uso']);
        $this->assertNull($datos['rendimiento_l_por_h']);
    }

    public function test_los_extremos_del_rango_son_inclusivos_y_lo_de_afuera_se_excluye(): void
    {
        $equipo = $this->crearEquipo();
        $this->crearCarga($equipo, '2026-10-31 23:59:59', 10, 1000, 10); // fuera
        $this->crearCarga($equipo, '2026-11-01 00:00:00', 20, 2000, 20); // dentro (inicio)
        $this->crearCarga($equipo, '2026-11-30 23:59:59', 30, 3000, 30); // dentro (fin)
        $this->crearCarga($equipo, '2026-12-01 00:00:00', 40, 4000, 40); // fuera

        $datos = $this->reporte($equipo)->assertOk()->json('data');

        $this->assertEquals(2, $datos['cargas_combustible']);
        $this->assertEquals(50, $datos['litros_totales']);
        $this->assertEquals(5000, $datos['gasto_combustible_clp']);
    }

    public function test_solo_cuenta_los_datos_del_equipo_consultado(): void
    {
        $equipo = $this->crearEquipo();
        $otro = $this->crearEquipo();
        $this->crearCarga($equipo, '2026-11-08 10:00:00', 10, 1000, 10);
        $this->crearCarga($otro, '2026-11-08 10:00:00', 999, 999000, 10);

        $datos = $this->reporte($equipo)->assertOk()->json('data');

        $this->assertEquals(10, $datos['litros_totales']);
    }

    public function test_valida_las_fechas(): void
    {
        $equipo = $this->crearEquipo();

        $this->reporte($equipo, '2026-11-30', '2026-11-01')
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');

        $this->getJson("/api/equipos/{$equipo->id}/reporte")
            ->assertUnprocessable()->assertJsonValidationErrors(['desde', 'hasta']);
    }

    public function test_equipo_inexistente_responde_404(): void
    {
        $this->getJson('/api/equipos/999/reporte?desde=2026-11-01&hasta=2026-11-30')->assertNotFound();
    }
}