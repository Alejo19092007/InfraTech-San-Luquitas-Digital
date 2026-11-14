@extends('layouts.app')
@section('titulo', 'Iniciar sesión')
@section('descripcion', 'Ingreso al portal para estudiantes, acudientes, docentes y administración.')

@section('contenido')
<div class="envoltura">
    <header class="cabeza-pagina">
        <h1>Iniciar sesión</h1>
        <p>Entra para ver y registrar las tareas. Si no tienes cuenta, pídela en secretaría.</p>
    </header>

    <form class="formulario" method="POST" action="{{ route('login.enviar') }}" data-formulario novalidate>
        @csrf

        <div class="campo @error('email') invalido @enderror">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <span class="error" id="email-error">{{ $errors->first('email') }}</span>
        </div>

        <div class="campo @error('password') invalido @enderror">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <span class="error" id="password-error">{{ $errors->first('password') }}</span>
        </div>

        <div class="campo campo-check">
            <label><input type="checkbox" name="recordar" value="1" @checked(old('recordar'))> Mantener la sesión abierta en este dispositivo</label>
        </div>

        <div class="botones">
            <button class="boton" type="submit">Entrar</button>
            <a class="boton boton-borde" href="{{ route('inicio') }}">Volver al inicio</a>
        </div>
    </form>
</div>
@endsection
