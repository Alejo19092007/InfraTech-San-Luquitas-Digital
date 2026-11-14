<?php

namespace App\Console\Commands;

use App\Models\Tarea;
use App\Models\User;
use App\Services\WhatsApp\Mensajeria;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class EnviarRecordatoriosTareas extends Command
{
    protected $signature = 'tareas:recordatorios {--simular : Muestra los mensajes sin enviarlos}';

    protected $description = 'Avisa por WhatsApp de las tareas que vencen hoy o mañana (estudiantes y acudientes con número verificado).';

    public function handle(): int
    {
        $hoy = CarbonImmutable::now('America/Bogota')->startOfDay();

        $tareas = Tarea::query()
            ->where('estado', 'pendiente')
            ->whereDate('fecha_entrega', '>=', $hoy->toDateString())
            ->whereDate('fecha_entrega', '<=', $hoy->addDay()->toDateString())
            ->with(['estudiante.acudientes'])
            ->orderBy('fecha_entrega')
            ->get();

        // telefono => [usuario, líneas]
        $porPersona = [];

        foreach ($tareas as $tarea) {
            $estudiante = $tarea->estudiante;
            $cuando = $tarea->fecha_entrega->isSameDay($hoy) ? 'hoy' : 'mañana';

            foreach ([$estudiante, ...$estudiante->acudientes] as $persona) {
                if (! $persona->tieneWhatsappVerificado()) {
                    continue;
                }

                $quien = $persona->is($estudiante) ? '' : $estudiante->primerNombre().': ';
                $porPersona[$persona->telefono_whatsapp][] = "• {$quien}{$tarea->materia} ({$cuando}), #{$tarea->id}";
            }
        }

        foreach ($porPersona as $telefono => $lineas) {
            $texto = "⏰ Recordatorio de tareas:\n".implode("\n", $lineas)."\n\nPara marcar una como entregada escribe HECHA y su número.";

            if ($this->option('simular')) {
                $this->line("→ {$telefono}\n{$texto}\n");
            } else {
                Mensajeria::enviar($telefono, $texto);
            }
        }

        $this->info(count($porPersona).' recordatorio(s) '.($this->option('simular') ? 'simulados' : 'encolados').'.');

        return self::SUCCESS;
    }
}
