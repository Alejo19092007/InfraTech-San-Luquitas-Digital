@extends('layouts.app')
@section('titulo', $novedad->exists ? 'Editar novedad' : 'Crear novedad')

@section('contenido')
<div class="container page-intro">
    <span class="eyebrow">Panel de gestión</span>
    <h1>{{ $novedad->exists ? 'Editar novedad' : 'Crear una novedad' }}</h1>
    <p>Comparte información clara y útil con estudiantes, familias y docentes.</p>
</div>

<div class="container form-shell">
    @if ($errors->any())
        <div class="alert alert-error" role="alert">Revisa los campos marcados antes de publicar.</div>
    @endif

    {{-- novalidate: la validación definitiva es la de Laravel; portal.js solo ayuda en los campos obligatorios --}}
    <form class="form-card" data-news-form method="POST" novalidate
          action="{{ $novedad->exists ? route('novedades.update', $novedad) : route('novedades.store') }}">
        @csrf
        @if ($novedad->exists) @method('PUT') @endif

        <div class="field @error('titulo') invalid @enderror">
            <label for="titulo">Título <span aria-hidden="true">*</span></label>
            <input id="titulo" name="titulo" type="text" maxlength="150" required placeholder="Ej. Jornada de lectura en la sede"
                   value="{{ old('titulo', $novedad->titulo) }}" @error('titulo') aria-invalid="true" @enderror>
            <span class="field-error">{{ $errors->first('titulo') ?: 'Escribe un título para la novedad.' }}</span>
        </div>

        <div class="field @error('categoria') invalid @enderror">
            <label for="categoria">Categoría <span aria-hidden="true">*</span></label>
            <select id="categoria" name="categoria" required>
                <option value="">Selecciona una categoría</option>
                @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
                    <option value="{{ $cat }}" @selected(old('categoria', $novedad->categoria) === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <span class="field-error">{{ $errors->first('categoria') ?: 'Selecciona una categoría.' }}</span>
        </div>

        <div class="field @error('fecha_publicacion') invalid @enderror">
            <label for="fecha_publicacion">Fecha <span aria-hidden="true">*</span></label>
            <input id="fecha_publicacion" name="fecha_publicacion" type="date" required
                   value="{{ old('fecha_publicacion', optional($novedad->fecha_publicacion)->format('Y-m-d')) }}">
            <span class="field-error">{{ $errors->first('fecha_publicacion') ?: 'Selecciona la fecha de publicación.' }}</span>
        </div>

        <div class="field @error('resumen') invalid @enderror">
            <label for="resumen">Resumen <span style="font-weight:500;color:var(--muted)">(opcional)</span></label>
            <textarea id="resumen" name="resumen" rows="3" maxlength="300" placeholder="Una frase corta para contar de qué se trata.">{{ old('resumen', $novedad->resumen) }}</textarea>
            <small>Máximo 300 caracteres. Ayuda a las familias a entender rápidamente la noticia.</small>
            <span class="field-error">{{ $errors->first('resumen') }}</span>
        </div>

        <div class="field @error('contenido') invalid @enderror">
            <label for="contenido">Contenido <span aria-hidden="true">*</span></label>
            <textarea id="contenido" name="contenido" required rows="8" placeholder="Escribe la información completa con palabras sencillas...">{{ old('contenido', $novedad->contenido) }}</textarea>
            <span class="field-error">{{ $errors->first('contenido') ?: 'Agrega el contenido de la novedad.' }}</span>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn btn-primary" type="submit">{{ $novedad->exists ? 'Guardar cambios' : 'Publicar novedad' }}</button>
            <a class="btn btn-outline" href="{{ $novedad->exists ? route('novedades.show', $novedad) : route('novedades.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
