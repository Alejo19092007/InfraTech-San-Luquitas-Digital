<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('portal.institucion') }}</title>
    {{-- Recursos locales: el portal funciona en la LAN aunque no haya internet --}}
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark navbar-sl">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('inicio') }}">🎓 San Luquitas</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">Inicio</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('institucional') ? 'active' : '' }}" href="{{ route('institucional') }}">Institucional</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('novedades.*') ? 'active' : '' }}" href="{{ route('novedades.index') }}">Novedades</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('recursos.*') ? 'active' : '' }}" href="{{ route('recursos.index') }}">Recursos</a></li>
                {{-- Aquí Samuel agrega "Tareas" e "Iniciar sesión" --}}
                @stack('menu')
            </ul>
            <a class="btn btn-moodle" href="{{ config('portal.moodle_url') }}">Ir al aula virtual (Moodle)</a>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
    @if (session('estado'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('estado') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @yield('contenido')
</main>

<footer class="border-top py-3 bg-white">
    <div class="container small d-flex flex-wrap justify-content-between gap-2">
        <span>{{ config('portal.institucion') }} · Vereda El Progreso · Caso académico ficticio</span>
        <span>Portal institucional · Laravel {{ app()->version() }}</span>
    </div>
</footer>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
