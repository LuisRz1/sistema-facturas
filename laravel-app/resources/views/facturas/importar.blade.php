@extends('layouts.app')

@section('title', 'Importar Facturas')
@section('breadcrumb', 'Importar Facturas')

@push('styles')
    <style>
        .import-wrap {
            max-width: 620px;
            margin: 0 auto;
        }

        /* ── Tipo recaudación ── */
        .recaudacion-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 6px;
        }

        .recaudacion-card {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: all .18s;
            background: var(--bg-secondary, #f8fafc);
            user-select: none;
        }

        .recaudacion-card:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
        }

        .recaudacion-card.selected {
            border-color: var(--accent);
            background: #dbeafe;
        }

        .recaudacion-card.selected .rc-icon { color: var(--accent); }
        .recaudacion-card.selected .rc-label { color: var(--accent); font-weight: 800; }

        .rc-icon { font-size: 22px; margin-bottom: 6px; display: block; }
        .rc-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); display: block; }

        .rc-ninguna.selected { border-color: #64748b; background: #f1f5f9; }
        .rc-ninguna.selected .rc-label { color: #64748b; }

        .rc-detraccion.selected { border-color: #d97706; background: #fef3c7; }
        .rc-detraccion.selected .rc-icon,
        .rc-detraccion.selected .rc-label { color: #92400e; }

        .rc-retencion.selected { border-color: #7c3aed; background: #ede9fe; }
        .rc-retencion.selected .rc-icon,
        .rc-retencion.selected .rc-label { color: #5b21b6; }

        .rc-autodetraccion.selected { border-color: #059669; background: #d1fae5; }
        .rc-autodetraccion.selected .rc-icon,
        .rc-autodetraccion.selected .rc-label { color: #065f46; }

        .porcentaje-row {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-top: 8px;
        }
        .porcentaje-row.show { display: flex; }
        .porcentaje-row label { font-size: 12px; font-weight: 700; color: var(--text-muted); white-space: nowrap; text-transform: uppercase; letter-spacing: .05em; }
        .porcentaje-row input { width: 90px; text-align: center; font-weight: 700; }
        .porcentaje-row span  { font-size: 13px; color: var(--text-muted); }

        /* ── Drop zone ── */
        .drop-zone {
            border: 2px dashed var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 48px 32px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            background: var(--bg-secondary, #f8fafc);
        }
        .drop-zone:hover,
        .drop-zone.over {
            border-color: var(--accent, #1d4ed8);
            background: rgba(59,130,246,.05);
        }
        .drop-zone svg { opacity: .35; margin-bottom: 14px; }
        .drop-zone h3  { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .drop-zone p   { font-size: 13px; color: var(--text-muted); margin: 0; }

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
        .file-pill button {
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); font-size: 18px; line-height: 1;
            padding: 2px 6px; border-radius: 4px;
        }
        .file-pill button:hover { background: rgba(239,68,68,.1); color: var(--accent-red); }

        .btn-submit {
            margin-top: 20px;
            width: 100%;
            height: 44px;
            font-size: 14px;
            font-weight: 600;
        }

        /* Resultado */
        .result-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
        }
        .result-box {
            background: var(--bg-secondary, #f8fafc);
            border-radius: 10px;
            padding: 18px 12px;
            text-align: center;
        }
        .result-box .num {
            font-size: 30px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            line-height: 1;
        }
        .result-box .lbl {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .result-box.verde  .num { color: #22c55e; }
        .result-box.amber  .num { color: #f59e0b; }
        .result-box.azul   .num { color: #3b82f6; }

        .resumen-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        .tag-ninguna       { background: #f1f5f9; color: #475569; }
        .tag-detraccion    { background: #fef3c7; color: #92400e; }
        .tag-retencion     { background: #ede9fe; color: #5b21b6; }
        .tag-autodetraccion{ background: #d1fae5; color: #065f46; }

        .errores-box {
            margin-top: 14px;
            background: rgba(239,68,68,.06);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 8px;
            padding: 12px 14px;
            max-height: 140px;
            overflow-y: auto;
            font-size: 12px;
            color: var(--accent-red);
        }
        .errores-box li { margin-bottom: 4px; list-style: none; }

        .section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border, #e2e8f0);
        }

        .divider { height: 1px; background: var(--border, #e2e8f0); margin: 22px 0; }
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
            $tipoTag = strtolower($r['tipo_recaudacion'] ?? 'ninguna');
            $tagLabels = [
                'ninguna'        => ['icon' => '',  'label' => 'Sin recaudación'],
                'detraccion'     => ['icon' => '', 'label' => 'Detracción ' . ($r['porcentaje'] ?? 0) . '%'],
                'retencion'      => ['icon' => '', 'label' => 'Retención '   . ($r['porcentaje'] ?? 0) . '%'],
                'autodetraccion' => ['icon' => '', 'label' => 'Autodetracción ' . ($r['porcentaje'] ?? 0) . '%'],
            ];
            $tagInfo = $tagLabels[$tipoTag] ?? $tagLabels['ninguna'];
        @endphp
        <div class="card import-wrap" style="margin-bottom:24px;">
            <p style="font-weight:600;font-size:15px;margin-bottom:4px;">✓ Importación completada</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">El archivo fue procesado correctamente.</p>
            <div class="result-grid">
                <div class="result-box verde">
                    <div class="num">{{ $r['insertadas'] }}</div>
                    <div class="lbl">Insertadas</div>
                </div>
                <div class="result-box amber">
                    <div class="num">{{ $r['omitidas'] }}</div>
                    <div class="lbl">Omitidas</div>
                </div>
                <div class="result-box azul">
                    <div class="num">{{ $r['duplicadas'] }}</div>
                    <div class="lbl">Duplicadas</div>
                </div>
            </div>
            <div>
                <span class="resumen-tag tag-{{ $tipoTag }}">
                    {{ $tagInfo['icon'] }} Recaudación aplicada: {{ $tagInfo['label'] }}
                </span>
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
            <div style="margin-top:16px;">
                <a href="{{ route('facturas.index') }}" class="btn btn-primary">Ver facturas importadas →</a>
            </div>
        </div>
    @endif

    {{-- ── ERROR ── --}}
    @if(session('error'))
        <div class="card import-wrap" style="margin-bottom:24px;border-left:3px solid var(--accent-red);">
            <p style="font-weight:600;color:var(--accent-red);margin-bottom:6px;">Error en la importación</p>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">{{ session('error') }}</p>
        </div>
    @endif

    {{-- ── FORMULARIO ── --}}
    <div class="card import-wrap">
        <form id="frm" method="POST" action="{{ route('facturas.importar.procesar') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- ── SECCIÓN 1: TIPO DE RECAUDACIÓN ── --}}
            <div style="margin-bottom: 24px; padding: 20px 24px; width: 100%;">
                <div class="section-label">① Tipo de recaudación a aplicar</div>
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">
                    Selecciona el tipo de recaudación que se asignará a <strong>todas</strong> las facturas de este archivo.
                </p>

                <input type="hidden" name="tipo_recaudacion" id="tipoRecaudacionInput" value="">

                <div class="recaudacion-grid">
                    <div class="recaudacion-card rc-ninguna selected" data-value="" onclick="seleccionarTipo(this)">
                        <span class="rc-icon"></span>
                        <span class="rc-label">Ninguna</span>
                    </div>
                    <div class="recaudacion-card rc-detraccion" data-value="DETRACCION" onclick="seleccionarTipo(this)">
                        <span class="rc-icon"></span>
                        <span class="rc-label">Detracción</span>
                    </div>
                    <div class="recaudacion-card rc-retencion" data-value="RETENCION" onclick="seleccionarTipo(this)">
                        <span class="rc-icon"></span>
                        <span class="rc-label">Retención</span>
                    </div>
                    <div class="recaudacion-card rc-autodetraccion" data-value="AUTODETRACCION" onclick="seleccionarTipo(this)">
                        <span class="rc-icon"></span>
                        <span class="rc-label">Autodetracción</span>
                    </div>
                </div>

                <div class="porcentaje-row" id="porcentajeRow">
                    <label for="porcentajeInput">Porcentaje:</label>
                    <input type="number"
                           name="porcentaje_recaudacion"
                           id="porcentajeInput"
                           class="form-input"
                           value="10"
                           min="0" max="100" step="0.01"
                           placeholder="10">
                    <span>% del importe total de cada factura</span>
                </div>
            </div>

            <div class="divider"></div>

            {{-- ── SECCIÓN 2: ARCHIVO EXCEL ── --}}
            <div style="margin-bottom: 24px; padding: 20px 24px; width: 100%;">
                <div class="section-label">② Archivo Excel de Nubefact</div>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                    Formato aceptado: <strong>.xlsx</strong> · Exportado desde Nubefact
                </p>

                <div class="drop-zone" id="dz" onclick="document.getElementById('fi').click()">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
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
            </div>


            @error('archivo')
            <p style="color:var(--accent-red);font-size:12px;margin-top:8px;">{{ $message }}</p>
            @enderror
            @error('tipo_recaudacion')
            <p style="color:var(--accent-red);font-size:12px;margin-top:8px;">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn btn-primary btn-submit" id="btnSub" disabled>
                Importar Facturas
            </button>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        const dz   = document.getElementById('dz');
        const pill = document.getElementById('pill');
        const btn  = document.getElementById('btnSub');

        // ── Selección de tipo de recaudación ──────────────────────────────
        function seleccionarTipo(card) {
            document.querySelectorAll('.recaudacion-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            const valor = card.dataset.value;
            document.getElementById('tipoRecaudacionInput').value = valor;

            const porcentajeRow = document.getElementById('porcentajeRow');
            porcentajeRow.classList.toggle('show', valor !== '');
        }

        // ── Drag & drop ───────────────────────────────────────────────────
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
            btn.disabled = false;
            btn.textContent = 'Importar Facturas';
        }

        function quitar() {
            document.getElementById('fi').value = '';
            pill.classList.remove('show');
            btn.disabled = true;
        }

        document.getElementById('frm').addEventListener('submit', function() {
            btn.disabled  = true;
            btn.textContent = ' Procesando…';
        });
    </script>
@endpush
