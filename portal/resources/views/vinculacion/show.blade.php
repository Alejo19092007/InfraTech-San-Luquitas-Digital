@extends('layouts.app')
@section('titulo', 'Vincular WhatsApp')

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / Mi WhatsApp</p>
    <header class="cabeza-pagina">
        <h1>Vincular mi WhatsApp</h1>
        <p>Para registrar tareas escribiendo al colegio, el bot debe saber que el número es tuyo. Son dos pasos y se hacen una sola vez.</p>
    </header>

    @if ($usuario->tieneWhatsappVerificado())
        <p class="alerta alerta-exito" role="status">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
            Tu número {{ $usuario->telefono_whatsapp }} está vinculado desde el {{ $usuario->telefono_verificado_at->locale('es')->isoFormat('D [de] MMMM') }}. Ya puedes escribirle al bot.
        </p>
    @endif

    @if ($codigo)
        <section class="codigo-caja" aria-labelledby="codigo-titulo">
            <h2 id="codigo-titulo">Paso 2: envía este código por WhatsApp</h2>
            <p class="codigo" aria-label="Código: {{ implode(' ', str_split($codigo)) }}">{{ substr($codigo, 0, 3) }} {{ substr($codigo, 3) }}</p>
            <p>Desde el número <strong>{{ $usuario->telefono_whatsapp }}</strong>, escribe al <span class="numero">{{ config('portal.whatsapp') }}</span>:</p>
            <p class="mensaje-ejemplo">VINCULAR {{ $codigo }}</p>
            <p class="tenue">El código sirve durante {{ \App\Models\CodigoVinculacion::VIGENCIA_HORAS }} horas. Si no tienes señal, envía el mensaje igual: WhatsApp lo entrega cuando vuelva la conexión.</p>
        </section>
    @elseif ($vigente && ! $usuario->tieneWhatsappVerificado())
        <p class="alerta alerta-aviso" role="status">Ya generaste un código que sigue vigente hasta las {{ $vigente->expira_at->format('g:i a') }} del {{ $vigente->expira_at->locale('es')->isoFormat('D [de] MMMM') }}. Por seguridad no se vuelve a mostrar: si lo perdiste, genera uno nuevo.</p>
    @endif

    @if ($errors->any())
        <p class="alerta alerta-error" role="alert">Revisa el número que escribiste.</p>
    @endif

    <form class="formulario" method="POST" action="{{ route('vincular.generar') }}" data-formulario novalidate>
        @csrf
        <div class="campo @error('telefono') invalido @enderror">
            <label for="telefono">Paso 1: tu número de WhatsApp <span class="obligatorio" aria-hidden="true">*</span></label>
            <input id="telefono" name="telefono" type="tel" inputmode="tel" autocomplete="tel" required
                   value="{{ old('telefono', $usuario->telefono_whatsapp) }}" placeholder="300 123 4567">
            <span class="ayuda">Celular colombiano o con indicativo (+57…). Es el número desde el que escribirás.</span>
            <span class="error">{{ $errors->first('telefono') ?: 'Escribe tu número.' }}</span>
        </div>
        <div class="botones">
            <button class="boton" type="submit">{{ $usuario->tieneWhatsappVerificado() ? 'Generar un código nuevo' : 'Generar código' }}</button>
        </div>
    </form>

    <section class="ayuda-bot" aria-labelledby="ayuda-bot-titulo">
        <h2 id="ayuda-bot-titulo">Qué puedes escribirle al bot</h2>
        <ul>
            <li><code>TAREA Matemáticas | 15/11 | Página 32 ejercicios 1-5</code> registra una tarea.</li>
            <li><code>MIS TAREAS</code> muestra lo que está pendiente.</li>
            <li><code>HECHA 12</code> marca la tarea 12 como entregada.</li>
            <li><code>AYUDA</code> muestra todos los comandos de tu rol.</li>
        </ul>
    </section>
</div>
@endsection
