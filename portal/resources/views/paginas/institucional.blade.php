@extends('layouts.app')
@section('titulo', 'Información institucional')

@section('contenido')
<div class="container page-intro">
    <span class="eyebrow">Nuestra comunidad</span>
    <h1>Una escuela, muchas veredas</h1>
    <p>Somos una institución rural que aprende de su territorio y acompaña a niñas y niños a descubrir todo lo que pueden lograr.</p>
</div>

<div class="container section"><div class="info-grid">
    <section class="info-panel"><span class="card-icon">🎯</span><h2>Nuestra misión</h2><p>Brindar una educación inclusiva, cercana y de calidad que fortalezca los saberes, valores y capacidades de nuestros estudiantes, en diálogo con la cultura campesina, el cuidado del entorno y las competencias digitales.</p></section>
    <section class="info-panel"><span class="card-icon">🌄</span><h2>Nuestra visión</h2><p>Para 2030 seremos una comunidad educativa reconocida por formar personas solidarias, creativas y comprometidas con el desarrollo sostenible de sus veredas, integrando la programación y el pensamiento computacional desde la primaria.</p></section>
</div></div>

<div class="container section">
    <div class="section-head"><div><span class="eyebrow">En cifras</span><h2>Datos generales</h2></div></div>
    <table class="data-table"><tbody>
        @foreach ([
            'Nombre' => config('portal.institucion'),
            'Carácter' => 'Oficial · Zona rural',
            'Rector(a)' => 'María Fernanda Rojas',
            'Estudiantes' => '186',
            'Docentes' => '12',
            'Jornada' => 'Mañana · lunes a viernes',
            'Niveles' => 'Preescolar y básica primaria (escuela nueva / multigrado)',
        ] as $dato => $valor)
            <tr><th scope="row">{{ $dato }}</th><td>{{ $valor }}</td></tr>
        @endforeach
    </tbody></table>
</div>

<div class="container section">
    <div class="section-head"><div><span class="eyebrow">Llegamos más lejos</span><h2>Nuestras tres sedes rurales</h2><p>Cada sede es un punto de encuentro para aprender y compartir.</p></div></div>
    <div class="site-list">
        @foreach ([
            ['Sede El Arrayán (principal)', 'Vereda El Arrayán · Grados 0.º a 5.º · sala de informática y servidor local'],
            ['Sede La Esperanza', 'Vereda La Esperanza · Grados 0.º a 5.º · escuela multigrado'],
            ['Sede Alto del Roble', 'Vereda Alto del Roble · Grados 0.º a 5.º · conectividad satelital intermitente'],
        ] as [$sede, $detalle])
            <article class="site-card"><span class="card-icon">📍</span><h3>{{ $sede }}</h3><p>{{ $detalle }}</p></article>
        @endforeach
    </div>
    <p class="breadcrumb">Toda la información de esta institución es ficticia y se usa con fines académicos.</p>
</div>
@endsection
