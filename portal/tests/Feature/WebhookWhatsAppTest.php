<?php

namespace Tests\Feature;

use App\Jobs\EnviarMensajeWhatsApp;
use App\Jobs\ProcesarMensajeWhatsApp;
use App\Models\MensajeWhatsapp;
use App\Models\Tarea;
use App\Services\WhatsApp\ClienteWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class WebhookWhatsAppTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    private const SECRETO = 'secreto-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.app_secret' => self::SECRETO,
            'services.whatsapp.verify_token' => 'token-de-verificacion',
            'services.whatsapp.token' => 'token-de-acceso',
            'services.whatsapp.phone_number_id' => '123456',
        ]);
        $this->crearEscuela();
    }

    /** Cuerpo como lo envía Meta para un mensaje de texto. */
    private function cuerpo(string $id, string $desde, string $texto): string
    {
        return json_encode(['object' => 'whatsapp_business_account', 'entry' => [['id' => '1', 'changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp',
            'messages' => [['from' => ltrim($desde, '+'), 'id' => $id, 'timestamp' => '1', 'type' => 'text', 'text' => ['body' => $texto]]],
        ]]]]]]);
    }

    private function enviar(string $cuerpo, ?string $firma = null)
    {
        $firma ??= 'sha256='.hash_hmac('sha256', $cuerpo, self::SECRETO);

        return $this->call('POST', '/webhook/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $firma,
        ], $cuerpo);
    }

    public function test_meta_verifica_la_suscripcion(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=token-de-verificacion&hub.challenge=987654')
            ->assertOk()->assertSee('987654');
    }

    public function test_la_verificacion_falla_con_token_incorrecto_o_sin_token_configurado(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=otro&hub.challenge=1')->assertForbidden();
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=1')->assertForbidden();

        config(['services.whatsapp.verify_token' => '']);
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=1')->assertForbidden();
    }

    public function test_rechaza_eventos_sin_firma_o_con_firma_falsa(): void
    {
        Queue::fake();
        $cuerpo = $this->cuerpo('wamid.1', $this->ana->telefono_whatsapp, 'AYUDA');

        $this->enviar($cuerpo, 'sha256='.str_repeat('0', 64))->assertForbidden();
        $this->call('POST', '/webhook/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json'], $cuerpo)->assertForbidden();

        $this->assertSame(0, MensajeWhatsapp::count());
        Queue::assertNothingPushed();
    }

    public function test_rechaza_todo_si_no_hay_secreto_configurado(): void
    {
        config(['services.whatsapp.app_secret' => '']);
        $cuerpo = $this->cuerpo('wamid.1', $this->ana->telefono_whatsapp, 'AYUDA');

        $this->enviar($cuerpo, 'sha256='.hash_hmac('sha256', $cuerpo, ''))->assertForbidden();
    }

    public function test_guarda_el_mensaje_crudo_y_lo_encola_en_whatsapp_in(): void
    {
        Queue::fake();

        $this->enviar($this->cuerpo('wamid.A', $this->ana->telefono_whatsapp, 'MIS TAREAS'))->assertOk()->assertJson(['recibido' => true]);

        $this->assertDatabaseHas('mensajes_whatsapp', [
            'wa_message_id' => 'wamid.A', 'telefono' => $this->ana->telefono_whatsapp, 'direccion' => 'entrante', 'estado' => 'recibido',
        ]);
        Queue::assertPushedOn('whatsapp-in', ProcesarMensajeWhatsApp::class);
    }

    public function test_un_mensaje_reenviado_por_meta_no_se_duplica(): void
    {
        Queue::fake();
        $cuerpo = $this->cuerpo('wamid.REP', $this->ana->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Algo');

        $this->enviar($cuerpo)->assertOk();
        $this->enviar($cuerpo)->assertOk();

        $this->assertSame(1, MensajeWhatsapp::where('wa_message_id', 'wamid.REP')->count());
        Queue::assertPushed(ProcesarMensajeWhatsApp::class, 1);
    }

    public function test_ignora_los_estados_de_entrega_y_los_cuerpos_vacios(): void
    {
        Queue::fake();
        $estados = json_encode(['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'x', 'status' => 'delivered']]]]]]]]);

        $this->enviar($estados)->assertOk();
        $this->enviar('{"entry":[]}')->assertOk();

        $this->assertSame(0, MensajeWhatsapp::count());
        $this->enviar('esto no es json')->assertStatus(400);
    }

    public function test_de_punta_a_punta_el_mensaje_crea_la_tarea_y_responde_por_la_api(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.RESP']]], 200)]);

        // Con la cola "sync" de las pruebas, todo se ejecuta en cadena.
        $this->enviar($this->cuerpo('wamid.E2E', $this->ana->telefono_whatsapp, 'TAREA Matemáticas | 20/11 | Página 32'))->assertOk();

        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->ana->id, 'materia' => 'Matemáticas', 'origen' => 'whatsapp']);
        $this->assertDatabaseHas('mensajes_whatsapp', ['wa_message_id' => 'wamid.E2E', 'estado' => 'procesado']);

        $saliente = MensajeWhatsapp::where('direccion', 'saliente')->first();
        $this->assertSame('enviado', $saliente->estado);
        $this->assertSame('wamid.RESP', $saliente->payload['wamid']);

        Http::assertSent(function ($peticion) {
            return str_contains($peticion->url(), '/123456/messages')
                && $peticion->hasHeader('Authorization', 'Bearer token-de-acceso')
                && $peticion['to'] === ltrim($this->ana->telefono_whatsapp, '+')
                && str_contains($peticion['text']['body'], 'Tarea #');
        });
    }

    public function test_un_numero_desconocido_queda_rechazado_en_la_bitacora(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]], 200)]);

        $this->enviar($this->cuerpo('wamid.X', '+573119999999', 'TAREA Matemáticas | 20/11 | Algo'))->assertOk();

        $this->assertDatabaseHas('mensajes_whatsapp', ['wa_message_id' => 'wamid.X', 'estado' => 'rechazado']);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_envio_falla_y_reintenta_cuando_meta_no_responde(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Servicio no disponible']], 503)]);
        $mensaje = MensajeWhatsapp::create([
            'telefono' => $this->ana->telefono_whatsapp, 'direccion' => 'saliente', 'payload' => ['texto' => 'Hola'], 'estado' => 'recibido',
        ]);

        $job = new EnviarMensajeWhatsApp($mensaje->id);

        try {
            $job->handle(app(ClienteWhatsApp::class));
            $this->fail('Debía lanzar una excepción para que la cola reintente.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }

        $this->assertStringContainsString('Servicio no disponible', $mensaje->fresh()->error);
        $this->assertNotSame('enviado', $mensaje->fresh()->estado);
        $this->assertSame([30, 120, 300, 900, 1800], $job->backoff());

        // Tras agotar los intentos queda como fallido
        $job->failed($e);
        $this->assertSame('fallido', $mensaje->fresh()->estado);
    }

    public function test_sin_credenciales_el_envio_falla_con_un_mensaje_claro_y_sin_filtrar_secretos(): void
    {
        config(['services.whatsapp.token' => null]);

        try {
            app(ClienteWhatsApp::class)->enviarTexto('+573000000001', 'Hola');
            $this->fail('Debía fallar.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('WHATSAPP_TOKEN', $e->getMessage());
        }
    }

    public function test_el_procesamiento_es_idempotente(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'w']]], 200)]);
        $this->enviar($this->cuerpo('wamid.I', $this->ana->telefono_whatsapp, 'TAREA Matemáticas | 20/11 | Algo'))->assertOk();
        $id = MensajeWhatsapp::where('wa_message_id', 'wamid.I')->value('id');

        // Un reintento de la cola del mismo mensaje no vuelve a crear la tarea
        (new ProcesarMensajeWhatsApp($id))->handle(app(\App\Bot\ProcesadorMensajes::class));

        $this->assertSame(1, Tarea::count());
    }

    public function test_el_webhook_no_exige_csrf_ni_sesion(): void
    {
        Queue::fake();
        // Si el CSRF estuviera activo, esta petición sin token respondería 419.
        $this->enviar($this->cuerpo('wamid.C', $this->ana->telefono_whatsapp, 'AYUDA'))->assertOk();
    }
}
