@extends('layouts.app')

@section('title', 'Reportes')
@section('breadcrumb', 'Reportes Financieros')

@push('styles')
    <style>
        .filtros-card { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
        .filtro-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
        .filtro-group label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }

        .report-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .rs-box { background: var(--card-bg); border-radius: var(--radius); padding: 20px 22px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 4px; }
        .rs-label { font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); font-weight: 600; }
        .rs-value { font-size: 22px; font-weight: 800; font-family: 'DM Mono', monospace; }
        .rs-box.azul  .rs-value { color: var(--accent); }
        .rs-box.verde .rs-value { color: var(--green); }
        .rs-box.rojo  .rs-value { color: var(--red); }
        .rs-box.amber .rs-value { color: var(--amber); }

        .preview-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }

        .badge-estado { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .estado-PENDIENTE  { background:#fef3c7; color:#92400e; }
        .estado-POR_VENCER { background:#ffedd5; color:#c2410c; }
        .estado-VENCIDA    { background:#fee2e2; color:#991b1b; }
        .estado-PAGADA     { background:#d1fae5; color:#065f46; }
        .estado-ANULADA    { background:#f1f5f9; color:#64748b; }
        .estado-OBSERVADA  { background:#ede9fe; color:#5b21b6; }

        .mono       { font-family: 'DM Mono', monospace; font-size: 12px; }
        .text-right { text-align: right; }
        .detrac-cell { color: #d97706; font-weight: 700; }
        .neto-cell   { color: #059669; font-weight: 700; }

        .btn-pdf {
            background: #0f172a; color: #fff; border: none;
            padding: 10px 20px; border-radius: 8px; font-size: 13px;
            font-weight: 700; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            transition: background .15s; text-decoration: none;
        }
        .btn-pdf:hover { background: #1e293b; color: #fff; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Reportes Financieros</h1>
            <p class="page-desc">Filtra por período, cliente y estado para generar el reporte en PDF.</p>
        </div>
    </div>

    {{-- ── FILTROS ── --}}
    <div class="card" style="margin-bottom:24px;padding:20px 24px;">
        <form id="frmFiltros" method="GET" action="{{ route('reportes.pdf') }}" target="_blank">
            <div class="filtros-card">

                {{-- Mes --}}
                <div class="filtro-group" style="max-width:160px;">
                    <label>Mes</label>
                    <select name="mes" class="form-input" id="selMes">
                        <option value="">Todos</option>
                        @foreach($meses as $num => $nombre)
                            <option value="{{ $num }}" {{ now()->month == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Año --}}
                <div class="filtro-group" style="max-width:110px;">
                    <label>Año</label>
                    <select name="anio" class="form-input" id="selAnio">
                        @foreach($anios as $a)
                            <option value="{{ $a }}" {{ now()->year == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cliente --}}
                <div class="filtro-group">
                    <label>Cliente</label>
                    <select name="id_cliente" class="form-input" id="selCliente">
                        <option value="">Todos los clientes</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id_cliente }}">{{ $c->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Estado --}}
                <div class="filtro-group" style="max-width:180px;">
                    <label>Estado de factura</label>
                    <select name="estado" class="form-input" id="selEstado">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="POR_VENCER">Por Vencer</option>
                        <option value="VENCIDA">Vencida</option>
                        <option value="PAGADA">Pagada</option>
                        <option value="ANULADA">Anulada</option>
                        <option value="OBSERVADA">Observada</option>
                    </select>
                </div>

                <div style="display:flex;gap:10px;flex-shrink:0;padding-bottom:1px;">
                    <button type="button" class="btn btn-outline" onclick="previsualizarReporte()">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Previsualizar
                    </button>
                    <button type="submit" class="btn-pdf">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar PDF
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── PREVIEW ── --}}
    <div id="previewArea" style="display:none;">
        <div class="report-stats" id="statsGrid"></div>
        <div class="card">
            <div class="preview-header" style="padding:20px 24px 0;">
                <div>
                    <div style="font-weight:700;font-size:15px;" id="previewTitle">Vista previa</div>
                    <div style="font-size:13px;color:var(--text-muted);" id="previewSub"></div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table id="previewTable">
                    <thead>
                    <tr>
                        <th>EMISIÓN</th>
                        <th>FACTURA</th>
                        <th>GLOSA / CONCEPTO</th>
                        <th class="text-right">IMP. BRUTO</th>
                        <th class="text-right">DETRACCIÓN</th>
                        <th class="text-right">NETO CAJA</th>
                        <th>ESTADO</th>
                    </tr>
                    </thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Estado vacío --}}
    <div id="emptyState" class="card" style="text-align:center;padding:56px 24px;color:var(--text-muted);">
        <svg width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="margin:0 auto 16px;color:#cbd5e1;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Selecciona los filtros y haz clic en <em>Previsualizar</em></p>
        <p style="font-size:13px;margin-top:6px;">o en <em>Descargar PDF</em> para generar el reporte directamente.</p>
    </div>

@endsection

@push('scripts')
    <script>
        function fmt(n) {
            return 'S/ ' + parseFloat(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function estadoBadge(e) {
            return `<span class="badge-estado estado-${e}">${e.replace('_',' ')}</span>`;
        }

        async function previsualizarReporte() {
            const mes      = document.getElementById('selMes').value;
            const anio     = document.getElementById('selAnio').value;
            const cliente  = document.getElementById('selCliente').value;
            const estado   = document.getElementById('selEstado').value;

            const params = new URLSearchParams();
            if (mes)     params.append('mes', mes);
            if (anio)    params.append('anio', anio);
            if (cliente) params.append('id_cliente', cliente);
            if (estado)  params.append('estado', estado);

            const res  = await fetch(`{{ route('reportes.json') }}?${params}`);
            const data = await res.json();

            if (!data.facturas.length) {
                document.getElementById('previewArea').style.display  = 'none';
                document.getElementById('emptyState').style.display   = 'block';
                document.getElementById('emptyState').querySelector('p').textContent = 'Sin resultados para los filtros seleccionados.';
                return;
            }

            const r = data.resumen;
            document.getElementById('statsGrid').innerHTML = `
                <div class="rs-box azul">
                    <div class="rs-label">Total Facturas</div>
                    <div class="rs-value">${r.total_facturas}</div>
                </div>
                <div class="rs-box amber">
                    <div class="rs-label">Total Bruto</div>
                    <div class="rs-value" style="font-size:16px;">${fmt(r.total_bruto)}</div>
                </div>
                <div class="rs-box rojo">
                    <div class="rs-label">Saldo por Cobrar</div>
                    <div class="rs-value" style="font-size:16px;">${fmt(r.saldo_cobrar)}</div>
                </div>
                <div class="rs-box verde">
                    <div class="rs-label">Total Neto Caja</div>
                    <div class="rs-value" style="font-size:16px;">${fmt(r.total_neto)}</div>
                </div>
            `;

            const rows = data.facturas.map(f => `
                <tr>
                    <td class="mono">${f.fecha_emision ?? '—'}</td>
                    <td class="mono" style="font-weight:700;">${f.serie}-${String(f.numero).padStart(8,'0')}</td>
                    <td style="font-size:12px;max-width:260px;">${f.glosa ?? '—'}</td>
                    <td class="mono text-right">${fmt(f.importe_total)}</td>
                    <td class="mono text-right detrac-cell">${f.monto_recaudacion > 0 ? fmt(f.monto_recaudacion) : '—'}</td>
                    <td class="mono text-right neto-cell">${fmt(f.neto_caja)}</td>
                    <td>${estadoBadge(f.estado)}</td>
                </tr>
            `).join('');

            document.getElementById('previewBody').innerHTML = rows;
            document.getElementById('previewTitle').textContent = `${data.cliente_nombre} — ${data.periodo_label}`;
            document.getElementById('previewSub').textContent   = `${r.total_facturas} facturas · Estado: ${data.estado_label}`;

            document.getElementById('emptyState').style.display  = 'none';
            document.getElementById('previewArea').style.display = 'block';
        }
    </script>
@endpush
