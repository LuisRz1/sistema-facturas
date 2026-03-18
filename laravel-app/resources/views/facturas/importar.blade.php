@extends('layouts.app')

@section('title', 'Importar Facturas')
@section('breadcrumb', 'Importar Facturas')

@push('styles')
    <style>
        .import-wrap { max-width: 640px; margin: 0 auto; }

        /* ── Selector tipo ── */
        .tipo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 10px;
        }

        .tipo-card {
            position: relative;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 18px 14px 14px;
            text-align: center;
            cursor: pointer;
            transition: all .18s;
            background: #f8fafc;
            user-select: none;
        }

        .tipo-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .tipo-card:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
        }

        /* DETRACCION */
        .tipo-card.sel-det { border-color: #d97706; background: #fef3c7; }
        .tipo-card.sel-det .tc-icon { color: #92400e; }
        .tipo-card.sel-det .tc-label { color: #92400e; }

        /* RETENCION */
        .tipo-card.sel-ret { border-color: #7c3aed; background: #ede9fe; }
        .tipo-card.sel-ret .tc-icon { color: #5b21b6; }
        .tipo-card.sel-ret .tc-label { color: #5b21b6; }

        /* NINGUNA */
        .tipo-card.sel-nin { border-color: #64748b; background: #f1f5f9; }
        .tipo-card.sel-nin .tc-icon { color: #475569; }
        .tipo-card.sel-nin .tc-label { color: #475569; }

        .tc-icon  { font-size: 28px; display: block; margin-bottom: 8px; }
        .tc-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .tc-desc  { font-size: 11px; color: var(--text-muted); margin-top: 4px; line-height: 1.4; }

        .tc-check {
            position: absolute;
            top: 8px; right: 10px;
            width: 18px; height: 18px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 900;
            transition: all .15s;
        }

        .tipo-card.sel-det .tc-check { border-color: #d97706; background: #d97706; color: #fff; }
        .tipo-card.sel-ret .tc-check { border-color: #7c3aed; background: #7c3aed; color: #fff; }
        .tipo-card.sel-nin .tc-check { border-color: #64748b; background: #64748b; color: #fff; }

        /* ── Info strip ── */
        .info-strip {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .info-strip.show { display: flex; }
        .strip-det { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .strip-ret { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
        .strip-nin { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* ── Drop zone ── */
        .drop-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 48px 32px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            background: #f8fafc;
        }
        .drop-zone:hover, .drop-zone.over {
            border-color: var(--accent);
            background: rgba(59,130,246,.05);
        }
        .drop-zone h3 { font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 14px 0 6px; }
        .drop-zone p  { font-size: 13px; color: var(--text-muted); margin: 0; }

        .file-pill {
            display: none;
            align-items: center;
            gap: 12px;
            background: rgba(34,197,94,.08);
            border: 1px solid rgba(34,197,94,.25);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 12px;
        }
        .file-pill.show { display: flex; }
        .file-pill .name { font-weight: 600; font-size: 14px; flex: 1; }
        .file-pill .size { font-size: 12px; color: var(--text-muted); }
        .file-pill button { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 18px; padding: 2px 6px; border-radius: 4px; }
        .file-pill button:hover { background: rgba(239,68,68,.1); color: #dc2626; }

        .btn-submit { margin-top: 20px; width: 100%; height: 46px; font-size: 14px; font-weight: 700; }

        /* ── Resultado ── */
        .result-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
        .result-box { background: #f8fafc; border-radius: 10px; padding: 18px 12px; text-align: center; }
        .result-box .num { font-size: 32px; font-weight: 700; font-family: 'DM Mono', monospace; line-height: 1; }
        .result-box .lbl { font-size: 11px; color: var(--text-muted); margin-top: 6px; text-transform: uppercase; letter-spacing: .05em; }
        .result-box.verde .num { color: #22c55e; }
        .result-box.amber .num { color: #f59e0b; }
        .result-box.azul  .num { color: #3b82f6; }

        .errores-box { margin-top: 14px; background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); border-radius: 8px; padding: 12px 14px; max-height: 140px; overflow-y: auto; font-size: 12px; color: #dc2626; }
        .errores-box li { margin-bottom: 4px; list-style: none; }

        .divider { height: 1px; background: var(--border, #e2e8f0); margin: 22px 0; }

        .section-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
            color: var(--text-muted); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border, #e2e8f0); }
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
        @php
            $r = session('resumen');
            $tipoLabel = match($r['tipo_recaudacion'] ?? 'NINGUNA') {
                'DETRACCION' => ['icon' => '🏦', 'text' => 'Detracción', 'class' => 'strip-det'],
                'RETENCION'  => ['icon' => '📋', 'text' => 'Retención',  'class' => 'strip-ret'],
                default      => ['icon' => '➖', 'text' => 'Sin recaudación', 'class' => 'strip-nin'],
            };
        @endphp
        <div class="card import-wrap" style="margin-bottom:24px;padding:24px;">
            <p style="font-weight:700;font-size:15px;margin-bottom:4px;">✓ Importación completada</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;">
                Tipo aplicado: <strong>{{ $tipoLabel['icon'] }} {{ $tipoLabel['text'] }}</strong>
            </p>
            <div class="result-grid">
                <div class="result-box verde"><div class="num">{{ $r['insertadas'] }}</div><div class="lbl">Insertadas</div></div>
                <div class="result-box amber"><div class="num">{{ $r['omitidas'] }}</div><div class="lbl">Omitidas</div></div>
                <div class="result-box azul"><div class="num">{{ $r['duplicadas'] }}</div><div class="lbl">Duplicadas</div></div>
            </div>
            @if(!empty($r['errores']))
                <div class="errores-box">
                    <ul style="padding:0;margin:0;">
                        @foreach($r['errores'] as $e)
                            <li>⚠ {{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div style="margin-top:16px;display:flex;gap:10px;">
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

            <div style="padding:24px 24px 20px;">

                {{-- ── PASO 1: TIPO ── --}}
                <div class="section-label">① Tipo de recaudación del archivo</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
                    Indica qué tipo de recaudación aplica a <strong>todas</strong> las facturas de este Excel.
                    El monto se leerá de la columna AE del archivo.
                </p>

                <div class="tipo-grid">
                    {{-- DETRACCION --}}
                    <label class="tipo-card" id="cardDet" onclick="selTipo('DETRACCION')">
                        <input type="radio" name="tipo_recaudacion" value="DETRACCION"
                            {{ old('tipo_recaudacion') === 'DETRACCION' ? 'checked' : '' }}>
                        <span class="tc-check" id="chkDet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Detracción</span>
                        <p class="tc-desc">Facturas con detracción al Banco de la Nación</p>
                    </label>

                    {{-- RETENCION --}}
                    <label class="tipo-card" id="cardRet" onclick="selTipo('RETENCION')">
                        <input type="radio" name="tipo_recaudacion" value="RETENCION"
                            {{ old('tipo_recaudacion') === 'RETENCION' ? 'checked' : '' }}>
                        <span class="tc-check" id="chkRet"></span>
                        <span class="tc-icon"></span>
                        <span class="tc-label">Retención</span>
                        <p class="tc-desc">Facturas con retención aplicada por el cliente</p>
                    </label>

                </div>

                {{-- Info strip dinámica --}}
                <div class="info-strip strip-det" id="stripDet">
                    Las facturas se importarán en estado <strong>POR VALIDAR DETRACCIÓN</strong> hasta que confirmes la detracción en cada una.
                </div>
                <div class="info-strip strip-ret" id="stripRet">
                    Las facturas se importarán en estado <strong>PENDIENTE</strong>. El monto de retención se registra desde la columna AE del Excel.
                </div>

                @error('tipo_recaudacion')
                <p style="color:#dc2626;font-size:12px;margin-top:8px;">{{ $message }}</p>
                @enderror

            </div>

            <div class="divider"></div>

            {{-- ── PASO 2: ARCHIVO ── --}}
            <div style="padding:0 24px 24px;">
                <div class="section-label" style="margin-top:4px;">② Archivo Excel de Nubefact</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                    Formato aceptado: <strong>.xlsx</strong> · Exportado directamente desde Nubefact
                </p>

                <div class="drop-zone" id="dz" onclick="document.getElementById('fi').click()">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" style="display:block;margin:0 auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <h3>Arrastra el archivo aquí</h3>
                    <p>o haz clic para seleccionarlo</p>
                </div>

                <input type="file" id="fi" name="archivo" accept=".xlsx,.xls" style="display:none"
                       onchange="seleccionar(this)">

                <div class="file-pill" id="pill">
                    <span style="font-size:22px;"></span>
                    <div style="flex:1;">
                        <div class="name" id="fname"></div>
                        <div class="size" id="fsize"></div>
                    </div>
                    <button type="button" onclick="quitar()" title="Quitar">✕</button>
                </div>

                @error('archivo')
                <p style="color:#dc2626;font-size:12px;margin-top:8px;">{{ $message }}</p>
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
        let tipoSeleccionado = '{{ old('tipo_recaudacion', '') }}';

        // Aplicar estado inicial si había old()
        if (tipoSeleccionado) selTipo(tipoSeleccionado, false);

        function selTipo(tipo, updateRadio = true) {
            tipoSeleccionado = tipo;

            // Reset cards
            ['Det','Ret','Nin'].forEach(t => {
                document.getElementById('card' + t).className = 'tipo-card';
                document.getElementById('chk'  + t).textContent = '';
            });

            // Reset strips
            ['stripDet','stripRet','stripNin'].forEach(id => {
                document.getElementById(id).classList.remove('show');
            });

            // Activar la seleccionada
            const map = { DETRACCION: ['Det','sel-det','stripDet'], RETENCION: ['Ret','sel-ret','stripRet'], NINGUNA: ['Nin','sel-nin','stripNin'] };
            if (map[tipo]) {
                const [suffix, cls, stripId] = map[tipo];
                document.getElementById('card' + suffix).classList.add(cls);
                document.getElementById('chk'  + suffix).textContent = '✓';
                document.getElementById(stripId).classList.add('show');
            }

            // Sincronizar radio button
            if (updateRadio) {
                document.querySelector(`input[name="tipo_recaudacion"][value="${tipo}"]`).checked = true;
            }

            actualizarBoton();
        }

        function actualizarBoton() {
            const tieneArchivo = document.getElementById('fi').files.length > 0;
            document.getElementById('btnSub').disabled = !(tieneArchivo && tipoSeleccionado);
        }

        // ── Drag & drop ──────────────────────────────────────────────
        const dz   = document.getElementById('dz');
        const pill = document.getElementById('pill');
        const btn  = document.getElementById('btnSub');

        dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('over'); });
        dz.addEventListener('dragleave', () => dz.classList.remove('over'));
        dz.addEventListener('drop', e => {
            e.preventDefault();
            dz.classList.remove('over');
            const f = e.dataTransfer.files[0];
            if (f) { document.getElementById('fi').files = e.dataTransfer.files; mostrar(f); }
        });

        function seleccionar(input) {
            if (input.files[0]) mostrar(input.files[0]);
        }

        function mostrar(f) {
            document.getElementById('fname').textContent = f.name;
            document.getElementById('fsize').textContent = (f.size / 1024 / 1024).toFixed(2) + ' MB';
            pill.classList.add('show');
            dz.style.display = 'none';
            actualizarBoton();
        }

        function quitar() {
            document.getElementById('fi').value = '';
            pill.classList.remove('show');
            dz.style.display = '';
            actualizarBoton();
        }

        document.getElementById('frm').addEventListener('submit', function() {
            btn.disabled    = true;
            btn.textContent = 'Procesando…';
        });
    </script>
@endpush
