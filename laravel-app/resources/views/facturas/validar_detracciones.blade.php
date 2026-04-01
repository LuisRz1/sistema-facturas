@extends('layouts.app')
@section('title', 'Validar Detracciones SUNAT')
@section('breadcrumb', 'Validar Detracciones')

@push('styles')
    <style>
        .vd-wrap { max-width: 680px; margin: 0 auto; }

        /* ── STEPS ── */
        .steps-bar {
            display: flex; align-items: center; gap: 0;
            background: #fff; border: 1.5px solid #fce8a8; border-radius: 12px;
            padding: 16px 24px; margin-bottom: 20px;
        }
        .step { display: flex; align-items: center; gap: 10px; flex: 1; }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800; border: 2px solid #fce8a8;
            color: #ca9d1f; background: #fffbeb;
            transition: all .3s;
        }
        .step.active .step-num { background: #f5c842; border-color: #d4a017; color: #000; }
        .step.done   .step-num { background: #d1fae5; border-color: #059669; color: #065f46; }
        .step-label { font-size: 11px; font-weight: 700; color: #9a8840; text-transform: uppercase; letter-spacing: .05em; }
        .step.active .step-label { color: #7a5d0f; }
        .step.done   .step-label { color: #065f46; }
        .step-sep { width: 32px; height: 2px; background: #fce8a8; flex-shrink: 0; }

        /* ── CARD ── */
        .vd-card { background: #fff; border: 1.5px solid #fce8a8; border-radius: 14px; margin-bottom: 20px; overflow: hidden; }
        .vd-card-header { background: #fffbeb; padding: 18px 24px; border-bottom: 1px solid #fce8a8; display: flex; align-items: center; gap: 12px; }
        .vd-card-header .hicon { width: 36px; height: 36px; border-radius: 9px; background: #f5c842; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vd-card-body { padding: 22px 24px; }

        /* ── DROP ZONE ── */
        .drop-zone {
            border: 2px dashed #fce8a8; border-radius: 12px; padding: 52px 32px;
            text-align: center; cursor: pointer; transition: all .2s; background: #fffdf5;
        }
        .drop-zone:hover, .drop-zone.over { border-color: #f5c842; background: #fffbeb; }
        .drop-zone h3 { font-size: 15px; font-weight: 700; color: #1f2937; margin: 14px 0 6px; }
        .drop-zone p  { font-size: 13px; color: #6b7280; }

        /* ── FILE PILL ── */
        .file-pill {
            display: none; align-items: center; gap: 12px;
            background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 10px;
            padding: 12px 16px; margin-top: 12px;
        }
        .file-pill.show { display: flex; }
        .file-pill .fname { font-weight: 700; font-size: 13.5px; flex: 1; color: #065f46; }
        .file-pill .fsize { font-size: 12px; color: #059669; }
        .file-pill button { background: none; border: none; cursor: pointer; color: #dc2626; font-size: 18px; padding: 2px 6px; border-radius: 4px; transition: background .15s; }
        .file-pill button:hover { background: #fee2e2; }

        /* ── SHEET SELECTOR ── */
        .sheet-selector { display: none; background: #fffbeb; border: 1.5px solid #fce8a8; border-radius: 10px; padding: 16px 20px; margin-top: 14px; }
        .sheet-selector.show { display: block; }
        .sheet-selector h4 { font-size: 13px; font-weight: 700; color: #92400e; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .sheet-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; margin: 4px 4px 4px 0;
            border: 1.5px solid #fce8a8; border-radius: 8px; background: #fff;
            font-size: 12px; font-weight: 700; cursor: pointer; transition: all .15s; color: #7a5d0f;
        }
        .sheet-btn:hover { border-color: #f5c842; background: #fffbeb; }
        .sheet-btn.selected { border-color: #f5c842; background: #f5c842; color: #000; }

        /* ── SUBMIT ── */
        .btn-submit {
            width: 100%; height: 48px; margin-top: 18px;
            background: #f5c842; color: #000; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 800; cursor: pointer; display: flex;
            align-items: center; justify-content: center; gap: 8px; transition: all .2s;
            font-family: inherit;
        }
        .btn-submit:hover:not(:disabled) { background: #e8b820; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(245,200,66,.35); }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; box-shadow: none; }

        /* ── LOADING ── */
        .loading-wrap { display: none; flex-direction: column; align-items: center; gap: 16px; padding: 32px; }
        .loading-wrap.show { display: flex; }
        .spinner { width: 40px; height: 40px; border: 4px solid #fce8a8; border-top-color: #f5c842; border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── MODAL RESULTADOS ── */
        .resultado-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .res-box { border-radius: 10px; padding: 16px 12px; text-align: center; }
        .res-box .rnum { font-size: 30px; font-weight: 800; font-family: 'DM Mono', monospace; line-height: 1; }
        .res-box .rlbl { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; margin-top: 6px; }
        .res-verde { background: #d1fae5; }
        .res-verde .rnum { color: #065f46; }
        .res-verde .rlbl { color: #059669; }
        .res-amber { background: #fef3c7; }
        .res-amber .rnum { color: #92400e; }
        .res-amber .rlbl { color: #d97706; }
        .res-slate { background: #f1f5f9; }
        .res-slate .rnum { color: #334155; }
        .res-slate .rlbl { color: #64748b; }

        /* ── TABLA RESULTADOS ── */
        .res-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .res-table thead tr { background: #0f172a; color: #fff; }
        .res-table thead th { padding: 8px 10px; text-align: left; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
        .res-table thead th.r { text-align: right; }
        .res-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .res-table tbody tr:nth-child(even) { background: #f8fafc; }
        .res-table tbody td { padding: 7px 10px; vertical-align: middle; }
        .res-table tbody td.r { text-align: right; }

        .badge-pagada      { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .badge-pendiente   { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .badge-pago_parcial{ background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .arrow-icon { color: #94a3b8; }

        .info-strip { display: flex; align-items: center; gap: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; color: #92400e; font-weight: 600; }
        .empty-result { text-align: center; padding: 40px; color: #94a3b8; }
        .empty-result svg { margin: 0 auto 12px; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('facturas.index') }}">Facturas</a>
                <span>›</span>
                <span>Validar Detracciones SUNAT</span>
            </div>
            <h1 class="page-title">Validar Detracciones</h1>
            <p class="page-desc">Importa el Excel oficial del Banco de la Nación para validar automáticamente las detracciones pendientes.</p>
        </div>
        <a href="{{ route('facturas.index') }}?filterEstado=POR+VALIDAR+DETRACCION" class="btn btn-outline">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            Ver pendientes
        </a>
    </div>

    <div class="vd-wrap">

        {{-- ── PASOS ── --}}
        <div class="steps-bar" id="stepsBar">
            <div class="step active" id="step1">
                <div class="step-num">1</div>
                <div class="step-label">Cargar Excel</div>
            </div>
            <div class="step-sep"></div>
            <div class="step" id="step2">
                <div class="step-num">2</div>
                <div class="step-label">Seleccionar hoja</div>
            </div>
            <div class="step-sep"></div>
            <div class="step" id="step3">
                <div class="step-num">3</div>
                <div class="step-label">Ver resultados</div>
            </div>
        </div>

        {{-- ── CARD CARGA ── --}}
        <div class="vd-card" id="cardCarga">
            <div class="vd-card-header">
                <div class="hicon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#000" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;color:#1f2937;">Archivo de Detracciones SUNAT</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">Descarga el reporte desde el portal del Banco de la Nación o SUNAT (.xlsx / .xls)</div>
                </div>
            </div>
            <div class="vd-card-body">

                {{-- Instrucción de columnas requeridas --}}
                <div class="info-strip">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                    El Excel debe tener en la <strong>Fila 2</strong> las columnas:
                    <strong>Fecha Pago</strong>, <strong>Monto Deposito</strong>,
                    <strong>Serie de Comprobante</strong>, <strong>Numero de Comprobante</strong>
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

                {{-- Pill del archivo seleccionado --}}
                <div class="file-pill" id="filePill">
                    <span style="font-size:22px;"></span>
                    <div style="flex:1;">
                        <div class="fname" id="fileName"></div>
                        <div class="fsize" id="fileSize"></div>
                    </div>
                    <button type="button" onclick="quitarArchivo()" title="Quitar">✕</button>
                </div>

                {{-- Selector de hoja (aparece si hay múltiples hojas) --}}
                <div class="sheet-selector" id="sheetSelector">
                    <h4>
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        El archivo tiene múltiples hojas. Selecciona la que contiene las detracciones:
                    </h4>
                    <div id="sheetButtons"></div>
                </div>

                {{-- Loading --}}
                <div class="loading-wrap" id="loadingWrap">
                    <div class="spinner"></div>
                    <p style="font-size:13px;font-weight:600;color:#7a5d0f;">Procesando detracciones…</p>
                    <p style="font-size:12px;color:#9a8840;">Esto puede tardar unos segundos</p>
                </div>

                {{-- Botón submit --}}
                <button type="button" class="btn-submit" id="btnProcesar" disabled onclick="procesarArchivo()">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Validar Detracciones
                </button>

            </div>
        </div>

    </div>

    {{-- ═══════════════════ MODAL RESULTADOS ═══════════════════ --}}
    <div class="modal-overlay" id="modalResultadosOverlay">
        <div class="modal" style="max-width:800px;">
            <div class="modal-header" style="background:linear-gradient(135deg,#f5c842 0%,#e8b820 100%);">
                <h2 style="color:#000;">✓ Detracciones Procesadas</h2>
                <p style="color:rgba(0,0,0,.65);" id="modalSubtitle">Se procesó el archivo correctamente</p>
                <button onclick="cerrarModal()" style="position:absolute;right:20px;top:20px;background:none;border:none;cursor:pointer;font-size:24px;color:#000;opacity:.6;">×</button>
            </div>
            <div class="modal-body" style="min-height:0;max-height:calc(90vh - 200px);overflow-y:auto;padding:22px 28px;">

                {{-- KPIs --}}
                <div class="resultado-grid" id="resKpis"></div>

                {{-- Tabla de facturas validadas --}}
                <div id="resTablaWrap"></div>

            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModal()" class="btn btn-ghost">Cerrar</button>
                <a href="{{ route('facturas.index') }}" class="btn btn-primary">Ver Facturas →</a>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const RUTA = '{{ route("detracciones.procesar") }}';

        let archivoSeleccionado = null;
        let hojaSeleccionada    = null;

        // ── Drop zone ─────────────────────────────────────────────────────────
        const dz = document.getElementById('dropZone');
        const fi = document.getElementById('fileInput');

        dz.addEventListener('click', () => fi.click());
        dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('over'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('over'));
        dz.addEventListener('drop', e => {
            e.preventDefault(); dz.classList.remove('over');
            const f = e.dataTransfer.files[0];
            if (f) cargarArchivo(f);
        });
        fi.addEventListener('change', () => {
            if (fi.files[0]) cargarArchivo(fi.files[0]);
        });

        function cargarArchivo(file) {
            archivoSeleccionado = file;
            hojaSeleccionada    = null;

            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('filePill').classList.add('show');
            dz.style.display = 'none';

            // Reset sheet selector
            document.getElementById('sheetSelector').classList.remove('show');
            document.getElementById('sheetButtons').innerHTML = '';

            actualizarBoton();
        }

        function quitarArchivo() {
            archivoSeleccionado = null;
            hojaSeleccionada    = null;
            fi.value            = '';
            document.getElementById('filePill').classList.remove('show');
            dz.style.display    = '';
            document.getElementById('sheetSelector').classList.remove('show');
            actualizarBoton();
        }

        function actualizarBoton() {
            const btn = document.getElementById('btnProcesar');
            btn.disabled = !(archivoSeleccionado !== null);
        }

        function seleccionarHoja(nombre, btn) {
            hojaSeleccionada = nombre;
            document.querySelectorAll('.sheet-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            actualizarBoton();
        }

        // ── Actualizar pasos ──────────────────────────────────────────────────
        function setStep(active) {
            [1, 2, 3].forEach(n => {
                const el = document.getElementById('step' + n);
                el.classList.remove('active', 'done');
                if (n < active)  el.classList.add('done');
                if (n === active) el.classList.add('active');
            });
        }

        // ── Procesar ──────────────────────────────────────────────────────────
        async function procesarArchivo() {
            if (!archivoSeleccionado) return;

            // Si hay selector de hojas y no se seleccionó ninguna, igual intentar
            // (la primera vez sin sheet_name detectará multi-sheet y mostrará el selector)

            const formData = new FormData();
            formData.append('_token',  CSRF);
            formData.append('archivo', archivoSeleccionado);
            if (hojaSeleccionada) formData.append('sheet_name', hojaSeleccionada);

            // Mostrar loading
            document.getElementById('btnProcesar').style.display  = 'none';
            document.getElementById('loadingWrap').classList.add('show');
            setStep(hojaSeleccionada ? 2 : 1);

            try {
                const res  = await fetch(RUTA, { method: 'POST', body: formData });
                const data = await res.json();

                document.getElementById('loadingWrap').classList.remove('show');
                document.getElementById('btnProcesar').style.display = '';

                if (!data.success && data.multi_sheet) {
                    // ── Mostrar selector de hojas ─────────────────────────────
                    setStep(2);
                    const sel = document.getElementById('sheetSelector');
                    const btns = document.getElementById('sheetButtons');
                    btns.innerHTML = data.sheets.map(s => `
                    <button type="button" class="sheet-btn" onclick="seleccionarHoja('${s.replace(/'/g, "\\'")}', this)">
                         ${s}
                    </button>
                `).join('');
                    sel.classList.add('show');
                    document.getElementById('btnProcesar').disabled = true;
                    return;
                }

                if (!data.success) {
                    alert(' Error: ' + (data.error || 'Error desconocido'));
                    document.getElementById('btnProcesar').disabled = false;
                    return;
                }

                // ── Éxito: mostrar modal de resultados ────────────────────────
                setStep(3);
                mostrarResultados(data);

            } catch (err) {
                document.getElementById('loadingWrap').classList.remove('show');
                document.getElementById('btnProcesar').style.display = '';
                alert('Error de red: ' + err.message);
            }
        }

        // ── Modal resultados ──────────────────────────────────────────────────
        function mostrarResultados(data) {
            document.getElementById('modalSubtitle').textContent =
                `${data.total_filas} filas procesadas del Excel`;

            // KPIs
            document.getElementById('resKpis').innerHTML = `
            <div class="res-box res-verde">
                <div class="rnum">${data.total_validadas}</div>
                <div class="rlbl">Facturas validadas</div>
            </div>
            <div class="res-box res-amber">
                <div class="rnum">${data.ya_validadas}</div>
                <div class="rlbl">Ya validadas / otro estado</div>
            </div>
            <div class="res-box res-slate">
                <div class="rnum">${data.no_encontradas}</div>
                <div class="rlbl">No encontradas en sistema</div>
            </div>
        `;

            // Tabla
            const wrap = document.getElementById('resTablaWrap');
            if (!data.validadas || data.validadas.length === 0) {
                wrap.innerHTML = `
                <div class="empty-result">
                    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p style="font-weight:700;font-size:14px;color:#475569;">No se encontraron facturas para validar</p>
                    <p style="font-size:12px;margin-top:4px;color:#94a3b8;">
                        Ninguna factura del Excel estaba en estado "POR VALIDAR DETRACCION" o no coincidieron serie/número.
                    </p>
                </div>`;
                document.getElementById('modalResultadosOverlay').classList.add('open');
                return;
            }

            const badgeMap = {
                'PAGADA':      'badge-pagada',
                'PENDIENTE':   'badge-pendiente',
                'PAGO PARCIAL':'badge-pago_parcial',
            };

            const rows = data.validadas.map(f => `
            <tr>
                <td style="font-family:'DM Mono',monospace;font-weight:700;font-size:11px;color:#d97706;">
                    ${f.serie}-${f.numero}
                </td>
                <td style="font-size:11px;font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    ${f.razon_social}
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;" class="r">
                    ${f.moneda} ${f.importe_total}
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;color:#d97706;" class="r">
                    ${f.moneda} ${f.monto_detraccion}
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;color:#dc2626;" class="r">
                    ${f.monto_pendiente !== '0.00' ? f.moneda + ' ' + f.monto_pendiente : '—'}
                </td>
                <td style="font-size:10px;color:#64748b;">
                    ${f.fecha_recaudacion ?? '—'}
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;">
                        <span style="font-size:9px;color:#94a3b8;text-transform:uppercase;font-weight:700;letter-spacing:.4px;">POR VALIDAR</span>
                        <span style="color:#94a3b8;">→</span>
                        <span class="${badgeMap[f.estado_nuevo] ?? 'badge-pendiente'}">${f.estado_nuevo}</span>
                    </span>
                </td>
            </tr>
        `).join('');

            wrap.innerHTML = `
            <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                Detalle de facturas validadas (${data.validadas.length})
            </div>
            <div style="overflow-x:auto;">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>FACTURA</th>
                            <th>CLIENTE</th>
                            <th class="r">IMPORTE</th>
                            <th class="r">DETRACCIÓN</th>
                            <th class="r">PENDIENTE</th>
                            <th>FECHA RECAUD.</th>
                            <th>CAMBIO DE ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;

            document.getElementById('modalResultadosOverlay').classList.add('open');
        }

        function cerrarModal() {
            document.getElementById('modalResultadosOverlay').classList.remove('open');
            // Si se validaron facturas, recargar para que la lista refleje los cambios
            if (document.getElementById('step3').classList.contains('active') ||
                document.getElementById('step3').classList.contains('done')) {
                // No forzar recarga aquí — el usuario puede ir a facturas desde el botón
            }
        }

        document.getElementById('modalResultadosOverlay').addEventListener('click', e => {
            if (e.target === e.currentTarget) cerrarModal();
        });
    </script>
@endpush
