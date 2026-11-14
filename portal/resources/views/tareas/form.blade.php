@extends('layouts.app')
@section('titulo', 'Registrar tarea')

@section('contenido')
@php $usuario = auth()->user(); @endphp
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / <a href="{{ route('tareas.index') }}">Tareas</a> / Registrar</p>
    <header class="cabeza-pagina">
        <h1>Registrar tarea</h1>
        <p>
            @if ($usuario->esRol('profesor')) La tarea se asigna a todos los estudiantes del grado que elijas.
            @elseif ($usuario->esRol('acudiente')) Elige a cuál de tus hijos corresponde.
            @else Escribe qué hay que hacer y para cuándo. @endif
            También puedes hacerlo por WhatsApp: escribe AYUDA al número del colegio.
        </p>
    </header>

    @if ($errors->any())
        <p class="alerta alerta-error" role="alert">Faltan datos o hay campos por corregir. Revisa los que están marcados en rojo.</p>
    @endif

    <form class="formulario" method="POST" action="{{ route('tareas.store') }}" data-formulario novalidate>
        @csrf

        @if ($usuario->esRol('acudiente'))
            <div class="campo @error('estudiante_id') invalido @enderror">
                <label for="estudiante_id">Estudiante <span class="obligatorio" aria-hidden="true">*</span></label>
                <select id="estudiante_id" name="estudiante_id" required>
                    <option value="">Elige uno</option>
                    @foreach ($hijos as $h)
                        <option value="{{ $h->id }}" @selected((string) old('estudiante_id') === (string) $h->id)>{{ $h->name }} (grado {{ $h->grado }})</option>
                    @endforeach
                </select>
                <span class="error">{{ $errors->first('estudiante_id') ?: 'Elige el estudiante.' }}</span>
            </div>
        @elseif ($usuario->esRol('profesor'))
            <div class="campo @error('grado') invalido @enderror">
                <label for="grado">Grado <span class="obligatorio" aria-hidden="true">*</span></label>
                <select id="grado" name="grado" required>
                    <option value="">Elige uno</option>
                    @foreach ($grados as $g)
                        <option value="{{ $g }}" @selected((string) old('grado') === (string) $g)>Grado {{ $g }}</option>
                    @endforeach
                </select>
                <span class="error">{{ $errors->first('grado') ?: 'Elige el grado.' }}</span>
            </div>
        @endif

        <div class="campo-fila">
            <div class="campo @error('materia') invalido @enderror">
                <label for="materia">Materia <span class="obligatorio" aria-hidden="true">*</span></label>
                <input id="materia" name="materia" type="text" maxlength="60" required value="{{ old('materia') }}" placeholder="Ej.: Matemáticas">
                <span class="error">{{ $errors->first('materia') ?: 'Escribe la materia.' }}</span>
            </div>
            <div class="campo @error('fecha_entrega') invalido @enderror">
                <label for="fecha_entrega">Fecha de entrega <span class="obligatorio" aria-hidden="true">*</span></label>
                <input id="fecha_entrega" name="fecha_entrega" type="date" required min="{{ now()->format('Y-m-d') }}" value="{{ old('fecha_entrega') }}">
                <span class="error">{{ $errors->first('fecha_entrega') ?: 'Elige la fecha de entrega.' }}</span>
            </div>
        </div>

        <div class="campo @error('descripcion') invalido @enderror">
            <label for="descripcion">¿Qué hay que hacer? <span class="obligatorio" aria-hidden="true">*</span></label>
            <textarea id="descripcion" name="descripcion" rows="4" maxlength="500" required>{{ old('descripcion') }}</textarea>
            <span class="ayuda">Máximo 500 letras.</span>
            <span class="error">{{ $errors->first('descripcion') ?: 'Escribe qué hay que hacer.' }}</span>
        </div>

        <div class="botones">
            <button class="boton" type="submit">Registrar tarea</button>
            <a class="boton boton-borde" href="{{ route('tareas.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
