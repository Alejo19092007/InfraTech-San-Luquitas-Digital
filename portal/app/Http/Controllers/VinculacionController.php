<?php

namespace App\Http\Controllers;

use App\Models\CodigoVinculacion;
use App\Support\Telefono;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VinculacionController extends Controller
{
    public function mostrar(Request $request): View
    {
        return view('vinculacion.show', [
            'usuario' => $request->user(),
            'codigo' => session('codigo'),   // solo existe justo después de generarlo
            'vigente' => CodigoVinculacion::activoDe($request->user()),
        ]);
    }

    public function generar(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $request->validate(['telefono' => ['required', 'string', 'max:25']], [
            'telefono.required' => 'Escribe tu número de WhatsApp.',
        ]);

        $telefono = Telefono::normalizar($request->input('telefono'));

        if ($telefono === null) {
            throw ValidationException::withMessages(['telefono' => 'Escribe un número válido, por ejemplo 300 123 4567.']);
        }

        $otraCuenta = \App\Models\User::where('telefono_whatsapp', $telefono)->whereKeyNot($usuario->id)->exists();

        if ($otraCuenta) {
            throw ValidationException::withMessages(['telefono' => 'Ese número ya está vinculado a otra cuenta. Pide ayuda en secretaría.']);
        }

        // Si cambia el número, deja de estar verificado hasta que envíe el código desde el nuevo.
        if ($usuario->telefono_whatsapp !== $telefono) {
            $usuario->forceFill(['telefono_whatsapp' => $telefono, 'telefono_verificado_at' => null])->save();
        }

        return redirect()->route('vincular')->with('codigo', CodigoVinculacion::generarPara($usuario));
    }
}
