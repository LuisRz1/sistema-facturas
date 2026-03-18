@extends('layouts.app')
@section('title', 'Gestión de Facturas')
@section('breadcrumb', 'Gestión de Facturas')

@push('styles')
    <style>
        /* ── FILTROS ── */
        .filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .filter-row .search-input-wrap { max-width:280px; }
        .filter-row .form-select { width:auto; min-width:160px; height:40px; }

        /* ── TABLE ── */
        .actions-cell { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
        .action-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:6px; border:none; cursor:pointer; transition:background .15s; color:var(--text-muted); background:transparent; }
        .action-btn:hover { background:var(--main-bg); color:var(--text-primary); }
        .client-cell { display:flex; flex-direction:column; gap:2px; }
        .client-name { font-weight:600; font-size:13.5px; }
        .client-ruc  { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); }
        .amount-main { font-weight:700; font-family:'DM Mono',monospace; font-size:13px; }
        .amount-sub  { font-size:11px; color:var(--text-muted); font-family:'DM Mono',monospace; margin-top:2px; }
        .notify-cell { display:flex; flex-direction:column; gap:4px; }
        .notify-meta { font-size:11px; color:var(--text-muted); }
        .btn-icon-text { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:6px; font-size:11.5px; font-weight:600; border:none; cursor:pointer; transition:all .15s; }
        .btn-wa   { background:#d1fae5; color:#059669; }
        .btn-wa:hover { background:#a7f3d0; }
        .btn-mail { background:#dbeafe; color:#1d4ed8; }
        .btn-mail:hover { background:#bfdbfe; }
        .serie-num { font-family:'DM Mono',monospace; font-weight:700; font-size:13px; color:var(--text-primary); }
        .tag { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
        .tag-wa   { background:#dcfce7; color:#16a34a; }
        .tag-mail { background:#dbeafe; color:#2563eb; }

        /* ── RANGO FECHAS ── */
        .date-range-wrap { display:flex; align-items:center; gap:10px; background:var(--card-bg); border:1px solid var(--border); border-radius:10px; padding:12px 20px; margin-bottom:20px; flex-wrap:wrap; }
        .date-range-wrap label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); white-space:nowrap; }
        .date-range-wrap input[type="date"] { height:38px; padding:0 12px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:var(--text-primary); outline:none; transition:border-color .15s; cursor:pointer; }
        .date-range-wrap input[type="date"]:focus { border-color:var(--accent); }
        .date-range-wrap .sep { color:var(--text-muted); font-size:14px; font-weight:600; }

        /* ── IMAGEN ── */
        .img-preview-thumb { width:36px; height:36px; object-fit:cover; border-radius:5px; border:1px solid var(--border); cursor:pointer; }

        /* ── ESTADOS BADGE (nuevo esquema) ── */
        .badge-pendiente         { background:#fef3c7; color:#92400e; }
        .badge-vencido           { background:#fee2e2; color:#7f1d1d; }
        .badge-pagada            { background:#d1fae5; color:#065f46; }
        .badge-pago_parcial      { background:#e0e7ff; color:#3730a3; }
        .badge-por_validar_det   { background:#fdf4ff; color:#7e22ce; border:1px solid #d8b4fe; }
        .badge-anulada           { background:#f1f5f9; color:#475569; }

        /* ── LEYENDA DE ESTADOS ── */
        .estados-legend {
            display:flex; gap:10px; flex-wrap:wrap; align-items:center;
            background:var(--card-bg); border:1px solid var(--border); border-radius:10px;
            padding:12px 20px; margin-bottom:16px; font-size:11px;
        }
        .estados-legend .legend-title {
            font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
            color:var(--text-muted); margin-right:4px; white-space:nowrap;
        }
        .legend-item { display:flex; align-items:center; gap:6px; }
        .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

        /* ── MONTO PENDIENTE ── */
        .monto-pendiente-cell { color:#dc2626; font-weight:700; font-family:'DM Mono',monospace; font-size:12px; }
        .monto-pendiente-zero { color:#059669; font-family:'DM Mono',monospace; font-size:12px; }

        /* ── MODAL PAGO ── */
        .pago-section { background:#f8fafc; border-radius:10px; padding:18px; margin-bottom:16px; border:1px solid var(--border); }
        .pago-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .calc-display { background:#fff; border:1px solid var(--border); border-radius:8px; padding:14px; margin-top:12px; font-size:13px; }
        .calc-row { display:flex; justify-content:space-between; align-items:center; padding:4px 0; }
        .calc-row.total { border-top:2px solid var(--border); margin-top:8px; padding-top:10px; font-weight:800; font-size:14px; }
        .calc-row.pending { color:#dc2626; }
        .calc-row.paid { color:#059669; }

        /* Tipo recaudacion selector */
        .tipo-rec-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:10px; }
        .tipo-rec-card { border:2px solid var(--border); border-radius:8px; padding:10px 8px; text-align:center; cursor:pointer; transition:all .15s; background:#f8fafc; font-size:11px; font-weight:700; text-transform:uppercase; }
        .tipo-rec-card:hover { border-color:#94a3b8; }
        .tipo-rec-card.active-det { border-color:#d97706; background:#fef3c7; color:#92400e; }
        .tipo-rec-card.active-auto { border-color:#059669; background:#d1fae5; color:#065f46; }
        .tipo-rec-card.active-ret { border-color:#7c3aed; background:#ede9fe; color:#5b21b6; }

        /* ── BOTÓN REPORTE VENCIDOS ── */
        .btn-reporte-vencidos { background:#7c3aed; color:#fff; border:none; }
        .btn-reporte-vencidos:hover { background:#6d28d9; }

        /* Modal de reporte a usuario */
        .usuario-option { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--border); border-radius:8px; cursor:pointer; transition:all .15s; margin-bottom:8px; }
        .usuario-option:hover { border-color:var(--accent); background:#f0f9ff; }
        .usuario-option.selected { border-color:var(--accent); background:#dbeafe; }
        .usuario-avatar-sm { width:32px; height:32px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Facturas</h1>
            <p class="page-desc">Control de facturas, pagos y notificaciones a clientes.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('facturas.importar') }}" class="btn btn-outline">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar Excel
            </a>
            <button type="button" class="btn btn-outline" onclick="generarReportePDF()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reporte PDF
            </button>
            <button type="button" class="btn btn-reporte-vencidos btn-sm" onclick="abrirModalReporteUsuario()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Enviar Reporte Vencidos
            </button>
            <button type="button" class="btn btn-outline" onclick="generarReporteDeuda()" style="border-color:#dc2626;color:#dc2626;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background=''">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Reporte Deuda General
            </button>
        </div>
    </div>

    {{-- ── LEYENDA DE ESTADOS ── --}}
    <div class="estados-legend">
        <span class="legend-title">Leyenda:</span>
        <div class="legend-item">
            <span class="legend-dot" style="background:#f59e0b;"></span>
            <span class="badge badge-pendiente" style="font-size:10px;">PENDIENTE</span>
            <span style="font-size:11px;color:var(--text-muted);">— Sin monto abonado registrado</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#ef4444;"></span>
            <span class="badge badge-vencido" style="font-size:10px;">VENCIDO</span>
            <span style="font-size:11px;color:var(--text-muted);">— Plazo de pago superado</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#22c55e;"></span>
            <span class="badge badge-pagada" style="font-size:10px;">PAGADA</span>
            <span style="font-size:11px;color:var(--text-muted);">— Abono + recaudación = importe total</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#818cf8;"></span>
            <span class="badge badge-pago_parcial" style="font-size:10px;">PAGO PARCIAL</span>
            <span style="font-size:11px;color:var(--text-muted);">— Monto abonado menor al total</span>
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background:#a855f7;border:2px solid #d8b4fe;"></span>
            <span class="badge badge-por_validar_det" style="font-size:10px;">POR VALIDAR DET.</span>
            <span style="font-size:11px;color:var(--text-muted);">— Detracción pendiente de validar</span>
        </div>
    </div>

    {{-- ── STATS ── --}}
    @php
        $total        = $facturas->sum('importe_total');
        $pendiente    = $facturas->whereIn('estado',['PENDIENTE','VENCIDO','POR VALIDAR DETRACCION'])->sum('monto_pendiente');
        $pagada       = $facturas->where('estado','PAGADA')->sum('importe_total');
        $parcial      = $facturas->where('estado','PAGO PARCIAL')->sum('monto_pendiente');
        $totalPendienteReal = $pendiente + $parcial;
    @endphp
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            <div><div class="stat-label">Total Facturado</div><div class="stat-value">S/ {{ number_format($total,2) }}</div></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><div class="stat-label">Saldo Pendiente</div><div class="stat-value">S/ {{ number_format($totalPendienteReal,2) }}</div></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
            <div><div class="stat-label">Cobrado</div><div class="stat-value">S/ {{ number_format($pagada,2) }}</div></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
            <div><div class="stat-label">Pago Parcial Pendiente</div><div class="stat-value">S/ {{ number_format($parcial,2) }}</div></div>
        </div>
    </div>

    {{-- ── FILTRO FECHAS ── --}}
    <form method="GET" action="{{ route('facturas.index') }}" id="frmFiltros">
        <div class="date-range-wrap">
            <label>Período:</label>
            <input type="date" name="fecha_desde" id="inputDesde" value="{{ $fechaDesde }}" onchange="document.getElementById('frmFiltros').submit()">
            <span class="sep">→</span>
            <input type="date" name="fecha_hasta" id="inputHasta" value="{{ $fechaHasta }}" onchange="document.getElementById('frmFiltros').submit()">
            <span style="font-size:12px;color:var(--text-muted);margin-left:6px;">
            Mostrando del <strong>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}</strong>
            al <strong>{{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</strong>
            &nbsp;·&nbsp; {{ $facturas->count() }} facturas
        </span>
            <div style="display:flex;gap:6px;margin-left:auto;">
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('mes')">Este mes</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('trimestre')">Trimestre</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('anio')">Este año</button>
            </div>
        </div>
    </form>

    {{-- ── TABLA ── --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Facturas</div>
                <div class="card-desc">{{ $facturas->count() }} facturas en el período seleccionado</div>
            </div>
        </div>

        <div class="search-bar">
            <div class="filter-row">
                <div class="search-input-wrap">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" class="form-input" id="searchInput" placeholder="Buscar factura, cliente..." onkeyup="filtrarTabla()">
                </div>
                <select class="form-select" id="filterEstado" onchange="filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="VENCIDO">Vencido</option>
                    <option value="PAGADA">Pagada</option>
                    <option value="PAGO PARCIAL">Pago Parcial</option>
                    <option value="POR VALIDAR DETRACCION">Por Validar Detracción</option>
                </select>
                <select class="form-select" id="filterMoneda" onchange="filtrarTabla()">
                    <option value="">Todas las monedas</option>
                    <option value="PEN">Soles (PEN)</option>
                    <option value="USD">Dólares (USD)</option>
                </select>
                <select class="form-select" id="filterEmpresa" onchange="filtrarTabla()" style="min-width:220px;">
                    <option value="">Todas las empresas</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id_cliente }}">{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table id="facturasTable">
                <thead>
                <tr>
                    <th>FACTURA</th>
                    <th>CLIENTE</th>
                    <th>EMISIÓN / VCTO.</th>
                    <th>IMPORTE</th>
                    <th>RECAUDACIÓN</th>
                    <th>ABONADO</th>
                    <th>PENDIENTE</th>
                    <th>COMPROBANTE</th>
                    <th>ESTADO</th>
                    <th>NOTIFICACIONES</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="facturasBody">
                @forelse($facturas as $factura)
                    @php
                        $estado = $factura->estado;
                        $badgeMap = [
                            'PENDIENTE'             => 'badge-pendiente',
                            'VENCIDO'               => 'badge-vencido',
                            'PAGADA'                => 'badge-pagada',
                            'PAGO PARCIAL'          => 'badge-pago_parcial',
                            'POR VALIDAR DETRACCION'=> 'badge-por_validar_det',
                            'ANULADA'               => 'badge-anulada',
                        ];
                        $badgeClass = $badgeMap[$estado] ?? 'badge-pendiente';
                        $montoRecaudacion = $factura->monto_recaudacion ?? 0;
                        $porcentaje       = $factura->porcentaje_recaudacion ?? 0;
                        $tipoRecaudacion  = $factura->tipo_recaudacion;
                        $montoAbonado     = $factura->monto_abonado ?? 0;
                        $montoPendiente   = $factura->monto_pendiente ?? $factura->importe_total;
                        $tieneComprobante = !empty($factura->ruta_comprobante_pago);
                        $puedeNotificarDeuda = in_array($estado, ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION']);
                        $ultimaNotifWa     = $factura->ultima_notif_wa ?? null;
                        $ultimaNotifCorreo = $factura->ultima_notif_correo ?? null;
                    @endphp
                    <tr data-cliente="{{ $factura->id_cliente }}" data-estado="{{ $estado }}"
                        data-moneda="{{ $factura->moneda }}"
                        data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '')) }}">

                        <td><div class="serie-num">{{ $factura->serie }}-{{ str_pad($factura->numero,8,'0',STR_PAD_LEFT) }}</div></td>

                        <td>
                            <div class="client-cell" onclick="abrirModalEditarCliente('{{ $factura->id_factura }}')" style="cursor:pointer;border-radius:6px;padding:4px;transition:background .15s;" onmouseover="this.style.background='var(--main-bg)'" onmouseout="this.style.background=''">
                                <div class="client-name" title="Haz clic para editar">{{ $factura->razon_social ?? 'Sin cliente' }}</div>
                                <div class="client-ruc">{{ $factura->ruc ?? '—' }}</div>
                            </div>
                        </td>

                        <td>
                            <div style="font-size:13px;">{{ $factura->fecha_emision }}</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">Vcto: <strong>{{ $factura->fecha_vencimiento ?? '—' }}</strong></div>
                        </td>

                        <td>
                            <div class="amount-main">{{ $factura->moneda }} {{ number_format($factura->importe_total,2) }}</div>
                            <div class="amount-sub">IGV: {{ number_format($factura->monto_igv ?? 0,2) }}</div>
                        </td>

                        <td style="text-align:center;">
                            @if($montoRecaudacion > 0)
                                <div style="font-weight:700;font-family:'DM Mono',monospace;font-size:12px;color:#d97706;">
                                    {{ $factura->moneda }} {{ number_format($montoRecaudacion,2) }}
                                </div>
                                <div style="font-size:10px;color:#92400e;font-weight:600;">
                                    {{ $porcentaje > 0 ? $porcentaje.'%' : '' }}
                                    {{ $tipoRecaudacion ? ' · '.$tipoRecaudacion : '' }}
                                </div>
                            @else
                                <span style="font-size:12px;color:var(--text-muted);">—</span>
                            @endif
                        </td>

                        <td style="text-align:right;">
                            @if($montoAbonado > 0)
                                <div style="font-weight:700;font-family:'DM Mono',monospace;font-size:12px;color:#059669;">
                                    {{ $factura->moneda }} {{ number_format($montoAbonado,2) }}
                                </div>
                                @if($factura->fecha_abono)
                                    <div style="font-size:10px;color:var(--text-muted);">{{ \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y') }}</div>
                                @endif
                            @else
                                <span style="font-size:12px;color:var(--text-muted);">—</span>
                            @endif
                        </td>

                        <td style="text-align:right;">
                            @if($estado === 'PAGADA')
                                <span class="monto-pendiente-zero">✓ Cancelado</span>
                            @elseif($montoPendiente > 0)
                                <div class="monto-pendiente-cell">{{ $factura->moneda }} {{ number_format($montoPendiente,2) }}</div>
                            @else
                                <span class="monto-pendiente-zero">—</span>
                            @endif
                        </td>

                        <td style="text-align:center;">
                            @if($tieneComprobante)
                                <a href="{{ $factura->ruta_comprobante_pago }}" target="_blank">
                                    <img src="{{ $factura->ruta_comprobante_pago }}" class="img-preview-thumb" alt="Comprobante">
                                </a>
                            @else
                                <span style="font-size:10px;color:var(--text-muted);">Sin imagen</span>
                            @endif
                        </td>

                        <td><span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$estado) }}</span></td>

                        <td>
                            <div class="notify-cell">
                                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                                    <span class="tag tag-wa" style="flex-shrink:0;">WA</span>
                                    @if($ultimaNotifWa)
                                        <span class="badge {{ $ultimaNotifWa->estado_envio==='ENVIADO'?'badge-enviado':'badge-error' }}" style="font-size:9px;padding:2px 6px;">{{ $ultimaNotifWa->estado_envio }}</span>
                                        <span class="notify-meta">{{ \Carbon\Carbon::parse($ultimaNotifWa->fecha_creacion)->format('d/m H:i') }}</span>
                                    @else
                                        <span style="color:var(--text-muted);font-size:11px;">Sin envíos</span>
                                    @endif
                                </div>
                                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-top:3px;">
                                    <span class="tag tag-mail" style="flex-shrink:0;">✉</span>
                                    @if($ultimaNotifCorreo)
                                        <span class="badge {{ $ultimaNotifCorreo->estado_envio==='ENVIADO'?'badge-enviado':'badge-error' }}" style="font-size:9px;padding:2px 6px;">{{ $ultimaNotifCorreo->estado_envio }}</span>
                                        <span class="notify-meta">{{ \Carbon\Carbon::parse($ultimaNotifCorreo->fecha_creacion)->format('d/m H:i') }}</span>
                                    @else
                                        <span style="color:var(--text-muted);font-size:11px;">Sin envíos</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;">
                                {{-- Editar factura --}}
                                <button type="button" onclick="abrirModalEditar('{{ $factura->id_factura }}')" class="action-btn" title="Editar datos factura" style="color:#7c3aed;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                {{-- Registrar pago / imagen --}}
                                <button type="button"
                                        onclick="abrirModalPago('{{ $factura->id_factura }}', {{ $factura->importe_total }}, '{{ $factura->moneda }}', {{ $montoAbonado }}, {{ $montoRecaudacion }}, {{ $porcentaje }}, '{{ $tipoRecaudacion }}', '{{ $estado }}')"
                                        class="action-btn"
                                        title="{{ $estado === 'PAGADA' ? 'Ver/Actualizar pago' : 'Registrar pago' }}"
                                        style="color:{{ $tieneComprobante ? '#059669' : ($montoAbonado > 0 ? '#1d4ed8' : '#d97706') }};">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </button>

                                {{-- Notificaciones deuda --}}
                                @if($puedeNotificarDeuda)
                                    <form method="POST" action="{{ route('facturas.enviar-whatsapp-manual',$factura->id_factura) }}" style="display:inline;">@csrf
                                        <button type="submit" class="btn-icon-text btn-wa" title="WA cobranza">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>WA
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-correo-manual',$factura->id_factura) }}" style="display:inline;">@csrf
                                        <button type="submit" class="btn-icon-text btn-mail" title="Correo cobranza">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>✉
                                        </button>
                                    </form>
                                @endif

                                {{-- Confirmación pagada --}}
                                @if($estado === 'PAGADA')
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-whatsapp',$factura->id_factura) }}" style="display:inline;">@csrf
                                        <button type="submit" class="btn-icon-text btn-wa" style="background:#a7f3d0;" title="WA confirmación">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-correo',$factura->id_factura) }}" style="display:inline;">@csrf
                                        <button type="submit" class="btn-icon-text btn-mail" style="background:#bfdbfe;" title="Correo confirmación">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11"><div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin facturas en el período seleccionado</p>
                                <p style="font-size:13px;margin-top:4px;">Cambia el rango de fechas o importa facturas.</p>
                            </div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ MODAL REGISTRAR PAGO + IMAGEN ═══════════ --}}
    <div class="modal-overlay" id="modalPagoOverlay">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h2>Registrar Pago / Abono</h2>
                <p id="modalPagoSubtitle">Ingresa el monto abonado y datos de recaudación</p>
                <button onclick="cerrarModalPago()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formPago" onsubmit="guardarPago(event)">
                @csrf
                <div class="modal-body" style="padding:24px;">

                    {{-- ── SECCIÓN ABONO ── --}}
                    <div class="pago-section">
                        <div class="pago-section-title">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Monto Abonado
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-group">
                                <label class="form-label">Monto Abonado *</label>
                                <input type="number" id="pagoMontoAbonado" name="monto_abonado" step="0.01" min="0" class="form-input" placeholder="0.00" oninput="recalcularPago()" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha de Abono</label>
                                <input type="date" id="pagoFechaAbono" name="fecha_abono" class="form-input" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    {{-- ── SECCIÓN RECAUDACIÓN ── --}}
                    <div class="pago-section" id="seccionRecaudacion">
                        <div class="pago-section-title">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Recaudación (Detracción / Autodetracción / Retención)
                        </div>

                        {{-- Selector tipo recaudación --}}
                        <div class="tipo-rec-grid">
                            <div class="tipo-rec-card" id="btnTipoNinguna" onclick="seleccionarTipoRec('')">Sin recaudación</div>
                            <div class="tipo-rec-card" id="btnTipoDet" onclick="seleccionarTipoRec('DETRACCION')">Detracción</div>
                            <div class="tipo-rec-card" id="btnTipoAuto" onclick="seleccionarTipoRec('AUTODETRACCION')">Autodetracción</div>
                            <div class="tipo-rec-card" id="btnTipoRet" onclick="seleccionarTipoRec('RETENCION')">Retención</div>
                        </div>
                        <input type="hidden" id="pagoTipoRecaudacion" name="tipo_recaudacion" value="">

                        {{-- Validar detracción checkbox --}}
                        <div id="validarDetraccionWrap" style="display:none;margin-bottom:12px;padding:10px 14px;background:#fef3c7;border-radius:8px;border:1px solid #fde68a;">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#92400e;">
                                <input type="checkbox" name="validar_detraccion" id="chkValidarDetraccion" value="1" style="width:16px;height:16px;accent-color:#d97706;" onchange="recalcularPago()">
                                Confirmo que esta factura SÍ aplica detracción
                            </label>
                            <p style="font-size:11px;color:#92400e;margin-top:6px;margin-left:26px;">Al marcar esta opción, se validará la detracción y cambiará el estado de la factura.</p>
                        </div>

                        <div id="camposRecaudacion" style="display:none;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-group">
                                <label class="form-label">Porcentaje (%)</label>
                                <input type="number" id="pagoPorcentaje" name="porcentaje_recaudacion" step="0.01" min="0" max="100" class="form-input" placeholder="10.00" oninput="calcularRecaudacion()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monto Recaudación</label>
                                <input type="number" id="pagoTotalRecaudacion" name="total_recaudacion" step="0.01" min="0" class="form-input" placeholder="0.00" oninput="recalcularPago()">
                            </div>
                        </div>
                    </div>

                    {{-- ── CALCULADORA VISUAL ── --}}
                    <div class="calc-display" id="calcDisplay">
                        <div class="calc-row">
                            <span>Importe Total:</span>
                            <strong id="calcImporte" style="font-family:'DM Mono',monospace;">S/ 0.00</strong>
                        </div>
                        <div class="calc-row">
                            <span>Monto Abonado:</span>
                            <span id="calcAbonado" style="font-family:'DM Mono',monospace;color:#059669;">S/ 0.00</span>
                        </div>
                        <div class="calc-row">
                            <span>Recaudación:</span>
                            <span id="calcRecaudacion" style="font-family:'DM Mono',monospace;color:#d97706;">S/ 0.00</span>
                        </div>
                        <div class="calc-row total" id="calcPendienteRow">
                            <span>Saldo Pendiente:</span>
                            <strong id="calcPendiente" class="monto-pendiente-cell">S/ 0.00</strong>
                        </div>
                        <div id="estadoPreview" style="margin-top:10px;padding:8px 12px;border-radius:6px;background:#f8fafc;font-size:12px;font-weight:700;text-align:center;"></div>
                    </div>

                    {{-- ── SECCIÓN COMPROBANTE ── --}}
                    <div class="pago-section" style="margin-top:16px;margin-bottom:0;">
                        <div class="pago-section-title">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Imagen de Comprobante (Opcional)
                        </div>
                        <div id="dropZonePago" style="border:2px dashed #cbd5e1;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .2s;" onclick="document.getElementById('fileComprobantePago').click()" onmouseover="this.style.borderColor='#1d4ed8';this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fff'">
                            <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;color:#94a3b8;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p style="font-size:13px;font-weight:600;margin-bottom:4px;">Arrastra o haz clic para adjuntar</p>
                            <p style="font-size:11px;color:#64748b;">JPG, PNG, GIF o PDF — máx. 5 MB</p>
                        </div>
                        <input type="file" id="fileComprobantePago" accept="image/*,application/pdf" style="display:none;" onchange="mostrarPreviewPago(event)">
                        <div id="previewPagoWrap" style="display:none;margin-top:12px;">
                            <img id="previewPagoImg" src="" style="max-width:100%;max-height:200px;border-radius:8px;border:1px solid #e2e8f0;">
                            <p id="previewPagoPdf" style="display:none;padding:10px;background:#f1f5f9;border-radius:8px;font-size:13px;color:#475569;">📄 PDF adjunto</p>
                            <button type="button" onclick="limpiarPreviewPago()" style="margin-top:8px;padding:5px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer;font-size:12px;">Quitar</button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalPago()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPago">Guardar Pago</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ MODAL EDITAR FACTURA ═══════════ --}}
    <div class="modal-overlay" id="modalEditarOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Factura</h2><p>Actualiza los datos de la factura</p>
                <button onclick="cerrarModalEditar()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditarFactura" onsubmit="guardarFactura(event)">
                @csrf @method('PUT')
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group"><label class="form-label">Fecha Emisión</label><input type="date" name="fecha_emision" id="editFechaEmision" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Fecha Vencimiento</label><input type="date" name="fecha_vencimiento" id="editFechaVencimiento" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Fecha Abono</label><input type="date" name="fecha_abono" id="editFechaAbono" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Estado</label>
                        <select name="estado" id="editEstado" class="form-input">
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="VENCIDO">Vencido</option>
                            <option value="PAGADA">Pagada</option>
                            <option value="PAGO PARCIAL">Pago Parcial</option>
                            <option value="POR VALIDAR DETRACCION">Por Validar Detracción</option>
                            <option value="ANULADA">Anulada</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Glosa</label><textarea name="glosa" id="editGlosa" class="form-input" style="resize:vertical;min-height:60px;"></textarea></div>
                    <div class="form-group"><label class="form-label">Forma de Pago</label><input type="text" name="forma_pago" id="editFormaPago" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Importe Total</label><input type="number" name="importe_total" id="editImporteTotal" step="0.01" class="form-input"></div>
                    <div class="form-group"><label class="form-label">IGV</label><input type="number" name="monto_igv" id="editMontoIgv" step="0.01" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Subtotal Gravado</label><input type="number" name="subtotal_gravado" id="editSubtotalGravado" step="0.01" class="form-input"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalEditar()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ MODAL EDITAR CLIENTE ═══════════ --}}
    <div class="modal-overlay" id="modalEditarClienteOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Cliente</h2><p>Actualiza los datos del cliente</p>
                <button onclick="cerrarModalEditarCliente()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditarCliente" onsubmit="guardarCliente(event)">
                @csrf @method('PUT')
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Razón Social</label><input type="text" name="razon_social" id="editRazonSocial" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">RUC</label><input type="text" name="ruc" id="editRuc" class="form-input" maxlength="11" required></div>
                    <div class="form-group"><label class="form-label">Celular</label><input type="text" name="celular" id="editCelular" class="form-input" maxlength="15"></div>
                    <div class="form-group"><label class="form-label">Correo</label><input type="email" name="correo" id="editCorreo" class="form-input"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Dirección Fiscal</label><input type="text" name="direccion_fiscal" id="editDireccionFiscal" class="form-input"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalEditarCliente()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ MODAL REPORTE VENCIDOS A USUARIO ═══════════ --}}
    <div class="modal-overlay" id="modalReporteUsuarioOverlay">
        <div class="modal" style="max-width:560px;">
            <div class="modal-header">
                <h2>Enviar Reporte a Usuario</h2>
                <p>Selecciona el usuario destino y el tipo de reporte</p>
                <button onclick="cerrarModalReporteUsuario()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body">

                {{-- Tipo de reporte --}}
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Tipo de Reporte</label>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:8px;">
                        <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .15s;font-size:12px;font-weight:700;text-align:center;" id="lblVencidos">
                            <input type="radio" name="tipoReporte" value="vencidos" style="display:none;" onchange="actualizarTipoReporte('vencidos')">
                            <span style="font-size:20px;"></span>
                            Vencidos
                        </label>
                        <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .15s;font-size:12px;font-weight:700;text-align:center;" id="lblPendientes">
                            <input type="radio" name="tipoReporte" value="pendientes" style="display:none;" onchange="actualizarTipoReporte('pendientes')">
                            <span style="font-size:20px;"></span>
                            Pendientes
                        </label>
                        <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;border:2px solid var(--accent);background:#dbeafe;border-radius:10px;cursor:pointer;transition:all .15s;font-size:12px;font-weight:700;text-align:center;color:var(--accent);" id="lblTodos">
                            <input type="radio" name="tipoReporte" value="todos" checked style="display:none;" onchange="actualizarTipoReporte('todos')">
                            <span style="font-size:20px;"></span>
                            Todos (Pend.+Venc.)
                        </label>
                    </div>
                </div>

                {{-- Filtro de fechas (opcional) --}}
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Filtro por fecha (opcional — dejar vacío para mostrar TODOS)</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:8px;">
                        <input type="date" id="reporteDesde" class="form-input" placeholder="Desde...">
                        <input type="date" id="reporteHasta" class="form-input" placeholder="Hasta...">
                    </div>
                    <p style="font-size:11px;color:var(--text-muted);margin-top:6px;">
                        Si no seleccionas fechas, el reporte incluirá <strong>todas</strong> las facturas del tipo seleccionado sin importar la fecha.
                    </p>
                </div>

                {{-- Selección de usuario --}}
                <div class="form-group">
                    <label class="form-label">Enviar a (selecciona usuario)</label>
                    <div id="listaUsuarios" style="margin-top:10px;max-height:220px;overflow-y:auto;">
                        @foreach($usuarios as $u)
                            <div class="usuario-option" onclick="seleccionarUsuario({{ $u->id_usuario }}, this)" data-id="{{ $u->id_usuario }}">
                                <div class="usuario-avatar-sm">{{ strtoupper(substr($u->nombre,0,1)) }}</div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;font-size:13px;">{{ $u->nombre }} {{ $u->apellido }}</div>
                                    <div style="font-size:11px;color:var(--text-muted);"> {{ $u->celular }}{{ $u->correo ? ' · '.$u->correo : '' }}</div>
                                </div>
                                <div style="color:var(--text-muted);font-size:18px;" class="check-icon">○</div>
                            </div>
                        @endforeach
                        @if($usuarios->isEmpty())
                            <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:20px;">No hay usuarios con celular registrado.</p>
                        @endif
                    </div>
                </div>

                <div id="reporteResultado" style="display:none;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-top:16px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModalReporteUsuario()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="enviarReporteUsuario()" class="btn btn-primary" id="btnEnviarReporteUsuario" disabled>
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Enviar por WhatsApp
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // ── Variables globales ────────────────────────────────────────────
            let facturaActualId   = null;
            let facturaImporte    = 0;
            let facturaMoneda     = 'S/';
            let usuarioSeleccionado = null;
            let tipoReporteSeleccionado = 'todos';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;

            // ── RANGO DE FECHAS ──────────────────────────────────────────────
            function setRango(tipo) {
                const hoy = new Date();
                const fmt = d => d.toISOString().split('T')[0];
                let desde, hasta = fmt(hoy);
                if (tipo === 'mes')       desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
                else if (tipo === 'trimestre') { const m = Math.floor(hoy.getMonth()/3)*3; desde = fmt(new Date(hoy.getFullYear(), m, 1)); }
                else                      desde = fmt(new Date(hoy.getFullYear(), 0, 1));
                document.getElementById('inputDesde').value = desde;
                document.getElementById('inputHasta').value = hasta;
                document.getElementById('frmFiltros').submit();
            }

            // ── FILTRAR TABLA ────────────────────────────────────────────────
            function filtrarTabla() {
                const search  = document.getElementById('searchInput').value.toLowerCase();
                const estado  = document.getElementById('filterEstado').value;
                const moneda  = document.getElementById('filterMoneda').value;
                const empresa = document.getElementById('filterEmpresa').value;
                document.querySelectorAll('#facturasBody tr[data-estado]').forEach(row => {
                    const ok = (!search || row.dataset.search.includes(search))
                        && (!estado  || row.dataset.estado  === estado)
                        && (!moneda  || row.dataset.moneda  === moneda)
                        && (!empresa || row.dataset.cliente === empresa);
                    row.style.display = ok ? '' : 'none';
                });
            }

            // ── REPORTES ─────────────────────────────────────────────────────
            function generarReportePDF() {
                const empresa = document.getElementById('filterEmpresa').value;
                const estado  = document.getElementById('filterEstado').value;
                const desde   = document.getElementById('inputDesde').value;
                const hasta   = document.getElementById('inputHasta').value;
                const params  = new URLSearchParams();
                if (empresa) params.append('id_cliente', empresa);
                if (estado)  params.append('estado', estado);
                if (desde)   params.append('fecha_desde', desde);
                if (hasta)   params.append('fecha_hasta', hasta);
                window.open('{{ route("reportes.pdf") }}?' + params.toString(), '_blank');
            }

            function generarReporteDeuda() {
                const desde = document.getElementById('inputDesde').value;
                const hasta = document.getElementById('inputHasta').value;
                const params = new URLSearchParams();
                if (desde) params.append('fecha_desde', desde);
                if (hasta)  params.append('fecha_hasta', hasta);
                window.open('{{ route("reportes.deuda-general") }}?' + params.toString(), '_blank');
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL REGISTRAR PAGO
            // ══════════════════════════════════════════════════════════════════
            function abrirModalPago(id, importe, moneda, montoAbonado, totalRec, pctRec, tipoRec, estado) {
                facturaActualId = id;
                facturaImporte  = parseFloat(importe);
                facturaMoneda   = moneda;

                document.getElementById('modalPagoSubtitle').textContent = `Factura #${id} · ${moneda} ${parseFloat(importe).toFixed(2)}`;
                document.getElementById('pagoMontoAbonado').value = montoAbonado > 0 ? montoAbonado : '';
                document.getElementById('pagoFechaAbono').value   = '{{ now()->format("Y-m-d") }}';
                document.getElementById('pagoTotalRecaudacion').value = totalRec > 0 ? totalRec : '';
                document.getElementById('pagoPorcentaje').value   = pctRec > 0 ? pctRec : '';
                document.getElementById('chkValidarDetraccion').checked = false;

                // Seleccionar tipo recaudación actual
                seleccionarTipoRec(tipoRec || '');

                // Mostrar aviso de validación si es POR VALIDAR DETRACCION
                document.getElementById('validarDetraccionWrap').style.display =
                    (tipoRec === 'DETRACCION' && (estado === 'POR VALIDAR DETRACCION' || estado === 'PENDIENTE')) ? 'block' : 'none';

                recalcularPago();
                document.getElementById('modalPagoOverlay').classList.add('open');
            }

            function cerrarModalPago() {
                document.getElementById('modalPagoOverlay').classList.remove('open');
                limpiarPreviewPago();
            }

            function seleccionarTipoRec(tipo) {
                document.getElementById('pagoTipoRecaudacion').value = tipo;

                // Reset todos
                ['btnTipoNinguna','btnTipoDet','btnTipoAuto','btnTipoRet'].forEach(id => {
                    const el = document.getElementById(id);
                    el.className = 'tipo-rec-card';
                });

                const camposRec = document.getElementById('camposRecaudacion');

                if (tipo === 'DETRACCION') {
                    document.getElementById('btnTipoDet').classList.add('active-det');
                    camposRec.style.display = 'grid';
                } else if (tipo === 'AUTODETRACCION') {
                    document.getElementById('btnTipoAuto').classList.add('active-auto');
                    camposRec.style.display = 'grid';
                    // Ocultar validación si cambia a autodetraccion
                    document.getElementById('validarDetraccionWrap').style.display = 'none';
                } else if (tipo === 'RETENCION') {
                    document.getElementById('btnTipoRet').classList.add('active-ret');
                    camposRec.style.display = 'grid';
                    document.getElementById('validarDetraccionWrap').style.display = 'none';
                } else {
                    document.getElementById('btnTipoNinguna').style.borderColor = '#1d4ed8';
                    document.getElementById('btnTipoNinguna').style.background = '#dbeafe';
                    document.getElementById('btnTipoNinguna').style.color = '#1d4ed8';
                    camposRec.style.display = 'none';
                    document.getElementById('pagoTotalRecaudacion').value = '';
                    document.getElementById('pagoPorcentaje').value = '';
                    document.getElementById('validarDetraccionWrap').style.display = 'none';
                }

                recalcularPago();
            }

            function calcularRecaudacion() {
                const pct      = parseFloat(document.getElementById('pagoPorcentaje').value) || 0;
                const importe  = facturaImporte;
                if (pct > 0 && importe > 0) {
                    document.getElementById('pagoTotalRecaudacion').value = (importe * pct / 100).toFixed(2);
                }
                recalcularPago();
            }

            function recalcularPago() {
                const abonado     = parseFloat(document.getElementById('pagoMontoAbonado').value) || 0;
                const recaudacion = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const pendiente   = Math.max(0, facturaImporte - abonado - recaudacion);
                const moneda      = facturaMoneda;
                const tipoRec     = document.getElementById('pagoTipoRecaudacion').value;
                const validarDet  = document.getElementById('chkValidarDetraccion').checked;

                document.getElementById('calcImporte').textContent     = `${moneda} ${facturaImporte.toFixed(2)}`;
                document.getElementById('calcAbonado').textContent     = `${moneda} ${abonado.toFixed(2)}`;
                document.getElementById('calcRecaudacion').textContent = `${moneda} ${recaudacion.toFixed(2)}`;
                document.getElementById('calcPendiente').textContent   = `${moneda} ${pendiente.toFixed(2)}`;

                // Preview del estado
                let estadoPreview = '';
                let estadoColor   = '';
                if (tipoRec === 'DETRACCION' && !validarDet && abonado === 0) {
                    estadoPreview = 'Estado: POR VALIDAR DETRACCIÓN';
                    estadoColor   = '#fdf4ff';
                } else if (abonado === 0) {
                    estadoPreview = 'Estado: PENDIENTE';
                    estadoColor   = '#fef3c7';
                } else if (pendiente <= 0) {
                    estadoPreview = 'Estado: PAGADA — Factura completamente cancelada';
                    estadoColor   = '#d1fae5';
                } else {
                    estadoPreview = `Estado: PAGO PARCIAL — Queda ${moneda} ${pendiente.toFixed(2)} pendiente`;
                    estadoColor   = '#e0e7ff';
                }

                const preview = document.getElementById('estadoPreview');
                preview.textContent = estadoPreview;
                preview.style.background = estadoColor;
            }

            async function guardarPago(event) {
                event.preventDefault();

                const btn = document.getElementById('btnGuardarPago');
                btn.disabled = true;
                btn.textContent = 'Guardando…';

                const montoAbonado      = parseFloat(document.getElementById('pagoMontoAbonado').value) || 0;
                const totalRecaudacion  = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const porcentaje        = parseFloat(document.getElementById('pagoPorcentaje').value) || 0;
                const tipoRec           = document.getElementById('pagoTipoRecaudacion').value;
                const fechaAbono        = document.getElementById('pagoFechaAbono').value;
                const validarDet        = document.getElementById('chkValidarDetraccion').checked;

                // Paso 1: Guardar pago
                try {
                    const res = await fetch(`/facturas/${facturaActualId}/pago`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({
                            monto_abonado:          montoAbonado,
                            total_recaudacion:      totalRecaudacion,
                            porcentaje_recaudacion: porcentaje,
                            tipo_recaudacion:       tipoRec || null,
                            fecha_abono:            fechaAbono,
                            validar_detraccion:     validarDet,
                        }),
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Error al guardar pago');
                } catch(e) {
                    alert('Error: ' + e.message);
                    btn.disabled = false;
                    btn.textContent = 'Guardar Pago';
                    return;
                }

                // Paso 2: Subir comprobante si hay archivo
                const file = document.getElementById('fileComprobantePago').files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('comprobante', file);
                    formData.append('_token', CSRF);
                    try {
                        const res2 = await fetch(`/facturas/${facturaActualId}/upload-comprobante`, {
                            method: 'POST', body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        await res2.json();
                    } catch(e) {
                        console.warn('Error subiendo comprobante:', e);
                    }
                }

                cerrarModalPago();
                location.reload();
            }

            // Preview comprobante en modal pago
            function mostrarPreviewPago(event) {
                const file = event.target.files[0]; if (!file) return;
                document.getElementById('previewPagoWrap').style.display = 'block';
                document.getElementById('dropZonePago').style.display = 'none';
                if (file.type === 'application/pdf') {
                    document.getElementById('previewPagoImg').style.display = 'none';
                    document.getElementById('previewPagoPdf').style.display = 'block';
                } else {
                    const r = new FileReader();
                    r.onload = e => { document.getElementById('previewPagoImg').src = e.target.result; document.getElementById('previewPagoImg').style.display = 'block'; document.getElementById('previewPagoPdf').style.display = 'none'; };
                    r.readAsDataURL(file);
                }
            }
            function limpiarPreviewPago() {
                document.getElementById('fileComprobantePago').value = '';
                document.getElementById('previewPagoWrap').style.display = 'none';
                document.getElementById('dropZonePago').style.display = 'block';
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL EDITAR FACTURA
            // ══════════════════════════════════════════════════════════════════
            function abrirModalEditar(id) {
                facturaActualId = id;
                document.getElementById('modalEditarOverlay').classList.add('open');
                fetch(`/facturas/${id}/edit`).then(r=>r.json()).then(f=>{
                    document.getElementById('editFechaEmision').value    = f.fecha_emision    || '';
                    document.getElementById('editFechaVencimiento').value= f.fecha_vencimiento|| '';
                    document.getElementById('editFechaAbono').value      = f.fecha_abono      || '';
                    document.getElementById('editEstado').value          = f.estado           || '';
                    document.getElementById('editGlosa').value           = f.glosa            || '';
                    document.getElementById('editFormaPago').value       = f.forma_pago       || '';
                    document.getElementById('editImporteTotal').value    = f.importe_total    || '';
                    document.getElementById('editMontoIgv').value        = f.monto_igv        || '';
                    document.getElementById('editSubtotalGravado').value = f.subtotal_gravado || '';
                });
            }
            function cerrarModalEditar() { document.getElementById('modalEditarOverlay').classList.remove('open'); }
            function guardarFactura(event) {
                event.preventDefault();
                const datos = {
                    fecha_emision:document.getElementById('editFechaEmision').value,
                    fecha_vencimiento:document.getElementById('editFechaVencimiento').value,
                    fecha_abono:document.getElementById('editFechaAbono').value,
                    estado:document.getElementById('editEstado').value,
                    glosa:document.getElementById('editGlosa').value,
                    forma_pago:document.getElementById('editFormaPago').value,
                    importe_total:document.getElementById('editImporteTotal').value,
                    monto_igv:document.getElementById('editMontoIgv').value,
                    subtotal_gravado:document.getElementById('editSubtotalGravado').value,
                };
                fetch(`/facturas/${facturaActualId}`,{method:'PUT',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},body:JSON.stringify(datos)})
                    .then(r=>r.json()).then(data=>{ if(data.success){cerrarModalEditar();location.reload();}else alert('Error: '+(data.message||'No se pudo guardar')); })
                    .catch(err=>alert('Error: '+err.message));
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL EDITAR CLIENTE
            // ══════════════════════════════════════════════════════════════════
            function abrirModalEditarCliente(id) {
                facturaActualId = id;
                document.getElementById('modalEditarClienteOverlay').classList.add('open');
                fetch(`/facturas/${id}/cliente`).then(r=>r.json()).then(c=>{
                    document.getElementById('editRazonSocial').value     = c.razon_social   || '';
                    document.getElementById('editRuc').value             = c.ruc            || '';
                    document.getElementById('editCelular').value         = c.celular        || '';
                    document.getElementById('editCorreo').value          = c.correo         || '';
                    document.getElementById('editDireccionFiscal').value = c.direccion_fiscal || '';
                }).catch(err=>alert('Error: '+err.message));
            }
            function cerrarModalEditarCliente() { document.getElementById('modalEditarClienteOverlay').classList.remove('open'); }
            function guardarCliente(event) {
                event.preventDefault();
                const datos = {
                    razon_social:     document.getElementById('editRazonSocial').value,
                    ruc:              document.getElementById('editRuc').value,
                    celular:          document.getElementById('editCelular').value,
                    correo:           document.getElementById('editCorreo').value,
                    direccion_fiscal: document.getElementById('editDireccionFiscal').value,
                };
                fetch(`/facturas/${facturaActualId}/cliente`,{method:'PUT',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},body:JSON.stringify(datos)})
                    .then(r=>r.json()).then(data=>{ if(data.success){cerrarModalEditarCliente();location.reload();}else alert('Error: '+(data.message||'')); })
                    .catch(err=>alert('Error: '+err.message));
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL REPORTE VENCIDOS A USUARIO
            // ══════════════════════════════════════════════════════════════════
            function abrirModalReporteUsuario() {
                usuarioSeleccionado = null;
                document.getElementById('btnEnviarReporteUsuario').disabled = true;
                document.getElementById('reporteResultado').style.display = 'none';
                document.querySelectorAll('.usuario-option').forEach(el => {
                    el.classList.remove('selected');
                    el.querySelector('.check-icon').textContent = '○';
                });
                document.getElementById('modalReporteUsuarioOverlay').classList.add('open');
            }
            function cerrarModalReporteUsuario() { document.getElementById('modalReporteUsuarioOverlay').classList.remove('open'); }

            function seleccionarUsuario(id, el) {
                usuarioSeleccionado = id;
                document.querySelectorAll('.usuario-option').forEach(o => { o.classList.remove('selected'); o.querySelector('.check-icon').textContent = '○'; });
                el.classList.add('selected');
                el.querySelector('.check-icon').textContent = '●';
                document.getElementById('btnEnviarReporteUsuario').disabled = false;
                document.getElementById('reporteResultado').style.display = 'none';
            }

            function actualizarTipoReporte(tipo) {
                tipoReporteSeleccionado = tipo;
                ['lblVencidos','lblPendientes','lblTodos'].forEach(id => {
                    const el = document.getElementById(id);
                    el.style.borderColor = 'var(--border)';
                    el.style.background  = '';
                    el.style.color       = '';
                });
                const mapa = { vencidos:'lblVencidos', pendientes:'lblPendientes', todos:'lblTodos' };
                const sel  = document.getElementById(mapa[tipo]);
                sel.style.borderColor = 'var(--accent)';
                sel.style.background  = '#dbeafe';
                sel.style.color       = 'var(--accent)';
            }

            async function enviarReporteUsuario() {
                if (!usuarioSeleccionado) return;
                const btn = document.getElementById('btnEnviarReporteUsuario');
                btn.disabled = true;
                btn.textContent = 'Enviando…';

                const body = new URLSearchParams({
                    id_usuario:  usuarioSeleccionado,
                    tipo:        tipoReporteSeleccionado,
                    fecha_desde: document.getElementById('reporteDesde').value,
                    fecha_hasta: document.getElementById('reporteHasta').value,
                    _token:      CSRF,
                });

                try {
                    const res  = await fetch('{{ route("facturas.reporte-vencidos-usuario") }}', { method:'POST', body });
                    const data = await res.json();
                    const el   = document.getElementById('reporteResultado');
                    el.textContent  = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || '');
                    el.style.background = data.success ? '#d1fae5' : '#fee2e2';
                    el.style.color      = data.success ? '#065f46' : '#7f1d1d';
                    el.style.display    = 'block';
                } catch(e) {
                    const el = document.getElementById('reporteResultado');
                    el.textContent = '✗ Error de red: ' + e.message;
                    el.style.background = '#fee2e2';
                    el.style.color = '#7f1d1d';
                    el.style.display = 'block';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Enviar por WhatsApp';
                    document.getElementById('btnEnviarReporteUsuario').innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> Enviar por WhatsApp';
                }
            }

            // ── Cierre de modales con click fuera ────────────────────────────
            ['modalPagoOverlay','modalEditarOverlay','modalEditarClienteOverlay','modalReporteUsuarioOverlay'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', e => { if(e.target === e.currentTarget) e.currentTarget.classList.remove('open'); });
            });
        </script>
    @endpush

@endsection
