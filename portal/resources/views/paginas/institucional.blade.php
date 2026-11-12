@extends('layouts.app')
@section('titulo', 'Información institucional')

@section('contenido')
<h1 class="h3 mb-4">Información institucional</h1>

<div class="row g-4 mb-4">
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <h2 class="h5">🎯 Misión</h2>
        <p class="mb-0">Formar niños y niñas de la zona rural con pensamiento crítico, valores de convivencia y competencias digitales, aprovechando la tecnología para cerrar las brechas de acceso a la educación de calidad.</p>
    </div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <h2 class="h5">🔭 Visión</h2>
        <p class="mb-0">En 2030 seremos una institución rural referente en el departamento por integrar la programación y el pensamiento computacional en la básica primaria, con una comunidad educativa conectada.</p>
    </div></div></div>
</div>

<div class="card mb-4"><div class="card-body">
    <h2 class="h5">📋 Datos generales</h2>
    <table class="table table-sm mb-0">
        <tbody>
            <tr><th class="w-25">Nombre</th><td>{{ config('portal.institucion') }}</td></tr>
            <tr><th>Carácter</th><td>Oficial · Zona rural</td></tr>
            <tr><th>Niveles</th><td>Preescolar y básica primaria (escuela nueva / multigrado)</td></tr>
            <tr><th>Jornada</th><td>Única (7:00 a. m. – 1:00 p. m.)</td></tr>
            <tr><th>Estudiantes</th><td>186</td></tr>
            <tr><th>Docentes</th><td>9</td></tr>
            <tr><th>Rector</th><td>Lic. Martha Lucía Pérez (ficticio)</td></tr>
        </tbody>
    </table>
</div></div>

<h2 class="h5 mb-3">🏫 Sedes</h2>
<div class="row g-3">
    @foreach ([
        ['Sede principal', 'Vereda El Progreso, km 12 vía al municipio', 'Preescolar a 5.º · sala de informática con 15 equipos'],
        ['Sede La Esperanza', 'Vereda La Esperanza', 'Escuela multigrado de 1.º a 5.º'],
        ['Sede Las Palmas', 'Vereda Las Palmas', 'Escuela multigrado de 1.º a 5.º · conectividad satelital intermitente'],
    ] as [$nombre, $ubicacion, $detalle])
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h3 class="h6">{{ $nombre }}</h3>
            <p class="small text-muted mb-1">{{ $ubicacion }}</p>
            <p class="small mb-0">{{ $detalle }}</p>
        </div></div></div>
    @endforeach
</div>
<p class="small text-muted mt-4">Toda la información de esta institución es ficticia y se usa con fines académicos.</p>
@endsection
