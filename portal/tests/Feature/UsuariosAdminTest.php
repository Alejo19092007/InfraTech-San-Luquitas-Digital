<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class UsuariosAdminTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscuela();
    }

    public function test_el_admin_crea_un_estudiante(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', [
            'name' => 'Laura Mesa', 'email' => 'laura@sanluquitas.local', 'password' => 'clave-segura-1', 'rol' => 'estudiante', 'grado' => 2,
            'telefono_whatsapp' => '310 123 4567',
        ])->assertRedirect(route('usuarios.index'));

        $laura = User::where('email', 'laura@sanluquitas.local')->first();
        $this->assertSame('estudiante', $laura->rol);
        $this->assertSame(2, $laura->grado);
        $this->assertSame('+573101234567', $laura->telefono_whatsapp);
        $this->assertNull($laura->telefono_verificado_at);
        $this->assertNotSame('clave-segura-1', $laura->password);   // guardada con hash
    }

    public function test_el_estudiante_exige_grado(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', [
            'name' => 'Laura Mesa', 'email' => 'laura@sanluquitas.local', 'password' => 'clave-segura-1', 'rol' => 'estudiante',
        ])->assertSessionHasErrors('grado');
    }

    public function test_el_admin_vincula_acudientes_con_sus_hijos(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', [
            'name' => 'Rosa Ortiz', 'email' => 'rosa@sanluquitas.local', 'password' => 'clave-segura-1', 'rol' => 'acudiente',
            'hijos' => [$this->mateo->id],
        ])->assertRedirect();

        $rosa = User::where('email', 'rosa@sanluquitas.local')->first();
        $this->assertSame([$this->mateo->id], $rosa->hijos()->pluck('users.id')->all());
    }

    public function test_el_admin_asigna_grados_a_un_docente(): void
    {
        $this->actingAs($this->admin)->put("/usuarios/{$this->docente->id}", [
            'name' => 'Martha Rojas', 'email' => $this->docente->email, 'rol' => 'profesor', 'grados' => '1, 2;3', 'materia' => 'Matemáticas',
        ])->assertRedirect();

        $this->assertSame([1, 2, 3], $this->docente->fresh()->gradosQueDicta());
    }

    public function test_los_grados_del_docente_deben_ser_numeros(): void
    {
        $this->actingAs($this->admin)->put("/usuarios/{$this->docente->id}", [
            'name' => 'Martha Rojas', 'email' => $this->docente->email, 'rol' => 'profesor', 'grados' => 'cuarto',
        ])->assertSessionHasErrors('grados');
    }

    public function test_editar_sin_contrasena_conserva_la_actual_y_cambiar_el_numero_pide_verificar(): void
    {
        $hash = $this->ana->password;

        $this->actingAs($this->admin)->put("/usuarios/{$this->ana->id}", [
            'name' => 'Ana María Pérez', 'email' => $this->ana->email, 'password' => '', 'rol' => 'estudiante', 'grado' => 5,
            'telefono_whatsapp' => '300 000 0555',
        ])->assertRedirect();

        $ana = $this->ana->fresh();
        $this->assertSame('Ana María Pérez', $ana->name);
        $this->assertSame($hash, $ana->password);
        $this->assertSame('+573000000555', $ana->telefono_whatsapp);
        $this->assertNull($ana->telefono_verificado_at);
    }

    public function test_correo_y_numero_no_se_repiten(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', [
            'name' => 'Copia', 'email' => $this->ana->email, 'password' => 'clave-segura-1', 'rol' => 'acudiente',
            'telefono_whatsapp' => $this->juan->telefono_whatsapp,
        ])->assertSessionHasErrors(['email', 'telefono_whatsapp']);
    }

    public function test_el_admin_no_puede_quitarse_el_rol_ni_borrarse(): void
    {
        $this->actingAs($this->admin)->put("/usuarios/{$this->admin->id}", [
            'name' => 'Secretaría', 'email' => $this->admin->email, 'rol' => 'profesor',
        ])->assertSessionHasErrors('rol');
        $this->assertSame('admin', $this->admin->fresh()->rol);

        $this->actingAs($this->admin)->delete("/usuarios/{$this->admin->id}")->assertSessionHasErrors('usuario');
        $this->assertNotNull($this->admin->fresh());
    }

    public function test_el_admin_elimina_un_usuario_y_se_van_sus_tareas(): void
    {
        $this->tarea($this->mateo);

        $this->actingAs($this->admin)->delete("/usuarios/{$this->mateo->id}")->assertRedirect(route('usuarios.index'));

        $this->assertNull(User::find($this->mateo->id));
        $this->assertDatabaseCount('tareas', 0);
    }

    public function test_los_demas_roles_no_gestionan_usuarios(): void
    {
        $this->actingAs($this->docente)->post('/usuarios', ['name' => 'X'])->assertForbidden();
        $this->actingAs($this->ana)->delete("/usuarios/{$this->juan->id}")->assertForbidden();
        $this->assertNotNull($this->juan->fresh());
    }
}
