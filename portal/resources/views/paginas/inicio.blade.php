@extends('layouts.app')

@section('contenido')
@php
    $portada = config('portal.fotos.paisaje');
@endphp
<section class="banner">
    @if (file_exists(public_path($portada['archivo'])))
        <img src="{{ asset($portada['archivo']) }}" alt="" width="1280" height="850" fetchpriority="high">
    @endif
    <div class="envoltura">
        <span class="banner-lema">{{ config('portal.lema') }}</span>
        <h1>Bienvenidos a la {{ config('portal.institucion') }}</h1>
        <p>Preescolar y básica primaria en tres sedes rurales. Nuestros estudiantes de 4.º y 5.º aprenden a programar en el aula virtual San Luquitas Digital.</p>
        <div class="banner-acciones">
            <a class="boton boton-amarillo" href="{{ config('portal.moodle_url') }}">Ingresar al aula virtual</a>
            <a class="boton boton-blanco" href="{{ route('institucional') }}">Conocer la institución</a>
        </div>
    </div>
    @if (file_exists(public_path($portada['archivo'])))
        <p class="banner-credito">Foto: <a href="{{ $portada['fuente'] }}" rel="noopener">{{ $portada['autor'] }}</a>, {{ $portada['licencia'] }}</p>
    @endif
</section>

<nav class="accesos envoltura" aria-label="Accesos rápidos">
    <ul>
        <li><a href="{{ config('portal.moodle_url') }}">
            <span class="icono"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/></svg></span>
            <span><strong>Aula virtual</strong><small>Cursos en Moodle</small></span></a></li>
        <li><a class="acc-verde" href="{{ route('novedades.index') }}">
            <span class="icono"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h13v14H6a2 2 0 0 1-2-2z"/><path d="M17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5"/></svg></span>
            <span><strong>Noticias</strong><small>Avisos y circulares</small></span></a></li>
        <li><a class="acc-amarillo" href="{{ route('recursos.index') }}">
            <span class="icono"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5M8 8h7"/></svg></span>
            <span><strong>Recursos educativos</strong><small>{{ $totalRecursos }} actividades</small></span></a></li>
        <li><a class="acc-morado" href="#whatsapp">
            <span class="icono"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.2 18.6z"/><path d="M9 10h6M9 13h4"/></svg></span>
            <span><strong>Tareas por WhatsApp</strong><small>Cómo registrarlas</small></span></a></li>
    </ul>
</nav>

<section class="seccion">
    <div class="envoltura">
        <div class="seccion-cabeza">
            <h2 class="seccion-titulo">Noticias</h2>
            <a class="ver-todo" href="{{ route('novedades.index') }}">Ver todas las noticias</a>
        </div>
        <ul class="noticias">
            @forelse ($novedades as $novedad)
                @include('partials.noticia', ['novedad' => $novedad])
            @empty
                <li class="vacio">Todavía no hay noticias publicadas.</li>
            @endforelse
        </ul>
    </div>
</section>

<section class="seccion seccion-gris">
    <div class="envoltura con-foto">
        @include('partials.foto', ['clave' => 'sala-computo'])
        <div>
            <h2 class="seccion-titulo">San Luquitas Digital</h2>
            <p class="seccion-intro">El aula virtual funciona en un servidor dentro de la escuela. Los computadores de la sala se conectan por la red local, así la clase sigue aunque se caiga el internet de la vereda.</p>
            <ul class="cifras">
                <li><strong>186</strong><span>estudiantes</span></li>
                <li><strong>12</strong><span>docentes</span></li>
                <li><strong>3</strong><span>sedes rurales</span></li>
            </ul>
            <ul class="lista-puntos">
                <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Curso “Mis primeros pasos en programación” para 4.º y 5.º.</span></li>
                <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Cada estudiante tiene su usuario y avanza a su ritmo.</span></li>
                <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Copia de seguridad automática todas las noches.</span></li>
            </ul>
            <a class="boton" href="{{ config('portal.moodle_url') }}">Ingresar al aula virtual</a>
        </div>
    </div>
</section>

<section class="seccion" id="whatsapp">
    <div class="envoltura">
        <div class="whatsapp">
            <span class="icono"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.2 18.6z"/><path d="M9 10h6M9 13h4"/></svg></span>
            <div>
                <h2>Registra las tareas por WhatsApp, incluso sin señal</h2>
                <p>Estudiantes, acudientes y docentes escriben al <span class="numero">{{ config('portal.whatsapp') }}</span>. Si el celular no tiene conexión, WhatsApp guarda el mensaje y lo envía cuando vuelva. La tarea queda en el portal y el sistema responde con la confirmación.</p>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion-gris">
    <div class="envoltura">
        <div class="seccion-cabeza"><h2 class="seccion-titulo">Enlaces de interés</h2></div>
        <ul class="enlaces">
            <li><a href="https://www.mineducacion.gov.co" rel="noopener"><strong>Ministerio de Educación Nacional</strong><small>mineducacion.gov.co</small></a></li>
            <li><a href="https://www.colombiaaprende.edu.co" rel="noopener"><strong>Colombia Aprende</strong><small>Contenidos educativos digitales</small></a></li>
            <li><a href="https://www.computadoresparaeducar.gov.co" rel="noopener"><strong>Computadores para Educar</strong><small>Tecnología para las sedes educativas</small></a></li>
            <li><a href="https://scratch.mit.edu" rel="noopener"><strong>Scratch</strong><small>Programación con bloques</small></a></li>
        </ul>
    </div>
</section>
@endsection
