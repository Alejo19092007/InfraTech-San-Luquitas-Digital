<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#174b35">
    <meta name="description" content="Portal de la {{ config('portal.institucion') }}">
    <title>@yield('titulo', 'Inicio') | {{ config('portal.institucion') }}</title>
    {{-- Todo se sirve desde el propio servidor: el portal funciona en la LAN sin internet --}}
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <script src="{{ asset('js/portal.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="{{ route('inicio') }}" aria-label="{{ config('portal.institucion') }}, inicio">
            <span class="brand-mark" aria-hidden="true"><svg width="27" height="27" viewBox="0 0 32 32" fill="none"><path d="M16 27V14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M16 18C7 18 5 11 6 5c7 0 12 3 10 13Z" fill="#8BCB85"/><path d="M16 22c9 0 12-7 11-13-7 0-12 3-11 13Z" fill="#F2C94C"/></svg></span>
            <span>San Luquitas<small>{{ config('portal.lema') }}</small></span>
        </a>
        <button class="menu-toggle" data-menu-toggle aria-expanded="false" aria-label="Abrir menú"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        <nav class="nav-links" data-nav aria-label="Navegación principal">
            <a href="{{ route('inicio') }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('institucional') }}" @if (request()->routeIs('institucional')) aria-current="page" @endif>Institucional</a>
            <a href="{{ route('novedades.index') }}" @if (request()->routeIs('novedades.*')) aria-current="page" @endif>Novedades</a>
            <a href="{{ route('recursos.index') }}" @if (request()->routeIs('recursos.*')) aria-current="page" @endif>Recursos</a>
            {{-- Aquí Samuel agrega "Tareas" e "Iniciar sesión" con @push('menu') --}}
            @stack('menu')
            <a class="moodle-link" href="{{ config('portal.moodle_url') }}" aria-label="Entrar al aula virtual Moodle">Entrar a Moodle ↗</a>
        </nav>
    </div>
</header>

<main id="contenido">
    @if (session('estado'))
        <div class="container"><div class="alert alert-success" role="status">✓ {{ session('estado') }}</div></div>
    @endif

    @yield('contenido')
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <strong>{{ config('portal.institucion') }}</strong>
            <p>Una escuela que aprende con su comunidad.</p>
            <p>Veredas de San Luquitas · Colombia · Caso académico ficticio</p>
        </div>
        <div class="footer-links">
            <a href="{{ route('institucional') }}">Conócenos</a>
            <a href="{{ route('novedades.index') }}">Novedades</a>
            <a href="{{ route('recursos.index') }}">Recursos</a>
            <a href="{{ config('portal.moodle_url') }}">Aula virtual</a>
        </div>
        <p>© {{ now()->year }} · Portal institucional</p>
    </div>
</footer>
@stack('scripts')
</body>
</html>
