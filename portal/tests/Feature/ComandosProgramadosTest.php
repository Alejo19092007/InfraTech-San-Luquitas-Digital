<?php

namespace Tests\Feature;

use App\Models\CodigoVinculacion;
use App\Models\MensajeWhatsapp;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class ComandosProgramadosTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-12 06:30', 'America/Bogota'));
        $this->crearEscuela();
    }

    public function test_recordatorios_avisan_al_estudiante_y_a_su_acudiente_de_lo_que_vence_hoy_o_manana(): void
    {
        Queue::fake();
        $this->tarea($this->ana, 'Hoy', 'pendiente', 0);
        $this->tarea($this->ana, 'Mañana', 'pendiente', 1);
        $this->tarea($this->ana, 'Lejana', 'pendiente', 5);
        $this->tarea($this->ana, 'Entregada', 'entregada', 0);
        $this->tarea($this->mateo, 'SinNumero', 'pendiente', 0);   // sin WhatsApp: no hay a quién avisar

        $this->artisan('tareas:recordatorios')->assertSuccessful();

        $paraAna = MensajeWhatsapp::where('telefono', $this->ana->telefono_whatsapp)->where('direccion', 'saliente')->first();
        $paraLuz = MensajeWhatsapp::where('telefono', $this->acudiente->telefono_whatsapp)->where('direccion', 'saliente')->first();

        $this->assertStringContainsString('Hoy (hoy)', $paraAna->texto());
        $this->assertStringContainsString('Mañana (mañana)', $paraAna->texto());
        $this->assertStringNotContainsString('Lejana', $paraAna->texto());
        $this->assertStringNotContainsString('Entregada', $paraAna->texto());
        $this->assertStringContainsString('Ana: Hoy', $paraLuz->texto());   // el acudiente ve de quién es
        $this->assertStringNotContainsString('SinNumero', $paraLuz->texto());
    }

    public function test_recordatorios_en_modo_simulacion_no_encolan_nada(): void
    {
        Queue::fake();
        $this->tarea($this->ana, 'Hoy', 'pendiente', 0);

        $this->artisan('tareas:recordatorios --simular')->expectsOutputToContain('Hoy (hoy)')->assertSuccessful();

        $this->assertSame(0, MensajeWhatsapp::count());
    }

    public function test_limpiar_codigos_borra_vencidos_y_usados(): void
    {
        CodigoVinculacion::generarPara($this->ana);                       // vigente
        $vencido = CodigoVinculacion::generarPara($this->juan);
        CodigoVinculacion::where('user_id', $this->juan->id)->update(['expira_at' => now()->subDay()]);
        CodigoVinculacion::create(['user_id' => $this->acudiente->id, 'codigo_hash' => 'x', 'expira_at' => now()->addDay(), 'usado_at' => now()]);

        $this->artisan('codigos:limpiar')->assertSuccessful();

        $this->assertSame(1, CodigoVinculacion::count());
        $this->assertSame($this->ana->id, CodigoVinculacion::first()->user_id);
    }

    public function test_el_simulador_aplica_el_mensaje_y_muestra_la_respuesta(): void
    {
        $this->artisan('whatsapp:simular', ['telefono' => '300 000 0001', 'mensaje' => 'TAREA Matemáticas | mañana | Página 32'])
            ->expectsOutputToContain('Tarea #')
            ->assertSuccessful();

        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->ana->id, 'origen' => 'whatsapp']);
    }

    public function test_el_simulador_rechaza_numeros_invalidos(): void
    {
        $this->artisan('whatsapp:simular', ['telefono' => 'abc', 'mensaje' => 'AYUDA'])->assertFailed();
    }
}
