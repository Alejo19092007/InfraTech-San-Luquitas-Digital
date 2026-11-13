@extends('layouts.app')
@section('titulo', 'La escuela')

@section('contenido')
<div class="envoltura">
    <header class="cabeza-pagina">
        <h1>Una escuela, tres veredas</h1>
        <p>San Luquitas es una institución oficial rural de preescolar y básica primaria. Trabajamos con el modelo Escuela Nueva: grupos multigrado, guías de aprendizaje y la comunidad metida en la escuela.</p>
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
                <div><dt>Estudiantes</dt><dd>186</dd></div>
                <div><dt>Docentes</dt><dd>12</dd></div>
                <div><dt>Jornada</dt><dd>Única, 7:00 a 1:00</dd></div>
                <div><dt>Carácter</dt><dd>Oficial, rural</dd></div>
                <div><dt>Niveles</dt><dd>Preescolar a 5.º</dd></div>
            </dl>
        </div>
        @include('partials.foto', ['clave' => 'estudiantes'])
    </div>
</section>

<section class="seccion">
    <div class="envoltura">
        <h2 class="seccion-titulo">Sedes</h2>
        <p class="seccion-intro">Cada sede es multigrado. El servidor y la sala de informática están en la sede principal.</p>
        <ul class="sedes">
            <li class="sede sede-principal"><h3>El Arrayán (principal)</h3><p>Preescolar a 5.º. Sala de informática con 15 equipos y el servidor del aula virtual.</p></li>
            <li class="sede"><h3>La Esperanza</h3><p>Escuela multigrado de 1.º a 5.º. Se conecta a la sede principal por radioenlace.</p></li>
            <li class="sede"><h3>Alto del Roble</h3><p>Escuela multigrado de 1.º a 5.º. Internet satelital intermitente.</p></li>
        </ul>
        <p class="nota">La institución y sus datos son ficticios; se usan con fines académicos.</p>
    </div>
</section>
@endsection
