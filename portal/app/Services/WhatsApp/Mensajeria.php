<?php

namespace App\Services\WhatsApp;

use App\Jobs\EnviarMensajeWhatsApp;
use App\Models\MensajeWhatsapp;

/** Punto único para mandar un mensaje: lo registra y lo encola con reintentos. */
final class Mensajeria
{
    public static function enviar(string $telefonoE164, string $texto): MensajeWhatsapp
    {
        $mensaje = MensajeWhatsapp::create([
            'telefono' => $telefonoE164,
            'direccion' => 'saliente',
            'payload' => ['texto' => $texto],
            'estado' => 'recibido', // pendiente de envío; pasa a enviado o fallido
        ]);

        EnviarMensajeWhatsApp::dispatch($mensaje->id);

        return $mensaje;
    }
}
