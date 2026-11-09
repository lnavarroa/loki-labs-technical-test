<?php

namespace Tests\Feature;

use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreaEquipos;
use Tests\TestCase;

class HorometroTest extends TestCase
{
    use RefreshDatabase, CreaEquipos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->autenticar();
    }

    private function registrarCarga(Equipo $equipo, float $horometro): TestResponse
    {
        return $this->postJson("/api/equipos/{$equipo->id}/cargas-combustible", [
            'fecha_hora' => '2026-11-08 10:00:00',
            'litros' => 50,
            'monto_clp' => 60000,
            'horometro' => $horometro,
        ]);
    }

    private function registrarMantencion(Equipo $equipo, float $horometro): TestResponse
    {
        return $this->postJson("/api/equipos/{$equipo->id}/mantenciones", [
            'tipo' => 'preventiva',
            'fecha' => '2026-11-08',
            'horometro' => $horometro,
            'costo_clp' => 250000,
        ]);
    }

    public function test_carga_con_horometro_mayor_actualiza_el_horometro_del_equipo(): void
    {
        $equipo = $this->crearEquipo(['horometro_actual' => 100]);

        $this->registrarCarga($equipo, 150.5)->assertCreated();

        $this->assertSame('150.5', $equipo->fresh()->horometro_actual);
        $this->assertDatabaseCount('cargas_combustible', 1);
    }

    public function test_carga_con_horometro_igual_al_actual_se_acepta_sin_cambiarlo(): void
    {
        $equipo = $this->crearEquipo(['horometro_actual' => 100]);

        $this->registrarCarga($equipo, 100)->assertCreated();

        $this->assertSame('100.0', $equipo->fresh()->horometro_actual);
    }

    public function test_carga_con_horometro_menor_se_rechaza_y_no_guarda_nada(): void
    {
        $equipo = $this->crearEquipo(['horometro_actual' => 100]);

        $this->registrarCarga($equipo, 99.9)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('horometro');

        $this->assertSame('100.0', $equipo->fresh()->horometro_actual);
        $this->assertDatabaseCount('cargas_combustible', 0);
    }

    public function test_mantencion_con_horometro_mayor_actualiza_el_horometro_del_equipo(): void
    {
        $equipo = $this->crearEquipo(['horometro_actual' => 100]);

        $this->registrarMantencion($equipo, 120)->assertCreated();

        $this->assertSame('120.0', $equipo->fresh()->horometro_actual);
    }

    public function test_mantencion_con_horometro_menor_al_de_una_carga_previa_se_rechaza(): void
    {
        $equipo = $this->crearEquipo(['horometro_actual' => 100]);
        $this->registrarCarga($equipo, 200)->assertCreated();

        $this->registrarMantencion($equipo, 150)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('horometro');

        $this->assertSame('200.0', $equipo->fresh()->horometro_actual);
        $this->assertDatabaseCount('mantenciones', 0);
    }

    public function test_carga_con_datos_invalidos_se_rechaza(): void
    {
        $equipo = $this->crearEquipo();

        $this->postJson("/api/equipos/{$equipo->id}/cargas-combustible", [
            'fecha_hora' => '2026-11-08 10:00:00',
            'litros' => 0,
            'monto_clp' => -5,
            'horometro' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors(['litros', 'monto_clp']);
    }

    public function test_registrar_en_un_equipo_inexistente_responde_404(): void
    {
        $this->postJson('/api/equipos/999/cargas-combustible', [
            'fecha_hora' => '2026-11-08 10:00:00',
            'litros' => 50,
            'monto_clp' => 60000,
            'horometro' => 10,
        ])->assertNotFound();
    }

    public function test_sin_token_se_responde_401(): void
    {
        $equipo = $this->crearEquipo();
        $this->app['auth']->forgetGuards();

        $this->postJson("/api/equipos/{$equipo->id}/cargas-combustible", [])->assertUnauthorized();
    }

    public function test_no_se_puede_eliminar_un_equipo_con_historial(): void
    {
        $equipo = $this->crearEquipo();
        $this->registrarCarga($equipo, 10)->assertCreated();

        $this->deleteJson("/api/equipos/{$equipo->id}")->assertStatus(409);

        $this->assertDatabaseHas('equipos', ['id' => $equipo->id]);
    }
}