<?php

namespace Tests\Unit\Bot;

use App\Bot\Comando;
use App\Bot\InterpreteComandos;
use App\Bot\TipoComando;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas del intérprete de comandos del bot de WhatsApp.
 * "Hoy" es siempre el jueves 12 de noviembre de 2026.
 */
class InterpreteComandosTest extends TestCase
{
    private function interpretar(string $texto): Comando
    {
        $hoy = new DateTimeImmutable('2026-11-12 10:30', new DateTimeZone('America/Bogota'));

        return InterpreteComandos::interpretar($texto, $hoy);
    }

    private function fechaDe(string $fechaTexto): ?string
    {
        return $this->interpretar("TAREA Ciencias | $fechaTexto | Leer")->fechaEntrega?->format('Y-m-d');
    }

    // ---------- TAREA ----------

    public function test_tarea_con_materia_fecha_y_descripcion(): void
    {
        $c = $this->interpretar('TAREA Matemáticas | 15/11 | Página 32 ejercicios 1-5');

        $this->assertTrue($c->esValido());
        $this->assertSame(TipoComando::Tarea, $c->tipo);
        $this->assertSame('Matemáticas', $c->materia);
        $this->assertSame('2026-11-15', $c->fechaEntrega->format('Y-m-d'));
        $this->assertSame('Página 32 ejercicios 1-5', $c->descripcion);
        $this->assertNull($c->hijo);
        $this->assertNull($c->grado);
    }

    public function test_tarea_en_minusculas_y_con_espacios_de_mas(): void
    {
        $c = $this->interpretar("  tarea   Ciencias Naturales|15/11|  Traer una planta  ");

        $this->assertTrue($c->esValido());
        $this->assertSame('Ciencias Naturales', $c->materia);
        $this->assertSame('Traer una planta', $c->descripcion);
    }

    public function test_acudiente_indica_el_hijo_con_arroba(): void
    {
        $c = $this->interpretar('TAREA @Ana Matemáticas | mañana | Tablas del 7');

        $this->assertTrue($c->esValido());
        $this->assertSame('Ana', $c->hijo);
        $this->assertSame('Matemáticas', $c->materia);
    }

    public function test_profesor_asigna_a_un_grado(): void
    {
        $c = $this->interpretar('TAREA GRADO 5 Ciencias | 20/11 | Maqueta del sistema solar');

        $this->assertTrue($c->esValido());
        $this->assertSame(5, $c->grado);
        $this->assertSame('Ciencias', $c->materia);
    }

    public function test_grado_fuera_de_rango_es_invalido(): void
    {
        $c = $this->interpretar('TAREA GRADO 15 Ciencias | 20/11 | Leer');

        $this->assertFalse($c->esValido());
        $this->assertSame(TipoComando::Tarea, $c->tipo);
    }

    public function test_la_descripcion_puede_tener_barras(): void
    {
        $c = $this->interpretar('TAREA Inglés | 15/11 | Verbos: go | went | gone');

        $this->assertSame('Verbos: go | went | gone', $c->descripcion);
    }

    public function test_tarea_incompleta_explica_el_formato(): void
    {
        foreach (['TAREA', 'TAREA Matemáticas', 'TAREA Matemáticas | 15/11', 'TAREA | 15/11 | Leer', 'TAREA @Ana | 15/11 | Leer'] as $texto) {
            $c = $this->interpretar($texto);

            $this->assertFalse($c->esValido(), $texto);
            $this->assertSame(TipoComando::Tarea, $c->tipo, $texto);
            $this->assertStringContainsString('TAREA materia | fecha | descripción', $c->error, $texto);
        }
    }

    public function test_materia_o_descripcion_demasiado_largas(): void
    {
        $this->assertFalse($this->interpretar('TAREA '.str_repeat('a', 61).' | 15/11 | Leer')->esValido());
        $this->assertFalse($this->interpretar('TAREA Ciencias | 15/11 | '.str_repeat('a', 501))->esValido());
    }

    // ---------- Fechas ----------

    public function test_fechas_con_palabras(): void
    {
        $this->assertSame('2026-11-12', $this->fechaDe('hoy'));
        $this->assertSame('2026-11-13', $this->fechaDe('Mañana'));
        $this->assertSame('2026-11-13', $this->fechaDe('viernes'));
        $this->assertSame('2026-11-16', $this->fechaDe('LUNES'));
        $this->assertSame('2026-11-18', $this->fechaDe('miércoles'));
        // Hoy es jueves: "jueves" es el de la próxima semana.
        $this->assertSame('2026-11-19', $this->fechaDe('jueves'));
    }

    public function test_fechas_con_numeros(): void
    {
        $this->assertSame('2026-11-15', $this->fechaDe('15/11/2026'));
        $this->assertSame('2026-11-15', $this->fechaDe('15-11-26'));
        $this->assertSame('2026-12-01', $this->fechaDe('1/12'));
        $this->assertSame('2026-11-12', $this->fechaDe('12/11'));
    }

    public function test_fecha_sin_anio_de_enero_pasa_al_anio_siguiente(): void
    {
        $this->assertSame('2027-01-15', $this->fechaDe('15/01'));
    }

    public function test_fecha_pasada_es_invalida(): void
    {
        foreach (['10/11', '10/11/2026', '15/06'] as $fecha) {
            $c = $this->interpretar("TAREA Ciencias | $fecha | Leer");

            $this->assertFalse($c->esValido(), $fecha);
            $this->assertStringContainsString('ya pasó', $c->error, $fecha);
        }
    }

    public function test_fecha_inexistente_lejana_o_ilegible_es_invalida(): void
    {
        foreach (['31/02', '15/13', '15/11/2028', 'pronto', '15 de noviembre'] as $fecha) {
            $c = $this->interpretar("TAREA Ciencias | $fecha | Leer");

            $this->assertFalse($c->esValido(), $fecha);
            $this->assertSame(TipoComando::Tarea, $c->tipo, $fecha);
        }
    }

    // ---------- Otros comandos ----------

    public function test_mis_tareas(): void
    {
        foreach (['MIS TAREAS', 'mis tareas', '  Mis   Tareas ', 'tareas'] as $texto) {
            $this->assertSame(TipoComando::MisTareas, $this->interpretar($texto)->tipo, $texto);
        }
    }

    public function test_hecha(): void
    {
        $this->assertSame(12, $this->interpretar('HECHA 12')->tareaId);
        $this->assertSame(12, $this->interpretar('hecha #12')->tareaId);

        foreach (['HECHA', 'HECHA doce', 'HECHA 12 13'] as $texto) {
            $c = $this->interpretar($texto);

            $this->assertFalse($c->esValido(), $texto);
            $this->assertSame(TipoComando::Hecha, $c->tipo, $texto);
        }
    }

    public function test_vincular(): void
    {
        $this->assertSame('483920', $this->interpretar('VINCULAR 483920')->codigo);
        $this->assertSame('483920', $this->interpretar('vincular 483 920')->codigo);
        // El código es texto: no debe perder los ceros de la izquierda.
        $this->assertSame('004821', $this->interpretar('VINCULAR 004821')->codigo);

        foreach (['VINCULAR', 'VINCULAR 123', 'VINCULAR 1234567', 'VINCULAR abcdef'] as $texto) {
            $c = $this->interpretar($texto);

            $this->assertFalse($c->esValido(), $texto);
            $this->assertSame(TipoComando::Vincular, $c->tipo, $texto);
        }
    }

    public function test_ayuda(): void
    {
        foreach (['AYUDA', 'ayuda', 'Hola', 'menú', '?'] as $texto) {
            $this->assertSame(TipoComando::Ayuda, $this->interpretar($texto)->tipo, $texto);
        }
    }

    public function test_mensaje_que_no_es_un_comando(): void
    {
        foreach (['', '   ', 'buenas tardes profe', 'tareaMatemáticas | 15/11 | Leer', '👍'] as $texto) {
            $c = $this->interpretar($texto);

            $this->assertSame(TipoComando::Desconocido, $c->tipo, $texto);
            $this->assertFalse($c->esValido(), $texto);
        }
    }
}
