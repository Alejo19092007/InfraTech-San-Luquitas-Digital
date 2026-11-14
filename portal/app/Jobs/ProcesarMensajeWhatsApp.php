<?php

namespace App\Jobs;

use App\Bot\ProcesadorMensajes;
use App\Models\MensajeWhatsapp;
use App\Services\WhatsApp\Mensajeria;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Interpreta un mensaje entrante y encola la respuesta. Cola: whatsapp-in. */
class ProcesarMensajeWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $mensajeId)
    {
        $this->onQueue('whatsapp-in');
    }

    public function handle(ProcesadorMensajes $procesador): void
    {
        $mensaje = MensajeWhatsapp::find($this->mensajeId);

        // Idempotente: si ya se procesó (reintento de la cola), no se hace dos veces.
        if (! $mensaje || $mensaje->direccion !== 'entrante' || $mensaje->estado !== 'recibido') {
            return;
        }

        $texto = ($mensaje->payload['type'] ?? 'text') === 'text' ? $mensaje->texto() : null;

        [$estado, $respuesta] = $procesador->procesar($mensaje->telefono, $texto);

        $mensaje->update(['estado' => $estado]);

        Mensajeria::enviar($mensaje->telefono, $respuesta);
    }
}
