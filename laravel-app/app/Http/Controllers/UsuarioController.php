<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Mostrar listado de usuarios.
     */
    public function index(): View
    {
        $usuarios = Usuario::orderBy('nombre')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Mostrar formulario para crear usuario.
     */
    public function create(): View
    {
        return view('usuarios.create');
    }

    /**
     * Guardar nuevo usuario.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'        => 'required|string|max:100',
            'apellido'      => 'required|string|max:100',
            'nombre_usuario'=> 'required|string|max:50|unique:usuario,nombre_usuario',
            'clave_usuario' => 'required|string|min:6|max:255',
            'correo'        => 'nullable|email|max:150|unique:usuario,correo',
            'celular'       => 'nullable|string|max:15',
            'id_rol'        => 'required|integer|exists:rol,id_rol',
        ]);

        // Encriptar contraseña
        $data['clave_usuario'] = Hash::make($data['clave_usuario']);

        Usuario::create($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Mostrar formulario para editar usuario.
     */
    public function edit(int $id): View
    {
        $usuario = Usuario::findOrFail($id);
        return view('usuarios.edit', compact('usuario'));
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'nombre'        => 'required|string|max:100',
            'apellido'      => 'required|string|max:100',
            'nombre_usuario'=> 'required|string|max:50|unique:usuario,nombre_usuario,' . $id . ',id_usuario',
            'correo'        => 'nullable|email|max:150|unique:usuario,correo,' . $id . ',id_usuario',
            'celular'       => 'nullable|string|max:15',
            'id_rol'        => 'required|integer|exists:rol,id_rol',
        ]);

        // Si se proporciona nueva contraseña, encriptarla
        if ($request->filled('clave_usuario')) {
            $request->validate(['clave_usuario' => 'required|string|min:6|max:255']);
            $data['clave_usuario'] = Hash::make($request->input('clave_usuario'));
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Eliminar usuario.
     */
    public function destroy(int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        // Verificar que no sea el usuario autenticado
        if ($usuario->id_usuario === auth()->user()->id_usuario) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
