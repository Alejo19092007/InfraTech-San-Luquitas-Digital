<?php

namespace App\Console\Commands;

use App\Bot\ProcesadorMensajes;
use App\Support\Telefono;
use Illuminate\Console\Command;

/**
 * Prueba el bot sin Meta ni internet (útil si falla la red en la demostración):
 *   php artisan whatsapp:simular +573000000001 "TAREA Matemáticas | mañana | Página 32"
 * Aplica el mensaje de verdad (crea la tarea) y muestra la respuesta, pero no envía nada.
 */
class SimularWhatsApp extends Command
{
    protected $signature = 'whatsapp:simular {telefono : Número del remitente} {mensaje : Texto que escribió}';

    protected $description = 'Procesa un mensaje como si llegara por WhatsApp y muestra la respuesta (sin enviarla).';

    public function handle(ProcesadorMensajes $procesador): int
    {
        $telefono = Telefono::normalizar($this->argument('telefono'));

        if ($telefono === null) {
            $this->error('El número no es válido. Ejemplo: +573000000001');

            return self::FAILURE;
        }

        [$estado, $respuesta] = $procesador->procesar($telefono, $this->argument('mensaje'));

        $this->line("<fg=gray>{$telefono} escribe:</> {$this->argument('mensaje')}");
        $this->line("<fg=gray>El bot responde ({$estado}):</>");
        $this->line($respuesta);

        return self::SUCCESS;
    }
}
