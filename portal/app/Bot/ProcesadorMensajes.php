<?php

namespace App\Bot;

use App\Models\CodigoVinculacion;
use App\Models\Tarea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Aplica un mensaje de WhatsApp: identifica a la persona por su número, interpreta el
 * comando y hace lo que su rol permite. Devuelve el texto de la respuesta.
 *
 * Es la única pieza que mezcla "qué pidió" (InterpreteComandos) con "qué puede hacer"
 * (rol y policies). No envía nada: quien la llama decide cómo entregar la respuesta.
 */
final class ProcesadorMensajes
{
    public const ZONA = 'America/Bogota';

    private const MAX_EN_LISTA = 8;

    /**
     * @return array{0: string, 1: string} [estado del mensaje ("procesado" o "rechazado"), respuesta]
     */
    public function procesar(string $telefono, ?string $texto): array
    {
        $usuario = User::where('telefono_whatsapp', $telefono)->first();

        if (! $usuario) {
            return ['rechazado', 'Tu número no está registrado. Pide acceso en secretaría o vincúlalo desde el portal.'];
        }

        if ($texto === null || trim($texto) === '') {
            return ['procesado', "Por ahora solo entiendo mensajes de texto.\nEscribe AYUDA para ver qué puedo hacer."];
        }

        $comando = InterpreteComandos::interpretar($texto, CarbonImmutable::now(self::ZONA));

        if (! $usuario->tieneWhatsappVerificado()) {
            if ($comando->tipo === TipoComando::Vincular) {
                return $comando->esValido()
                    ? ['procesado', $this->vincular($usuario, $comando->codigo)]
                    : ['procesado', $comando->error];
            }

            return ['rechazado', "Tu número aún no está verificado.\nEn el portal entra a «Vincular WhatsApp», copia el código de 6 números y escríbeme: VINCULAR 123456"];
        }

        if (! $comando->esValido()) {
            return ['procesado', $comando->error ?? "No entendí el mensaje.\n".$this->ayuda($usuario)];
        }

        $respuesta = match ($comando->tipo) {
            TipoComando::Tarea => $this->crearTarea($usuario, $comando),
            TipoComando::MisTareas => $this->misTareas($usuario),
            TipoComando::Hecha => $this->marcarHecha($usuario, $comando->tareaId),
            TipoComando::Vincular => 'Tu número ya está vinculado a tu cuenta.',
            TipoComando::Ayuda => $this->ayuda($usuario),
            default => $this->ayuda($usuario),
        };

        return ['procesado', $respuesta];
    }

    private function vincular(User $usuario, string $codigo): string
    {
        $vigente = CodigoVinculacion::activoDe($usuario);

        if (! $vigente) {
            return 'No hay un código vigente. Genera uno nuevo en el portal, en «Vincular WhatsApp».';
        }

        if (! $vigente->coincide($codigo)) {
            $vigente->increment('intentos');

            return 'Ese código no es el correcto. Revisa los 6 números en el portal, en «Vincular WhatsApp».';
        }

        DB::transaction(function () use ($vigente, $usuario) {
            $vigente->forceFill(['usado_at' => now()])->save();
            $usuario->forceFill(['telefono_verificado_at' => now()])->save();
        });

        return "✅ Número vinculado a tu cuenta, {$usuario->primerNombre()}.\n".$this->ayuda($usuario);
    }

    private function crearTarea(User $usuario, Comando $c): string
    {
        $destinatarios = $this->destinatariosDeTarea($usuario, $c);

        if (is_string($destinatarios)) {
            return $destinatarios; // mensaje explicando por qué no se puede
        }

        $tareas = DB::transaction(fn () => $destinatarios->map(fn (User $estudiante) => Tarea::create([
            'estudiante_id' => $estudiante->id,
            'creada_por_id' => $usuario->id,
            'materia' => $c->materia,
            'descripcion' => $c->descripcion,
            'fecha_entrega' => $c->fechaEntrega->format('Y-m-d'),
            'estado' => 'pendiente',
            'origen' => 'whatsapp',
        ])));

        $fecha = $this->fecha($c->fechaEntrega);

        if ($tareas->count() === 1) {
            $t = $tareas->first();
            $para = $usuario->esRol('estudiante') ? '' : ' para '.$destinatarios->first()->primerNombre();

            return "✅ Tarea #{$t->id} registrada{$para}.\n{$c->materia} · entrega {$fecha}\n{$c->descripcion}";
        }

        return "✅ Tarea asignada a {$tareas->count()} estudiantes de grado {$c->grado}.\n{$c->materia} · entrega {$fecha}\n{$c->descripcion}";
    }

    /** @return Collection<int, User>|string Estudiantes que recibirán la tarea, o el mensaje de error. */
    private function destinatariosDeTarea(User $usuario, Comando $c): Collection|string
    {
        switch ($usuario->rol) {
            case 'estudiante':
                if ($c->hijo !== null || $c->grado !== null) {
                    return "Los estudiantes registran sus propias tareas:\nTAREA materia | fecha | descripción";
                }

                return collect([$usuario]);

            case 'acudiente':
                if ($c->grado !== null) {
                    return 'Asignar tareas a todo un grado es solo para docentes. Para tu hijo escribe: TAREA @nombre materia | fecha | descripción';
                }

                $hijos = $usuario->hijos()->orderBy('name')->get();

                if ($hijos->isEmpty()) {
                    return 'Todavía no tienes estudiantes vinculados a tu cuenta. Pídelo en secretaría.';
                }

                if ($c->hijo !== null) {
                    $buscado = Texto::normalizar($c->hijo);
                    $coinciden = $hijos->filter(fn (User $h) => str_starts_with(Texto::normalizar($h->name), $buscado));

                    if ($coinciden->count() === 1) {
                        return $coinciden;
                    }

                    $nombres = $hijos->map->primerNombre()->implode(', ');

                    return $coinciden->isEmpty()
                        ? "No encontré a «{$c->hijo}» entre tus hijos ({$nombres}). Escribe el nombre como aparece en el portal."
                        : 'Hay más de un hijo con ese nombre ('.$hijos->pluck('name')->implode(', ').'). Registra la tarea desde el portal, en «Tareas».';
                }

                if ($hijos->count() === 1) {
                    return $hijos;
                }

                return "Tienes varios hijos ({$hijos->map->primerNombre()->implode(', ')}). Indica para cuál:\nTAREA @{$hijos->first()->primerNombre()} materia | fecha | descripción";

            case 'profesor':
                $dicta = $usuario->gradosQueDicta();

                if ($c->grado === null) {
                    return "Para asignar una tarea indica el grado:\nTAREA GRADO 5 Ciencias | 15/11 | Leer la página 10";
                }

                if (! in_array($c->grado, $dicta, true)) {
                    return $dicta === []
                        ? 'Todavía no tienes grados asignados. Pídelo en secretaría.'
                        : "No tienes asignado el grado {$c->grado}. Dictas: ".implode(', ', $dicta).'.';
                }

                $estudiantes = User::where('rol', 'estudiante')->where('grado', $c->grado)->orderBy('name')->get();

                return $estudiantes->isEmpty() ? "No hay estudiantes registrados en el grado {$c->grado}." : $estudiantes;

            default:
                return 'La administración gestiona las tareas desde el portal.';
        }
    }

    private function misTareas(User $usuario): string
    {
        if ($usuario->esRol('admin')) {
            return 'La administración consulta las tareas desde el portal, en la sección «Tareas».';
        }

        $consulta = Tarea::query()->visiblesPara($usuario)->where('estado', 'pendiente');
        $total = (clone $consulta)->count();

        if ($total === 0) {
            return '🎉 No tienes tareas pendientes.';
        }

        $tareas = $consulta->with('estudiante')->orderBy('fecha_entrega')->orderBy('id')->limit(self::MAX_EN_LISTA)->get();
        $mostrarNombre = ! $usuario->esRol('estudiante');

        $lineas = $tareas->map(function (Tarea $t) use ($mostrarNombre) {
            $quien = $mostrarNombre ? ' · '.$t->estudiante->primerNombre() : '';
            $vencida = $t->estaVencida() ? ' ⚠️ vencida' : '';

            return "#{$t->id} {$t->materia} · {$this->fecha($t->fecha_entrega)}{$quien}{$vencida}\n   ".mb_strimwidth($t->descripcion, 0, 70, '…');
        });

        $resto = $total > $tareas->count() ? "\n…y ".($total - $tareas->count()).' más en el portal.' : '';

        return "📋 Tareas pendientes ({$total}):\n".$lineas->implode("\n").$resto."\n\nPara marcar una: HECHA número";
    }

    private function marcarHecha(User $usuario, int $id): string
    {
        $tarea = Tarea::find($id);

        // Misma respuesta si no existe o no es suya: no se revela qué tareas hay.
        if (! $tarea || ! Gate::forUser($usuario)->allows('update', $tarea)) {
            return "No encontré la tarea #{$id} entre tus tareas. Con MIS TAREAS ves los números.";
        }

        if (! $tarea->estaPendiente()) {
            return "La tarea #{$id} ya estaba entregada.";
        }

        $tarea->marcarEntregada();

        return "✅ Tarea #{$id} ({$tarea->materia}) marcada como entregada.";
    }

    public function ayuda(User $usuario): string
    {
        $comunes = "MIS TAREAS → lista lo pendiente\nHECHA 12 → marca la tarea 12 como entregada\nAYUDA → este menú";

        return match ($usuario->rol) {
            'estudiante' => "Puedo ayudarte con:\nTAREA materia | fecha | descripción\nEj.: TAREA Matemáticas | 15/11 | Página 32\n".$comunes,
            'acudiente' => "Puedo ayudarte con:\nTAREA @nombre materia | fecha | descripción\nEj.: TAREA @Ana Matemáticas | 15/11 | Página 32\n".$comunes,
            'profesor' => "Puedo ayudarte con:\nTAREA GRADO 5 materia | fecha | descripción\nEj.: TAREA GRADO 5 Ciencias | 15/11 | Leer la página 10\nMIS TAREAS → lista lo pendiente de tus grados\nAYUDA → este menú",
            default => 'La administración usa el portal para gestionar usuarios y tareas.',
        };
    }

    private function fecha(\DateTimeInterface $fecha): string
    {
        return CarbonImmutable::instance($fecha)->locale('es')->isoFormat('ddd D/MM');
    }
}
