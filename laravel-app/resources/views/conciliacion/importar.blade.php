@extends('layouts.app')

@section('title', 'Importar Extracto Bancario')
@section('breadcrumb', 'Conciliación / Importar')

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

    .page-header { animation: fadeDown .5s ease-out; }

    .page-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -.4px;
    }
    .page-desc {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .import-wrap {
        max-width: 720px;
        margin: 0 auto;
        animation: slideUp .55s ease-out .15s both;
    }

    /* ── ALERTAS PERSONALIZADAS ── */
    .alerta-dup {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 22px;
        font-size: 13px;
        color: #991b1b;
        font-weight: 600;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: fadeDown .4s ease-out;
    }
    .alerta-dup svg { flex-shrink: 0; margin-top: 1px; }

    .alert-success-local {
        background: #d1fae5;
        border: 1px solid #6ee7b7;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 22px;
        font-size: 13px;
        color: #065f46;
        font-weight: 600;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: fadeDown .4s ease-out;
    }
    .alert-success-local svg { flex-shrink: 0; margin-top: 1px; }

    .alert-error-local {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 22px;
        font-size: 13px;
        color: #7f1d1d;
        font-weight: 600;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: fadeDown .4s ease-out;
    }
    .alert-error-local svg { flex-shrink: 0; margin-top: 1px; }
    .alert-error-local ul { margin: 6px 0 0; padding-left: 20px; }
    .alert-error-local li { margin-bottom: 2px; font-weight: 500; }

    /* ── CARDS ── */
    .card-gold {
        background: #fff;
        border: 1.5px solid var(--gold-b);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(245,200,66,.08);
    }
    .card-gold-header {
        padding: 18px 24px;
        background: var(--gold-l);
        border-bottom: 1px solid var(--gold-b);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .card-gold-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--gold-xd);
    }
    .card-gold-body {
        padding: 28px;
    }

    /* ── SECTION LABEL ── */
    .section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--gold-xd);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--gold-b);
    }

    /* ── DROP ZONE ── */
    .drop-zone {
        border: 2px dashed var(--gold-b);
        border-radius: 14px;
        padding: 56px 32px;
        text-align: center;
        cursor: pointer;
        transition: all .25s ease;
        background: #fffdf5;
        position: relative;
    }
    .drop-zone:hover,
    .drop-zone.over {
        border-color: var(--gold);
        background: var(--gold-l);
        box-shadow: 0 0 0 6px rgba(245, 200, 66, .08);
    }
    .drop-zone.drag-active {
        border-color: var(--gold-h);
        background: var(--gold-l);
        box-shadow: 0 0 0 6px rgba(245, 200, 66, .12);
    }
    .drop-zone h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--gold-xd);
        margin: 14px 0 6px;
    }
    .drop-zone p {
        font-size: 13px;
        color: #8b7a3a;
        margin: 0;
    }
    .drop-zone .supported {
        display: inline-block;
        margin-top: 14px;
        font-size: 11px;
        font-weight: 700;
        color: var(--gold-d);
        background: var(--gold-l);
        border: 1px solid var(--gold-b);
        padding: 5px 14px;
        border-radius: 20px;
    }
    .drop-zone svg { color: var(--gold); }

    /* ── FILE PILL ── */
    .file-pill {
        display: none;
        align-items: center;
        gap: 14px;
        background: var(--gold-l);
        border: 1.5px solid var(--gold-b);
        border-radius: 12px;
        padding: 16px 18px;
        margin-top: 16px;
    }
    .file-pill.show { display: flex; }
    .file-pill .file-icon {
        width: 44px; height: 44px;
        background: #fff;
        border: 1.5px solid var(--gold-b);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--gold);
    }
    .file-pill .name {
        font-weight: 700;
        font-size: 14px;
        flex: 1;
        word-break: break-all;
        color: var(--text-primary);
    }
    .file-pill .size {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
        font-family: 'DM Mono', monospace;
    }
    .file-pill button {
        background: none;
        border: 1.5px solid transparent;
        cursor: pointer;
        color: #94a3b8;
        font-size: 24px;
        padding: 2px 10px;
        border-radius: 8px;
        line-height: 1;
        transition: all .15s;
        flex-shrink: 0;
    }
    .file-pill button:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    /* ── SUBMIT BUTTON ── */
    .btn-submit {
        margin-top: 22px;
        width: 100%;
        height: 50px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--gold);
        color: #1c1600;
        border: none;
        cursor: pointer;
        transition: all .15s ease;
    }
    .btn-submit:hover {
        background: var(--gold-h);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(245,200,66,.35);
    }
    .btn-submit:disabled {
        opacity: .45;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* ── BANCOS GRID ── */
    .bancos-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-top: 16px;
    }
    .banco-card {
        background: #fff;
        border: 1.5px solid var(--gold-b);
        border-radius: 12px;
        padding: 20px 18px;
        text-align: center;
        transition: all .18s;
    }
    .banco-card:hover {
        border-color: var(--gold);
        background: var(--gold-l);
        box-shadow: 0 4px 16px rgba(245,200,66,.1);
        transform: translateY(-2px);
    }
    .banco-card .banco-dot {
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }
    .banco-card .banco-name {
        font-size: 15px;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -.2px;
    }
    .banco-card .banco-meta {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 5px;
    }
    .banco-card .banco-moneda {
        font-size: 10px;
        font-weight: 600;
        color: var(--gold-xd);
        margin-top: 8px;
        background: var(--gold-l);
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        border: 1px solid var(--gold-b);
    }
    .banco-card .banco-formatos {
        font-size: 10px;
        color: var(--text-muted);
        margin-top: 6px;
    }

    /* ── INFO TIP ── */
    .info-tip {
        margin-top: 20px;
        background: var(--gold-l);
        border: 1px solid var(--gold-b);
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .info-tip p {
        font-size: 12px;
        color: var(--gold-xd);
        margin: 0;
        font-weight: 500;
        line-height: 1.5;
    }
    .info-tip svg {
        flex-shrink: 0;
        margin-top: 1px;
        color: var(--gold);
    }

    /* ── BOTONES OUTLINE / GHOST ── */
    .btn-gold-outline {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all .15s ease;
        white-space: nowrap;
        background: #fff;
        color: var(--gold-d);
        border: 1.5px solid var(--gold-b);
    }
    .btn-gold-outline:hover {
        background: var(--gold-l);
        border-color: var(--gold);
        color: var(--gold-xd);
    }

    .btn-gold-ghost {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all .15s ease;
        white-space: nowrap;
        background: transparent;
        color: var(--text-muted);
        border: 1px solid transparent;
    }
    .btn-gold-ghost:hover {
        background: var(--gold-l);
        color: var(--text-primary);
        border-color: var(--gold-b);
    }

    /* ── LOADING OVERLAY ── */
    .loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(253, 248, 236, .85);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 18px;
    }
    .loading-overlay.show { display: flex; }
    .spinner {
        width: 52px;
        height: 52px;
        border: 4px solid var(--gold-b);
        border-top-color: var(--gold);
        border-radius: 50%;
        animation: spin .75s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .loading-overlay p { font-size: 14px; font-weight: 600; color: var(--text-primary); margin: 0; }
    .loading-overlay .loading-sub { font-size: 12px; color: var(--text-muted); margin: 0; }

    @media (max-width: 600px) {
        .bancos-grid { grid-template-columns: 1fr; }
        .card-gold-body { padding: 20px; }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>Importar Extracto Bancario
        </h1>
        <p class="page-desc">Carga tu archivo de movimientos bancarios (BCP o Interbank) para conciliar automáticamente.</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('conciliacion.historial') }}" class="btn-gold-ghost">Historial</a>
        <a href="{{ route('conciliacion.dashboard') }}" class="btn-gold-ghost">Dashboard</a>
    </div>
</div>

{{-- ERROR DE DUPLICADO / ERROR GENERAL --}}
@if(session('error'))
    <div class="alerta-dup">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- MENSAJE DE EXITO --}}
@if(session('success'))
    <div class="alert-success-local">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- ERRORES DE VALIDACION --}}
@if($errors->any())
    <div class="alert-error-local">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <div>
            <strong>Corrige los siguientes errores:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- FORMULARIO DE IMPORTACION --}}
<div class="card-gold import-wrap">
    <form id="frmImportar" method="POST" action="{{ route('conciliacion.importar.procesar') }}"
          enctype="multipart/form-data" onsubmit="mostrarCarga()">
        @csrf

        <div class="card-gold-body">

            {{-- Seccion: Archivo --}}
            <div class="section-label">1. Seleccionar archivo</div>

            <div class="drop-zone" id="dropZone">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" style="display:block;margin:0 auto;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <h3>Arrastra tu archivo aquí</h3>
                <p>o haz clic para seleccionarlo</p>
                <span class="supported">{{ $extensiones }}</span>
                <input type="file" name="archivo" id="archivoInput"
                       accept="{{ implode(',', array_map(fn($e) => '.'.$e, ['xlsx','xls','csv'])) }}"
                       style="display:none;" required>
            </div>

            <div class="file-pill" id="filePill">
                <div class="file-icon">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="name" id="fileName">archivo.xlsx</span>
                <span class="size" id="fileSize">0 KB</span>
                <button type="button" onclick="removerArchivo()" title="Quitar archivo">&times;</button>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit" disabled>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Importar Extracto Bancario
            </button>

        </div>
    </form>
</div>

{{-- BANCOS SOPORTADOS --}}
<div class="card-gold import-wrap" style="margin-top:24px; animation: slideUp .55s ease-out .25s both;">
    <div class="card-gold-body">
        <div class="section-label">Bancos compatibles</div>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:18px;line-height:1.5;">
            El sistema detecta automáticamente el banco de origen analizando la estructura del archivo.
            Formatos soportados: <strong style="color:var(--text-primary);">{{ $extensiones }}</strong>
        </p>

        <div class="bancos-grid">
            @forelse($bancosSoportados as $banco)
                <div class="banco-card">
                    <div class="banco-name">
                        @php
                            $dotColor = '#f5c842';
                            if(stripos($banco['nombre'] ?? '', 'bcp') !== false || stripos($banco['codigo'] ?? '', 'bcp') !== false) {
                                $dotColor = '#0039a6';
                            } elseif(stripos($banco['nombre'] ?? '', 'interbank') !== false || stripos($banco['codigo'] ?? '', 'interbank') !== false) {
                                $dotColor = '#00a650';
                            }
                        @endphp
                        <span class="banco-dot" style="background:{{ $dotColor }};"></span>
                        {{ $banco['nombre'] ?? $banco['codigo'] ?? 'Banco' }}
                    </div>
                    <div class="banco-meta">{{ $banco['nombre'] ?? '' }}</div>
                    @if(!empty($banco['monedas']))
                        <div class="banco-moneda">
                            {{ is_array($banco['monedas']) ? implode(' · ', $banco['monedas']) : $banco['monedas'] }}
                        </div>
                    @endif
                    @if(!empty($banco['formatos']))
                        <div class="banco-formatos">
                            {{ is_array($banco['formatos']) ? implode(', ', $banco['formatos']) : $banco['formatos'] }}
                        </div>
                    @endif
                </div>
            @empty
                {{-- Fallback: bancos por defecto si no hay datos del controlador --}}
                <div class="banco-card">
                    <div class="banco-name">
                        <span class="banco-dot" style="background:#0039a6;"></span> BCP
                    </div>
                    <div class="banco-meta">Banco de Crédito del Perú</div>
                    <div class="banco-moneda">Soles (PEN) · Dólares (USD)</div>
                </div>
                <div class="banco-card">
                    <div class="banco-name">
                        <span class="banco-dot" style="background:#00a650;"></span> Interbank
                    </div>
                    <div class="banco-meta">Banco Internacional del Perú</div>
                    <div class="banco-moneda">Soles (PEN) · Dólares (USD)</div>
                </div>
            @endforelse
        </div>

        <div class="info-tip">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>Se ignoran automáticamente: ITF, comisiones bancarias, mantenimiento de cuenta y otros cargos administrativos.</p>
        </div>
    </div>
</div>

{{-- OVERLAY DE CARGA --}}
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <p>Procesando archivo...</p>
    <p class="loading-sub">Esto puede tomar unos segundos. No cierres esta ventana.</p>
</div>

@endsection

@push('scripts')
<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('archivoInput');
    const filePill = document.getElementById('filePill');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const btnSubmit = document.getElementById('btnSubmit');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const allowedExtensions = ['xlsx', 'xls', 'csv'];

    // Drag & Drop
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            validarYAsignarArchivo(files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            validarYAsignarArchivo(fileInput.files[0]);
        }
    });

    function validarYAsignarArchivo(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(ext)) {
            alert('Formato no permitido. Use archivos: .xlsx, .xls, .csv');
            return;
        }

        const maxSize = {{ $tamanoMaximoMB ?? 20 }} * 1024 * 1024;
        if (file.size > maxSize) {
            alert('El archivo excede el tamaño máximo de {{ $tamanoMaximoMB ?? 20 }} MB.');
            return;
        }

        // Actualizar UI
        fileName.textContent = file.name;
        fileSize.textContent = formatSize(file.size);
        filePill.classList.add('show');
        dropZone.classList.add('drag-active');
        btnSubmit.disabled = false;
    }

    function removerArchivo() {
        fileInput.value = '';
        filePill.classList.remove('show');
        dropZone.classList.remove('drag-active');
        btnSubmit.disabled = true;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function mostrarCarga() {
        loadingOverlay.classList.add('show');
    }
</script>
@endpush
