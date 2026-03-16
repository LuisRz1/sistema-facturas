@extends('layouts.app')
@section('title', 'Crear Usuario')
@section('breadcrumb', 'Crear Usuario')

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Crear Usuario</h1>
            <p class="page-desc">Agrega un nuevo usuario al sistema.</p>
        </div>
    </div>

    <div class="card" style="max-width:600px;">
        <div class="card-header">
            <div>
                <div class="card-title">Nuevo Usuario</div>
                <div class="card-desc">Completa el formulario para crear un nuevo usuario</div>
            </div>
        </div>

        <form method="POST" action="{{ route('usuarios.store') }}" style="padding:24px;">
            @csrf

            {{-- Nombre --}}
            <div class="form-group">
                <label class="form-label">Nombre <span style="color:#dc2626;">*</span></label>
                <input type="text" name="nombre" id="nombre" class="form-input @error('nombre') is-invalid @enderror" 
                       value="{{ old('nombre') }}" placeholder="Juan" required>
                @error('nombre')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Apellido --}}
            <div class="form-group">
                <label class="form-label">Apellido <span style="color:#dc2626;">*</span></label>
                <input type="text" name="apellido" id="apellido" class="form-input @error('apellido') is-invalid @enderror" 
                       value="{{ old('apellido') }}" placeholder="Pérez" required>
                @error('apellido')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Nombre de Usuario --}}
            <div class="form-group">
                <label class="form-label">Nombre de Usuario <span style="color:#dc2626;">*</span></label>
                <input type="text" name="nombre_usuario" id="nombre_usuario" class="form-input @error('nombre_usuario') is-invalid @enderror" 
                       value="{{ old('nombre_usuario') }}" placeholder="jperez" required>
                @error('nombre_usuario')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Contraseña --}}
            <div class="form-group">
                <label class="form-label">Contraseña <span style="color:#dc2626;">*</span></label>
                <input type="password" name="clave_usuario" id="clave_usuario" class="form-input @error('clave_usuario') is-invalid @enderror" 
                       placeholder="Mínimo 6 caracteres" required>
                @error('clave_usuario')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Correo --}}
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" class="form-input @error('correo') is-invalid @enderror" 
                       value="{{ old('correo') }}" placeholder="usuario@example.com">
                @error('correo')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Celular --}}
            <div class="form-group">
                <label class="form-label">Celular</label>
                <input type="text" name="celular" id="celular" class="form-input @error('celular') is-invalid @enderror" 
                       value="{{ old('celular') }}" placeholder="987654321" maxlength="15">
                @error('celular')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Rol --}}
            <div class="form-group">
                <label class="form-label">Rol <span style="color:#dc2626;">*</span></label>
                <select name="id_rol" id="id_rol" class="form-input @error('id_rol') is-invalid @enderror" required>
                    <option value="">Selecciona un rol</option>
                    <option value="1" {{ old('id_rol') == 1 ? 'selected' : '' }}>Administrador</option>
                    <option value="2" {{ old('id_rol') == 2 ? 'selected' : '' }}>Usuario</option>
                </select>
                @error('id_rol')<span class="error-message">{{ $message }}</span>@enderror
            </div>

            {{-- Botones --}}
            <div style="display:flex;gap:10px;margin-top:30px;">
                <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>

@endsection
