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
                <div><dt>Estudiantes</dt><dd>68</dd></div>
                <div><dt>Docentes</dt><dd>6, más la rectora</dd></div>
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
            <h2 class="seccion-titulo">Nuestra escuela</h2>
        </div>
        <ul class="sedes">
            @foreach ([
                ['Aulas multigrado', 'Tres salones donde se atienden preescolar y los grados 1.º a 5.º con guías de Escuela Nueva.'],
                ['Sala de informática', '12 computadores conectados por red local al servidor del aula virtual, que sigue funcionando aunque falle el internet.'],
                ['Huerta y restaurante escolar', 'La huerta la cuidan los estudiantes; con ella se complementa el almuerzo que se sirve en la escuela.'],
            ] as [$sede, $detalle])
                <li class="sede">
                    <h3>{{ $sede }}</h3>
                    <p>{{ $detalle }}</p>
                </li>
            @endforeach
        </ul>
        <p class="nota">La institución y sus datos son ficticios; se usan con fines académicos.</p>
    </div>
</section>
@endsection
