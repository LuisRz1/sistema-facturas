@extends('layouts.app')
@section('title', 'Conciliacion - Configuracion')
@section('breadcrumb', 'Conciliacion / Configuracion de Parsers')

@push('styles')
<style>
    :root {
        --gold: #f5c842;
        --gold-h: #e8b820;
        --gold-l: #fffbeb;
        --gold-b: #fce8a8;
        --gold-m: #fdd457;
        --gold-d: #ca9d1f;
        --gold-xd: #7a5d0f;
        --bg: #fdf8ec;
    }
    body { background: var(--bg) !important; }

    @keyframes fadeDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
    @keyframes slideUp  { from { opacity:0; transform:translateY(16px);  } to { opacity:1; transform:translateY(0); } }
    @keyframes rowIn    { from { opacity:0; transform:translateX(-8px);  } to { opacity:1; transform:translateX(0); } }

    .page-header { animation:fadeDown .5s ease-out; }
    .page-title  { font-size:22px; font-weight:800; color:#0f172a; letter-spacing:-.4px; }
    .page-desc   { font-size:13px; color:#64748b; margin-top:4px; }

    .card {
        background:#fff; border-radius:14px; border:1.5px solid var(--gold-b);
        box-shadow:0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow:hidden; margin-bottom:20px;
    }
    .card:nth-child(2) { animation:slideUp .55s ease-out .15s both; }
    .card:nth-child(3) { animation:slideUp .55s ease-out .22s both; }
    .card:nth-child(4) { animation:slideUp .55s ease-out .29s both; }
    .card:nth-child(5) { animation:slideUp .55s ease-out .36s both; }

    .card-header {
        padding:18px 24px; border-bottom:1px solid var(--gold-b);
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;
        background:var(--gold-l);
    }
    .card-title { font-size:15px; font-weight:700; color:var(--gold-xd); }
    .card-body { padding:20px 24px; }

    .badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:50px; font-size:11px; font-weight:700;
    }
    .badge-activo    { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-inactivo  { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
    .badge-info      { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
    .badge-warning   { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .badge-success   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-secondary { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
    .badge-empty     { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }

    .btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:9px 16px; border-radius:10px; font-size:13px; font-weight:600;
        cursor:pointer; border:none; text-decoration:none; transition:all .15s ease;
        white-space:nowrap;
    }
    .btn-primary { background:var(--gold); color:#1c1600; font-weight:700; border:1.5px solid var(--gold); }
    .btn-primary:hover { background:var(--gold-h); border-color:var(--gold-h); transform:translateY(-1px); }
    .btn-outline { background:#fff; color:var(--gold-d); border:1.5px solid var(--gold-b); }
    .btn-outline:hover { background:var(--gold-l); border-color:var(--gold-m); }
    .btn-sm { padding:6px 12px; font-size:12px; border-radius:6px; }
    .btn-icon { padding:6px 10px; }

    table { width:100%; border-collapse:collapse; }
    thead tr { background:var(--gold-l); }
    th {
        padding:10px 14px; text-align:left; font-size:10px; font-weight:700;
        text-transform:uppercase; letter-spacing:.8px; color:var(--gold-xd);
        white-space:nowrap; border-bottom:1px solid var(--gold-b);
    }
    td {
        padding:12px 14px; font-size:13px; border-bottom:1px solid #fef9e7;
        vertical-align:middle;
    }
    tbody tr { animation:rowIn .4s ease-out; }
    tbody tr:nth-child(1) { animation-delay:.2s; }
    tbody tr:nth-child(2) { animation-delay:.25s; }
    tbody tr:nth-child(3) { animation-delay:.30s; }
    tbody tr:hover { background:var(--gold-l); }
    tbody tr:last-child td { border-bottom:none; }

    .text-mono { font-family:'DM Mono',monospace; }
    .text-truncate { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    .empty-state { text-align:center; padding:48px 24px; color:#94a3b8; }
    .empty-state svg { margin:0 auto 16px; opacity:.4; }
    .empty-state p  { font-size:14px; font-weight:500; }

    /* ── MODAL ── */
    .modal-overlay {
        position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px);
        z-index:200; display:flex; align-items:center; justify-content:center; padding:24px;
        opacity:0; pointer-events:none; transition:opacity .2s;
    }
    .modal-overlay.open { opacity:1; pointer-events:all; }

    .modal {
        background:#fff; border-radius:16px; width:100%; max-width:720px; max-height:90vh;
        overflow:hidden; display:flex; flex-direction:column;
        box-shadow:0 8px 32px rgba(0,0,0,.12);
        transform:translateY(20px); transition:transform .25s ease;
    }
    .modal-overlay.open .modal { transform:translateY(0); }

    .modal-header {
        background:linear-gradient(135deg, var(--gold) 0%, var(--gold-h) 100%);
        border-top:3px solid var(--gold-xd);
        padding:24px 28px;
    }
    .modal-header h2 { font-size:20px; font-weight:700; color:#000; }
    .modal-header p  { font-size:13px; color:rgba(0,0,0,.7); margin-top:4px; }
    .modal-header .btn-close {
        background:none; border:none; cursor:pointer; color:#000; opacity:.6;
        font-size:20px; line-height:1; padding:4px;
    }
    .modal-header .btn-close:hover { opacity:1; }

    .modal-body { padding:28px; overflow-y:auto; flex:1; }
    .modal-footer {
        padding:20px 28px; border-top:1px solid var(--gold-b);
        display:flex; justify-content:flex-end; gap:10px; background:var(--gold-l);
    }

    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    .form-full { grid-column:1 / -1; }

    .form-label {
        font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:var(--gold-xd); margin-bottom:6px; display:block;
    }
    .form-input, .form-select, .form-textarea {
        width:100%; height:40px; padding:0 12px; border:1.5px solid var(--gold-b); border-radius:10px;
        font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:#0f172a;
        outline:none; transition:border-color .15s;
    }
    .form-textarea { height:auto; padding:10px 12px; resize:vertical; font-family:'DM Mono',monospace; font-size:12px; }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color:var(--gold); box-shadow:0 0 0 2px var(--gold-l);
    }
    .form-check {
        display:flex; align-items:flex-start; gap:10px;
    }
    .form-check input[type="checkbox"] { accent-color:var(--gold); width:18px; height:18px; flex-shrink:0; margin-top:2px; }
    .form-check label { font-size:13px; font-weight:600; color:#0f172a; }
    .form-helper { font-size:11px; color:#94a3b8; margin-top:4px; display:block; }
    .text-danger { color:#dc2626; }

    .banco-toggle {
        font-size:12px; color:var(--gold-d); cursor:pointer; text-decoration:underline;
        margin-top:4px; display:inline-block; font-weight:600;
    }
    .banco-toggle:hover { color:var(--gold-xd); }

    @media (max-width:768px) {
        .form-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>
            Configuracion de Parsers Bancarios
        </h1>
        <p class="page-desc">Administra las reglas de parseo y conciliacion por banco</p>
    </div>
    <button class="btn btn-primary btn-sm" onclick="abrirModalNuevaConfig()">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Nueva Configuracion
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success" style="padding:14px 18px;border-radius:10px;margin-bottom:20px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error" style="padding:14px 18px;border-radius:10px;margin-bottom:20px;background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:500;">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
@endif

{{-- Cards por Banco --}}
@forelse($configs as $banco => $bancoConfigs)
@php $activa = $bancoConfigs->firstWhere('activo', 1); @endphp
<div class="card">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <h3 class="card-title">{{ $banco }}</h3>
            @if($activa)
                <span class="badge badge-activo">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Activo — v{{ $activa->version }}
                </span>
            @else
                <span class="badge badge-empty">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Sin config activa
                </span>
            @endif
        </div>
        <button class="btn btn-outline btn-sm"
                onclick="editarConfig('{{ $activa ? $activa->id_config : $bancoConfigs->first()->id_config }}')">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Editar
        </button>
    </div>
    <div class="card-body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th style="padding-left:24px;">Version</th>
                        <th>Tolerancia Monto</th>
                        <th>Tolerancia Dias</th>
                        <th>Mapeo Columnas</th>
                        <th>Tipos Ignorables</th>
                        <th>Estado</th>
                        <th style="padding-right:24px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bancoConfigs as $cfg)
                    <tr>
                        <td style="padding-left:24px;font-family:'DM Mono',monospace;font-weight:700;color:var(--gold-d);">v{{ $cfg->version }}</td>
                        <td class="text-mono" style="font-weight:600;">S/ {{ number_format($cfg->tolerancia_monto, 2) }}</td>
                        <td class="text-mono">{{ $cfg->tolerancia_dias }} dias</td>
                        <td>
                            @php
                                $mapeo = is_string($cfg->mapeo_columnas) ? json_decode($cfg->mapeo_columnas, true) : $cfg->mapeo_columnas;
                                $countMapeo = is_array($mapeo) ? count($mapeo) : 0;
                            @endphp
                            <span class="badge badge-info">{{ $countMapeo }} columnas</span>
                        </td>
                        <td>
                            @php
                                $tipos = is_string($cfg->tipos_ignorables) ? json_decode($cfg->tipos_ignorables, true) : $cfg->tipos_ignorables;
                                $countTipos = is_array($tipos) ? count($tipos) : 0;
                            @endphp
                            <span class="badge badge-warning">{{ $countTipos }} tipos</span>
                        </td>
                        <td>
                            @if($cfg->activo)
                                <span class="badge badge-activo">Activo</span>
                            @else
                                <span class="badge badge-inactivo">Inactivo</span>
                            @endif
                        </td>
                        <td style="padding-right:24px;">
                            <button class="btn btn-outline btn-sm btn-icon" onclick="editarConfig({{ $cfg->id_config }})" title="Editar configuracion">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
<div class="card" style="animation:slideUp .55s ease-out .15s both;">
    <div class="card-body">
        <div class="empty-state">
            <svg width="56" height="56" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p style="font-weight:600;font-size:15px;color:#0f172a;">Sin configuraciones</p>
            <p style="font-size:13px;">No hay configuraciones de parser bancario. Crea una nueva para comenzar.</p>
            <button class="btn btn-primary btn-sm" onclick="abrirModalNuevaConfig()" style="margin-top:12px;">
                Crear Primera Configuracion
            </button>
        </div>
    </div>
</div>
@endforelse

{{-- MODAL: Nueva/Editar Configuracion --}}
<div class="modal-overlay" id="modalConfig">
    <div class="modal">
        <form method="POST" action="{{ route('conciliacion.configuracion.parsers.guardar') }}" id="formParser">
            @csrf
            <input type="hidden" name="id_config" id="configId">

            <div class="modal-header">
                <div style="flex:1;">
                    <h2 id="modalConfigLabel">Nueva Configuracion de Parser</h2>
                    <p>Define las reglas de parseo y tolerancias para conciliacion automatica</p>
                </div>
                <button type="button" class="btn-close" onclick="cerrarModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label">Banco <span class="text-danger">*</span></label>
                        <select name="banco" id="campoBanco" class="form-select" required>
                            <option value="">Seleccionar banco</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="form-input" id="bancoManual" placeholder="Escribir un banco nuevo..."
                               style="display:none;margin-top:6px;">
                        <a href="#" class="banco-toggle" id="toggleBancoManual" onclick="toggleBancoInput(); return false;">
                            + Agregar nuevo banco
                        </a>
                    </div>
                    <div>
                        <label class="form-label">Version <span class="text-danger">*</span></label>
                        <input type="text" name="version" id="campoVersion" class="form-input" placeholder="Ej: 1.0.0" required>
                    </div>
                    <div>
                        <label class="form-label">Tolerancia Monto (S/) <span class="text-danger">*</span></label>
                        <input type="number" name="tolerancia_monto" id="campoToleranciaMonto" class="form-input"
                               step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="form-label">Tolerancia Dias <span class="text-danger">*</span></label>
                        <input type="number" name="tolerancia_dias" id="campoToleranciaDias" class="form-input"
                               min="0" placeholder="0" required>
                    </div>
                    <div class="form-full">
                        <label class="form-label">Mapeo de Columnas (JSON) <span class="text-danger">*</span></label>
                        <textarea name="mapeo_columnas" id="campoMapeo" class="form-textarea" rows="5"
                                  placeholder='{"fecha": "col_A", "descripcion": "col_B", "importe": "col_C"}'
                                  required></textarea>
                        <span class="form-helper">Formato JSON: mapea campos logicos a nombres de columna del archivo bancario.</span>
                    </div>
                    <div class="form-full">
                        <label class="form-label">Tipos Ignorables (JSON)</label>
                        <textarea name="tipos_ignorables" id="campoTiposIgnorables" class="form-textarea" rows="3"
                                  placeholder='["COMISION", "MANTENIMIENTO", "ITF"]'></textarea>
                        <span class="form-helper">Formato JSON: lista de tipos de movimiento a ignorar durante la conciliacion.</span>
                    </div>
                    <div class="form-full">
                        <div class="form-check">
                            <input type="checkbox" name="activo" value="1" id="campoActivo">
                            <div>
                                <label for="campoActivo" style="display:block;cursor:pointer;">
                                    Activo — Marcar como configuracion activa para este banco
                                </label>
                                <span class="form-helper">Solo una configuracion puede estar activa por banco.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Configuracion</button>
            </div>
        </form>
    </div>
</div>

@stop

@push('scripts')
<script>
    const configsData = @json($configs->flatten()->keyBy('id_config'));

    function abrirModalNuevaConfig() {
        document.getElementById('formParser').reset();
        document.getElementById('configId').value = '';
        document.getElementById('modalConfigLabel').textContent = 'Nueva Configuracion de Parser';
        resetBancoManual();
        document.getElementById('modalConfig').classList.add('open');
    }

    function cerrarModal() {
        document.getElementById('modalConfig').classList.remove('open');
    }

    function toggleBancoInput() {
        const select = document.getElementById('campoBanco');
        const manual = document.getElementById('bancoManual');
        const toggle = document.getElementById('toggleBancoManual');

        if (manual.style.display === 'none' || manual.style.display === '') {
            manual.style.display = 'block';
            select.style.display = 'none';
            select.required = false;
            manual.required = true;
            select.name = '';
            manual.name = 'banco';
            toggle.textContent = '− Usar lista de bancos existentes';
        } else {
            manual.style.display = 'none';
            select.style.display = 'block';
            select.required = true;
            manual.required = false;
            select.name = 'banco';
            manual.name = '';
            toggle.textContent = '+ Agregar nuevo banco';
        }
    }

    function resetBancoManual() {
        const select = document.getElementById('campoBanco');
        const manual = document.getElementById('bancoManual');
        const toggle = document.getElementById('toggleBancoManual');
        manual.style.display = 'none';
        manual.value = '';
        manual.required = false;
        select.style.display = 'block';
        select.required = true;
        select.name = 'banco';
        manual.name = '';
        toggle.textContent = '+ Agregar nuevo banco';
    }

    function editarConfig(idConfig) {
        const cfg = configsData[idConfig];
        if (!cfg) return;

        document.getElementById('configId').value = cfg.id_config;

        // Banco
        const select = document.getElementById('campoBanco');
        const manual = document.getElementById('bancoManual');
        const toggle = document.getElementById('toggleBancoManual');

        // Check if banco exists in select options
        let found = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === cfg.banco) {
                select.value = cfg.banco;
                found = true;
                break;
            }
        }

        if (found) {
            manual.style.display = 'none';
            manual.required = false;
            manual.value = '';
            select.style.display = 'block';
            select.required = true;
            select.name = 'banco';
            manual.name = '';
            toggle.textContent = '+ Agregar nuevo banco';
        } else {
            select.style.display = 'none';
            select.required = false;
            select.name = '';
            manual.style.display = 'block';
            manual.value = cfg.banco;
            manual.required = true;
            manual.name = 'banco';
            toggle.textContent = '− Usar lista de bancos existentes';
        }

        document.getElementById('campoVersion').value = cfg.version;
        document.getElementById('campoToleranciaMonto').value = cfg.tolerancia_monto;
        document.getElementById('campoToleranciaDias').value = cfg.tolerancia_dias;

        // Formatear JSON
        try {
            const mapeo = typeof cfg.mapeo_columnas === 'string'
                ? JSON.parse(cfg.mapeo_columnas) : cfg.mapeo_columnas;
            document.getElementById('campoMapeo').value = JSON.stringify(mapeo, null, 2);
        } catch(e) {
            document.getElementById('campoMapeo').value = cfg.mapeo_columnas || '';
        }

        try {
            const tipos = typeof cfg.tipos_ignorables === 'string'
                ? JSON.parse(cfg.tipos_ignorables) : cfg.tipos_ignorables;
            document.getElementById('campoTiposIgnorables').value = JSON.stringify(tipos || [], null, 2);
        } catch(e) {
            document.getElementById('campoTiposIgnorables').value = cfg.tipos_ignorables || '[]';
        }

        document.getElementById('campoActivo').checked = cfg.activo == 1;
        document.getElementById('modalConfigLabel').textContent = 'Editar Configuracion — ' + cfg.banco;

        document.getElementById('modalConfig').classList.add('open');
    }

    // Close modal on overlay click
    document.getElementById('modalConfig').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('modalConfig').classList.contains('open')) {
            cerrarModal();
        }
    });
</script>
@endpush
