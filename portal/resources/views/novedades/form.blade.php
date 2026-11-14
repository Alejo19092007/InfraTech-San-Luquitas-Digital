@extends('layouts.app')
@section('titulo', $novedad->exists ? 'Editar noticia' : 'Publicar noticia')

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / <a href="{{ route('novedades.index') }}">Noticias</a> / {{ $novedad->exists ? 'Editar' : 'Publicar' }}</p>
    <header class="cabeza-pagina">
        <h1>{{ $novedad->exists ? 'Editar noticia' : 'Publicar noticia' }}</h1>
        <p>Escribe con palabras sencillas: lo leen estudiantes, familias y docentes.</p>
    </header>

    @if ($errors->any())
        <p class="alerta alerta-error" role="alert">Faltan datos o hay campos por corregir. Revisa los que están marcados en rojo.</p>
    @endif

    <form class="formulario" method="POST" data-formulario novalidate
          action="{{ $novedad->exists ? route('novedades.update', $novedad) : route('novedades.store') }}">
        @csrf
        @if ($novedad->exists) @method('PUT') @endif

        <div class="campo @error('titulo') invalido @enderror">
            <label for="titulo">Título <span class="obligatorio" aria-hidden="true">*</span></label>
            <input id="titulo" name="titulo" type="text" maxlength="150" required value="{{ old('titulo', $novedad->titulo) }}"
                   placeholder="Ej.: Reunión de acudientes el viernes" aria-describedby="titulo-error" @error('titulo') aria-invalid="true" @enderror>
            <span class="error" id="titulo-error">{{ $errors->first('titulo') ?: 'Escribe el título de la noticia.' }}</span>
        </div>

        <div class="campo-fila">
            <div class="campo @error('categoria') invalido @enderror">
                <label for="categoria">Categoría <span class="obligatorio" aria-hidden="true">*</span></label>
                <select id="categoria" name="categoria" required aria-describedby="categoria-error">
                    <option value="">Elige una</option>
                    @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
                        <option value="{{ $cat }}" @selected(old('categoria', $novedad->categoria) === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <span class="error" id="categoria-error">{{ $errors->first('categoria') ?: 'Elige una categoría.' }}</span>
            </div>
            <div class="campo @error('fecha_publicacion') invalido @enderror">
                <label for="fecha_publicacion">Fecha de publicación <span class="obligatorio" aria-hidden="true">*</span></label>
                <input id="fecha_publicacion" name="fecha_publicacion" type="date" required aria-describedby="fecha-error"
                       value="{{ old('fecha_publicacion', optional($novedad->fecha_publicacion)->format('Y-m-d')) }}">
                <span class="error" id="fecha-error">{{ $errors->first('fecha_publicacion') ?: 'Elige la fecha.' }}</span>
            </div>
        </div>

        <div class="campo @error('resumen') invalido @enderror">
            <label for="resumen">Resumen (opcional)</label>
            <input id="resumen" name="resumen" type="text" maxlength="300" value="{{ old('resumen', $novedad->resumen) }}" aria-describedby="resumen-ayuda">
            <span class="ayuda" id="resumen-ayuda">Una frase que se muestra en el listado de noticias. Máximo 300 caracteres.</span>
            <span class="error">{{ $errors->first('resumen') }}</span>
        </div>

        <div class="campo @error('contenido') invalido @enderror">
            <label for="contenido">Contenido <span class="obligatorio" aria-hidden="true">*</span></label>
            <textarea id="contenido" name="contenido" required rows="9" aria-describedby="contenido-error">{{ old('contenido', $novedad->contenido) }}</textarea>
            <span class="error" id="contenido-error">{{ $errors->first('contenido') ?: 'Escribe el contenido de la noticia.' }}</span>
        </div>

        <div class="botones">
            <button class="boton" type="submit">{{ $novedad->exists ? 'Guardar cambios' : 'Publicar noticia' }}</button>
            <a class="boton boton-borde" href="{{ $novedad->exists ? route('novedades.show', $novedad) : route('novedades.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
