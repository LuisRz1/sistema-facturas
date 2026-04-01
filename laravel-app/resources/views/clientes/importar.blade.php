@extends('layouts.app')
@section('title', 'Importar Clientes')
@section('breadcrumb', 'Importar Clientes')

@push('styles')
    <style>
        :root { --gold:#f5c842; --gold-h:#e8b820; --gold-l:#fffbeb; --gold-b:#ead96a; --gold-m:#d4a017; --gold-d:#9a6e10; }

        @keyframes fadeDown { from{opacity:0;transform:translateY(-12px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideUp  { from{opacity:0;transform:translateY(16px)}  to{opacity:1;transform:translateY(0)} }

        .import-wrap { max-width:680px; margin:0 auto; }

        .drop-zone {
            border:2px dashed var(--gold-b); border-radius:14px; padding:52px 32px;
            text-align:center; cursor:pointer; transition:all .2s; background:#fffdf5;
        }
        .drop-zone:hover, .drop-zone.over { border-color:var(--gold); background:var(--gold-l); }
        .drop-zone h3 { font-size:15px; font-weight:700; color:#1f2937; margin:14px 0 6px; }
        .drop-zone p  { font-size:13px; color:#6b7280; }

        .file-pill {
            display:none; align-items:center; gap:12px;
            background:#d1fae5; border:1px solid #a7f3d0; border-radius:10px;
            padding:12px 16px; margin-top:12px;
        }
        .file-pill.show { display:flex; }
        .file-pill .fname { font-weight:700; font-size:13px; flex:1; color:#065f46; }
        .file-pill .fsize { font-size:12px; color:#059669; }
        .file-pill button { background:none; border:none; cursor:pointer; color:#dc2626; font-size:18px; padding:2px 6px; border-radius:4px; }
        .file-pill button:hover { background:#fee2e2; }

        .btn-submit {
            width:100%; height:46px; margin-top:18px;
            background:var(--gold); color:#000; border:none; border-radius:10px;
            font-size:14px; font-weight:800; cursor:pointer; display:flex;
            align-items:center; justify-content:center; gap:8px;
            transition:all .2s; font-family:inherit;
        }
        .btn-submit:hover:not(:disabled) { background:var(--gold-h); transform:translateY(-1px); box-shadow:0 4px 14px rgba(245,200,66,.35); }
        .btn-submit:disabled { opacity:.55; cursor:not-allowed; transform:none; }

        .result-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:20px; }
        .result-box { border-radius:10px; padding:16px 12px; text-align:center; }
        .result-box .rnum { font-size:30px; font-weight:800; font-family:'DM Mono',monospace; line-height:1; }
        .result-box .rlbl { font-size:10px; text-transform:uppercase; letter-spacing:.06em; font-weight:700; margin-top:6px; }
        .res-verde { background:#d1fae5; }
        .res-verde .rnum,.res-verde .rlbl { color:#065f46; }
        .res-azul  { background:#dbeafe; }
        .res-azul  .rnum,.res-azul  .rlbl { color:#1d4ed8; }
        .res-amber { background:#fef3c7; }
        .res-amber .rnum,.res-amber .rlbl { color:#92400e; }

        .format-box {
            background:#fffbeb; border:1.5px solid var(--gold-b); border-radius:10px;
            padding:16px 18px; margin-bottom:18px;
        }
        .format-box h4 { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-d); margin-bottom:10px; }
        .format-box ul { padding-left:18px; }
        .format-box ul li { font-size:12px; color:var(--gold-d); margin-bottom:4px; line-height:1.5; }
        .format-box ul li strong { color:#1f2937; }

        .spinner { width:20px; height:20px; border:3px solid rgba(0,0,0,.2); border-top-color:#000; border-radius:50%; animation:spin .7s linear infinite; display:none; }
        @keyframes spin { to { transform:rotate(360deg); } }
    </style>
@endpush

@section('content')

    <div class="page-header" style="animation:fadeDown .5s ease-out;">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('clientes.index') }}">Directorio Clientes</a>
                <span>›</span>
                <span>Importar desde Excel</span>
            </div>
            <h1 class="page-title">Importar Clientes</h1>
            <p class="page-desc">Carga masiva de clientes desde el Excel de Nubefact (personas naturales y jurídicas).</p>
        </div>
        <a href="{{ route('clientes.index') }}" class="btn btn-ghost">← Volver</a>
    </div>

    <div class="import-wrap">

        {{-- RESULTADO --}}
        @if(session('resumen_importacion'))
            @php $ri = session('resumen_importacion'); @endphp
            <div class="card" style="margin-bottom:20px;padding:24px;animation:slideUp .4s ease-out;">
                <p style="font-weight:800;font-size:15px;margin-bottom:4px;color:#065f46;">✓ Importación completada</p>
                <div class="result-grid">
                    <div class="result-box res-verde">
                        <div class="rnum">{{ $ri['insertados'] }}</div>
                        <div class="rlbl">Clientes nuevos</div>
                    </div>
                    <div class="result-box res-azul">
                        <div class="rnum">{{ $ri['actualizados'] }}</div>
                        <div class="rlbl">Actualizados</div>
                    </div>
                    <div class="result-box res-amber">
                        <div class="rnum">{{ $ri['omitidos'] }}</div>
                        <div class="rlbl">Omitidos</div>
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <a href="{{ route('clientes.index') }}" class="btn btn-primary">Ver directorio →</a>
                </div>
            </div>
        @endif

        {{-- ERROR --}}
        @if(session('error'))
            <div class="card" style="margin-bottom:20px;border-left:3px solid #dc2626;padding:18px 24px;">
                <p style="font-weight:600;color:#dc2626;margin-bottom:4px;">Error</p>
                <p style="font-size:13px;color:var(--text-muted);">{{ session('error') }}</p>
            </div>
        @endif

        <div class="card" style="animation:slideUp .5s .1s ease-out both;">
            <div class="card-header">
                <div>
                    <div class="card-title">Archivo Excel de Entidades</div>
                    <div class="card-desc">Formato exportado desde Nubefact — "20482304665-ENTIDADES.xlsx"</div>
                </div>
            </div>

            <div style="padding:24px;">

                {{-- Formato esperado --}}
                <div class="format-box">
                    <h4>Columnas que se procesarán</h4>
                    <ul>
                        <li><strong>TIPO DE DOCUMENTO</strong> — <code>6</code> = RUC (Persona Jurídica), <code>1</code> = DNI (Persona Natural)</li>
                        <li><strong>NUMERO</strong> → columna <code>ruc</code> en base de datos (RUC o DNI)</li>
                        <li><strong>DENOMINACION</strong> → columna <code>razon_social</code></li>
                        <li><strong>DIRECCION</strong> → columna <code>direccion_fiscal</code></li>
                        <li><strong>EMAIL</strong> → columna <code>correo</code></li>
                        <li><strong>TELEFONO MOVIL</strong> → columna <code>celular</code></li>
                    </ul>
                    <p style="font-size:11px;color:#92400e;margin-top:10px;">
                        Si el cliente ya existe (mismo número), sus datos serán <strong>actualizados</strong>.
                        Se completarán solo los campos vacíos para no sobreescribir datos existentes.
                    </p>
                </div>

                {{-- Drop zone --}}
                <div class="drop-zone" id="dropZone">
                    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#d4a017" stroke-width="1.4" style="display:block;margin:0 auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <h3>Arrastra el archivo aquí</h3>
                    <p>o haz clic para seleccionarlo · .xlsx / .xls</p>
                </div>
                <input type="file" id="fileInput" accept=".xlsx,.xls" style="display:none;">

                <div class="file-pill" id="filePill">
                    <span style="font-size:22px;"></span>
                    <div style="flex:1;">
                        <div class="fname" id="fileName"></div>
                        <div class="fsize" id="fileSize"></div>
                    </div>
                    <button type="button" onclick="quitarArchivo()" title="Quitar">✕</button>
                </div>

                @error('archivo')
                <p style="color:#dc2626;font-size:12px;margin-top:8px;font-weight:600;">{{ $message }}</p>
                @enderror

                <form id="frmImportar" method="POST" action="{{ route('clientes.importar.procesar') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="archivo" id="hiddenFile" style="display:none;" accept=".xlsx,.xls">
                    <button type="submit" class="btn-submit" id="btnSubmit" disabled>
                        <div class="spinner" id="spinner"></div>
                        <span id="btnTxt">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importar Clientes
                    </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const dz  = document.getElementById('dropZone');
            const fi  = document.getElementById('fileInput');
            let archivoSeleccionado = null;

            dz.addEventListener('click', () => fi.click());
            dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('over'); });
            dz.addEventListener('dragleave', () => dz.classList.remove('over'));
            dz.addEventListener('drop', e => {
                e.preventDefault(); dz.classList.remove('over');
                const f = e.dataTransfer.files[0];
                if (f) cargarArchivo(f);
            });
            fi.addEventListener('change', () => { if (fi.files[0]) cargarArchivo(fi.files[0]); });

            function cargarArchivo(file) {
                archivoSeleccionado = file;
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                document.getElementById('filePill').classList.add('show');
                dz.style.display = 'none';

                // Copiar archivo al input del formulario
                const dt = new DataTransfer();
                dt.items.add(file);
                document.getElementById('hiddenFile').files = dt.files;

                document.getElementById('btnSubmit').disabled = false;
            }

            function quitarArchivo() {
                archivoSeleccionado = null;
                fi.value = '';
                document.getElementById('hiddenFile').value = '';
                document.getElementById('filePill').classList.remove('show');
                dz.style.display = '';
                document.getElementById('btnSubmit').disabled = true;
            }

            document.getElementById('frmImportar').addEventListener('submit', function() {
                const btn = document.getElementById('btnSubmit');
                btn.disabled = true;
                document.getElementById('spinner').style.display = 'block';
                document.getElementById('btnTxt').style.display  = 'none';
            });
        </script>
    @endpush

@endsection
