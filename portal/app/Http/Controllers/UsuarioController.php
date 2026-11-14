<?php

namespace App\Http\Controllers;

use App\Models\AsignacionProfesor;
use App\Models\User;
use App\Support\Telefono;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Gestión de usuarios: solo el rol admin (secretaría). */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $rol = $request->query('rol');

        $usuarios = User::query()
            ->when(in_array($rol, User::ROLES, true), fn ($q) => $q->where('rol', $rol))
            ->orderByRaw("case rol when 'admin' then 0 when 'profesor' then 1 when 'acudiente' then 2 else 3 end")
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('usuarios.index', ['usuarios' => $usuarios, 'rol' => $rol]);
    }

    public function create(): View
    {
        return view('usuarios.form', $this->datosDeFormulario(new User(['rol' => 'estudiante'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request, new User);

        DB::transaction(function () use ($datos, $request) {
            $usuario = User::create($datos);
            $this->sincronizarRelaciones($usuario, $request);
        });

        return redirect()->route('usuarios.index')->with('estado', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', $this->datosDeFormulario($usuario));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);

        // Un administrador no puede quitarse a sí mismo el rol: se quedaría sin acceso a esta pantalla.
        if ($usuario->is($request->user()) && $datos['rol'] !== 'admin') {
            return back()->withInput()->withErrors(['rol' => 'No puedes cambiar tu propio rol de administración.']);
        }

        // Si cambió el número, hay que volver a verificarlo.
        if (($datos['telefono_whatsapp'] ?? null) !== $usuario->telefono_whatsapp) {
            $datos['telefono_verificado_at'] = null;
        }

        DB::transaction(function () use ($usuario, $datos, $request) {
            $usuario->update($datos);
            $this->sincronizarRelaciones($usuario, $request);
        });

        return redirect()->route('usuarios.index')->with('estado', 'Cambios guardados.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes eliminar tu propia cuenta.']);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('estado', 'Usuario eliminado.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, User $usuario): array
    {
        $escrito = $request->input('telefono_whatsapp');
        $telefono = Telefono::normalizar($escrito);

        if (filled($escrito) && $telefono === null) {
            throw ValidationException::withMessages(['telefono_whatsapp' => 'Escribe un número válido, por ejemplo 300 123 4567.']);
        }

        $request->merge(['telefono_whatsapp' => $telefono]);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => [$usuario->exists ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
            'rol' => ['required', Rule::in(User::ROLES)],
            'grado' => ['nullable', 'required_if:rol,estudiante', 'integer', 'between:1,11'],
            'telefono_whatsapp' => ['nullable', 'string', Rule::unique('users', 'telefono_whatsapp')->ignore($usuario->id)],
            'hijos' => ['nullable', 'array'],
            'hijos.*' => ['integer', Rule::exists('users', 'id')->where('rol', 'estudiante')],
            'grados' => ['nullable', 'string', 'max:40', 'regex:/^\s*\d{1,2}(\s*[,;]\s*\d{1,2})*\s*$/'],
            'materia' => ['nullable', 'string', 'max:60'],
        ], [
            'name.required' => 'Escribe el nombre completo.',
            'email.required' => 'Escribe el correo.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'Escribe una contraseña de al menos 8 caracteres.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'grado.required_if' => 'Elige el grado del estudiante.',
            'telefono_whatsapp.unique' => 'Ese número ya pertenece a otra cuenta.',
            'grados.regex' => 'Escribe los grados separados por comas. Ejemplo: 4, 5.',
        ]);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        // Solo los estudiantes tienen grado propio; los docentes tienen asignaciones.
        if ($datos['rol'] !== 'estudiante') {
            $datos['grado'] = null;
        }

        return collect($datos)->only(['name', 'email', 'password', 'rol', 'grado', 'telefono_whatsapp'])->all();
    }

    private function sincronizarRelaciones(User $usuario, Request $request): void
    {
        $usuario->hijos()->sync($usuario->rol === 'acudiente' ? ($request->input('hijos') ?? []) : []);

        $usuario->asignaciones()->delete();

        if ($usuario->rol === 'profesor') {
            $grados = collect(preg_split('/[,;]/', (string) $request->input('grados')))
                ->map(fn ($g) => (int) trim($g))->filter(fn ($g) => $g >= 1 && $g <= 11)->unique();

            foreach ($grados as $grado) {
                AsignacionProfesor::create([
                    'profesor_id' => $usuario->id,
                    'grado' => $grado,
                    'materia' => $request->input('materia') ?: null,
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function datosDeFormulario(User $usuario): array
    {
        return [
            'usuario' => $usuario,
            'estudiantes' => User::where('rol', 'estudiante')->orderBy('grado')->orderBy('name')->get(),
            'hijosActuales' => $usuario->exists ? $usuario->hijos()->pluck('users.id')->all() : [],
            'gradosActuales' => $usuario->exists ? implode(', ', $usuario->gradosQueDicta()) : '',
            'materiaActual' => $usuario->exists ? $usuario->asignaciones()->value('materia') : '',
        ];
    }
}
