<?php

namespace App\Bot;

/**
 * Comandos que entiende el bot de WhatsApp (sección 7.5 del contexto).
 */
enum TipoComando: string
{
    case Tarea = 'tarea';
    case MisTareas = 'mis_tareas';
    case Hecha = 'hecha';
    case Vincular = 'vincular';
    case Ayuda = 'ayuda';
    case Desconocido = 'desconocido';
}
