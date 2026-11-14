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
        <h1>{{ config('portal.institucion') }}</h1>
        <p>Preescolar y primaria en la vereda El Arrayán. Este año los grados 4.º y 5.º empezaron a programar en el aula virtual.</p>
        <div class="banner-acciones">
            <a class="boton boton-amarillo" href="{{ config('portal.moodle_url') }}">Ingresar al aula virtual</a>
            <a class="boton boton-blanco" href="{{ route('institucional') }}">Conocer la institución</a>
        </div>
    </div>
    @if (file_exists(public_path($portada['archivo'])) && $portada['autor'])
        <p class="banner-credito">Foto: <a href="{{ $portada['fuente'] }}" rel="noopener">{{ $portada['autor'] }}</a>, {{ $portada['licencia'] }}</p>
    @endif
</section>

<nav class="accesos envoltura" aria-label="Accesos rápidos">
    <ul>
        <li><a href="{{ config('portal.moodle_url') }}">
            <span><strong>Aula virtual</strong><small>Cursos en Moodle</small></span></a></li>
        <li><a class="acc-verde" href="{{ route('novedades.index') }}">
            <span><strong>Noticias</strong><small>Avisos y circulares</small></span></a></li>
        <li><a class="acc-amarillo" href="{{ route('recursos.index') }}">
            <span><strong>Recursos educativos</strong><small>{{ $totalRecursos }} actividades</small></span></a></li>
        <li><a class="acc-morado" href="#whatsapp">
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
                <li><strong>68</strong><span>estudiantes</span></li>
                <li><strong>6</strong><span>docentes</span></li>
                <li><strong>12</strong><span>computadores en la sala</span></li>
            </ul>
            <ul class="lista-puntos">
                <li>Curso “Mis primeros pasos en programación” para 4.º y 5.º.</li>
                <li>Cada estudiante tiene su usuario y avanza a su ritmo.</li>
                <li>Copia de seguridad automática todas las noches.</li>
            </ul>
            <a class="boton" href="{{ config('portal.moodle_url') }}">Ingresar al aula virtual</a>
        </div>
    </div>
</section>

<section class="seccion" id="whatsapp">
    <div class="envoltura">
        <div class="whatsapp">
            <div>
                <h2>Registra las tareas por WhatsApp, incluso sin señal</h2>
                <p>Estudiantes, acudientes y docentes escriben al <span class="numero">{{ config('portal.whatsapp') }}</span>. Si el celular no tiene conexión, WhatsApp guarda el mensaje y lo envía cuando vuelva. La tarea queda en el portal y el sistema responde con la confirmación.</p>
            </div>
            <div class="chat" aria-label="Ejemplo de conversación">
                <p class="chat-yo">tarea 5B matemáticas guía de fracciones para el viernes</p>
                <p class="chat-bot">Tarea registrada para 5.º B, Matemáticas: “guía de fracciones”, entrega viernes 21 de noviembre.</p>
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
            <li><a href="https://www.computadoresparaeducar.gov.co" rel="noopener"><strong>Computadores para Educar</strong><small>Tecnología para las escuelas</small></a></li>
            <li><a href="https://scratch.mit.edu" rel="noopener"><strong>Scratch</strong><small>Programación con bloques</small></a></li>
        </ul>
    </div>
</section>
@endsection
