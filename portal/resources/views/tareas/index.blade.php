@extends('layouts.app')
@section('titulo', 'Tareas')

@section('contenido')
@php
    $usuario = auth()->user();
    $hayFiltros = collect($filtros)->filter()->isNotEmpty();
@endphp
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / Tareas</p>
    <header class="cabeza-pagina cabeza-con-accion">
        <div>
            <h1>Tareas</h1>
            <p>
                @switch($usuario->rol)
                    @case('estudiante') Tus tareas pendientes y entregadas. @break
                    @case('acudiente') Las tareas de tus hijos. @break
                    @case('profesor') Las tareas de los grados que dictas. @break
                    @default Todas las tareas de la institución.
                @endswitch
            </p>
        </div>
        @can('create', \App\Models\Tarea::class)
            <a class="boton" href="{{ route('tareas.create') }}">Registrar tarea</a>
        @endcan
    </header>

    <dl class="resumen" aria-label="Resumen">
        <div><dt>Pendientes</dt><dd>{{ $resumen['pendientes'] }}</dd></div>
        <div class="@if ($resumen['vencidas']) alerta-num @endif"><dt>Vencidas</dt><dd>{{ $resumen['vencidas'] }}</dd></div>
        <div><dt>Entregadas</dt><dd>{{ $resumen['entregadas'] }}</dd></div>
    </dl>

    <form class="filtros-tareas" method="GET" action="{{ route('tareas.index') }}" role="search" aria-label="Filtrar tareas">
        <div class="campo">
            <label for="f-estado">Estado</label>
            <select id="f-estado" name="estado">
                <option value="">Todos</option>
                <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendientes</option>
                <option value="entregada" @selected(($filtros['estado'] ?? '') === 'entregada')>Entregadas</option>
            </select>
        </div>
        @if ($grados->count() > 1 || $usuario->esRol('admin', 'profesor'))
            <div class="campo">
                <label for="f-grado">Grado</label>
                <select id="f-grado" name="grado">
                    <option value="">Todos</option>
                    @foreach ($grados as $g)
                        <option value="{{ $g }}" @selected((string) ($filtros['grado'] ?? '') === (string) $g)>Grado {{ $g }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="campo">
            <label for="f-materia">Materia</label>
            <input id="f-materia" name="materia" type="text" maxlength="60" value="{{ $filtros['materia'] ?? '' }}" placeholder="Ej.: Ciencias">
        </div>
        <div class="campo">
            <label for="f-origen">Llegó por</label>
            <select id="f-origen" name="origen">
                <option value="">Todos</option>
                <option value="whatsapp" @selected(($filtros['origen'] ?? '') === 'whatsapp')>WhatsApp</option>
                <option value="web" @selected(($filtros['origen'] ?? '') === 'web')>El portal</option>
            </select>
        </div>
        <div class="botones">
            <button class="boton" type="submit">Filtrar</button>
            @if ($hayFiltros)<a class="boton boton-borde" href="{{ route('tareas.index') }}">Quitar filtros</a>@endif
        </div>
    </form>

    @if ($tareas->isEmpty())
        <div class="vacio" role="status">
            <h2>{{ $hayFiltros ? 'Ninguna tarea coincide con los filtros' : 'Todavía no hay tareas' }}</h2>
            <p>
                @if ($hayFiltros) Cambia o quita los filtros para ver más.
                @elseif ($usuario->esRol('estudiante', 'acudiente', 'profesor')) Registra la primera desde el botón «Registrar tarea» o escribiendo al WhatsApp del colegio.
                @else Aún no se ha registrado ninguna. @endif
            </p>
        </div>
    @else
        <div class="tabla-envoltura">
            <table class="tabla">
                <caption class="solo-lectores">Lista de tareas</caption>
                <thead>
                    <tr>
                        <th scope="col">N.º</th>
                        <th scope="col">Materia y descripción</th>
                        @unless ($usuario->esRol('estudiante'))<th scope="col">Estudiante</th>@endunless
                        <th scope="col">Entrega</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Llegó por</th>
                        <th scope="col"><span class="solo-lectores">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($tareas as $t)
                    <tr class="@if ($t->estaVencida()) fila-vencida @endif">
                        <td data-label="N.º">{{ $t->id }}</td>
                        <td data-label="Tarea"><strong>{{ $t->materia }}</strong><br><span class="tenue">{{ $t->descripcion }}</span></td>
                        @unless ($usuario->esRol('estudiante'))
                            <td data-label="Estudiante">{{ $t->estudiante->name }}<br><span class="tenue">Grado {{ $t->estudiante->grado }}</span></td>
                        @endunless
                        <td data-label="Entrega"><time datetime="{{ $t->fecha_entrega->format('Y-m-d') }}">{{ $t->fecha_entrega->locale('es')->isoFormat('ddd D [de] MMM') }}</time></td>
                        <td data-label="Estado">
                            @if ($t->estado === 'entregada')
                                <span class="estado estado-ok">Entregada</span>
                            @elseif ($t->estaVencida())
                                <span class="estado estado-vencida">Vencida</span>
                            @else
                                <span class="estado estado-pendiente">Pendiente</span>
                            @endif
                        </td>
                        <td data-label="Llegó por"><span class="origen origen-{{ $t->origen }}">{{ $t->origen === 'whatsapp' ? 'WhatsApp' : 'Portal' }}</span></td>
                        <td class="celda-accion">
                            @if ($t->estaPendiente())
                                @can('update', $t)
                                    <form method="POST" action="{{ route('tareas.entregar', $t) }}">
                                        @csrf @method('PATCH')
                                        <button class="boton boton-chico boton-borde" type="submit" aria-label="Marcar la tarea {{ $t->id }} de {{ $t->materia }} como entregada">Marcar entregada</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $tareas->links() }}
    @endif
</div>
@endsection
