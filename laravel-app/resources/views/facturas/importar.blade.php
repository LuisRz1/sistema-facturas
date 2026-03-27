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
            display:none; align-items:flex-start; gap:10px;
            padding:11px 16px; border-radius:8px;
            font-size:12px; font-weight:600; margin-top:12px;
        }
        .info-strip.show { display:flex; }
        .strip-det { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .strip-ret { background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe; }

        /* Bloque formato SUNAT retenciones */
        .ret-format-box {
            display:none;
            background:#f5f3ff; border:1.5px solid #ddd6fe; border-radius:10px;
            padding:14px 18px; margin-top:14px; font-size:12px; color:#5b21b6;
        }
        .ret-format-box.show { display:block; }
        .ret-format-box h4 { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
        .ret-format-box ul { padding-left:18px; margin:0; }
        .ret-format-box ul li { margin-bottom:4px; line-height:1.5; }
        .ret-format-box strong { color:#4c1d95; }

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

        /* Resultado detracción/nubefact */
        .result-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:20px; }
        .result-box { background:#f8fafc; border-radius:10px; padding:18px 12px; text-align:center; }
        .result-box .num { font-size:32px; font-weight:700; font-family:'DM Mono',monospace; line-height:1; }
        .result-box .lbl { font-size:11px; color:var(--text-muted); margin-top:6px; text-transform:uppercase; letter-spacing:.05em; }
        .result-box.verde .num { color:#22c55e; }
        .result-box.amber .num { color:#f59e0b; }
        .result-box.azul  .num { color:#3b82f6; }
        .result-box.rojo  .num { color:#dc2626; }

        .errores-box { margin-top:14px; background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.2); border-radius:8px; padding:12px 14px; max-height:140px; overflow-y:auto; font-size:12px; color:#dc2626; }
        .errores-box li { margin-bottom:4px; list-style:none; }

        /* Tabla resultado retenciones */
        .ret-result-wrap { margin-top:20px; }
        .ret-result-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#5b21b6; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
        .ret-table { width:100%; border-collapse:collapse; font-size:11px; }
        .ret-table thead tr { background:#4c1d95; color:#fff; }
        .ret-table thead th { padding:7px 10px; text-align:left; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
        .ret-table thead th.r { text-align:right; }
        .ret-table tbody tr { border-bottom:1px solid #f3f4f6; }
        .ret-table tbody tr:nth-child(even) { background:#faf5ff; }
        .ret-table tbody td { padding:7px 10px; }
        .ret-table tbody td.r { text-align:right; font-family:'DM Mono',monospace; }
        .badge-ok   { background:#d1fae5; color:#065f46; padding:2px 7px; border-radius:10px; font-size:9px; font-weight:800; }
        .badge-miss { background:#fee2e2; color:#7f1d1d; padding:2px 7px; border-radius:10px; font-size:9px; font-weight:800; }

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

    {{-- ── RESULTADO RETENCIONES ── --}}
    @if(session('resumen_tipo') === 'retencion' && session('resumen'))
        @php $r = session('resumen'); @endphp
        <div class="card import-wrap" style="margin-bottom:24px;padding:24px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <span style="font-size:20px;"></span>
                <div>
                    <p style="font-weight:700;font-size:15px;margin:0;">Importación de Retenciones completada</p>
                    <p style="font-size:12px;color:var(--text-muted);margin:2px 0 0;">Excel SUNAT procesado correctamente</p>
                </div>
            </div>

            <div class="result-grid" style="grid-template-columns:repeat(4,1fr);">
                <div class="result-box verde">
                    <div class="num">{{ $r['procesadas'] }}</div>
                    <div class="lbl">Retenciones registradas</div>
                </div>
                <div class="result-box rojo">
                    <div class="num">{{ $r['no_encontradas'] }}</div>
                    <div class="lbl">No encontradas</div>
                </div>
                <div class="result-box azul">
                    <div class="num">{{ $r['clientes_creados'] ?? 0 }}</div>
                    <div class="lbl">Clientes creados</div>
                </div>
                <div class="result-box amber">
                    <div class="num">{{ count($r['errores']) }}</div>
                    <div class="lbl">Con error</div>
                </div>
            </div>
            @if(($r['clientes_creados'] ?? 0) > 0)
                <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:6px;padding:9px 12px;margin-top:10px;font-size:12px;color:#1d4ed8;font-weight:600;">
                    ℹ {{ $r['clientes_creados'] }} cliente(s) nuevo(s) creado(s) automáticamente usando el RUC del campo Emisor del Excel.
                </div>
            @endif

            @if(!empty($r['errores']))
                <div class="errores-box" style="margin-top:14px;">
                    <ul style="padding:0;margin:0;">
                        @foreach($r['errores'] as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($r['resultados']))
                <div class="ret-result-wrap">
                    <div class="ret-result-title">
                        <span></span> Detalle de facturas procesadas ({{ count($r['resultados']) }})
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="ret-table">
                            <thead>
                            <tr>
                                <th>FACTURA (Excel)</th>
                                <th>EMISOR (RUC)</th>
                                <th class="r">IMPORTE</th>
                                <th class="r">RETENCIÓN</th>
                                <th class="r">IMP. PAGADO</th>
                                <th>F. EMISIÓN</th>
                                <th>F. RETENCIÓN</th>
                                <th>ESTADO</th>
                                <th>RESULTADO</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($r['resultados'] as $res)
                                <tr style="{{ $res['accion'] === 'NO_ENCONTRADA' ? 'background:#fff1f2;' : '' }}">
                                    <td style="font-family:'DM Mono',monospace;font-weight:700;font-size:11px;color:#7c3aed;">
                                        {{ $res['serie'] }}-{{ $res['numero'] }}
                                        @if(!empty($res['serie_real']))
                                            <div style="font-size:9px;color:#059669;font-weight:600;margin-top:1px;">
                                                ↳ en DB: {{ $res['serie_real'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="font-size:11px;" title="{{ $res['emisor'] }}">
                                        <div style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $res['emisor'] }}</div>
                                        @if(!empty($res['ruc_emisor']))
                                            <div style="font-size:9px;color:#64748b;font-family:'DM Mono',monospace;">{{ $res['ruc_emisor'] }}</div>
                                        @endif
                                    </td>
                                    <td class="r">
                                        {{ $res['importe'] ? 'S/ '.number_format($res['importe'],2) : '—' }}
                                    </td>
                                    <td class="r" style="color:#7c3aed;font-weight:700;">
                                        S/ {{ number_format($res['retencion'],2) }}
                                    </td>
                                    <td class="r" style="color:#059669;">
                                        {{ ($res['importe_pagado'] ?? 0) > 0 ? 'S/ '.number_format($res['importe_pagado'],2) : '—' }}
                                    </td>
                                    <td style="font-size:10px;color:#64748b;">
                                        {{ $res['fecha_emision'] ? \Carbon\Carbon::parse($res['fecha_emision'])->format('d/m/Y') : '—' }}
                                    </td>
                                    <td style="font-size:10px;color:#059669;font-weight:600;">
                                        {{ $res['fecha_recaudacion'] ? \Carbon\Carbon::parse($res['fecha_recaudacion'])->format('d/m/Y') : '—' }}
                                    </td>
                                    <td>
                                            <span style="font-size:10px;font-weight:700;color:#374151;">
                                                {{ $res['estado_anterior'] ?? '—' }}
                                            </span>
                                    </td>
                                    <td>
                                        @if($res['accion'] === 'RETENCION_REGISTRADA')
                                            <span class="badge-ok">✓ OK</span>
                                        @else
                                            @if($res['accion'] === 'NO_ENCONTRADA')
                                                <span class="badge-miss">✗ No encontrada</span>
                                                <a href="{{ route('facturas.index') }}?search={{ $res['serie'] }}-{{ $res['numero'] }}"
                                                   style="font-size:9px;color:#1d4ed8;text-decoration:underline;margin-left:4px;">
                                                    Buscar →
                                                </a>
                                            @endif
                                        @endif

                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p style="font-size:11px;color:#92400e;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;padding:9px 12px;margin-top:12px;">
                        <strong>El importe pagado</strong> se registró como referencia pero requiere validación manual.
                        Confirma en el módulo de facturas si el pago fue efectuado para actualizar el estado.
                    </p>
                </div>
            @endif

            <div style="margin-top:16px;">
                <a href="{{ route('facturas.index') }}" class="btn btn-primary">Ver facturas →</a>
            </div>
        </div>
    @endif

    {{-- ── RESULTADO DETRACCIÓN/NUBEFACT ── --}}
    @if(session('resumen') && session('resumen_tipo') !== 'retencion')
        @php $r = session('resumen'); @endphp
        <div class="card import-wrap" style="margin-bottom:24px;padding:24px;">
            <p style="font-weight:700;font-size:15px;margin-bottom:4px;">✓ Importación completada</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;">
                Tipo seleccionado:
                <strong>{{ ($r['tipo_recaudacion'] ?? '') === 'DETRACCION' ? 'Detracción' : 'Retención' }}</strong><br>
                <span style="font-size:12px;color:var(--text-muted);">
                  ℹ Se aplicó solo a las facturas que lo indicaban en columna AI del Excel.
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
        {{-- El action cambia dinámicamente según el tipo seleccionado --}}
        <form id="frm" method="POST" action="{{ route('facturas.importar.procesar') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- Input oculto sincronizado desde JS --}}
            <input type="hidden" name="tipo_recaudacion" id="inputTipo" value="">

            <div style="padding:24px 24px 20px;">

                <div class="section-label">① Tipo de recaudación del archivo</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
                    Selecciona el tipo de recaudación del archivo a importar.
                </p>

                <div class="tipo-grid">
                    <div class="tipo-card" id="cardDet" onclick="selTipo('DETRACCION')">
                        <span class="tc-check" id="chkDet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Detracción</span>
                        <p class="tc-desc">Facturas con detracción al Banco de la Nación — formato Nubefact</p>
                    </div>
                    <div class="tipo-card" id="cardRet" onclick="selTipo('RETENCION')">
                        <span class="tc-check" id="chkRet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Retención</span>
                        <p class="tc-desc">Facturas con retención aplicada por el cliente — formato SUNAT</p>
                    </div>
                </div>

                {{-- Info strip detracción --}}
                <div class="info-strip strip-det" id="stripDet">
                    <span>ℹ</span>
                    <span>Formato <strong>Nubefact</strong>. Solo se aplica recaudación a las facturas con "SI" en columna AI. Las demás quedarán <strong>PENDIENTE</strong> sin recaudación.</span>
                </div>

                {{-- Info strip retención + formato esperado --}}
                <div class="info-strip strip-ret" id="stripRet">
                    <span></span>
                    <span>Formato <strong>SUNAT — Consulta de Comprobantes Emitidos/Recibidos</strong>. Se registra la retención en las facturas encontradas sin cambiar su estado actual.</span>
                </div>

                <div class="ret-format-box" id="retFormatBox">
                    <h4>Formato esperado del Excel SUNAT</h4>
                    <ul>
                        <li><strong>Emisor:</strong> nombre de la empresa que realizó la retención</li>
                        <li><strong>Fecha de emisión</strong> (lado derecho del bloque) → fecha de pago de la retención</li>
                        <li><strong>Total comprobante</strong> → importe total de la factura</li>
                        <li><strong>Retención S/</strong> → monto retenido que se registra como recaudación</li>
                        <li><strong>Importe pagado</strong> → se guarda como referencia, requiere validación manual</li>
                        <li><strong>Serie + Número</strong> → identifica la factura en el sistema</li>
                        <li><strong>Fecha de emisión</strong> (en la fila de la factura) → fecha de emisión de la factura</li>
                    </ul>
                    <p style="margin-top:10px;font-size:11px;color:#6d28d9;">
                        Se procesan <strong>todas las facturas</strong> del Excel incluyendo las de estado ANULADO.
                        El estado de la factura <strong>no cambia</strong>; solo se registra la retención y se actualiza el monto pendiente.
                    </p>
                </div>

                @error('tipo_recaudacion')
                <p style="color:#dc2626;font-size:12px;margin-top:10px;font-weight:600;">⚠ {{ $message }}</p>
                @enderror
            </div>

            <div class="divider"></div>

            <div style="padding:0 24px 24px;">
                <div class="section-label" id="labelArchivo">② Archivo Excel</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;" id="textoArchivo">
                    Formato: <strong>.xlsx</strong>
                </p>

                <div class="drop-zone" id="dz">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="1.4" style="display:block;margin:0 auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <h3>Arrastra el archivo aquí</h3>
                    <p>o haz clic para seleccionarlo</p>
                </div>

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
            var tipoActual   = '';
            var tieneArchivo = false;

            var RUTA_NUBEFACT  = '{{ route("facturas.importar.procesar") }}';
            var RUTA_RETENCION = '{{ route("facturas.importar.retenciones.procesar") }}';

            // ── Selección de tipo ─────────────────────────────────────────────────
            window.selTipo = function(tipo) {
                tipoActual = tipo;

                var cardDet = document.getElementById('cardDet');
                var cardRet = document.getElementById('cardRet');
                var chkDet  = document.getElementById('chkDet');
                var chkRet  = document.getElementById('chkRet');

                // Reset visual
                cardDet.style.borderColor = cardDet.style.background = '';
                cardRet.style.borderColor = cardRet.style.background = '';
                chkDet.textContent = chkDet.style.cssText = '';
                chkRet.textContent = chkRet.style.cssText = '';

                document.getElementById('stripDet').classList.remove('show');
                document.getElementById('stripRet').classList.remove('show');
                document.getElementById('retFormatBox').classList.remove('show');

                var frm = document.getElementById('frm');

                if (tipo === 'DETRACCION') {
                    cardDet.style.borderColor = '#d97706';
                    cardDet.style.background  = '#fef3c7';
                    chkDet.textContent        = '✓';
                    chkDet.style.cssText      = 'color:#fff;background:#d97706;border-color:#d97706;';
                    document.getElementById('stripDet').classList.add('show');
                    document.getElementById('textoArchivo').innerHTML = 'Formato: <strong>.xlsx</strong> · Exportado directamente desde Nubefact';
                    document.getElementById('labelArchivo').textContent = '② Archivo Excel de Nubefact';
                    document.getElementById('btnSub').textContent = 'Importar Facturas';
                    frm.action = RUTA_NUBEFACT;

                } else if (tipo === 'RETENCION') {
                    cardRet.style.borderColor = '#7c3aed';
                    cardRet.style.background  = '#ede9fe';
                    chkRet.textContent        = '✓';
                    chkRet.style.cssText      = 'color:#fff;background:#7c3aed;border-color:#7c3aed;';
                    document.getElementById('stripRet').classList.add('show');
                    document.getElementById('retFormatBox').classList.add('show');
                    document.getElementById('textoArchivo').innerHTML = 'Formato: <strong>.xlsx</strong> · Exportado desde SUNAT → Consulta de Comprobantes Emitidos/Recibidos';
                    document.getElementById('labelArchivo').textContent = '② Archivo Excel de Retenciones SUNAT';
                    document.getElementById('btnSub').textContent = 'Importar Retenciones';
                    frm.action = RUTA_RETENCION;
                }

                document.getElementById('inputTipo').value = tipo;
                actualizarBoton();
            };

            function actualizarBoton() {
                document.getElementById('btnSub').disabled = !(tieneArchivo && tipoActual !== '');
            }

            // ── Drop zone ─────────────────────────────────────────────────────────
            var dz = document.getElementById('dz');
            var fi = document.getElementById('fi');

            dz.addEventListener('click', function(e) { e.preventDefault(); fi.click(); });
            dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.classList.add('over'); });
            dz.addEventListener('dragleave', function() { dz.classList.remove('over'); });
            dz.addEventListener('drop', function(e) {
                e.preventDefault(); dz.classList.remove('over');
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(e.dataTransfer.files[0]);
                        fi.files = dt.files;
                    } catch(err) {}
                    mostrarArchivo(e.dataTransfer.files[0]);
                }
            });
            fi.addEventListener('change', function() {
                if (fi.files && fi.files.length > 0) mostrarArchivo(fi.files[0]);
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

            // ── Submit ────────────────────────────────────────────────────────────
            document.getElementById('frm').addEventListener('submit', function(e) {
                if (!tipoActual) { e.preventDefault(); alert('Selecciona el tipo de recaudación.'); return; }
                if (!tieneArchivo) { e.preventDefault(); alert('Selecciona un archivo Excel.'); return; }
                var btn = document.getElementById('btnSub');
                btn.disabled    = true;
                btn.textContent = 'Procesando… por favor espera';
            });

            // ── Restaurar selección si hay old() ─────────────────────────────────
            var oldTipo = '{{ old("tipo_recaudacion") }}';
            if (oldTipo === 'DETRACCION' || oldTipo === 'RETENCION') {
                selTipo(oldTipo);
            }

        })();
    </script>
@endpush
