<?php

namespace Tests\Feature;

use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEquipos;
use Tests\TestCase;

class MantencionPreventivaTest extends TestCase
{
    use RefreshDatabase, CreaEquipos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autenticar();
    }

    private function crearMantencion(Equipo $equipo, string $tipo, float $horometro): void
    {
        $equipo->mantenciones()->create([
            'tipo' => $tipo,
            'fecha' => '2026-11-08',
            'horometro' => $horometro,
            'costo_clp' => 100000,
        ]);
    }

    private function codigosPendientes(): array
    {
        return collect($this->getJson('/api/equipos/mantencion-preventiva')->assertOk()->json('data'))
            ->pluck('codigo_interno')
            ->all();
    }

    public function test_sin_preventiva_se_mide_desde_cero_y_el_limite_es_estricto(): void
    {
        $this->crearEquipo(['codigo_interno' => 'A', 'horometro_actual' => 251]);
        $this->crearEquipo(['codigo_interno' => 'B', 'horometro_actual' => 250]);
        $this->crearEquipo(['codigo_interno' => 'C', 'horometro_actual' => 100]);

        $this->assertSame(['A'], $this->codigosPendientes());
    }

    public function test_se_mide_desde_la_ultima_preventiva(): void
    {
        $reciente = $this->crearEquipo(['codigo_interno' => 'RECIENTE', 'horometro_actual' => 1000]);
        $this->crearMantencion($reciente, 'preventiva', 800); // 200 h: no

        $atrasado = $this->crearEquipo(['codigo_interno' => 'ATRASADO', 'horometro_actual' => 1000]);
        $this->crearMantencion($atrasado, 'preventiva', 600); // 400 h: sí

        $this->assertSame(['ATRASADO'], $this->codigosPendientes());
    }

    public function test_la_correctiva_no_reinicia_el_conteo_pero_la_preventiva_si(): void
    {
        $equipo = $this->crearEquipo(['codigo_interno' => 'EQ', 'horometro_actual' => 1000]);

        $this->crearMantencion($equipo, 'correctiva', 1000);
        $this->assertSame(['EQ'], $this->codigosPendientes());

        $this->crearMantencion($equipo, 'preventiva', 1000);
        $this->assertSame([], $this->codigosPendientes());
    }
}