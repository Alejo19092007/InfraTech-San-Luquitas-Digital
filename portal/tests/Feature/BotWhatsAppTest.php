<?php

namespace Tests\Feature;

use App\Bot\ProcesadorMensajes;
use App\Models\CodigoVinculacion;
use App\Models\Tarea;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscuela;
use Tests\TestCase;

/** El bot aplicado a la base de datos: quién puede hacer qué según su rol. "Hoy" es jueves 12/11/2026. */
class BotWhatsAppTest extends TestCase
{
    use CreaEscuela, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00', 'America/Bogota'));
        $this->crearEscuela();
    }

    private function escribe(string $telefono, string $texto): array
    {
        return app(ProcesadorMensajes::class)->procesar($telefono, $texto);
    }

    public function test_numero_desconocido_se_rechaza_y_no_crea_nada(): void
    {
        [$estado, $respuesta] = $this->escribe('+573119999999', 'TAREA Matemáticas | 15/11 | Página 32');

        $this->assertSame('rechazado', $estado);
        $this->assertStringContainsString('Tu número no está registrado', $respuesta);
        $this->assertSame(0, Tarea::count());
    }

    public function test_numero_sin_verificar_solo_puede_vincular(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', '+573000000077', 3, verificado: false);

        [$estado, $respuesta] = $this->escribe($nuevo->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Algo');
        $this->assertSame('rechazado', $estado);
        $this->assertStringContainsString('VINCULAR', $respuesta);
        $this->assertSame(0, Tarea::count());
    }

    public function test_vincular_con_el_codigo_correcto_verifica_el_numero(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', '+573000000077', 3, verificado: false);
        $codigo = CodigoVinculacion::generarPara($nuevo);

        [$estado, $respuesta] = $this->escribe($nuevo->telefono_whatsapp, "VINCULAR {$codigo}");

        $this->assertSame('procesado', $estado);
        $this->assertStringContainsString('Número vinculado', $respuesta);
        $this->assertNotNull($nuevo->fresh()->telefono_verificado_at);
        $this->assertNotNull(CodigoVinculacion::where('user_id', $nuevo->id)->first()->usado_at);

        // Ya vinculado, puede crear tareas
        [, $r] = $this->escribe($nuevo->telefono_whatsapp, 'TAREA Sociales | mañana | Mapa');
        $this->assertStringContainsString('Tarea #', $r);
    }

    public function test_codigo_incorrecto_no_vincula_y_se_bloquea_tras_cinco_intentos(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', '+573000000077', 3, verificado: false);
        $codigo = CodigoVinculacion::generarPara($nuevo);
        $mal = $codigo === '000000' ? '111111' : '000000';

        for ($i = 0; $i < CodigoVinculacion::MAX_INTENTOS; $i++) {
            [, $r] = $this->escribe($nuevo->telefono_whatsapp, "VINCULAR {$mal}");
            $this->assertStringContainsString('no es el correcto', $r);
        }

        // Aunque ahora acierte, el código ya está bloqueado
        [, $r] = $this->escribe($nuevo->telefono_whatsapp, "VINCULAR {$codigo}");
        $this->assertStringContainsString('No hay un código vigente', $r);
        $this->assertNull($nuevo->fresh()->telefono_verificado_at);
    }

    public function test_codigo_vencido_no_sirve(): void
    {
        $nuevo = $this->usuario('estudiante', 'Pedro Gil', '+573000000077', 3, verificado: false);
        $codigo = CodigoVinculacion::generarPara($nuevo);

        Carbon::setTestNow(now()->addHours(25));
        [, $r] = $this->escribe($nuevo->telefono_whatsapp, "VINCULAR {$codigo}");

        $this->assertStringContainsString('No hay un código vigente', $r);
        $this->assertNull($nuevo->fresh()->telefono_verificado_at);
    }

    public function test_el_codigo_se_guarda_con_hash(): void
    {
        $codigo = CodigoVinculacion::generarPara($this->ana);

        $this->assertDatabaseMissing('codigos_vinculacion', ['codigo_hash' => $codigo]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codigo);
    }

    public function test_el_estudiante_crea_su_tarea(): void
    {
        [$estado, $r] = $this->escribe($this->ana->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Página 32 ejercicios 1-5');

        $this->assertSame('procesado', $estado);
        $this->assertStringContainsString('registrada', $r);
        $tarea = Tarea::where('estudiante_id', $this->ana->id)->firstOrFail();
        $this->assertSame($this->ana->id, $tarea->creada_por_id);
        $this->assertSame('Matemáticas', $tarea->materia);
        $this->assertSame('Página 32 ejercicios 1-5', $tarea->descripcion);
        $this->assertSame('2026-11-15', $tarea->fecha_entrega->toDateString());
        $this->assertSame('whatsapp', $tarea->origen);
        $this->assertSame('pendiente', $tarea->estado);
    }

    public function test_el_estudiante_no_puede_usar_hijo_ni_grado(): void
    {
        [, $r1] = $this->escribe($this->ana->telefono_whatsapp, 'TAREA @Juan Matemáticas | 15/11 | Algo');
        [, $r2] = $this->escribe($this->ana->telefono_whatsapp, 'TAREA GRADO 5 Matemáticas | 15/11 | Algo');

        $this->assertStringContainsString('propias tareas', $r1);
        $this->assertStringContainsString('propias tareas', $r2);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_acudiente_con_varios_hijos_debe_indicar_cual(): void
    {
        [, $r] = $this->escribe($this->acudiente->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Página 32');

        $this->assertStringContainsString('varios hijos', $r);
        $this->assertStringContainsString('TAREA @', $r);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_acudiente_crea_para_un_hijo_con_arroba(): void
    {
        [, $r] = $this->escribe($this->acudiente->telefono_whatsapp, 'TAREA @Juan Matemáticas | 15/11 | Página 32');

        $this->assertStringContainsString('para Juan', $r);
        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->juan->id, 'creada_por_id' => $this->acudiente->id, 'origen' => 'whatsapp']);
    }

    public function test_el_acudiente_no_puede_crear_para_un_estudiante_ajeno(): void
    {
        [, $r] = $this->escribe($this->acudiente->telefono_whatsapp, 'TAREA @Mateo Matemáticas | 15/11 | Página 32');

        $this->assertStringContainsString('No encontré a «Mateo»', $r);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_acudiente_con_un_solo_hijo_no_necesita_indicarlo(): void
    {
        $this->acudiente->hijos()->sync([$this->ana->id]);

        $this->escribe($this->acudiente->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Página 32');

        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->ana->id]);
    }

    public function test_el_docente_asigna_a_un_grado_que_dicta(): void
    {
        [, $r] = $this->escribe($this->docente->telefono_whatsapp, 'TAREA GRADO 4 Ciencias | viernes | Traer una planta');

        $this->assertStringContainsString('2 estudiantes de grado 4', $r);
        $this->assertSame(2, Tarea::where('materia', 'Ciencias')->count());
        $this->assertDatabaseHas('tareas', ['estudiante_id' => $this->mateo->id, 'creada_por_id' => $this->docente->id]);
    }

    public function test_el_docente_no_asigna_a_un_grado_que_no_dicta(): void
    {
        $this->usuario('estudiante', 'Camila', null, 2);

        [, $r] = $this->escribe($this->docente->telefono_whatsapp, 'TAREA GRADO 2 Ciencias | viernes | Algo');

        $this->assertStringContainsString('No tienes asignado el grado 2', $r);
        $this->assertSame(0, Tarea::count());
    }

    public function test_el_docente_debe_indicar_el_grado(): void
    {
        [, $r] = $this->escribe($this->docente->telefono_whatsapp, 'TAREA Ciencias | viernes | Algo');

        $this->assertStringContainsString('TAREA GRADO', $r);
    }

    public function test_el_admin_no_crea_tareas_por_whatsapp(): void
    {
        [, $r] = $this->escribe($this->admin->telefono_whatsapp, 'TAREA Matemáticas | 15/11 | Algo');

        $this->assertStringContainsString('desde el portal', $r);
        $this->assertSame(0, Tarea::count());
    }

    public function test_un_comando_mal_escrito_explica_como_corregirlo(): void
    {
        [, $r] = $this->escribe($this->ana->telefono_whatsapp, 'TAREA Matemáticas');

        $this->assertStringContainsString('TAREA materia | fecha | descripción', $r);
    }

    public function test_mis_tareas_lista_lo_pendiente_en_orden_de_fecha(): void
    {
        $this->tarea($this->ana, 'Lejana', 'pendiente', 9);
        $this->tarea($this->ana, 'Cercana', 'pendiente', 1);
        $this->tarea($this->ana, 'Hecha', 'entregada', 2);
        $this->tarea($this->juan, 'DeJuan', 'pendiente', 2);

        [, $r] = $this->escribe($this->ana->telefono_whatsapp, 'MIS TAREAS');

        $this->assertStringContainsString('Tareas pendientes (2)', $r);
        $this->assertLessThan(strpos($r, 'Lejana'), strpos($r, 'Cercana'));
        $this->assertStringNotContainsString('Hecha', $r);
        $this->assertStringNotContainsString('DeJuan', $r);
    }

    public function test_mis_tareas_del_acudiente_incluye_el_nombre_del_hijo(): void
    {
        $this->tarea($this->juan, 'Cálculo');

        [, $r] = $this->escribe($this->acudiente->telefono_whatsapp, 'mis tareas');

        $this->assertStringContainsString('Cálculo', $r);
        $this->assertStringContainsString('Juan', $r);
    }

    public function test_mis_tareas_sin_pendientes(): void
    {
        [, $r] = $this->escribe($this->ana->telefono_whatsapp, 'MIS TAREAS');

        $this->assertStringContainsString('No tienes tareas pendientes', $r);
    }

    public function test_mis_tareas_corta_la_lista_si_es_larga(): void
    {
        foreach (range(1, 11) as $i) {
            $this->tarea($this->ana, "Materia{$i}", 'pendiente', $i);
        }

        [, $r] = $this->escribe($this->ana->telefono_whatsapp, 'MIS TAREAS');

        $this->assertStringContainsString('(11)', $r);
        $this->assertStringContainsString('…y 3 más en el portal', $r);
    }

    public function test_hecha_marca_la_propia_y_la_de_un_hijo(): void
    {
        $propia = $this->tarea($this->ana);
        $deJuan = $this->tarea($this->juan);

        [, $r] = $this->escribe($this->ana->telefono_whatsapp, "HECHA {$propia->id}");
        $this->assertStringContainsString('marcada como entregada', $r);
        $this->assertSame('entregada', $propia->fresh()->estado);

        $this->escribe($this->acudiente->telefono_whatsapp, "HECHA {$deJuan->id}");
        $this->assertSame('entregada', $deJuan->fresh()->estado);
    }

    public function test_hecha_no_marca_tareas_ajenas_ni_revela_que_existen(): void
    {
        $ajena = $this->tarea($this->mateo);

        [, $deAna] = $this->escribe($this->ana->telefono_whatsapp, "HECHA {$ajena->id}");
        [, $inexistente] = $this->escribe($this->ana->telefono_whatsapp, 'HECHA 9999');

        $this->assertStringContainsString('No encontré la tarea', $deAna);
        $this->assertStringContainsString('No encontré la tarea', $inexistente);
        $this->assertTrue($ajena->fresh()->estaPendiente());
    }

    public function test_el_docente_no_marca_entregas(): void
    {
        $tarea = $this->tarea($this->ana);

        [, $r] = $this->escribe($this->docente->telefono_whatsapp, "HECHA {$tarea->id}");

        $this->assertStringContainsString('No encontré la tarea', $r);
        $this->assertTrue($tarea->fresh()->estaPendiente());
    }

    public function test_hecha_en_una_tarea_ya_entregada(): void
    {
        $tarea = $this->tarea($this->ana, 'Mate', 'entregada');

        [, $r] = $this->escribe($this->ana->telefono_whatsapp, "HECHA {$tarea->id}");

        $this->assertStringContainsString('ya estaba entregada', $r);
    }

    public function test_la_ayuda_depende_del_rol(): void
    {
        [, $est] = $this->escribe($this->ana->telefono_whatsapp, 'AYUDA');
        [, $acu] = $this->escribe($this->acudiente->telefono_whatsapp, 'AYUDA');
        [, $doc] = $this->escribe($this->docente->telefono_whatsapp, 'ayuda');

        $this->assertStringContainsString('TAREA materia', $est);
        $this->assertStringContainsString('TAREA @nombre', $acu);
        $this->assertStringContainsString('TAREA GRADO', $doc);
    }

    public function test_un_mensaje_que_no_es_texto_recibe_una_aclaracion(): void
    {
        [$estado, $r] = app(ProcesadorMensajes::class)->procesar($this->ana->telefono_whatsapp, null);

        $this->assertSame('procesado', $estado);
        $this->assertStringContainsString('solo entiendo mensajes de texto', $r);
    }

    public function test_un_texto_desconocido_muestra_la_ayuda(): void
    {
        [, $r] = $this->escribe($this->ana->telefono_whatsapp, 'buenas tardes profe');

        $this->assertStringContainsString('No entendí', $r);
        $this->assertStringContainsString('TAREA materia', $r);
    }
}
