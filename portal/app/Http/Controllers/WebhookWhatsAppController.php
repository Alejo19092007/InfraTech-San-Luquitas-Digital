<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarMensajeWhatsApp;
use App\Models\MensajeWhatsapp;
use App\Services\WhatsApp\FirmaMeta;
use App\Support\Telefono;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Entrada de la Cloud API de Meta (por el túnel ngrok → nginx:8081 → esta ruta).
 *
 * Regla de oro: responder 200 rápido. Aquí solo se verifica la firma, se guarda el
 * mensaje crudo y se encola; el trabajo real lo hace el job ProcesarMensajeWhatsApp.
 */
class WebhookWhatsAppController extends Controller
{
    /** GET: Meta verifica la suscripción una sola vez al registrar el webhook. */
    public function verificar(Request $request): Response
    {
        // PHP convierte "hub.mode" en "hub_mode" al leer la URL; se aceptan ambas formas.
        $modo = $request->query('hub_mode', $request->query('hub.mode'));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $desafio = (string) $request->query('hub_challenge', $request->query('hub.challenge'));
        $esperado = (string) config('services.whatsapp.verify_token');

        abort_unless($modo === 'subscribe' && $esperado !== '' && hash_equals($esperado, $token), 403);

        return response($desafio, 200)->header('Content-Type', 'text/plain');
    }

    /** POST: eventos (mensajes entrantes y estados de entrega). */
    public function recibir(Request $request): JsonResponse
    {
        $cuerpo = $request->getContent();

        abort_unless(
            FirmaMeta::esValida($cuerpo, $request->header('X-Hub-Signature-256'), config('services.whatsapp.app_secret')),
            403,
            'Firma no válida.'
        );

        $datos = json_decode($cuerpo, true);

        abort_unless(is_array($datos), 400, 'Cuerpo no válido.');

        foreach ($datos['entry'] ?? [] as $entrada) {
            foreach ($entrada['changes'] ?? [] as $cambio) {
                // "statuses" (entregado, leído…) también llegan aquí: no son mensajes y se ignoran.
                foreach ($cambio['value']['messages'] ?? [] as $mensaje) {
                    $this->guardarYEncolar($mensaje);
                }
            }
        }

        return response()->json(['recibido' => true]);
    }

    /** @param  array<string, mixed>  $mensaje */
    private function guardarYEncolar(array $mensaje): void
    {
        $id = $mensaje['id'] ?? null;
        $telefono = Telefono::normalizar($mensaje['from'] ?? null);

        if (! is_string($id) || $telefono === null) {
            return;
        }

        // wa_message_id es único: si Meta reenvía el mismo mensaje, no se duplica nada.
        $registro = MensajeWhatsapp::createOrFirst(
            ['wa_message_id' => $id],
            ['telefono' => $telefono, 'direccion' => 'entrante', 'payload' => $mensaje, 'estado' => 'recibido'],
        );

        if ($registro->wasRecentlyCreated) {
            ProcesarMensajeWhatsApp::dispatch($registro->id);
        }
    }
}
