<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Envía mensajes de texto con la Cloud API de Meta. */
class ClienteWhatsApp
{
    public function configurado(): bool
    {
        return config('services.whatsapp.token') && config('services.whatsapp.phone_number_id');
    }

    /**
     * @return string|null id del mensaje enviado (wamid), si Meta lo devuelve
     *
     * @throws RuntimeException si Meta rechaza el mensaje o no hay conexión (el job reintenta)
     */
    public function enviarTexto(string $telefonoE164, string $texto): ?string
    {
        if (! $this->configurado()) {
            throw new RuntimeException('WhatsApp no está configurado: faltan WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID.');
        }

        $version = config('services.whatsapp.api_version');
        $url = "https://graph.facebook.com/{$version}/".config('services.whatsapp.phone_number_id').'/messages';

        $respuesta = Http::withToken(config('services.whatsapp.token'))
            ->timeout(15)
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($telefonoE164, '+'),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $texto],
            ]);

        if ($respuesta->failed()) {
            // El token nunca va en el mensaje de error.
            throw new RuntimeException('Meta respondió '.$respuesta->status().': '.($respuesta->json('error.message') ?? $respuesta->body()));
        }

        return $respuesta->json('messages.0.id');
    }
}
