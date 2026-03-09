@extends('layouts.app')

@section('title', 'Importar Facturas')
@section('breadcrumb', 'Importar Facturas')

@push('styles')
    <style>
        .import-wrap {
            max-width: 560px;
            margin: 0 auto;
        }

        .drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 56px 32px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
            background: var(--bg-secondary);
        }
        .drop-zone:hover,
        .drop-zone.over {
            border-color: var(--accent-blue);
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
            background: var(--bg-secondary);
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
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
            Formato aceptado: <strong>.xlsx</strong> · Exportado desde Nubefact
        </p>

        <form id="frm" method="POST" action="{{ route('facturas.importar.procesar') }}"
              enctype="multipart/form-data">
            @csrf

            {{-- Drop zone --}}
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

            {{-- Pill de archivo --}}
            <div class="file-pill" id="pill">
                <span style="font-size:22px;">📗</span>
                <div style="flex:1;">
                    <div class="name" id="fname"></div>
                    <div class="size" id="fsize"></div>
                </div>
                <button type="button" onclick="quitar()" title="Quitar">✕</button>
            </div>

            @error('archivo')
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

        // Drag & drop
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

        // Al enviar: feedback visual sin bloquear el submit
        document.getElementById('frm').addEventListener('submit', function() {
            btn.disabled  = true;
            btn.textContent = '⏳ Procesando…';
        });
    </script>
@endpush
