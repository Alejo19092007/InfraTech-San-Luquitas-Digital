@extends('layouts.app')
@section('titulo', 'Nuestra institución')

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / Nuestra institución</p>
    <header class="cabeza-pagina">
        <h1>Nuestra institución</h1>
        <p>Somos una institución oficial rural de preescolar y básica primaria. Trabajamos con el modelo Escuela Nueva: grupos multigrado, guías de aprendizaje y participación de las familias.</p>
    </header>

    <section class="proposito">
        <div>
            <h2>Misión</h2>
            <p>Brindar una educación cercana y de calidad que fortalezca los saberes, los valores y las capacidades de nuestros estudiantes, en diálogo con la cultura campesina, el cuidado del entorno y las competencias digitales.</p>
        </div>
        <div>
            <h2>Visión</h2>
            <p>En 2030 seremos una institución rural reconocida en el departamento por integrar la programación y el pensamiento computacional desde la primaria, con familias que participan y una comunidad conectada.</p>
        </div>
    </section>
</div>

<section class="seccion">
    <div class="envoltura con-foto">
        <div>
            <h2 class="seccion-titulo">Datos generales</h2>
            <dl class="datos">
                <div><dt>Rectora</dt><dd>María Fernanda Rojas</dd></div>
                <div><dt>Carácter</dt><dd>Oficial, rural</dd></div>
                <div><dt>Estudiantes</dt><dd>186</dd></div>
                <div><dt>Docentes</dt><dd>12</dd></div>
                <div><dt>Niveles</dt><dd>Preescolar a 5.º</dd></div>
                <div><dt>Jornada</dt><dd>Única, 7:00 a. m. a 1:00 p. m.</dd></div>
            </dl>
        </div>
        @include('partials.foto', ['clave' => 'estudiantes'])
    </div>
</section>

<section class="seccion seccion-gris">
    <div class="envoltura">
        <div class="seccion-cabeza">
            <h2 class="seccion-titulo">Sedes</h2>
        </div>
        <ul class="sedes">
            @foreach ([
                ['El Arrayán (sede principal)', 'Preescolar a 5.º. Sala de informática con 15 equipos y el servidor del aula virtual.'],
                ['La Esperanza', 'Escuela multigrado de 1.º a 5.º. Conectada a la sede principal por radioenlace.'],
                ['Alto del Roble', 'Escuela multigrado de 1.º a 5.º. Internet satelital intermitente.'],
            ] as [$sede, $detalle])
                <li class="sede">
                    <h3><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>{{ $sede }}</h3>
                    <p>{{ $detalle }}</p>
                </li>
            @endforeach
        </ul>
        <p class="nota">La institución y sus datos son ficticios; se usan con fines académicos.</p>
    </div>
</section>
@endsection
