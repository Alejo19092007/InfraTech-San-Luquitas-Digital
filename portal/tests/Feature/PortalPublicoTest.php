<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Las páginas públicas del Portal responden sin iniciar sesión. */
class PortalPublicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_publicas_responden(): void
    {
        foreach (['/', '/institucional', '/novedades', '/recursos', '/ingresar'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_el_acceso_al_aula_virtual_redirige_a_moodle(): void
    {
        $this->get('/moodle')->assertRedirect(config('portal.moodle_url'));
    }

    public function test_las_rutas_privadas_no_son_publicas(): void
    {
        foreach (['/tareas', '/tareas/nueva', '/vincular-whatsapp', '/usuarios'] as $ruta) {
            $this->get($ruta)->assertRedirect('/ingresar');
        }
    }
}
