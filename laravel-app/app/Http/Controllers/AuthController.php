<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string',
            'clave_usuario'  => 'required|string',
        ], [
            'nombre_usuario.required' => 'El usuario es obligatorio.',
            'clave_usuario.required'  => 'La contraseña es obligatoria.',
        ]);

        $usuario = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();

        if (!$usuario) {
            return back()->withErrors(['nombre_usuario' => 'Usuario no encontrado.'])->withInput();
        }

        // Soporta contraseña en texto plano (legacy) Y contraseña hasheada
        $passwordValida = Hash::check($request->clave_usuario, $usuario->clave_usuario)
            || $usuario->clave_usuario === $request->clave_usuario;

        if (!$passwordValida) {
            return back()->withErrors(['clave_usuario' => 'Contraseña incorrecta.'])->withInput();
        }

        Auth::login($usuario, $request->boolean('recordar'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
