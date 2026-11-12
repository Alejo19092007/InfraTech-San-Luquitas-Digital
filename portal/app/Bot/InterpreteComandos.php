<?php

namespace App\Bot;

use DateTimeImmutable;

/**
 * Convierte el texto de un mensaje de WhatsApp en un Comando.
 *
 * No toca la base de datos ni conoce al usuario: solo entiende el texto.
 * Así se puede probar sin Laravel y el job que procesa el mensaje decide
 * después qué hacer según el rol de quien escribió.
 */
final class InterpreteComandos
{
    public const MAX_MATERIA = 60;

    public const MAX_DESCRIPCION = 500;

    private const GRADO_MIN = 1;

    private const GRADO_MAX = 11;

    private const FORMATO_TAREA = "Para registrar una tarea escribe:\n"
        ."TAREA materia | fecha | descripción\n"
        .'Ej.: TAREA Matemáticas | 15/11 | Página 32 ejercicios 1-5';

    /**
     * @param  DateTimeImmutable  $hoy  Fecha actual en la zona horaria del colegio.
     */
    public static function interpretar(string $texto, DateTimeImmutable $hoy): Comando
    {
        $texto = trim($texto);
        $normalizado = Texto::normalizar($texto);

        if (in_array($normalizado, ['ayuda', 'menu', 'hola', '?'], true)) {
            return Comando::ayuda();
        }

        if (in_array($normalizado, ['mis tareas', 'tareas'], true)) {
            return Comando::misTareas();
        }

        [$palabra, $resto] = array_pad(explode(' ', $normalizado, 2), 2, '');

        return match ($palabra) {
            // "TAREA" mide 5 caracteres: el resto se toma del texto original para conservar tildes y mayúsculas.
            'tarea' => self::tarea(mb_substr($texto, 5), $hoy),
            'hecha' => self::hecha($resto),
            'vincular' => self::vincular($resto),
            default => Comando::desconocido(),
        };
    }

    private static function tarea(string $resto, DateTimeImmutable $hoy): Comando
    {
        // Límite de 3 para que la descripción pueda contener "|".
        $partes = array_map(trim(...), explode('|', $resto, 3));

        if (count($partes) < 3 || in_array('', $partes, true)) {
            return Comando::invalido(TipoComando::Tarea, self::FORMATO_TAREA);
        }

        [$cabecera, $fechaTexto, $descripcion] = $partes;
        $hijo = null;
        $grado = null;

        if (preg_match('/^@(\S+)\s*(.*)$/u', $cabecera, $m)) {
            // Acudiente: TAREA @Ana Matemáticas | ...
            $hijo = $m[1];
            $cabecera = $m[2];
        } elseif (preg_match('/^grado\s+(\d{1,2})[°º]?\s*(.*)$/iu', $cabecera, $m)) {
            // Profesor: TAREA GRADO 5 Ciencias | ...
            $grado = (int) $m[1];
            $cabecera = $m[2];

            if ($grado < self::GRADO_MIN || $grado > self::GRADO_MAX) {
                return Comando::invalido(
                    TipoComando::Tarea,
                    'El grado debe estar entre '.self::GRADO_MIN.' y '.self::GRADO_MAX.'. Ej.: TAREA GRADO 5 Ciencias | 15/11 | Leer la página 10'
                );
            }
        }

        $materia = trim($cabecera);

        if ($materia === '') {
            return Comando::invalido(TipoComando::Tarea, "Falta la materia.\n".self::FORMATO_TAREA);
        }

        if (mb_strlen($materia) > self::MAX_MATERIA) {
            return Comando::invalido(TipoComando::Tarea, 'El nombre de la materia es muy largo (máximo '.self::MAX_MATERIA.' letras).');
        }

        if (mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
            return Comando::invalido(TipoComando::Tarea, 'La descripción es muy larga (máximo '.self::MAX_DESCRIPCION.' letras). Escríbela más corta.');
        }

        try {
            $fecha = InterpreteFechas::interpretar($fechaTexto, $hoy);
        } catch (FechaInvalida $e) {
            return Comando::invalido(TipoComando::Tarea, $e->getMessage());
        }

        return Comando::tarea($materia, $fecha, $descripcion, $hijo, $grado);
    }

    private static function hecha(string $resto): Comando
    {
        if (! preg_match('/^#?(\d{1,9})$/', $resto, $m)) {
            return Comando::invalido(
                TipoComando::Hecha,
                'Escribe HECHA y el número de la tarea. Ej.: HECHA 12. Con MIS TAREAS ves los números.'
            );
        }

        return Comando::hecha((int) $m[1]);
    }

    private static function vincular(string $resto): Comando
    {
        // Se aceptan espacios o guiones dentro del código: "483 920".
        $codigo = str_replace([' ', '-'], '', $resto);

        if (! preg_match('/^\d{6}$/', $codigo)) {
            return Comando::invalido(
                TipoComando::Vincular,
                'El código tiene 6 números. Ej.: VINCULAR 483920. Lo encuentras en el portal, en "Vincular WhatsApp".'
            );
        }

        return Comando::vincular($codigo);
    }
}
