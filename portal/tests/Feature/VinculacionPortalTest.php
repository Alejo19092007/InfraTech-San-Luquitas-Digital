<?php

namespace Tests\Feature;

use App\Bot\ProcesadorMensajes;
use App\Models\CodigoVinculacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class VinculacionPortalTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscuela();
    }

    public function test_flujo_completo_portal_y_whatsapp(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', null, 3);

        // Paso 1 y 2 en el portal
        $respuesta = $this->actingAs($nuevo)->followingRedirects()->post('/vincular-whatsapp', ['telefono' => '311 555 0123']);
        $respuesta->assertOk()->assertSee('VINCULAR');

        $nuevo->refresh();
        $this->assertSame('+573115550123', $nuevo->telefono_whatsapp);
        $this->assertNull($nuevo->telefono_verificado_at);

        // El código aparece una sola vez
        preg_match('/VINCULAR (\d{6})/', $respuesta->getContent(), $m);
        $this->assertNotEmpty($m);
        $this->actingAs($nuevo)->get('/vincular-whatsapp')->assertDontSee($m[1]);

        // Paso 3: el mensaje de WhatsApp
        [, $r] = app(ProcesadorMensajes::class)->procesar('+573115550123', "VINCULAR {$m[1]}");
        $this->assertStringContainsString('Número vinculado', $r);

        $this->actingAs($nuevo->fresh())->get('/vincular-whatsapp')->assertSee('está vinculado');
    }

    public function test_valida_el_numero(): void
    {
        $this->actingAs($this->ana)->post('/vincular-whatsapp', ['telefono' => 'abc'])->assertSessionHasErrors('telefono');
        $this->actingAs($this->ana)->post('/vincular-whatsapp', ['telefono' => ''])->assertSessionHasErrors('telefono');
        $this->assertSame(0, CodigoVinculacion::count());
    }

    public function test_un_numero_no_puede_pertenecer_a_dos_cuentas(): void
    {
        $otro = $this->usuario('estudiante', 'Otro', null, 3);

        $this->actingAs($otro)->post('/vincular-whatsapp', ['telefono' => $this->ana->telefono_whatsapp])
            ->assertSessionHasErrors('telefono');

        $this->assertNull($otro->fresh()->telefono_whatsapp);
    }

    public function test_cambiar_de_numero_obliga_a_verificar_de_nuevo(): void
    {
        $this->actingAs($this->ana)->post('/vincular-whatsapp', ['telefono' => '300 999 8877'])->assertRedirect();

        $this->assertSame('+573009998877', $this->ana->fresh()->telefono_whatsapp);
        $this->assertNull($this->ana->fresh()->telefono_verificado_at);
    }

    public function test_un_codigo_nuevo_invalida_el_anterior(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', '+573000000077', 3, verificado: false);
        $primero = CodigoVinculacion::generarPara($nuevo);
        CodigoVinculacion::generarPara($nuevo);

        $this->assertSame(1, CodigoVinculacion::where('user_id', $nuevo->id)->count());
        [, $r] = app(ProcesadorMensajes::class)->procesar($nuevo->telefono_whatsapp, "VINCULAR {$primero}");
        $this->assertStringContainsString('no es el correcto', $r);
    }
}
