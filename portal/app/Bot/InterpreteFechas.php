<?php

namespace App\Bot;

use DateTimeImmutable;

/**
 * Convierte la fecha de entrega escrita por el usuario en una fecha real.
 *
 * Formatos aceptados: "15/11", "15/11/2026", "15-11-26", "hoy", "mañana"
 * y días de la semana ("viernes" = el próximo viernes).
 */
final class InterpreteFechas
{
    private const DIAS_SEMANA = [
        'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4,
        'viernes' => 5, 'sabado' => 6, 'domingo' => 7,
    ];

    /** Una tarea a más de un año casi siempre es un error al escribir. */
    private const MAX_DIAS_FUTURO = 366;

    /**
     * Sin año, una fecha que ya pasó se entiende como del año siguiente solo si
     * queda cerca (ej. "15/01" escrito en noviembre). Si no, se toma como error.
     */
    private const MAX_DIAS_CAMBIO_DE_ANIO = 183;

    /**
     * @param  DateTimeImmutable  $hoy  Fecha actual en la zona horaria del colegio.
     *
     * @throws FechaInvalida
     */
    public static function interpretar(string $texto, DateTimeImmutable $hoy): DateTimeImmutable
    {
        $hoy = $hoy->setTime(0, 0);
        $texto = Texto::normalizar($texto);

        if ($texto === 'hoy') {
            return $hoy;
        }

        if ($texto === 'manana') {
            return $hoy->modify('+1 day');
        }

        if (isset(self::DIAS_SEMANA[$texto])) {
            $dias = (self::DIAS_SEMANA[$texto] - (int) $hoy->format('N') + 7) % 7;

            // "jueves" escrito un jueves es el de la próxima semana; para hoy existe "hoy".
            return $hoy->modify('+'.($dias ?: 7).' days');
        }

        if (! preg_match('#^(\d{1,2})[/.\-](\d{1,2})(?:[/.\-](\d{4}|\d{2}))?$#', $texto, $partes)) {
            throw new FechaInvalida(
                'No entendí la fecha. Escríbela como 15/11, o usa "hoy", "mañana" o un día como "viernes".'
            );
        }

        $dia = (int) $partes[1];
        $mes = (int) $partes[2];
        $anioEscrito = isset($partes[3]);
        $anio = $anioEscrito ? (int) $partes[3] : (int) $hoy->format('Y');
        if ($anioEscrito && $anio < 100) {
            $anio += 2000;
        }

        $fecha = self::crear($hoy, $dia, $mes, $anio);

        if ($fecha < $hoy && ! $anioEscrito) {
            $siguiente = checkdate($mes, $dia, $anio + 1) ? $hoy->setDate($anio + 1, $mes, $dia) : null;
            if ($siguiente !== null && $hoy->diff($siguiente)->days <= self::MAX_DIAS_CAMBIO_DE_ANIO) {
                return $siguiente;
            }
        }

        if ($fecha < $hoy) {
            throw new FechaInvalida('Esa fecha ya pasó. Revisa el día y el mes de entrega.');
        }

        if ($hoy->diff($fecha)->days > self::MAX_DIAS_FUTURO) {
            throw new FechaInvalida('Esa fecha está a más de un año. Revisa el día, el mes y el año.');
        }

        return $fecha;
    }

    private static function crear(DateTimeImmutable $hoy, int $dia, int $mes, int $anio): DateTimeImmutable
    {
        if (! checkdate($mes, $dia, $anio)) {
            throw new FechaInvalida('Esa fecha no existe. Escríbela como día/mes, por ejemplo 15/11.');
        }

        return $hoy->setDate($anio, $mes, $dia);
    }
}
