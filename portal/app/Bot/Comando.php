<?php

namespace App\Bot;

use DateTimeImmutable;

/**
 * Resultado de interpretar un mensaje de WhatsApp.
 *
 * Solo describe lo que el usuario pidió. Quién puede hacerlo (permisos por rol)
 * y la búsqueda del hijo o del grado se resuelven después, al procesar el mensaje.
 */
final readonly class Comando
{
    private function __construct(
        public TipoComando $tipo,
        public ?string $error = null,
        public ?string $materia = null,
        public ?DateTimeImmutable $fechaEntrega = null,
        public ?string $descripcion = null,
        public ?string $hijo = null,
        public ?int $grado = null,
        public ?int $tareaId = null,
        public ?string $codigo = null,
    ) {}

    public static function tarea(
        string $materia,
        DateTimeImmutable $fechaEntrega,
        string $descripcion,
        ?string $hijo = null,
        ?int $grado = null,
    ): self {
        return new self(
            TipoComando::Tarea,
            materia: $materia,
            fechaEntrega: $fechaEntrega,
            descripcion: $descripcion,
            hijo: $hijo,
            grado: $grado,
        );
    }

    public static function misTareas(): self
    {
        return new self(TipoComando::MisTareas);
    }

    public static function hecha(int $tareaId): self
    {
        return new self(TipoComando::Hecha, tareaId: $tareaId);
    }

    public static function vincular(string $codigo): self
    {
        return new self(TipoComando::Vincular, codigo: $codigo);
    }

    public static function ayuda(): self
    {
        return new self(TipoComando::Ayuda);
    }

    public static function desconocido(): self
    {
        return new self(TipoComando::Desconocido);
    }

    /** Comando conocido pero mal escrito; $error explica cómo corregirlo y se envía tal cual por WhatsApp. */
    public static function invalido(TipoComando $tipo, string $error): self
    {
        return new self($tipo, error: $error);
    }

    public function esValido(): bool
    {
        return $this->error === null && $this->tipo !== TipoComando::Desconocido;
    }
}
