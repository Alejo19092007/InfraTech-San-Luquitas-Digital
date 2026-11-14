<?php

namespace App\Services\WhatsApp;

final class FirmaMeta
{
    /**
     * Comprueba el encabezado X-Hub-Signature-256 ("sha256=<hmac>") contra el cuerpo crudo.
     * Sin secreto configurado se rechaza todo: nunca se acepta un mensaje sin poder verificarlo.
     */
    public static function esValida(string $cuerpoCrudo, ?string $encabezado, ?string $secreto): bool
    {
        if ($secreto === null || $secreto === '' || $encabezado === null || ! str_starts_with($encabezado, 'sha256=')) {
            return false;
        }

        $esperada = hash_hmac('sha256', $cuerpoCrudo, $secreto);

        return hash_equals($esperada, substr($encabezado, 7));
    }
}
