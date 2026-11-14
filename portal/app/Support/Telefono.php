<?php

namespace App\Support;

final class Telefono
{
    /**
     * Deja un número en formato E.164 (+573001234567) o devuelve null si no parece un celular.
     * Acepta "300 123 4567", "+57 300 123 4567" y "573001234567" (así llega de WhatsApp).
     * Un número de 10 dígitos que empieza por 3 se toma como celular colombiano.
     */
    public static function normalizar(?string $numero): ?string
    {
        if ($numero === null) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $numero) ?? '';

        if (strlen($digitos) === 10 && str_starts_with($digitos, '3')) {
            $digitos = '57'.$digitos;
        }

        // E.164: entre 8 y 15 dígitos en total.
        if (strlen($digitos) < 8 || strlen($digitos) > 15) {
            return null;
        }

        return '+'.$digitos;
    }
}
