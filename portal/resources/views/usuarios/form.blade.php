@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Crear usuario')

@section('contenido')
@php $rolActual = old('rol', $usuario->rol); @endphp
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / <a href="{{ route('usuarios.index') }}">Usuarios</a> / {{ $usuario->exists ? 'Editar' : 'Crear' }}</p>
    <header class="cabeza-pagina">
        <h1>{{ $usuario->exists ? 'Editar usuario' : 'Crear usuario' }}</h1>
        <p>Usa datos ficticios: el portal es un caso académico.</p>
    </header>

    @if ($errors->any())
        <p class="alerta alerta-error" role="alert">Faltan datos o hay campos por corregir. Revisa los que están marcados en rojo.</p>
    @endif

    <form class="formulario" method="POST" data-formulario novalidate
          action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <div class="campo @error('name') invalido @enderror">
            <label for="name">Nombre completo <span class="obligatorio" aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" maxlength="120" required value="{{ old('name', $usuario->name) }}">
            <span class="error">{{ $errors->first('name') ?: 'Escribe el nombre.' }}</span>
        </div>

        <div class="campo-fila">
            <div class="campo @error('email') invalido @enderror">
                <label for="email">Correo <span class="obligatorio" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" maxlength="190" required value="{{ old('email', $usuario->email) }}">
                <span class="error">{{ $errors->first('email') ?: 'Escribe un correo válido.' }}</span>
            </div>
            <div class="campo @error('password') invalido @enderror">
                <label for="password">Contraseña @unless ($usuario->exists)<span class="obligatorio" aria-hidden="true">*</span>@endunless</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" @unless ($usuario->exists) required @endunless>
                @if ($usuario->exists)<span class="ayuda">Déjala vacía para conservar la actual.</span>@endif
                <span class="error">{{ $errors->first('password') ?: 'Mínimo 8 caracteres.' }}</span>
            </div>
        </div>

        <div class="campo-fila">
            <div class="campo @error('rol') invalido @enderror">
                <label for="rol">Rol <span class="obligatorio" aria-hidden="true">*</span></label>
                <select id="rol" name="rol" required data-rol>
                    @foreach (\App\Models\User::ETIQUETAS_ROL as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($rolActual === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <span class="error">{{ $errors->first('rol') }}</span>
            </div>
            <div class="campo @error('telefono_whatsapp') invalido @enderror">
                <label for="telefono_whatsapp">Número de WhatsApp</label>
                <input id="telefono_whatsapp" name="telefono_whatsapp" type="tel" inputmode="tel" value="{{ old('telefono_whatsapp', $usuario->telefono_whatsapp) }}" placeholder="300 123 4567">
                <span class="ayuda">Opcional. La persona lo verifica después con el código del portal.</span>
                <span class="error">{{ $errors->first('telefono_whatsapp') }}</span>
            </div>
        </div>

        <fieldset class="grupo-rol" data-para="estudiante" @if ($rolActual !== 'estudiante') hidden @endif>
            <legend>Estudiante</legend>
            <div class="campo @error('grado') invalido @enderror">
                <label for="grado">Grado</label>
                <select id="grado" name="grado">
                    <option value="">Elige uno</option>
                    @for ($g = 1; $g <= 11; $g++)
                        <option value="{{ $g }}" @selected((string) old('grado', $usuario->grado) === (string) $g)>Grado {{ $g }}</option>
                    @endfor
                </select>
                <span class="error">{{ $errors->first('grado') ?: 'Elige el grado.' }}</span>
            </div>
        </fieldset>

        <fieldset class="grupo-rol" data-para="acudiente" @if ($rolActual !== 'acudiente') hidden @endif>
            <legend>Hijos a cargo</legend>
            @forelse ($estudiantes as $e)
                <label class="opcion"><input type="checkbox" name="hijos[]" value="{{ $e->id }}" @checked(in_array($e->id, old('hijos', $hijosActuales)))> {{ $e->name }} <span class="tenue">(grado {{ $e->grado }})</span></label>
            @empty
                <p class="tenue">Todavía no hay estudiantes. Crea primero a los estudiantes.</p>
            @endforelse
        </fieldset>

        <fieldset class="grupo-rol" data-para="profesor" @if ($rolActual !== 'profesor') hidden @endif>
            <legend>Docente</legend>
            <div class="campo-fila">
                <div class="campo @error('grados') invalido @enderror">
                    <label for="grados">Grados que dicta</label>
                    <input id="grados" name="grados" type="text" value="{{ old('grados', $gradosActuales) }}" placeholder="4, 5">
                    <span class="ayuda">Separados por comas.</span>
                    <span class="error">{{ $errors->first('grados') }}</span>
                </div>
                <div class="campo">
                    <label for="materia">Materia</label>
                    <input id="materia" name="materia" type="text" maxlength="60" value="{{ old('materia', $materiaActual) }}" placeholder="Ej.: Ciencias">
                </div>
            </div>
        </fieldset>

        <div class="botones">
            <button class="boton" type="submit">{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</button>
            <a class="boton boton-borde" href="{{ route('usuarios.index') }}">Cancelar</a>
        </div>
    </form>

    @if ($usuario->exists && ! $usuario->is(auth()->user()))
        <form class="formulario formulario-peligro" method="POST" action="{{ route('usuarios.destroy', $usuario) }}"
              onsubmit="return confirm('¿Eliminar este usuario? Se borran también sus tareas. No se puede deshacer.')">
            @csrf @method('DELETE')
            <h2>Eliminar usuario</h2>
            <p>Se borran también las tareas de esta persona.</p>
            <button class="boton boton-peligro" type="submit">Eliminar usuario</button>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Muestra solo los campos que corresponden al rol elegido.
    (function () {
        var rol = document.querySelector('[data-rol]');
        if (!rol) return;
        function mostrar() {
            document.querySelectorAll('[data-para]').forEach(function (g) { g.hidden = g.dataset.para !== rol.value; });
        }
        rol.addEventListener('change', mostrar);
        mostrar();
    })();
</script>
@endpush
