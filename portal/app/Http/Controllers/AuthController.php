<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function mostrarLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido, por ejemplo nombre@sanluquitas.local.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        if (! Auth::attempt($datos, $request->boolean('recordar'))) {
            // Mismo mensaje para correo inexistente y contraseña errónea: no revela qué cuentas existen.
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tareas.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('estado', 'Cerraste la sesión.');
    }
}
