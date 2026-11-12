@extends('layouts.app')
@section('titulo', 'Inicio')

@section('contenido')
<section class="hero p-4 p-md-5 mb-4">
    <h1 class="display-6 fw-bold">Bienvenidos a la {{ config('portal.institucion') }}</h1>
    <p class="lead mb-4">Educación rural con tecnología: nuestros estudiantes de 4.º y 5.º de primaria dan sus primeros pasos en programación desde el aula virtual <strong>San Luquitas Digital</strong>.</p>
    <a href="{{ config('portal.moodle_url') }}" class="btn btn-moodle btn-lg me-2">Entrar al aula virtual</a>
    <a href="{{ route('institucional') }}" class="btn btn-outline-light btn-lg">Conócenos</a>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Últimas novedades</h2>
            <a href="{{ route('novedades.index') }}">Ver todas →</a>
        </div>
        @forelse ($novedades as $novedad)
            <article class="card mb-3">
                <div class="card-body">
                    <span class="badge etiqueta mb-2">{{ $novedad->categoria }}</span>
                    <h3 class="h5"><a href="{{ route('novedades.show', $novedad) }}" class="text-decoration-none">{{ $novedad->titulo }}</a></h3>
                    <p class="text-muted small mb-1">{{ $novedad->fecha_publicacion->translatedFormat('d \d\e F \d\e Y') }}</p>
                    <p class="mb-0">{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 140) }}</p>
                </div>
            </article>
        @empty
            <p class="text-muted">Aún no hay novedades publicadas.</p>
        @endforelse
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h5">📚 Recursos educativos</h2>
            <p>{{ $totalRecursos }} enlaces seleccionados para aprender programación jugando.</p>
            <a href="{{ route('recursos.index') }}" class="btn btn-outline-success btn-sm">Explorar recursos</a>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="h5">💬 Tareas por WhatsApp</h2>
            <p class="mb-0">Estudiantes, acudientes y profesores pueden registrar tareas escribiendo al número de la institución, incluso sin conexión en ese momento.</p>
        </div></div>
    </div>
</div>
@endsection
