<?php

namespace Tests\Feature;

use App\Models\Tarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

class TareasPanelTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscuela();
    }

    public function test_cada_rol_ve_solo_lo_que_le_corresponde(): void
    {
        $this->tarea($this->ana, 'TareaAna');
        $this->tarea($this->juan, 'TareaJuan');
        $this->tarea($this->mateo, 'TareaMateo');
        $otro = $this->usuario('estudiante', 'Camila Díaz', null, 2);
        $this->tarea($otro, 'TareaGrado2');

        // Estudiante: solo la suya
        $this->actingAs($this->ana)->get('/tareas')->assertSee('TareaAna')->assertDontSee('TareaJuan')->assertDontSee('TareaMateo');

        // Acudiente: la de sus hijos
        $this->actingAs($this->acudiente)->get('/tareas')->assertSee('TareaAna')->assertSee('TareaJuan')->assertDontSee('TareaMateo');

        // Docente de los grados 4 y 5: no ve el grado 2
        $this->actingAs($this->docente)->get('/tareas')->assertSee('TareaAna')->assertSee('TareaMateo')->assertDontSee('TareaGrado2');

        // Admin: todo
        $this->actingAs($this->admin)->get('/tareas')->assertSee('TareaGrado2')->assertSee('TareaMateo');
    }

    public function test_filtros_por_estado_materia_origen_y_grado(): void
    {
        $this->tarea($this->ana, 'Geometría', 'pendiente', 2, 'whatsapp');
        $this->tarea($this->ana, 'Poesía', 'entregada', 2, 'web');
        $this->tarea($this->juan, 'Cálculo', 'pendiente', 2, 'web');

        $this->actingAs($this->admin)->get('/tareas?estado=entregada')->assertSee('Poesía')->assertDontSee('Geometría');
        $this->actingAs($this->admin)->get('/tareas?origen=whatsapp')->assertSee('Geometría')->assertDontSee('Poesía');
        $this->actingAs($this->admin)->get('/tareas?materia=cálc')->assertSee('Cálculo')->assertDontSee('Geometría');
        $this->actingAs($this->admin)->get('/tareas?grado=5')->assertSee('Geometría')->assertDontSee('Cálculo');
        $this->actingAs($this->admin)->get('/tareas?estado=inventado')->assertSessionHasErrors('estado');
    }

    public function test_las_vencidas_se_marcan_y_se_cuentan(): void
    {
        $this->tarea($this->ana, 'Atrasada', 'pendiente', -2);
        $this->tarea($this->ana, 'AlDia', 'pendiente', 5);

        $this->actingAs($this->ana)->get('/tareas')->assertSee('Vencida')->assertSee('fila-vencida', false);
    }

    public function test_sin_tareas_muestra_mensaje_vacio(): void
    {
        $this->actingAs($this->ana)->get('/tareas')->assertSee('Todavía no hay tareas');
    }

    public function test_el_estudiante_registra_su_tarea_desde_la_web(): void
    {
        $this->actingAs($this->ana)->post('/tareas', [
            'materia' => 'Sociales', 'fecha_entrega' => now()->addDays(2)->toDateString(), 'descripcion' => 'Mapa de la vereda',
        ])->assertRedirect(route('tareas.index'));

        $this->assertDatabaseHas('tareas', [
            'estudiante_id' => $this->ana->id, 'creada_por_id' => $this->ana->id, 'materia' => 'Sociales', 'origen' => 'web', 'estado' => 'pendiente',
        ]);
    }

    public function test_valida_los_campos_de_la_tarea(): void
    {
        $this->actingAs($this->ana)->post('/tareas', ['materia' => '', 'fecha_entrega' => now()->subDay()->toDateString(), 'descripcion' => ''])
            ->assertSessionHasErrors(['materia', 'fecha_entrega', 'descripcion']);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_acudiente_solo_registra_para_sus_hijos(): void
    {
        $datos = ['materia' => 'Artes', 'fecha_entrega' => now()->addDay()->toDateString(), 'descripcion' => 'Dibujo libre'];

        $this->actingAs($this->acudiente)->post('/tareas', $datos + ['estudiante_id' => $this->juan->id])->assertRedirect(route('tareas.index'));
        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->juan->id, 'creada_por_id' => $this->acudiente->id]);

        // Un estudiante que no es su hijo: no se crea nada
        $this->actingAs($this->acudiente)->post('/tareas', $datos + ['estudiante_id' => $this->mateo->id])->assertSessionHasErrors('estudiante_id');
        $this->assertDatabaseMissing('tareas', ['estudiante_id' => $this->mateo->id]);
    }

    public function test_el_docente_asigna_a_todo_un_grado_pero_solo_a_los_suyos(): void
    {
        $datos = ['materia' => 'Ciencias', 'fecha_entrega' => now()->addDays(3)->toDateString(), 'descripcion' => 'Traer una planta'];

        $this->actingAs($this->docente)->post('/tareas', $datos + ['grado' => 4])->assertRedirect(route('tareas.index'));
        $this->assertSame(2, Tarea::where('materia', 'Ciencias')->count());   // Juan y Mateo (grado 4)
        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->mateo->id, 'creada_por_id' => $this->docente->id]);

        // Grado 2: no lo dicta
        $this->usuario('estudiante', 'Camila', null, 2);
        $this->actingAs($this->docente)->post('/tareas', $datos + ['grado' => 2])->assertSessionHasErrors('grado');
    }

    public function test_el_admin_no_registra_tareas(): void
    {
        $this->actingAs($this->admin)->get('/tareas/nueva')->assertForbidden();
        $this->actingAs($this->admin)->post('/tareas', [])->assertForbidden();
    }

    public function test_marcar_como_entregada_respeta_los_permisos(): void
    {
        $tarea = $this->tarea($this->ana);

        // Otro estudiante no puede
        $this->actingAs($this->juan)->patch("/tareas/{$tarea->id}/entregar")->assertForbidden();
        // El docente ve la tarea pero no la marca
        $this->actingAs($this->docente)->patch("/tareas/{$tarea->id}/entregar")->assertForbidden();
        $this->assertTrue($tarea->fresh()->estaPendiente());

        // El acudiente de Ana sí
        $this->actingAs($this->acudiente)->patch("/tareas/{$tarea->id}/entregar")->assertRedirect();
        $this->assertSame('entregada', $tarea->fresh()->estado);
        $this->assertNotNull($tarea->fresh()->entregada_at);
    }
}
