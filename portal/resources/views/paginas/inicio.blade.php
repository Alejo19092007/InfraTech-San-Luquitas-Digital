@extends('layouts.app')

@section('contenido')
<section class="portada">
    <div class="envoltura">
        <div>
            <h1>Aprender en la vereda, conectados con el mundo.</h1>
            <p class="entrada">En San Luquitas los niños de 4.º y 5.º dan sus primeros pasos en programación desde el aula virtual. Las familias siguen la vida de la escuela aquí, aunque la señal vaya y venga.</p>
            <div class="portada-acciones">
                <a class="boton boton-maiz" href="{{ config('portal.moodle_url') }}">Entrar al aula virtual</a>
                <a class="boton boton-borde" href="{{ route('novedades.index') }}">Leer la cartelera</a>
            </div>
        </div>
        @include('partials.foto', ['clave' => 'a-clase', 'carga' => 'eager'])
    </div>
</section>

<nav class="zocalo" aria-label="Accesos principales">
    <ul class="envoltura">
        <li><a href="{{ config('portal.moodle_url') }}">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/></svg>
            <strong>Aula virtual</strong><span>Curso "Mis primeros pasos en programación"</span></a></li>
        <li><a href="{{ route('novedades.index') }}">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="1.5"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
            <strong>Cartelera</strong><span>Avisos, eventos y reuniones</span></a></li>
        <li><a href="{{ route('recursos.index') }}">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5M8 7h7"/></svg>
            <strong>Recursos</strong><span>{{ $totalRecursos }} actividades para clase y casa</span></a></li>
        <li><a href="#whatsapp">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.2 18.6z"/><path d="M9 10h6M9 13h4"/></svg>
            <strong>Tareas por WhatsApp</strong><span>Funciona aunque no haya señal</span></a></li>
    </ul>
</nav>

<section class="seccion">
    <div class="envoltura dos-columnas">
        <header>
            <h2 class="seccion-titulo">Cartelera</h2>
            <p class="seccion-intro">Lo que está pasando en las tres sedes.</p>
            <a class="ver-todo" href="{{ route('novedades.index') }}">Ver todos los avisos</a>
        </header>
        <ol class="cartelera">
            @forelse ($novedades as $novedad)
                @include('partials.aviso', ['novedad' => $novedad])
            @empty
                <li class="vacio">Todavía no hay avisos publicados.</li>
            @endforelse
        </ol>
    </div>
</section>

<section class="seccion">
    <div class="envoltura con-foto">
        @include('partials.foto', ['clave' => 'sala-computo'])
        <div>
            <h2 class="seccion-titulo">Un aula virtual que no depende de la señal</h2>
            <p class="seccion-intro">San Luquitas Digital vive en un servidor dentro de la escuela. Los computadores de la sala se conectan a él por la red local, así la clase sigue aunque se caiga el internet de la vereda.</p>
            <ul class="lista-puntos">
                <li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Tres unidades: algoritmos, pensamiento computacional y programación con bloques.</span></li>
                <li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Cada estudiante tiene su usuario y avanza a su ritmo.</span></li>
                <li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg><span>Copia de seguridad automática todas las noches.</span></li>
            </ul>
        </div>
    </div>
</section>

<section class="seccion" id="whatsapp">
    <div class="envoltura">
        <div class="whatsapp">
            <h2>¿Sin internet en la casa? Igual puedes registrar las tareas.</h2>
            <p>Estudiantes, acudientes y profesores escriben al número de la escuela, <span class="numero">{{ config('portal.whatsapp') }}</span>. Si el celular no tiene señal, WhatsApp guarda el mensaje y lo envía apenas vuelva la conexión. La tarea aparece en el portal y el bot confirma que quedó registrada.</p>
        </div>
    </div>
</section>
@endsection
