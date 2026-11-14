<?php

namespace App\Jobs;

use App\Models\MensajeWhatsapp;
use App\Services\WhatsApp\ClienteWhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Envía un mensaje saliente. Cola: whatsapp-out.
 * Si no hay internet (o Meta falla) reintenta con espera creciente: 30 s, 2 min, 5 min, 15 min, 30 min.
 */
class EnviarMensajeWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public int $mensajeId)
    {
        $this->onQueue('whatsapp-out');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300, 900, 1800];
    }

    public function handle(ClienteWhatsApp $cliente): void
    {
        $mensaje = MensajeWhatsapp::find($this->mensajeId);

        if (! $mensaje || $mensaje->direccion !== 'saliente' || $mensaje->estado === 'enviado') {
            return;
        }

        $mensaje->update(['intentos' => $this->attempts()]);

        try {
            $wamid = $cliente->enviarTexto($mensaje->telefono, $mensaje->texto() ?? '');
        } catch (Throwable $e) {
            $mensaje->update(['error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e; // la cola lo reintenta con backoff
        }

        $mensaje->update([
            'estado' => 'enviado',
            'error' => null,
            'payload' => array_merge($mensaje->payload ?? [], ['wamid' => $wamid]),
        ]);
    }

    public function failed(Throwable $e): void
    {
        MensajeWhatsapp::whereKey($this->mensajeId)->update([
            'estado' => 'fallido',
            'error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
