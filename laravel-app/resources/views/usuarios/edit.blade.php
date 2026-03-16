@extends('layouts.app')
@section('title', 'Editar Usuario')
@section('breadcrumb', 'Editar Usuario')

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Editar Usuario</h1>
            <p class="page-desc">Actualiza la información del usuario.</p>
        </div>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header">
            <div>
                <div class="card-title">{{ $usuario->nombre }} {{ $usuario->apellido }}</div>
                <div class="card-desc">Modifica los datos del usuario @{{ $usuario->nombre_usuario }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('usuarios.update', $usuario->id_usuario) }}" style="padding:24px;">
            @csrf
            @method('PUT')

            {{-- Nombre --}}
            <div class="form-group">
                <label class="form-label">Nombre <span style="color:#dc2626;">*</span></label>
                <input type="text" name="nombre" id="nombre" class="form-input @error('nombre') is-invalid @enderror" 
                       value="{{ old('nombre', $usuario->nombre) }}" placeholder="Juan" required>
                @error('nombre')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Apellido --}}
            <div class="form-group">
                <label class="form-label">Apellido <span style="color:#dc2626;">*</span></label>
                <input type="text" name="apellido" id="apellido" class="form-input @error('apellido') is-invalid @enderror" 
                       value="{{ old('apellido', $usuario->apellido) }}" placeholder="Pérez" required>
                @error('apellido')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Nombre de Usuario --}}
            <div class="form-group">
                <label class="form-label">Nombre de Usuario <span style="color:#dc2626;">*</span></label>
                <input type="text" name="nombre_usuario" id="nombre_usuario" class="form-input @error('nombre_usuario') is-invalid @enderror" 
                       value="{{ old('nombre_usuario', $usuario->nombre_usuario) }}" placeholder="jperez" required>
                @error('nombre_usuario')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Nueva Contraseña (Opcional) --}}
            <div class="form-group">
                <label class="form-label">Nueva Contraseña <small style="color:#64748b;">(dejar vacío para no cambiar)</small></label>
                <input type="password" name="clave_usuario" id="clave_usuario" class="form-input @error('clave_usuario') is-invalid @enderror" 
                       placeholder="Mínimo 6 caracteres">
                @error('clave_usuario')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Correo --}}
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" class="form-input @error('correo') is-invalid @enderror" 
                       value="{{ old('correo', $usuario->correo) }}" placeholder="usuario@example.com">
                @error('correo')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Celular --}}
            <div class="form-group">
                <label class="form-label">Celular</label>
                <input type="text" name="celular" id="celular" class="form-input @error('celular') is-invalid @enderror" 
                       value="{{ old('celular', $usuario->celular) }}" placeholder="987654321" maxlength="15">
                @error('celular')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Rol --}}
            <div class="form-group">
                <label class="form-label">Rol <span style="color:#dc2626;">*</span></label>
                <select name="id_rol" id="id_rol" class="form-input @error('id_rol') is-invalid @enderror" required>
                    <option value="1" {{ old('id_rol', $usuario->id_rol) == 1 ? 'selected' : '' }}>Administrador</option>
                    <option value="2" {{ old('id_rol', $usuario->id_rol) == 2 ? 'selected' : '' }}>Usuario</option>
                </select>
                @error('id_rol')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Botones --}}
            <div style="display:flex;gap:10px;margin-top:30px;">
                <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>

@endsection
