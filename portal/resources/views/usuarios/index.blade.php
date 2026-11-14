@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / Usuarios</p>
    <header class="cabeza-pagina cabeza-con-accion">
        <div>
            <h1>Usuarios</h1>
            <p>Cuentas de estudiantes, acudientes, docentes y administración.</p>
        </div>
        <a class="boton" href="{{ route('usuarios.create') }}">Crear usuario</a>
    </header>

    @if ($errors->any())
        <p class="alerta alerta-error" role="alert">{{ $errors->first() }}</p>
    @endif

    <nav class="pestanas" aria-label="Filtrar por rol">
        <a href="{{ route('usuarios.index') }}" @if (! $rol) aria-current="page" @endif>Todos</a>
        @foreach (\App\Models\User::ETIQUETAS_ROL as $clave => $etiqueta)
            <a href="{{ route('usuarios.index', ['rol' => $clave]) }}" @if ($rol === $clave) aria-current="page" @endif>{{ $etiqueta }}</a>
        @endforeach
    </nav>

    <div class="tabla-envoltura">
        <table class="tabla">
            <caption class="solo-lectores">Usuarios registrados</caption>
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Correo</th>
                    <th scope="col">WhatsApp</th>
                    <th scope="col"><span class="solo-lectores">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($usuarios as $u)
                <tr>
                    <td data-label="Nombre"><strong>{{ $u->name }}</strong>@if ($u->grado)<br><span class="tenue">Grado {{ $u->grado }}</span>@endif</td>
                    <td data-label="Rol">{{ $u->etiquetaRol() }}</td>
                    <td data-label="Correo">{{ $u->email }}</td>
                    <td data-label="WhatsApp">
                        @if ($u->telefono_whatsapp)
                            {{ $u->telefono_whatsapp }}<br>
                            <span class="estado {{ $u->tieneWhatsappVerificado() ? 'estado-ok' : 'estado-pendiente' }}">{{ $u->tieneWhatsappVerificado() ? 'Vinculado' : 'Sin verificar' }}</span>
                        @else
                            <span class="tenue">Sin número</span>
                        @endif
                    </td>
                    <td class="celda-accion"><a class="boton boton-chico boton-borde" href="{{ route('usuarios.edit', $u) }}" aria-label="Editar a {{ $u->name }}">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $usuarios->links() }}
</div>
@endsection
