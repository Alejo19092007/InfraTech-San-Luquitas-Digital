<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b3d7a">
    <meta name="description" content="@yield('descripcion', 'Portal de la '.config('portal.institucion').': noticias, recursos educativos y acceso al aula virtual.')">
    <title>@hasSection('titulo')@yield('titulo') | @endif{{ config('portal.institucion') }}</title>
    {{-- Aplica antes de pintar el tamaño de letra y el contraste que eligió la persona --}}
    <script>
        try {
            var r = document.documentElement, t = localStorage.getItem('sl-tema'), l = localStorage.getItem('sl-letra');
            if (t) r.classList.add(t);
            if (l) r.dataset.letra = l;
        } catch (e) {}
    </script>
    {{-- Todo se sirve desde el propio servidor (fuentes incluidas): funciona en la LAN sin internet --}}
    <link rel="preload" href="{{ asset('fonts/montserrat.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v=3">
    <script src="{{ asset('js/portal.js') }}?v=3" defer></script>
</head>
<body>
<a class="saltar" href="#contenido">Saltar al contenido</a>

<div class="utilidad">
    <div class="envoltura">
        <p>{{ config('portal.horario') }} · Tel. {{ config('portal.telefono') }}</p>
        <div class="accesibilidad" role="group" aria-label="Opciones de accesibilidad">
            <span>Accesibilidad</span>
            <button type="button" data-letra="menos" aria-label="Reducir el tamaño de la letra">A−</button>
            <button type="button" data-letra="mas" aria-label="Aumentar el tamaño de la letra">A+</button>
            <button type="button" data-contraste aria-pressed="false">Contraste</button>
        </div>
    </div>
</div>

<header class="cabecera">
    <div class="envoltura">
        <a class="marca" href="{{ route('inicio') }}">
            @include('partials.escudo', ['tam' => 58])
            <span>
                <span class="marca-nombre">{{ config('portal.institucion') }}</span>
                <span class="marca-sub">{{ config('portal.lema') }}</span>
            </span>
        </a>
        <button class="menu-abrir" type="button" data-menu-abrir aria-expanded="false" aria-controls="menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            Menú
        </button>
    </div>
</header>

<div class="barra">
    <div class="envoltura">
        <nav class="menu" id="menu" aria-label="Principal">
            <a href="{{ route('inicio') }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('institucional') }}" @if (request()->routeIs('institucional')) aria-current="page" @endif>Nuestra institución</a>
            <a href="{{ route('novedades.index') }}" @if (request()->routeIs('novedades.*')) aria-current="page" @endif>Noticias</a>
            <a href="{{ route('recursos.index') }}" @if (request()->routeIs('recursos.*')) aria-current="page" @endif>Recursos educativos</a>
            {{-- Samuel agrega aquí "Tareas" e "Iniciar sesión" con @push('menu') --}}
            @stack('menu')
            <a class="menu-aula" href="{{ config('portal.moodle_url') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/></svg>
                Aula virtual
            </a>
        </nav>
    </div>
</div>

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

<footer>
    <div class="pie-pagina">
        <div class="envoltura">
            <div class="pie-marca">
                @include('partials.escudo', ['tam' => 52])
                <div>
                    <h2>{{ config('portal.institucion') }}</h2>
                    <p>{{ config('portal.direccion') }}</p>
                    <p>Teléfono: {{ config('portal.telefono') }}</p>
                    <p>Correo: {{ config('portal.correo') }}</p>
                    <p>Atención: {{ config('portal.horario') }}</p>
                </div>
            </div>
            <div>
                <h3>Portal</h3>
                <ul>
                    <li><a href="{{ route('institucional') }}">Nuestra institución</a></li>
                    <li><a href="{{ route('novedades.index') }}">Noticias</a></li>
                    <li><a href="{{ route('recursos.index') }}">Recursos educativos</a></li>
                    <li><a href="{{ config('portal.moodle_url') }}">Aula virtual San Luquitas Digital</a></li>
                </ul>
            </div>
            <div>
                <h3>Tareas por WhatsApp</h3>
                <p>Registra las tareas escribiendo al <span class="numero">{{ config('portal.whatsapp') }}</span>. Si no hay señal, el mensaje se envía cuando vuelva.</p>
            </div>
        </div>
    </div>
    <div class="pie-legal">
        <div class="envoltura">
            <p>© {{ now()->year }} {{ config('portal.institucion') }}. Caso académico: la institución y sus datos de contacto son ficticios. Las fotografías son de escuelas rurales reales de Colombia, con licencia Creative Commons y crédito en cada imagen.</p>
        </div>
    </div>
</footer>
@stack('scripts')
</body>
</html>
