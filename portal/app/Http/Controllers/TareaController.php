<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TareaController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        Gate::authorize('viewAny', Tarea::class);

        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(Tarea::ESTADOS)],
            'origen' => ['nullable', Rule::in(Tarea::ORIGENES)],
            'grado' => ['nullable', 'integer', 'between:1,11'],
            'materia' => ['nullable', 'string', 'max:60'],
        ]);

        $consulta = Tarea::query()->visiblesPara($usuario)->with(['estudiante', 'creadaPor']);

        // En PostgreSQL "like" distingue mayúsculas; "ilike" no. En otros motores "like" ya no las distingue.
        $operadorSinMayusculas = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $consulta
            ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filtros['origen'] ?? null, fn ($q, $v) => $q->where('origen', $v))
            ->when($filtros['grado'] ?? null, fn ($q, $v) => $q->whereHas('estudiante', fn ($e) => $e->where('grado', $v)))
            ->when($filtros['materia'] ?? null, fn ($q, $v) => $q->where('materia', $operadorSinMayusculas, '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $v).'%'));

        // Pendientes primero y, dentro de cada grupo, las que vencen antes.
        $tareas = $consulta->orderByRaw("case when estado = 'pendiente' then 0 else 1 end")
            ->orderBy('fecha_entrega')->orderBy('id')
            ->paginate(15)->withQueryString();

        $base = Tarea::query()->visiblesPara($usuario);
        $resumen = [
            'pendientes' => (clone $base)->where('estado', 'pendiente')->count(),
            'vencidas' => (clone $base)->where('estado', 'pendiente')->whereDate('fecha_entrega', '<', now()->toDateString())->count(),
            'entregadas' => (clone $base)->where('estado', 'entregada')->count(),
        ];

        $grados = Tarea::query()->visiblesPara($usuario)->join('users as e', 'e.id', '=', 'tareas.estudiante_id')
            ->whereNotNull('e.grado')->distinct()->orderBy('e.grado')->pluck('e.grado');

        return view('tareas.index', compact('tareas', 'resumen', 'filtros', 'grados'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Tarea::class);

        return view('tareas.form', $this->opcionesDeFormulario($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        Gate::authorize('create', Tarea::class);

        $reglas = [
            'materia' => ['required', 'string', 'max:60'],
            'fecha_entrega' => ['required', 'date', 'after_or_equal:today'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];

        if ($usuario->esRol('acudiente')) {
            $reglas['estudiante_id'] = ['required', 'integer'];
        } elseif ($usuario->esRol('profesor')) {
            $reglas['grado'] = ['required', 'integer', 'between:1,11'];
        }

        $datos = $request->validate($reglas, [
            'materia.required' => 'Escribe la materia.',
            'fecha_entrega.required' => 'Elige la fecha de entrega.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a hoy.',
            'descripcion.required' => 'Escribe qué hay que hacer.',
            'estudiante_id.required' => 'Elige para quién es la tarea.',
            'grado.required' => 'Elige el grado.',
        ]);

        $estudiantes = match ($usuario->rol) {
            'estudiante' => collect([$usuario]),
            'acudiente' => User::whereKey($datos['estudiante_id'])->get(),
            'profesor' => User::where('rol', 'estudiante')->where('grado', $datos['grado'])->get(),
        };

        // Solo se crea para quienes la policy permite; así un formulario manipulado no sirve.
        $estudiantes = $estudiantes->filter(fn (User $e) => Gate::forUser($usuario)->allows('crearPara', [Tarea::class, $e]));

        if ($estudiantes->isEmpty()) {
            return back()->withInput()->withErrors([
                $usuario->esRol('profesor') ? 'grado' : 'estudiante_id' => 'No tienes permiso para asignar tareas a esa selección o no hay estudiantes en ella.',
            ]);
        }

        DB::transaction(fn () => $estudiantes->each(fn (User $e) => Tarea::create([
            'estudiante_id' => $e->id,
            'creada_por_id' => $usuario->id,
            'materia' => $datos['materia'],
            'descripcion' => $datos['descripcion'],
            'fecha_entrega' => $datos['fecha_entrega'],
            'estado' => 'pendiente',
            'origen' => 'web',
        ])));

        $cuantas = $estudiantes->count();

        return redirect()->route('tareas.index')->with('estado', $cuantas === 1
            ? 'Tarea registrada.'
            : "Tarea asignada a {$cuantas} estudiantes.");
    }

    public function entregar(Tarea $tarea): RedirectResponse
    {
        Gate::authorize('update', $tarea);

        if ($tarea->estaPendiente()) {
            $tarea->marcarEntregada();
        }

        return back()->with('estado', 'Marcaste la tarea de «'.$tarea->materia.'» como entregada.');
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(User $usuario): array
    {
        return [
            'hijos' => $usuario->esRol('acudiente') ? $usuario->hijos()->orderBy('name')->get() : collect(),
            'grados' => $usuario->esRol('profesor') ? $usuario->gradosQueDicta() : [],
        ];
    }
}
