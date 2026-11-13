<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d4f91">
    <meta name="description" content="@yield('descripcion', 'Portal de la '.config('portal.institucion').': novedades, recursos educativos y acceso al aula virtual.')">
    <title>@hasSection('titulo')@yield('titulo') · @endif{{ config('portal.institucion') }}</title>
    {{-- Todo se sirve desde el propio servidor (fuentes incluidas): funciona en la LAN sin internet --}}
    <link rel="preload" href="{{ asset('fonts/bricolage-grotesque.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v=2">
    <script src="{{ asset('js/portal.js') }}?v=2" defer></script>
</head>
<body>
<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="cabecera">
    <div class="envoltura">
        <a class="marca" href="{{ route('inicio') }}">
            {{-- Escuela de vereda: techo a dos aguas y zócalo --}}
            <svg width="40" height="40" viewBox="0 0 40 40" aria-hidden="true">
                <rect width="40" height="40" rx="6" fill="#1d4f91"/>
                <path d="M8 19 20 9l12 10" fill="none" stroke="#f2b632" stroke-width="3" stroke-linejoin="round"/>
                <rect x="11" y="18" width="18" height="13" fill="#fff"/>
                <rect x="11" y="26" width="18" height="5" fill="#f2b632"/>
                <rect x="18" y="22" width="4" height="9" fill="#1d4f91"/>
            </svg>
            <span><span class="marca-nombre">San Luquitas</span><span class="marca-sub">Institución Educativa rural</span></span>
        </a>
        <button class="menu-abrir" type="button" data-menu-abrir aria-expanded="false" aria-controls="menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            Menú
        </button>
        <nav class="menu" id="menu" aria-label="Principal">
            <a href="{{ route('inicio') }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('novedades.index') }}" @if (request()->routeIs('novedades.*')) aria-current="page" @endif>Cartelera</a>
            <a href="{{ route('recursos.index') }}" @if (request()->routeIs('recursos.*')) aria-current="page" @endif>Recursos</a>
            <a href="{{ route('institucional') }}" @if (request()->routeIs('institucional')) aria-current="page" @endif>La escuela</a>
            {{-- Samuel agrega aquí "Tareas" e "Iniciar sesión" con @push('menu') --}}
            @stack('menu')
            <a class="boton" href="{{ config('portal.moodle_url') }}">Aula virtual</a>
        </nav>
    </div>
</header>

<main id="contenido">
    @if (session('estado'))
        <div class="envoltura">
            <p class="alerta alerta-exito" role="status">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                {{ session('estado') }}
            </p>
        </div>
    @endif

    @yield('contenido')
</main>

<footer class="pie-pagina">
    <div class="envoltura">
        <div>
            <h2>{{ config('portal.institucion') }}</h2>
            <p>Preescolar y básica primaria en tres sedes rurales.</p>
            <p>{{ config('portal.municipio') }}, Colombia.</p>
        </div>
        <div>
            <h3>En este portal</h3>
            <ul>
                <li><a href="{{ route('novedades.index') }}">Cartelera</a></li>
                <li><a href="{{ route('recursos.index') }}">Recursos educativos</a></li>
                <li><a href="{{ route('institucional') }}">La escuela</a></li>
                <li><a href="{{ config('portal.moodle_url') }}">Aula virtual San Luquitas Digital</a></li>
            </ul>
        </div>
        <div>
            <h3>Tareas por WhatsApp</h3>
            <p>Escribe al <span class="numero">{{ config('portal.whatsapp') }}</span>. Si no tienes señal, el mensaje se envía cuando vuelva.</p>
        </div>
        <p class="creditos">Caso académico: la institución, sus datos y su número de WhatsApp son ficticios. Las fotografías son de escuelas rurales reales de Colombia, publicadas con licencia Creative Commons; cada una lleva su crédito.</p>
    </div>
</footer>
@stack('scripts')
</body>
</html>
