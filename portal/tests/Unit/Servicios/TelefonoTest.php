<?php

namespace Tests\Unit\Servicios;

use App\Support\Telefono;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TelefonoTest extends TestCase
{
    #[DataProvider('numeros')]
    public function test_normaliza(?string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, Telefono::normalizar($entrada));
    }

    public static function numeros(): array
    {
        return [
            'celular colombiano' => ['300 123 4567', '+573001234567'],
            'con indicativo y signo' => ['+57 300 123 4567', '+573001234567'],
            'como llega de WhatsApp' => ['573001234567', '+573001234567'],
            'con guiones' => ['(300) 123-4567', '+573001234567'],
            'otro país' => ['+1 415 555 0132', '+14155550132'],
            'muy corto' => ['12345', null],
            'letras' => ['abc', null],
            'vacío' => ['', null],
            'nulo' => [null, null],
            'demasiado largo' => ['1234567890123456', null],
        ];
    }
}
