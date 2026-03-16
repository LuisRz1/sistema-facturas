@extends('layouts.app')
@section('title', 'Gestión de Usuarios')
@section('breadcrumb', 'Gestión de Usuarios')

@push('styles')
    <style>
        .filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .filter-row .search-input-wrap { max-width:280px; }
        .actions-cell { display:flex; align-items:center; gap:4px; }
        .action-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; border:none; cursor:pointer; transition:background .15s; color:var(--text-muted); background:transparent; }
        .action-btn:hover { background:var(--main-bg); color:var(--text-primary); }
        .user-cell { display:flex; flex-direction:column; gap:2px; }
        .user-name { font-weight:600; font-size:13.5px; }
        .user-username { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); }
        .badge { display:inline-block; padding:4px 10px; border-radius:4px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
        .badge-admin { background:#fca5a5; color:#7f1d1d; }
        .badge-user { background:#d1fae5; color:#065f46; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Usuarios</h1>
            <p class="page-desc">Administra los usuarios del sistema y sus permisos.</p>
        </div>
        <div class="page-actions">
            <button type="button" onclick="abrirModalCrearUsuario()" class="btn btn-primary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Crear Usuario
            </button>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Usuarios</div>
                <div class="card-desc">{{ $usuarios->count() }} usuarios registrados en el sistema</div>
            </div>
        </div>

        <div class="search-bar">
            <div class="filter-row">
                <div class="search-input-wrap">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" class="form-input" id="searchInput" placeholder="Buscar usuario..." onkeyup="filtrarTabla()">
                </div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table id="usuariosTable">
                <thead>
                <tr>
                    <th>USUARIO</th><th>CORREO</th><th>CELULAR</th><th>ESTADO CONTACTO</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="usuariosBody">
                @forelse($usuarios as $usuario)
                    <tr data-search="{{ strtolower($usuario->nombre . ' ' . $usuario->apellido . ' ' . $usuario->nombre_usuario) }}">
                        <td>
                            <div class="user-cell">
                                <div class="user-name">{{ $usuario->nombre }} {{ $usuario->apellido }}</div>
                                <div class="user-username">@ {{ $usuario->nombre_usuario }}</div>
                                @if($usuario->id_rol == 1)
                                    <span class="badge badge-admin">Administrador</span>
                                @else
                                    <span class="badge badge-user">Usuario</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $usuario->correo ?? '—' }}</td>
                        <td>{{ $usuario->celular ?? '—' }}</td>
                        <td>
                            <div style="font-size:12px;">
                                @if(auth()->user()->id_usuario === $usuario->id_usuario)
                                    <span style="color:#7c3aed;font-weight:600;">Sesión actual</span>
                                @else
                                    —
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;">
                                <a href="{{ route('usuarios.edit', $usuario->id_usuario) }}" class="action-btn" title="Editar">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                @if(auth()->user()->id_usuario !== $usuario->id_usuario)
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario->id_usuario) }}" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn" title="Eliminar" style="color:#dc2626;">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3H4v2h16V7h-3z"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v12a3 3 0 003 3h12a3 3 0 003-3V9h-6m0 0V5a3 3 0 00-3-3H9a3 3 0 00-3 3v4z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin usuarios registrados</p>
                                <p style="font-size:13px;margin-top:4px;"><button type="button" onclick="abrirModalCrearUsuario()" style="color:#1d4ed8;text-decoration:none;font-weight:600;background:none;border:none;cursor:pointer;">Crea el primer usuario</button></p>
                            </div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CREAR USUARIO -->
    <div class="modal-overlay" id="modalCrearUsuarioOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Crear Usuario</h2>
                <p>Agrega un nuevo usuario al sistema</p>
                <button onclick="cerrarModalCrearUsuario()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form method="POST" action="{{ route('usuarios.store') }}" onsubmit="return validarFormulario(event)">
                @csrf
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group"><label class="form-label">Nombre <span style="color:#dc2626;">*</span></label><input type="text" name="nombre" id="crearNombre" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Apellido <span style="color:#dc2626;">*</span></label><input type="text" name="apellido" id="crearApellido" class="form-input" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Nombre de Usuario <span style="color:#dc2626;">*</span></label><input type="text" name="nombre_usuario" id="crearNombreUsuario" class="form-input" placeholder="jperez" required></div>
                    <div class="form-group"><label class="form-label">Contraseña <span style="color:#dc2626;">*</span></label><input type="password" name="clave_usuario" id="crearClaveUsuario" class="form-input" placeholder="Mínimo 6 caracteres" required></div>
                    <div class="form-group"><label class="form-label">Correo Electrónico</label><input type="email" name="correo" id="crearCorreo" class="form-input" placeholder="usuario@example.com"></div>
                    <div class="form-group"><label class="form-label">Celular</label><input type="text" name="celular" id="crearCelular" class="form-input" placeholder="987654321" maxlength="15"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Rol <span style="color:#dc2626;">*</span></label>
                        <select name="id_rol" id="crearIdRol" class="form-input" required>
                            <option value="">Selecciona un rol</option>
                            <option value="1">Administrador</option>
                            <option value="2">Usuario</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalCrearUsuario()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function abrirModalCrearUsuario() {
                document.getElementById('modalCrearUsuarioOverlay').classList.add('open');
                limpiarFormularioCrear();
            }

            function cerrarModalCrearUsuario() {
                document.getElementById('modalCrearUsuarioOverlay').classList.remove('open');
            }

            function limpiarFormularioCrear() {
                document.getElementById('crearNombre').value = '';
                document.getElementById('crearApellido').value = '';
                document.getElementById('crearNombreUsuario').value = '';
                document.getElementById('crearClaveUsuario').value = '';
                document.getElementById('crearCorreo').value = '';
                document.getElementById('crearCelular').value = '';
                document.getElementById('crearIdRol').value = '';
            }

            function validarFormulario(event) {
                const nombre = document.getElementById('crearNombre').value.trim();
                const apellido = document.getElementById('crearApellido').value.trim();
                const nombreUsuario = document.getElementById('crearNombreUsuario').value.trim();
                const claveUsuario = document.getElementById('crearClaveUsuario').value.trim();
                const idRol = document.getElementById('crearIdRol').value;

                if (!nombre) { alert('El nombre es requerido'); return false; }
                if (!apellido) { alert('El apellido es requerido'); return false; }
                if (!nombreUsuario) { alert('El nombre de usuario es requerido'); return false; }
                if (claveUsuario.length < 6) { alert('La contraseña debe tener mínimo 6 caracteres'); return false; }
                if (!idRol) { alert('Debes seleccionar un rol'); return false; }

                return true;
            }

            function filtrarTabla() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                document.querySelectorAll('#usuariosBody tr[data-search]').forEach(row => {
                    const ok = !search || row.dataset.search.includes(search);
                    row.style.display = ok ? '' : 'none';
                });
            }

            document.getElementById('modalCrearUsuarioOverlay')?.addEventListener('click', e => {
                if (e.target === e.currentTarget) cerrarModalCrearUsuario();
            });
        </script>
    @endpush

@endsection
