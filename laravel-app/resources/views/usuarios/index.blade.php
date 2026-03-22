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
        .badge-user  { background:#d1fae5; color:#065f46; }

        /* Toast flotante */
        .toast-notif {
            position:fixed; bottom:24px; right:24px; z-index:9999;
            padding:14px 20px; border-radius:10px; font-size:13px; font-weight:600;
            display:flex; align-items:center; gap:10px;
            box-shadow:0 8px 24px rgba(0,0,0,.15);
            transform:translateY(80px); opacity:0;
            transition:all .3s cubic-bezier(.16,1,.3,1);
            max-width:380px; pointer-events:none;
        }
        .toast-notif.show { transform:translateY(0); opacity:1; pointer-events:all; }
        .toast-notif.ok    { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
        .toast-notif.error { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Usuarios</h1>
            <p class="page-desc">Administra los usuarios del sistema y sus permisos.</p>
        </div>
        <div class="page-actions">
            <button type="button" onclick="abrirModalCrear()" class="btn btn-primary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Crear Usuario
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Usuarios</div>
                <div class="card-desc">{{ $usuarios->count() }} usuarios registrados</div>
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
            <table>
                <thead>
                <tr>
                    <th>USUARIO</th><th>CORREO</th><th>CELULAR</th><th>ROL</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="usuariosBody">
                @forelse($usuarios as $usuario)
                    <tr id="fila-usuario-{{ $usuario->id_usuario }}"
                        data-search="{{ strtolower($usuario->nombre.' '.$usuario->apellido.' '.$usuario->nombre_usuario) }}">
                        <td>
                            <div class="user-cell">
                                <div class="user-name">{{ $usuario->nombre }} {{ $usuario->apellido }}</div>
                                <div class="user-username">@ {{ $usuario->nombre_usuario }}</div>
                            </div>
                        </td>
                        <td>{{ $usuario->correo ?? '—' }}</td>
                        <td>{{ $usuario->celular ?? '—' }}</td>
                        <td>
                            @if($usuario->id_rol == 1)
                                <span class="badge badge-admin">Administrador</span>
                            @else
                                <span class="badge badge-user">Usuario</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;">
                                {{-- EDITAR — abre modal en vez de navegar a otra página --}}
                                <button type="button"
                                        class="action-btn" title="Editar usuario"
                                        onclick="abrirModalEditar(
                                        {{ $usuario->id_usuario }},
                                        '{{ addslashes($usuario->nombre) }}',
                                        '{{ addslashes($usuario->apellido) }}',
                                        '{{ addslashes($usuario->nombre_usuario) }}',
                                        '{{ $usuario->correo ?? '' }}',
                                        '{{ $usuario->celular ?? '' }}',
                                        {{ $usuario->id_rol }}
                                    )">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @if(auth()->user()->id_usuario !== $usuario->id_usuario)
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario->id_usuario) }}"
                                          style="display:inline;"
                                          onsubmit="return confirm('¿Eliminar al usuario {{ addslashes($usuario->nombre) }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn" title="Eliminar" style="color:#dc2626;">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">
                            <div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v12a3 3 0 003 3h12a3 3 0 003-3V9h-6m0 0V5a3 3 0 00-3-3H9a3 3 0 00-3 3v4z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin usuarios registrados</p>
                            </div>
                        </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ MODAL CREAR ═══ --}}
    <div class="modal-overlay" id="modalCrearOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Crear Usuario</h2>
                <p>Agrega un nuevo usuario al sistema</p>
                <button onclick="cerrarModalCrear()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form method="POST" action="{{ route('usuarios.store') }}" onsubmit="return validarFormCrear(event)">
                @csrf
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="cNombre" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Apellido *</label><input type="text" name="apellido" id="cApellido" class="form-input" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Nombre de Usuario *</label><input type="text" name="nombre_usuario" id="cNombreUsr" class="form-input" placeholder="jperez" required></div>
                    <div class="form-group"><label class="form-label">Contraseña * <small style="font-weight:400;">(mín. 6)</small></label><input type="password" name="clave_usuario" id="cClave" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Correo</label><input type="email" name="correo" id="cCorreo" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Celular</label><input type="text" name="celular" id="cCelular" class="form-input" maxlength="15"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Rol *</label>
                        <select name="id_rol" id="cRol" class="form-input" required>
                            <option value="">— Seleccionar —</option>
                            <option value="1">Administrador</option>
                            <option value="2">Usuario</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalCrear()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ MODAL EDITAR ═══ --}}
    <div class="modal-overlay" id="modalEditarOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Usuario</h2>
                <p id="subtitleEditar">Actualiza los datos del usuario</p>
                <button onclick="cerrarModalEditar()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditarUsuario" onsubmit="guardarUsuario(event)">
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group"><label class="form-label">Nombre *</label><input type="text" id="eNombre" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Apellido *</label><input type="text" id="eApellido" class="form-input" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Nombre de Usuario *</label><input type="text" id="eNombreUsr" class="form-input" required></div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Nueva Contraseña <small style="font-weight:400;text-transform:none;">(vacío = sin cambio)</small></label>
                        <input type="password" id="eClave" class="form-input" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group"><label class="form-label">Correo</label><input type="email" id="eCorreo" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Celular</label><input type="text" id="eCelular" class="form-input" maxlength="15"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Rol *</label>
                        <select id="eRol" class="form-input" required>
                            <option value="1">Administrador</option>
                            <option value="2">Usuario</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalEditar()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEditar">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast --}}
    <div class="toast-notif" id="toastNotif">
        <svg id="toastIco" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"></svg>
        <span id="toastTxt"></span>
    </div>

    @push('scripts')
        <script>
            const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
            let editandoId = null;

            // ── Filtro ────────────────────────────────────────────────────────────
            function filtrarTabla() {
                const s = document.getElementById('searchInput').value.toLowerCase();
                document.querySelectorAll('#usuariosBody tr[data-search]').forEach(r => {
                    r.style.display = !s || r.dataset.search.includes(s) ? '' : 'none';
                });
            }

            // ── Toast ─────────────────────────────────────────────────────────────
            function showToast(msg, ok = true) {
                const el  = document.getElementById('toastNotif');
                const ico = document.getElementById('toastIco');
                document.getElementById('toastTxt').textContent = msg;
                el.className = 'toast-notif ' + (ok ? 'ok' : 'error');
                ico.innerHTML = ok
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>';
                el.classList.add('show');
                setTimeout(() => el.classList.remove('show'), 3500);
            }

            // ── Modal CREAR ───────────────────────────────────────────────────────
            function abrirModalCrear() {
                ['cNombre','cApellido','cNombreUsr','cClave','cCorreo','cCelular','cRol']
                    .forEach(id => { document.getElementById(id).value = ''; });
                document.getElementById('modalCrearOverlay').classList.add('open');
            }
            function cerrarModalCrear() { document.getElementById('modalCrearOverlay').classList.remove('open'); }
            function validarFormCrear(e) {
                const pw = document.getElementById('cClave').value;
                if (pw.length < 6) { e.preventDefault(); showToast('La contraseña debe tener mínimo 6 caracteres', false); return false; }
                return true;
            }

            // ── Modal EDITAR ──────────────────────────────────────────────────────
            function abrirModalEditar(id, nombre, apellido, nombreUsr, correo, celular, rol) {
                editandoId = id;
                document.getElementById('subtitleEditar').textContent = `Editando: ${nombre} ${apellido} (@${nombreUsr})`;
                document.getElementById('eNombre').value    = nombre;
                document.getElementById('eApellido').value  = apellido;
                document.getElementById('eNombreUsr').value = nombreUsr;
                document.getElementById('eClave').value     = '';
                document.getElementById('eCorreo').value    = correo;
                document.getElementById('eCelular').value   = celular;
                document.getElementById('eRol').value       = rol;
                document.getElementById('modalEditarOverlay').classList.add('open');
            }
            function cerrarModalEditar() { document.getElementById('modalEditarOverlay').classList.remove('open'); }

            async function guardarUsuario(event) {
                event.preventDefault();
                const btn = document.getElementById('btnGuardarEditar');
                btn.disabled = true;
                btn.textContent = 'Guardando…';

                const clave = document.getElementById('eClave').value;
                const datos = {
                    nombre:         document.getElementById('eNombre').value,
                    apellido:       document.getElementById('eApellido').value,
                    nombre_usuario: document.getElementById('eNombreUsr').value,
                    correo:         document.getElementById('eCorreo').value  || null,
                    celular:        document.getElementById('eCelular').value || null,
                    id_rol:         document.getElementById('eRol').value,
                };
                if (clave) datos.clave_usuario = clave;

                try {
                    const res  = await fetch(`/usuarios/${editandoId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(datos),
                    });

                    const data = await res.json().catch(() => ({ success: false, message: 'Error al procesar respuesta' }));

                    if (res.ok && data.success) {
                        cerrarModalEditar();
                        showToast(data.message || `✓ Usuario ${datos.nombre} ${datos.apellido} actualizado correctamente.`);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        let msg = data.message || 'Error al guardar';
                        if (data.errors && typeof data.errors === 'object') {
                            msg = Object.entries(data.errors)
                                .map(([k, v]) => Array.isArray(v) ? v.join(' ') : v)
                                .join(' • ');
                        }
                        showToast(msg, false);
                    }
                } catch (err) {
                    showToast('Error de conexión: ' + err.message, false);
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Guardar Cambios';
                }
            }

            // ── Cerrar modales al click fuera ─────────────────────────────────────
            ['modalCrearOverlay', 'modalEditarOverlay'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', e => {
                    if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
                });
            });
        </script>
    @endpush

@endsection
