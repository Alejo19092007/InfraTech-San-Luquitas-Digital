@extends('layouts.app')
@section('titulo', $novedad->exists ? 'Editar novedad' : 'Nueva novedad')

@section('contenido')
<h1 class="h3 mb-4">{{ $novedad->exists ? 'Editar novedad' : 'Nueva novedad' }}</h1>

@if ($errors->any())
    <div class="alert alert-danger">Revisa los campos marcados.</div>
@endif

<form method="POST" action="{{ $novedad->exists ? route('novedades.update', $novedad) : route('novedades.store') }}" class="card"><div class="card-body">
    @csrf
    @if ($novedad->exists) @method('PUT') @endif

    <div class="mb-3">
        <label for="titulo" class="form-label">Título *</label>
        <input id="titulo" name="titulo" value="{{ old('titulo', $novedad->titulo) }}" maxlength="150" required
               class="form-control @error('titulo') is-invalid @enderror">
        @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="categoria" class="form-label">Categoría *</label>
            <select id="categoria" name="categoria" class="form-select @error('categoria') is-invalid @enderror">
                @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
                    <option value="{{ $cat }}" @selected(old('categoria', $novedad->categoria ?? 'General') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            @error('categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="fecha_publicacion" class="form-label">Fecha de publicación *</label>
            <input type="date" id="fecha_publicacion" name="fecha_publicacion" required
                   value="{{ old('fecha_publicacion', optional($novedad->fecha_publicacion)->format('Y-m-d')) }}"
                   class="form-control @error('fecha_publicacion') is-invalid @enderror">
            @error('fecha_publicacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <label for="resumen" class="form-label">Resumen <span class="text-muted">(opcional)</span></label>
        <input id="resumen" name="resumen" value="{{ old('resumen', $novedad->resumen) }}" maxlength="300"
               class="form-control @error('resumen') is-invalid @enderror">
        @error('resumen')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="contenido" class="form-label">Contenido *</label>
        <textarea id="contenido" name="contenido" rows="8" required
                  class="form-control @error('contenido') is-invalid @enderror">{{ old('contenido', $novedad->contenido) }}</textarea>
        @error('contenido')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button class="btn btn-success">{{ $novedad->exists ? 'Guardar cambios' : 'Publicar' }}</button>
    <a href="{{ $novedad->exists ? route('novedades.show', $novedad) : route('novedades.index') }}" class="btn btn-link">Cancelar</a>
</div></form>
@endsection
