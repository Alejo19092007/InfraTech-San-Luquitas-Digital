<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscuela();
    }

    public function test_quien_no_inicio_sesion_va_al_formulario_de_acceso(): void
    {
        $this->get('/tareas')->assertRedirect(route('login'));
        $this->get('/vincular-whatsapp')->assertRedirect(route('login'));
        $this->get('/usuarios')->assertRedirect(route('login'));
    }

    public function test_el_formulario_de_acceso_se_muestra(): void
    {
        $this->get('/ingresar')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_entra_con_correo_y_contrasena_correctos(): void
    {
        $this->post('/ingresar', ['email' => $this->ana->email, 'password' => 'clave-de-prueba-1'])
            ->assertRedirect(route('tareas.index'));

        $this->assertAuthenticatedAs($this->ana);
    }

    public function test_rechaza_contrasena_incorrecta_sin_revelar_si_el_correo_existe(): void
    {
        $mal = $this->post('/ingresar', ['email' => $this->ana->email, 'password' => 'otra']);
        $noExiste = $this->post('/ingresar', ['email' => 'nadie@sanluquitas.local', 'password' => 'otra']);

        $mal->assertSessionHasErrors('email');
        $noExiste->assertSessionHasErrors('email');
        $this->assertSame(session('errors')->first('email'), 'El correo o la contraseña no son correctos.');
        $this->assertGuest();
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs($this->ana)->post('/salir')->assertRedirect(route('inicio'));
        $this->assertGuest();
    }

    public function test_solo_el_admin_entra_a_usuarios(): void
    {
        foreach ([$this->ana, $this->acudiente, $this->docente] as $persona) {
            $this->actingAs($persona)->get('/usuarios')->assertForbidden();
        }

        $this->actingAs($this->admin)->get('/usuarios')->assertOk()->assertSee('Martha Rojas');
    }

    public function test_el_menu_cambia_segun_la_sesion(): void
    {
        $this->get('/')->assertSee('Iniciar sesión')->assertDontSee('Mi WhatsApp');
        $this->actingAs($this->ana)->get('/')->assertSee('Mi WhatsApp')->assertSee('Tareas')->assertDontSee('>Usuarios<', false);
        $this->actingAs($this->admin)->get('/')->assertSee('Usuarios');
    }

    public function test_la_edicion_de_noticias_exige_ser_admin_cuando_no_esta_abierta(): void
    {
        config(['portal.edicion_abierta' => false]);

        $this->get('/novedades/crear')->assertForbidden();
        $this->actingAs($this->ana)->get('/novedades/crear')->assertForbidden();
        $this->actingAs($this->admin)->get('/novedades/crear')->assertOk();
    }
}
