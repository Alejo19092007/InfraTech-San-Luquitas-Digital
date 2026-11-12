<?php

namespace App\Bot;

final class Texto
{
    /**
     * Deja el texto listo para comparar palabras clave: minúsculas, sin tildes
     * (ni ñ) y con un solo espacio entre palabras. "  MAÑANA " → "manana".
     * No usar para guardar datos: solo para reconocer comandos.
     */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    }
}
