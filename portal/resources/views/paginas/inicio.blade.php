@extends('layouts.app')
@section('titulo', 'Inicio')

@section('contenido')
<section class="container hero">
    <div class="hero-content">
        <span class="eyebrow">✦ Bienvenidos a nuestra escuela</span>
        <h1>¡El campo también es un lugar para grandes ideas!</h1>
        <p>Aprendemos juntos, cuidamos nuestra tierra y construimos oportunidades para todos. En el aula virtual <strong>San Luquitas Digital</strong>, los niños de 4.º y 5.º dan sus primeros pasos en programación.</p>
        <div class="hero-actions">
            <a class="btn btn-yellow" href="{{ config('portal.moodle_url') }}">Entrar al aula virtual (Moodle) <span aria-hidden="true">↗</span></a>
            <a class="btn btn-light" href="{{ route('recursos.index') }}">Explorar recursos <span aria-hidden="true">→</span></a>
        </div>
    </div>
    <svg class="hero-art" viewBox="0 0 260 230" aria-hidden="true"><path d="M15 210Q70 110 130 190T250 150V230H15Z" fill="#8DBB72" opacity=".7"/><path d="M0 220Q85 145 145 205T260 180V230H0Z" fill="#F2C94C"/><circle cx="190" cy="52" r="27" fill="#F2C94C"/><path d="M58 175V90m0 38q-37-2-36-39 37 0 36 39Zm0-17q37-2 36-39-37 0-36 39Z" fill="#A9D69A" stroke="#fff" stroke-width="3"/><path d="M115 190v-50m0 18q-27-2-27-29 27 0 27 29Zm0-12q28-2 28-29-28 0-28 29Z" fill="#d5e9ad" stroke="#fff" stroke-width="3"/></svg>
</section>

<section class="container section">
    <div class="section-head">
        <div><span class="eyebrow">Lo que pasa en San Luquitas</span><h2>Últimas novedades</h2><p>Noticias para estar conectados con nuestra comunidad.</p></div>
        <a class="card-link" href="{{ route('novedades.index') }}">Ver todas las novedades →</a>
    </div>
    <div class="grid">
        @forelse ($novedades as $novedad)
            <article class="card">
                <span class="{{ $novedad->claseEtiqueta() }}">{{ $novedad->categoria }}</span>
                <div class="card-meta"><span>📅 {{ $novedad->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }}</span></div>
                <h3>{{ $novedad->titulo }}</h3>
                <p>{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 140) }}</p>
                <a class="card-link" href="{{ route('novedades.show', $novedad) }}">Leer novedad →</a>
            </article>
        @empty
            <div class="empty-state">Aún no hay novedades publicadas.</div>
        @endforelse
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <div><span class="eyebrow">Aprender jugando</span><h2>Un mundo de recursos</h2><p>{{ $totalRecursos }} recursos para practicar en clase o en casa.</p></div>
    </div>
    <div class="grid">
        <a class="card" href="{{ route('recursos.index', ['categoria' => 'Programación visual']) }}"><span class="card-icon">🧩</span><h3>Programación visual</h3><p>Descubre cómo dar instrucciones y crear historias paso a paso.</p><span class="card-link">Ir a recursos →</span></a>
        <a class="card" href="{{ route('recursos.index', ['categoria' => 'Pensamiento computacional']) }}"><span class="card-icon">🧠</span><h3>Pensamiento computacional</h3><p>Resuelve retos usando lógica, patrones y creatividad.</p><span class="card-link">Ir a recursos →</span></a>
        <a class="card" href="{{ route('recursos.index', ['categoria' => 'Para docentes']) }}"><span class="card-icon">🌱</span><h3>Material para docentes</h3><p>Ideas y guías para acompañar el aprendizaje.</p><span class="card-link">Ir a recursos →</span></a>
    </div>
</section>

<section class="container section">
    <div class="notice">
        <span class="card-icon">💬</span>
        <div>
            <strong>¿No tienes internet? ¡No te quedes por fuera!</strong>
            <p>Estudiantes, acudientes y profesores pueden registrar las tareas por WhatsApp escribiendo al número institucional <strong>{{ config('portal.whatsapp') }}</strong>. Si no hay señal, el mensaje se envía solo cuando vuelva la conexión.</p>
        </div>
    </div>
</section>
@endsection
