@extends('layouts.app')

@section('title', 'Importar Facturas')
@section('breadcrumb', 'Importar Facturas')

@push('styles')
    <style>
        .import-wrap { max-width: 640px; margin: 0 auto; }

        .tipo-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:10px; }

        .tipo-card {
            position: relative;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 22px 16px 16px;
            text-align: center;
            cursor: pointer;
            transition: all .18s;
            background: #f8fafc;
            user-select: none;
        }
        .tipo-card:hover { border-color:#94a3b8; background:#f1f5f9; }

        .tc-icon  { font-size:32px; display:block; margin-bottom:10px; }
        .tc-label { font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); display:block; }
        .tc-desc  { font-size:11px; color:var(--text-muted); margin-top:6px; line-height:1.4; }

        .tc-check {
            position:absolute; top:10px; right:12px;
            width:20px; height:20px; border-radius:50%;
            border:2px solid var(--border);
            display:flex; align-items:center; justify-content:center;
            font-size:11px; font-weight:900;
        }

        .info-strip {
            display:none; align-items:center; gap:10px;
            padding:11px 16px; border-radius:8px;
            font-size:12px; font-weight:600; margin-top:12px;
        }
        .info-strip.show { display:flex; }
        .strip-det { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .strip-ret { background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe; }

        .drop-zone {
            border:2px dashed #e2e8f0; border-radius:12px;
            padding:52px 32px; text-align:center;
            cursor:pointer; transition:border-color .2s, background .2s;
            background:#f8fafc;
        }
        .drop-zone:hover, .drop-zone.over { border-color:var(--accent); background:rgba(59,130,246,.05); }
        .drop-zone h3 { font-size:15px; font-weight:600; color:var(--text-primary); margin:14px 0 6px; }
        .drop-zone p  { font-size:13px; color:var(--text-muted); margin:0; }

        .file-pill {
            display:none; align-items:center; gap:12px;
            background:rgba(34,197,94,.08); border:1px solid rgba(34,197,94,.25);
            border-radius:10px; padding:12px 16px; margin-top:12px;
        }
        .file-pill.show { display:flex; }
        .file-pill .name { font-weight:600; font-size:14px; flex:1; }
        .file-pill .size { font-size:12px; color:var(--text-muted); }
        .file-pill button { background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:20px; padding:2px 6px; border-radius:4px; }
        .file-pill button:hover { background:rgba(239,68,68,.1); color:#dc2626; }

        .btn-submit { margin-top:20px; width:100%; height:46px; font-size:14px; font-weight:700; }

        .result-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:20px; }
        .result-box { background:#f8fafc; border-radius:10px; padding:18px 12px; text-align:center; }
        .result-box .num { font-size:32px; font-weight:700; font-family:'DM Mono',monospace; line-height:1; }
        .result-box .lbl { font-size:11px; color:var(--text-muted); margin-top:6px; text-transform:uppercase; letter-spacing:.05em; }
        .result-box.verde .num { color:#22c55e; }
        .result-box.amber .num { color:#f59e0b; }
        .result-box.azul  .num { color:#3b82f6; }

        .errores-box { margin-top:14px; background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.2); border-radius:8px; padding:12px 14px; max-height:140px; overflow-y:auto; font-size:12px; color:#dc2626; }
        .errores-box li { margin-bottom:4px; list-style:none; }

        .divider { height:1px; background:var(--border, #e2e8f0); margin:24px 0; }

        .section-label {
            font-size:11px; font-weight:700; text-transform:uppercase;
            letter-spacing:.07em; color:var(--text-muted); margin-bottom:10px;
            display:flex; align-items:center; gap:8px;
        }
        .section-label::after { content:''; flex:1; height:1px; background:var(--border, #e2e8f0); }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('facturas.index') }}">Facturas</a>
                <span>›</span>
                <span>Importar Excel</span>
            </div>
            <h1 class="page-title">Importar Facturas</h1>
        </div>
        <a href="{{ route('facturas.index') }}" class="btn btn-ghost">← Volver</a>
    </div>

    {{-- ── RESULTADO ── --}}
    @if(session('resumen'))
        @php $r = session('resumen'); @endphp
        <div class="card import-wrap" style="margin-bottom:24px;padding:24px;">
            <p style="font-weight:700;font-size:15px;margin-bottom:4px;">✓ Importación completada</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;">
                Tipo seleccionado:
                <strong>{{ ($r['tipo_recaudacion'] ?? '') === 'DETRACCION' ? 'Detracción' : 'Retención' }}</strong><br>
                <span style="font-size:12px;color:var(--text-muted);">
                  ℹ Se aplicó solo a las facturas que lo indicaban en columna AI del Excel. Las demás sin recaudación.
                </span>
            </p>
            <div class="result-grid">
                <div class="result-box verde"><div class="num">{{ $r['insertadas'] }}</div><div class="lbl">Insertadas</div></div>
                <div class="result-box amber"><div class="num">{{ $r['omitidas'] }}</div><div class="lbl">Omitidas</div></div>
                <div class="result-box azul"><div class="num">{{ $r['duplicadas'] }}</div><div class="lbl">Duplicadas</div></div>
            </div>
            @if(!empty($r['errores']))
                <div class="errores-box">
                    <ul style="padding:0;margin:0;">
                        @foreach($r['errores'] as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <div style="margin-top:16px;">
                <a href="{{ route('facturas.index') }}" class="btn btn-primary">Ver facturas importadas →</a>
            </div>
        </div>
    @endif

    {{-- ── ERROR ── --}}
    @if(session('error'))
        <div class="card import-wrap" style="margin-bottom:24px;border-left:3px solid #dc2626;padding:18px 24px;">
            <p style="font-weight:600;color:#dc2626;margin-bottom:6px;">Error en la importación</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">{{ session('error') }}</p>
        </div>
    @endif

    {{-- ── FORMULARIO ── --}}
    <div class="card import-wrap">
        <form id="frm" method="POST" action="{{ route('facturas.importar.procesar') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- Input oculto sincronizado desde JS --}}
            <input type="hidden" name="tipo_recaudacion" id="inputTipo" value="">

            <div style="padding:24px 24px 20px;">

                <div class="section-label">① Tipo de recaudación del archivo</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
                    Selecciona el tipo de recaudación. Solo se aplicará a las facturas que lo indiquen en la columna AI del Excel.
                    Las demás quedarán sin recaudación (PENDIENTE).
                </p>

                <div class="tipo-grid">
                    <div class="tipo-card" id="cardDet" onclick="selTipo('DETRACCION')">
                        <span class="tc-check" id="chkDet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Detracción</span>
                        <p class="tc-desc">Facturas con detracción al Banco de la Nación</p>
                    </div>
                    <div class="tipo-card" id="cardRet" onclick="selTipo('RETENCION')">
                        <span class="tc-check" id="chkRet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Retención</span>
                        <p class="tc-desc">Facturas con retención aplicada por el cliente</p>
                    </div>
                </div>

                <div class="info-strip strip-det" id="stripDet">
                    Solo a facturas con "SI" en columna AI: <strong>POR VALIDAR DETRACCIÓN</strong> (si tienen monto).
                </div>
                <div class="info-strip strip-ret" id="stripRet">
                    Solo a facturas con "SI" en columna AI: estado <strong>PENDIENTE</strong> con recaudación del monto en AE.
                </div>

                @error('tipo_recaudacion')
                <p style="color:#dc2626;font-size:12px;margin-top:10px;font-weight:600;">⚠ {{ $message }}</p>
                @enderror
            </div>

            <div class="divider"></div>

            <div style="padding:0 24px 24px;">
                <div class="section-label">② Archivo Excel de Nubefact</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                    Formato: <strong>.xlsx</strong> · Exportado directamente desde Nubefact
                </p>

                <div class="drop-zone" id="dz">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="1.4" style="display:block;margin:0 auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <h3>Arrastra el archivo aquí</h3>
                    <p>o haz clic para seleccionarlo</p>
                </div>

                {{-- Input fuera del drop zone para evitar conflictos --}}
                <input type="file" id="fi" name="archivo" accept=".xlsx,.xls" style="display:none;">

                <div class="file-pill" id="pill">
                    <span style="font-size:24px;"></span>
                    <div style="flex:1;">
                        <div class="name" id="fname"></div>
                        <div class="size" id="fsize"></div>
                    </div>
                    <button type="button" onclick="quitarArchivo()" title="Quitar">✕</button>
                </div>

                @error('archivo')
                <p style="color:#dc2626;font-size:12px;margin-top:8px;font-weight:600;">{{ $message }}</p>
                @enderror

                <button type="submit" class="btn btn-primary btn-submit" id="btnSub" disabled>
                    Importar Facturas
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        (function() {
            // ── Estado ────────────────────────────────────────────────────────
            var tipoActual   = '';
            var tieneArchivo = false;

            // ── Selección de tipo ─────────────────────────────────────────────
            window.selTipo = function(tipo) {
                tipoActual = tipo;

                // Reset
                var cardDet = document.getElementById('cardDet');
                var cardRet = document.getElementById('cardRet');
                var chkDet  = document.getElementById('chkDet');
                var chkRet  = document.getElementById('chkRet');

                cardDet.style.borderColor = '';
                cardDet.style.background  = '';
                cardRet.style.borderColor = '';
                cardRet.style.background  = '';
                chkDet.textContent = '';
                chkDet.style.cssText = '';
                chkRet.textContent = '';
                chkRet.style.cssText = '';

                document.getElementById('stripDet').classList.remove('show');
                document.getElementById('stripRet').classList.remove('show');

                // Activar
                if (tipo === 'DETRACCION') {
                    cardDet.style.borderColor = '#d97706';
                    cardDet.style.background  = '#fef3c7';
                    chkDet.textContent        = '✓';
                    chkDet.style.cssText      = 'color:#fff;background:#d97706;border-color:#d97706;';
                    document.getElementById('stripDet').classList.add('show');
                } else if (tipo === 'RETENCION') {
                    cardRet.style.borderColor = '#7c3aed';
                    cardRet.style.background  = '#ede9fe';
                    chkRet.textContent        = '✓';
                    chkRet.style.cssText      = 'color:#fff;background:#7c3aed;border-color:#7c3aed;';
                    document.getElementById('stripRet').classList.add('show');
                }

                document.getElementById('inputTipo').value = tipo;
                actualizarBoton();
            };

            function actualizarBoton() {
                document.getElementById('btnSub').disabled = !(tieneArchivo && tipoActual !== '');
            }

            // ── Drop zone ─────────────────────────────────────────────────────
            var dz = document.getElementById('dz');
            var fi = document.getElementById('fi');

            dz.addEventListener('click', function(e) {
                e.preventDefault();
                fi.click();
            });

            dz.addEventListener('dragover', function(e) {
                e.preventDefault();
                dz.classList.add('over');
            });

            dz.addEventListener('dragleave', function() {
                dz.classList.remove('over');
            });

            dz.addEventListener('drop', function(e) {
                e.preventDefault();
                dz.classList.remove('over');
                var files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(files[0]);
                        fi.files = dt.files;
                    } catch(err) {}
                    mostrarArchivo(files[0]);
                }
            });

            fi.addEventListener('change', function() {
                if (fi.files && fi.files.length > 0) {
                    mostrarArchivo(fi.files[0]);
                }
            });

            function mostrarArchivo(file) {
                document.getElementById('fname').textContent = file.name;
                document.getElementById('fsize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                document.getElementById('pill').classList.add('show');
                dz.style.display = 'none';
                tieneArchivo = true;
                actualizarBoton();
            }

            window.quitarArchivo = function() {
                fi.value = '';
                document.getElementById('pill').classList.remove('show');
                dz.style.display = '';
                tieneArchivo = false;
                actualizarBoton();
            };

            // ── Submit ────────────────────────────────────────────────────────
            document.getElementById('frm').addEventListener('submit', function(e) {
                if (!tipoActual) {
                    e.preventDefault();
                    alert('Selecciona el tipo de recaudación antes de importar.');
                    return;
                }
                if (!tieneArchivo) {
                    e.preventDefault();
                    alert('Selecciona un archivo Excel antes de importar.');
                    return;
                }
                var btn = document.getElementById('btnSub');
                btn.disabled    = true;
                btn.textContent = 'Procesando… por favor espera';
            });

            // ── Restaurar selección si hay error de validación ────────────────
            var oldTipo = '{{ old('tipo_recaudacion') }}';
            if (oldTipo === 'DETRACCION' || oldTipo === 'RETENCION') {
                selTipo(oldTipo);
            }

        })();
    </script>
@endpush
