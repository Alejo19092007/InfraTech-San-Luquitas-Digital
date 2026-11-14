<?php

namespace Tests\Unit\Servicios;

use App\Services\WhatsApp\FirmaMeta;
use PHPUnit\Framework\TestCase;

class FirmaMetaTest extends TestCase
{
    public function test_acepta_una_firma_correcta(): void
    {
        $cuerpo = '{"hola":"mundo"}';

        $this->assertTrue(FirmaMeta::esValida($cuerpo, 'sha256='.hash_hmac('sha256', $cuerpo, 's3cr3to'), 's3cr3to'));
    }

    public function test_rechaza_firmas_incorrectas_o_mal_formadas(): void
    {
        $cuerpo = '{"hola":"mundo"}';
        $buena = hash_hmac('sha256', $cuerpo, 's3cr3to');

        $this->assertFalse(FirmaMeta::esValida($cuerpo.' ', 'sha256='.$buena, 's3cr3to'));   // cuerpo alterado
        $this->assertFalse(FirmaMeta::esValida($cuerpo, 'sha256='.$buena, 'otro-secreto'));
        $this->assertFalse(FirmaMeta::esValida($cuerpo, $buena, 's3cr3to'));                   // sin el prefijo
        $this->assertFalse(FirmaMeta::esValida($cuerpo, 'sha1='.$buena, 's3cr3to'));
        $this->assertFalse(FirmaMeta::esValida($cuerpo, null, 's3cr3to'));
    }

    public function test_sin_secreto_nunca_es_valida(): void
    {
        $cuerpo = '{}';

        $this->assertFalse(FirmaMeta::esValida($cuerpo, 'sha256='.hash_hmac('sha256', $cuerpo, ''), ''));
        $this->assertFalse(FirmaMeta::esValida($cuerpo, 'sha256='.hash_hmac('sha256', $cuerpo, ''), null));
    }
}
