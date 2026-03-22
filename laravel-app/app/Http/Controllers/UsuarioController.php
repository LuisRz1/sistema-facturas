<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = Usuario::orderBy('nombre')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create');
    }

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

        $data['clave_usuario'] = Hash::make($data['clave_usuario']);
        Usuario::create($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(int $id): View
    {
        $usuario = Usuario::findOrFail($id);
        return view('usuarios.edit', compact('usuario'));
    }

    /**
     * Actualizar usuario.
     * Soporta tanto peticiones normales (redirect) como AJAX (JSON).
     */
    public function update(Request $request, int $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $data = $request->validate([
                'nombre'        => 'required|string|max:100',
                'apellido'      => 'required|string|max:100',
                'nombre_usuario'=> 'required|string|max:50|unique:usuario,nombre_usuario,' . $id . ',id_usuario',
                'correo'        => 'nullable|email|max:150|unique:usuario,correo,' . $id . ',id_usuario',
                'celular'       => 'nullable|string|max:15',
                'id_rol'        => 'required|integer|exists:rol,id_rol',
            ]);

            if ($request->filled('clave_usuario')) {
                $request->validate(['clave_usuario' => 'required|string|min:6|max:255']);
                $data['clave_usuario'] = Hash::make($request->input('clave_usuario'));
            }

            $usuario->update($data);

            // Si es petición AJAX → siempre devolver JSON (para modal inline)
            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "✓ Usuario {$usuario->nombre} {$usuario->apellido} actualizado correctamente.",
                    'usuario' => $usuario->only(['id_usuario','nombre','apellido','nombre_usuario','correo','celular','id_rol']),
                ]);
            }

            return redirect()->route('usuarios.index')
                ->with('success', 'Usuario actualizado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar usuario: ' . $e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        if ($usuario->id_usuario === auth()->user()->id_usuario) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
