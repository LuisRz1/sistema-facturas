@extends('layouts.app')

@section('title', 'Directorio de Clientes')
@section('breadcrumb', 'Directorio de Clientes')

@push('styles')
    <style>
        .avatar-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--accent-light);
            color: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px;
            flex-shrink: 0;
        }

        .client-row-main {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 3px;
        }

        .contact-item svg { flex-shrink: 0; }

        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .estado-completo { background: #d1fae5; color: #065f46; }
        .estado-incompleto { background: #fef3c7; color: #92400e; }
        .estado-sin_datos { background: #f1f5f9; color: #64748b; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Directorio de Clientes</h1>
            <p class="page-desc">Administra los datos de contacto de tus clientes y socios comerciales.</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="abrirModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Agregar Cliente
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Clientes Registrados</div>
                <div class="card-desc">{{ $clientes->count() }} clientes en el sistema</div>
            </div>
        </div>

        <div class="search-bar">
            <div class="search-input-wrap" style="max-width:320px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                <input type="text" class="form-input" id="searchInput" placeholder="Buscar por nombre o RUC..." onkeyup="filtrarClientes()">
            </div>
            <select class="form-select" id="filterEstado" onchange="filtrarClientes()" style="width:auto;min-width:180px;">
                <option value="">Todos los contactos</option>
                <option value="COMPLETO">Completo</option>
                <option value="INCOMPLETO">Incompleto</option>
                <option value="SIN_DATOS">Sin datos</option>
            </select>
        </div>

        <div style="overflow-x:auto;">
            <table id="clientesTable">
                <thead>
                <tr>
                    <th>EMPRESA / CLIENTE</th>
                    <th>RUC</th>
                    <th>CONTACTO</th>
                    <th>DIRECCIÓN</th>
                    <th>ESTADO</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="clientesBody">
                @forelse($clientes as $cliente)
                    @php
                        $inicial = strtoupper(substr($cliente->razon_social, 0, 1));
                        $estadoClass = 'estado-'.strtolower($cliente->estado_contacto);
                    @endphp
                    <tr data-search="{{ strtolower($cliente->razon_social.' '.$cliente->ruc) }}" data-estado="{{ $cliente->estado_contacto }}">
                        <td>
                            <div class="client-row-main">
                                <div class="avatar-circle">{{ $inicial }}</div>
                                <div>
                                    <div style="font-weight:600;font-size:13.5px;">{{ $cliente->razon_social }}</div>
                                    <div style="font-size:11px;color:var(--text-muted);">
                                        Desde {{ \Carbon\Carbon::parse($cliente->fecha_creacion)->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-mono" style="font-size:13px;font-weight:600;">{{ $cliente->ruc }}</span>
                        </td>
                        <td>
                            @if($cliente->correo)
                                <div class="contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $cliente->correo }}
                                </div>
                            @endif
                            @if($cliente->celular)
                                <div class="contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    {{ $cliente->celular }}
                                </div>
                            @endif
                            @if(!$cliente->correo && !$cliente->celular)
                                <span style="font-size:12px;color:var(--text-muted);">Sin datos de contacto</span>
                            @endif
                        </td>
                        <td style="max-width:200px;">
                            <span style="font-size:12px;color:var(--text-muted);">
                                {{ $cliente->direccion_fiscal ? Str::limit($cliente->direccion_fiscal, 40) : '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="estado-badge {{ $estadoClass }}">
                                {{ str_replace('_',' ', $cliente->estado_contacto) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;">
                                <button class="action-btn" onclick="editarCliente({{ $cliente->id_cliente }}, '{{ addslashes($cliente->razon_social) }}', '{{ $cliente->ruc }}', '{{ $cliente->celular }}', '{{ addslashes($cliente->direccion_fiscal ?? '') }}', '{{ $cliente->correo }}')" title="Editar">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('clientes.destroy', $cliente->id_cliente) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este cliente?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn" title="Eliminar" style="color:var(--red);">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin clientes registrados</p>
                                <p style="font-size:13px;margin-top:4px;">Agrega tu primer cliente usando el botón superior.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL CREAR / EDITAR --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Cliente</h2>
                <p id="modalDesc">Ingresa los datos del nuevo cliente o proveedor.</p>
            </div>
            <form id="clienteForm" method="POST" action="{{ route('clientes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Razón Social *</label>
                            <input type="text" name="razon_social" id="f_razon" class="form-input" placeholder="Ej. Constructora ABC S.A.C." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">RUC *</label>
                            <input type="text" name="ruc" id="f_ruc" class="form-input" placeholder="20123456789" maxlength="11" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Celular / WhatsApp</label>
                            <input type="text" name="celular" id="f_celular" class="form-input" placeholder="+51 987 654 321">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" name="correo" id="f_correo" class="form-input" placeholder="contacto@empresa.com">
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Dirección Fiscal</label>
                            <input type="text" name="direccion_fiscal" id="f_direccion" class="form-input" placeholder="Av. Los Tulipanes 123, Lima">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let editandoId = null;

            function abrirModal() {
                editandoId = null;
                document.getElementById('modalTitle').textContent = 'Nuevo Cliente';
                document.getElementById('modalDesc').textContent = 'Ingresa los datos del nuevo cliente.';
                document.getElementById('clienteForm').action = '{{ route('clientes.store') }}';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('btnSubmit').textContent = 'Guardar Cliente';
                ['f_razon','f_ruc','f_celular','f_correo','f_direccion'].forEach(id => document.getElementById(id).value = '');
                document.getElementById('modalOverlay').classList.add('open');
            }

            function editarCliente(id, razon, ruc, celular, direccion, correo) {
                editandoId = id;
                document.getElementById('modalTitle').textContent = 'Editar Cliente';
                document.getElementById('modalDesc').textContent = 'Actualiza los datos del cliente.';
                document.getElementById('clienteForm').action = '/clientes/' + id;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('f_razon').value = razon;
                document.getElementById('f_ruc').value = ruc;
                document.getElementById('f_celular').value = celular || '';
                document.getElementById('f_correo').value = correo || '';
                document.getElementById('f_direccion').value = direccion || '';
                document.getElementById('btnSubmit').textContent = 'Actualizar Cliente';
                document.getElementById('modalOverlay').classList.add('open');
            }

            function cerrarModal() {
                document.getElementById('modalOverlay').classList.remove('open');
            }

            document.getElementById('modalOverlay').addEventListener('click', function(e) {
                if (e.target === this) cerrarModal();
            });

            function filtrarClientes() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const estado = document.getElementById('filterEstado').value;
                document.querySelectorAll('#clientesBody tr[data-search]').forEach(row => {
                    const matchSearch = !search || row.dataset.search.includes(search);
                    const matchEstado = !estado || row.dataset.estado === estado;
                    row.style.display = (matchSearch && matchEstado) ? '' : 'none';
                });
            }
        </script>
    @endpush

@endsection
