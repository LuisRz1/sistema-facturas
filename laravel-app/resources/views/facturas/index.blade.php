@extends('layouts.app')

@section('title', 'Gestión de Facturas')
@section('breadcrumb', 'Gestión de Facturas')

@push('styles')
    <style>
        .filter-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-row .search-input-wrap { max-width: 280px; }
        .filter-row .form-select { width: auto; min-width: 160px; height: 40px; }

        .actions-cell { display: flex; align-items: center; gap: 4px; }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px; height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background .15s;
            color: var(--text-muted);
            background: transparent;
        }

        .action-btn:hover { background: var(--main-bg); color: var(--text-primary); }
        .action-btn.whatsapp:hover { background: #d1fae5; color: #059669; }
        .action-btn.correo:hover { background: #dbeafe; color: #1d4ed8; }

        .client-cell { display: flex; flex-direction: column; gap: 2px; }
        .client-name { font-weight: 600; font-size: 13.5px; }
        .client-ruc { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--text-muted); }

        .amount-main { font-weight: 700; font-family: 'DM Mono', monospace; font-size: 13px; }
        .amount-sub { font-size: 11px; color: var(--text-muted); font-family: 'DM Mono', monospace; margin-top: 2px; }

        .notify-cell { display: flex; flex-direction: column; gap: 4px; }
        .notify-meta { font-size: 11px; color: var(--text-muted); }

        .btn-icon-text {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .15s;
        }

        .btn-wa   { background: #d1fae5; color: #059669; }
        .btn-wa:hover { background: #a7f3d0; }
        .btn-mail { background: #dbeafe; color: #1d4ed8; }
        .btn-mail:hover { background: #bfdbfe; }

        .serie-num { font-family: 'DM Mono', monospace; font-weight: 700; font-size: 13px; color: var(--text-primary); }

        .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .tag-wa   { background: #dcfce7; color: #16a34a; }
        .tag-mail { background: #dbeafe; color: #2563eb; }

        /* Selector de mes */
        .mes-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 20px;
        }
        .mes-nav label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); white-space: nowrap; }
        .mes-nav select { height: 36px; min-width: 140px; }
        .mes-nav .btn-sm { height: 36px; }

        /* Indicador de comprobante */
        .comprobante-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-left: 4px;
        }
        .comprobante-dot.tiene { background: #22c55e; }
        .comprobante-dot.sin   { background: #cbd5e1; }

        .img-preview-thumb {
            width: 36px; height: 36px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid var(--border);
            cursor: pointer;
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Facturas</h1>
            <p class="page-desc">Control de deuda, detracciones y notificaciones automáticas a clientes.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('facturas.importar') }}" class="btn btn-outline">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar Excel
            </a>
            <button type="button" class="btn btn-outline" onclick="generarReportePDF()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Reporte PDF
            </button>
        </div>
    </div>

    {{-- STATS --}}
    @php
        $total     = $facturas->sum('importe_total');
        $pendiente = $facturas->whereIn('estado', ['PENDIENTE','POR_VENCER'])->sum('importe_total');
        $pagada    = $facturas->where('estado','PAGADA')->sum('importe_total');
        $vencida   = $facturas->where('estado','VENCIDA')->sum('importe_total');
    @endphp

    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="stat-label">Total Facturado</div>
                <div class="stat-value">S/ {{ number_format($total, 2) }}</div>
            </div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="stat-label">Cuentas por Cobrar</div>
                <div class="stat-value">S/ {{ number_format($pendiente, 2) }}</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div class="stat-label">Cobrado</div>
                <div class="stat-value">S/ {{ number_format($pagada, 2) }}</div>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <div class="stat-label">Deuda Vencida</div>
                <div class="stat-value">S/ {{ number_format($vencida, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- FILTROS DE REPORTE PDF --}}
    {{-- FILTROS UNIFICADOS --}}
    <form method="GET" action="{{ route('facturas.index') }}" id="frmFiltrosFacturas">
        <div class="card" style="margin-bottom: 24px; padding: 20px 24px; width: 100%;">
            <div style="display: flex; align-items: flex-end; gap: 18px; flex-wrap: wrap;">

                <div style="display: flex; flex-direction: column; gap: 6px; width: 360px;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                        Empresa
                    </label>
                    <select class="form-input" id="filterEmpresa" onchange="filtrarTabla()">
                        <option value="">Todas las Empresas</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id_cliente }}">{{ $cliente->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px; width: 200px;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                        Estado
                    </label>
                    <select class="form-input" id="filterEstadoReporte" onchange="filtrarTabla()">
                        <option value="">Todos los Estados</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="POR_VENCER">Por Vencer</option>
                        <option value="VENCIDA">Vencida</option>
                        <option value="PAGADA">Pagada</option>
                        <option value="ANULADA">Anulada</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px; width: 180px;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                        Mes
                    </label>
                    <select name="mes" class="form-input" onchange="document.getElementById('frmFiltrosFacturas').submit()">
                        @foreach($meses as $num => $nombre)
                            <option value="{{ $num }}" {{ $mesActual == $num ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px; width: 140px;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                        Año
                    </label>
                    <select name="anio" class="form-input" onchange="document.getElementById('frmFiltrosFacturas').submit()">
                        @foreach($anios as $a)
                            <option value="{{ $a }}" {{ $anioActual == $a ? 'selected' : '' }}>
                                {{ $a }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="font-size:13px;color:var(--text-muted); padding-bottom: 10px;">
                    — Mostrando facturas de <strong>{{ $meses[$mesActual] }} {{ $anioActual }}</strong>
                </div>

            </div>
        </div>
    </form>

    {{-- TABLE CARD --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Facturas — {{ $meses[$mesActual] }} {{ $anioActual }}</div>
                <div class="card-desc">{{ $facturas->count() }} facturas registradas en el período</div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="search-bar">
            <div class="filter-row" id="filterRow">
                <div class="search-input-wrap">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" class="form-input" id="searchInput" placeholder="Buscar factura, cliente..." onkeyup="filtrarTabla()">
                </div>
                <select class="form-select" id="filterEstado" onchange="filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="POR_VENCER">Por Vencer</option>
                    <option value="VENCIDA">Vencida</option>
                    <option value="PAGADA">Pagada</option>
                    <option value="ANULADA">Anulada</option>
                </select>
                <select class="form-select" id="filterMoneda" onchange="filtrarTabla()">
                    <option value="">Todas las monedas</option>
                    <option value="PEN">Soles (PEN)</option>
                    <option value="USD">Dólares (USD)</option>
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
                    <th>MONTOS</th>
                    <th>% RECAUDACIÓN</th>
                    <th>RECAUDACIÓN</th>
                    <th>TIPO</th>
                    <th>F. ABONO</th>
                    <th>COMPROBANTE</th>
                    <th>ESTADO</th>
                    <th>ÚLTIMA NOTIF.</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="facturasBody">
                @forelse($facturas as $factura)
                    @php
                        $ultimaNotif = $factura->notificaciones->first() ?? null;
                        $badgeMap = [
                            'PENDIENTE' => 'badge-pendiente',
                            'POR_VENCER'=> 'badge-por_vencer',
                            'VENCIDA'   => 'badge-vencida',
                            'PAGADA'    => 'badge-pagada',
                            'ANULADA'   => 'badge-anulada',
                            'OBSERVADA' => 'badge-pendiente',
                        ];
                        $badgeClass            = $badgeMap[$factura->estado] ?? 'badge-pendiente';
                        $puedeNotificarDeuda   = in_array($factura->estado, ['PENDIENTE','POR_VENCER','VENCIDA']);
                        $porcentajeRecaudacion = $factura->porcentaje_recaudacion ?? 0;
                        $montoRecaudacion      = $factura->monto_recaudacion ?? 0;
                        $tipoRecaudacion       = $factura->tipo_recaudacion_actual;
                        $tieneComprobante      = !empty($factura->ruta_comprobante_pago);
                    @endphp
                    <tr
                        data-cliente="{{ $factura->id_cliente }}"
                        data-estado="{{ $factura->estado }}"
                        data-moneda="{{ $factura->moneda }}"
                        data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '')) }}"
                    >
                        <td>
                            <div class="serie-num">{{ $factura->serie }}-{{ str_pad($factura->numero, 8, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td>
                            <div class="client-cell">
                                <div class="client-name">{{ $factura->razon_social ?? 'Sin cliente' }}</div>
                                <div class="client-ruc">{{ $factura->ruc ?? '—' }}</div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px;">{{ $factura->fecha_emision }}</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
                                Vcto: <strong>{{ $factura->fecha_vencimiento ?? '—' }}</strong>
                            </div>
                        </td>
                        <td>
                            <div class="amount-main">{{ $factura->moneda }} {{ number_format($factura->importe_total, 2) }}</div>
                            <div class="amount-sub">IGV: {{ number_format($factura->monto_igv ?? 0, 2) }}</div>
                        </td>
                        <td style="text-align:center;font-weight:600;">
                            {{ $porcentajeRecaudacion > 0 ? $porcentajeRecaudacion . '%' : '—' }}
                        </td>
                        <td style="text-align:right;font-weight:600;color:#d97706;font-family:'DM Mono',monospace;">
                            {{ $montoRecaudacion > 0 ? $factura->moneda . ' ' . number_format($montoRecaudacion, 2) : '—' }}
                        </td>
                        <td style="text-align:center;font-size:10px;font-weight:600;color:#7c3aed;">
                            {{ $tipoRecaudacion ?? '—' }}
                        </td>
                        <td style="text-align:center;font-family:'DM Mono',monospace;">
                            {{ $factura->fecha_abono ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y') : '—' }}
                        </td>
                        {{-- COLUMNA COMPROBANTE: miniatura o botón para subir --}}
                        <td style="text-align:center;">
                            @if($tieneComprobante)
                                <a href="{{ $factura->ruta_comprobante_pago }}" target="_blank" title="Ver comprobante">
                                    <img src="{{ $factura->ruta_comprobante_pago }}" class="img-preview-thumb" alt="Comprobante">
                                </a>
                            @else
                                <span style="font-size:10px;color:var(--text-muted);">Sin imagen</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $factura->estado }}</span>
                        </td>
                        <td>
                            <div class="notify-cell">
                                @if($ultimaNotif)
                                    <div>
                                        <span class="badge {{ $ultimaNotif->estado_envio === 'ENVIADO' ? 'badge-enviado' : 'badge-error' }}">
                                            {{ $ultimaNotif->estado_envio }}
                                        </span>
                                    </div>
                                    <div class="notify-meta">
                                        <span class="tag {{ $ultimaNotif->canal === 'WHATSAPP' ? 'tag-wa' : 'tag-mail' }}">{{ $ultimaNotif->canal }}</span>
                                        &nbsp;{{ \Carbon\Carbon::parse($ultimaNotif->fecha_creacion)->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="notify-meta">{{ Str::limit($ultimaNotif->observacion, 28) }}</div>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">Sin notificaciones</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;flex-wrap:wrap;gap:4px;">

                                {{-- EDITAR --}}
                                <button type="button"
                                        onclick="abrirModalEditar('{{ $factura->id_factura }}')"
                                        class="action-btn" title="Editar factura" style="color:#7c3aed;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                {{-- SUBIR COMPROBANTE / IMAGEN FACTURA (TODAS las facturas) --}}
                                <button type="button"
                                        onclick="abrirModalComprobante('{{ $factura->id_factura }}')"
                                        class="action-btn" title="{{ $tieneComprobante ? 'Actualizar imagen de factura' : 'Subir imagen de factura / comprobante' }}"
                                        style="color:{{ $tieneComprobante ? '#059669' : '#d97706' }};">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>

                                {{-- NOTIFICACIONES: PENDIENTES (deuda) --}}
                                @if($puedeNotificarDeuda)
                                    <form method="POST" action="{{ route('facturas.enviar-whatsapp-manual', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-wa" title="Enviar aviso de deuda por WhatsApp">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            WA
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-correo-manual', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-mail" title="Enviar aviso de deuda por Correo">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            ✉
                                        </button>
                                    </form>
                                @endif

                                {{-- NOTIFICACIONES: PAGADAS (envío de comprobante) --}}
                                @if($factura->estado === 'PAGADA')
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-whatsapp', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-wa"
                                                title="{{ $tieneComprobante ? 'Enviar comprobante por WhatsApp' : 'Enviar confirmación por WhatsApp (sin imagen)' }}"
                                                style="background:{{ $tieneComprobante ? '#a7f3d0' : '#d1fae5' }};">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            {{ $tieneComprobante ? '📎' : '' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-correo', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-mail" title="Enviar confirmación por Correo" style="background:#bfdbfe;">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </button>
                                    </form>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin facturas en {{ $meses[$mesActual] }} {{ $anioActual }}</p>
                                <p style="font-size:13px;margin-top:4px;">Cambia el período o importa facturas del mes.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── MODAL EDITAR FACTURA ── -->
    <div class="modal-overlay" id="modalEditarOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Editar Factura</h2>
                <p>Actualiza los datos de la factura</p>
                <button onclick="cerrarModalEditar()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditarFactura" onsubmit="guardarFactura(event)">
                @csrf
                @method('PUT')
                <div class="modal-body" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Fecha Emisión</label>
                        <input type="date" name="fecha_emision" id="editFechaEmision" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" id="editFechaVencimiento" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha Abono</label>
                        <input type="date" name="fecha_abono" id="editFechaAbono" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="editEstado" class="form-input">
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="POR_VENCER">Por Vencer</option>
                            <option value="VENCIDA">Vencida</option>
                            <option value="PAGADA">Pagada</option>
                            <option value="ANULADA">Anulada</option>
                            <option value="OBSERVADA">Observada</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Glosa / Descripción</label>
                        <textarea name="glosa" id="editGlosa" class="form-input" style="resize:vertical;min-height:60px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pago</label>
                        <input type="text" name="forma_pago" id="editFormaPago" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Importe Total</label>
                        <input type="number" name="importe_total" id="editImporteTotal" step="0.01" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IGV</label>
                        <input type="number" name="monto_igv" id="editMontoIgv" step="0.01" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subtotal Gravado</label>
                        <input type="number" name="subtotal_gravado" id="editSubtotalGravado" step="0.01" class="form-input">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalEditar()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── MODAL SUBIR COMPROBANTE / IMAGEN ── -->
    <div class="modal-overlay" id="modalComprobanteOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>📎 Imagen de Factura / Comprobante</h2>
                <p id="modalComprobanteDesc">Sube la foto de la factura o comprobante de pago. Se envía por WhatsApp a clientes con estado PAGADA.</p>
                <button onclick="cerrarModalComprobante()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formComprobante" onsubmit="enviarComprobante(event)" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="text-align:center;padding:32px 24px;">
                    <div id="dropZone"
                         style="border:2px dashed #cbd5e1;border-radius:12px;padding:36px;cursor:pointer;transition:all .2s;"
                         onmouseover="this.style.borderColor='#1d4ed8';this.style.background='#eff6ff'"
                         onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fff'">
                        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto;color:#cbd5e1;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p style="margin:14px 0 6px;font-weight:600;font-size:14px;">Arrastra o haz clic para seleccionar</p>
                        <p style="font-size:12px;color:#64748b;">JPG, PNG, GIF o PDF — máximo 5 MB</p>
                        <p style="font-size:11px;color:#94a3b8;margin-top:4px;">Se sube a Cloudinary ☁️</p>
                        <input type="file" name="comprobante" id="fileComprobante" accept="image/*,application/pdf" style="display:none;" onchange="mostrarPreview(event)">
                    </div>
                    <div id="preview" style="display:none;margin-top:16px;">
                        <img id="previewImg" src="" style="max-width:100%;max-height:280px;border-radius:8px;border:1px solid #e2e8f0;">
                        <p id="previewPdf" style="display:none;padding:12px;background:#f1f5f9;border-radius:8px;font-size:13px;color:#475569;">📄 Archivo PDF seleccionado</p>
                        <button type="button" onclick="limpiarPreview()" style="display:block;margin:10px auto 0;padding:7px 16px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;font-size:12px;">Cambiar archivo</button>
                    </div>
                    <div id="uploadProgress" style="display:none;margin-top:16px;">
                        <div style="background:#e2e8f0;border-radius:50px;height:6px;overflow:hidden;">
                            <div id="progressBar" style="background:#1d4ed8;height:100%;width:0%;transition:width .3s;"></div>
                        </div>
                        <p style="font-size:12px;color:#64748b;margin-top:8px;">Subiendo a Cloudinary…</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalComprobante()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEnviarComprobante">☁️ Subir Imagen</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let facturaActualId = null;

            // Inicializar dropzone
            document.getElementById('dropZone').addEventListener('click', () => document.getElementById('fileComprobante').click());
            document.getElementById('dropZone').addEventListener('dragover', (e) => { e.preventDefault(); });
            document.getElementById('dropZone').addEventListener('drop', (e) => {
                e.preventDefault();
                const files = e.dataTransfer.files;
                if (files.length) {
                    document.getElementById('fileComprobante').files = files;
                    mostrarPreview({ target: { files } });
                }
            });

            // ── MODAL EDITAR ──────────────────────────────────────────────────
            function abrirModalEditar(id) {
                facturaActualId = id;
                document.getElementById('modalEditarOverlay').classList.add('open');
                fetch(`/facturas/${id}/edit`)
                    .then(r => r.json())
                    .then(factura => {
                        document.getElementById('editFechaEmision').value    = factura.fecha_emision || '';
                        document.getElementById('editFechaVencimiento').value = factura.fecha_vencimiento || '';
                        document.getElementById('editFechaAbono').value      = factura.fecha_abono || '';
                        document.getElementById('editEstado').value          = factura.estado || '';
                        document.getElementById('editGlosa').value           = factura.glosa || '';
                        document.getElementById('editFormaPago').value       = factura.forma_pago || '';
                        document.getElementById('editImporteTotal').value    = factura.importe_total || '';
                        document.getElementById('editMontoIgv').value        = factura.monto_igv || '';
                        document.getElementById('editSubtotalGravado').value = factura.subtotal_gravado || '';
                    });
            }

            function cerrarModalEditar() {
                document.getElementById('modalEditarOverlay').classList.remove('open');
            }

            function guardarFactura(event) {
                event.preventDefault();
                const datos = {
                    fecha_emision:     document.getElementById('editFechaEmision').value,
                    fecha_vencimiento: document.getElementById('editFechaVencimiento').value,
                    fecha_abono:       document.getElementById('editFechaAbono').value,
                    estado:            document.getElementById('editEstado').value,
                    glosa:             document.getElementById('editGlosa').value,
                    forma_pago:        document.getElementById('editFormaPago').value,
                    importe_total:     document.getElementById('editImporteTotal').value,
                    monto_igv:         document.getElementById('editMontoIgv').value,
                    subtotal_gravado:  document.getElementById('editSubtotalGravado').value,
                };

                fetch(`/facturas/${facturaActualId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(datos),
                })
                    .then(r => { if (!r.ok) throw new Error(`Error ${r.status}`); return r.json(); })
                    .then(data => {
                        if (data.success) { cerrarModalEditar(); location.reload(); }
                        else alert('Error: ' + (data.message || 'No se pudo guardar'));
                    })
                    .catch(err => alert('Error al guardar: ' + err.message));
            }

            // ── MODAL COMPROBANTE ─────────────────────────────────────────────
            function abrirModalComprobante(id) {
                facturaActualId = id;
                limpiarPreview();
                document.getElementById('uploadProgress').style.display = 'none';
                document.getElementById('modalComprobanteOverlay').classList.add('open');
            }

            function cerrarModalComprobante() {
                document.getElementById('modalComprobanteOverlay').classList.remove('open');
            }

            function mostrarPreview(event) {
                const file = event.target.files[0];
                if (!file) return;

                document.getElementById('preview').style.display = 'block';
                document.getElementById('dropZone').style.display = 'none';

                if (file.type === 'application/pdf') {
                    document.getElementById('previewImg').style.display = 'none';
                    document.getElementById('previewPdf').style.display = 'block';
                } else {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('previewImg').src = e.target.result;
                        document.getElementById('previewImg').style.display = 'block';
                        document.getElementById('previewPdf').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            }

            function limpiarPreview() {
                document.getElementById('fileComprobante').value = '';
                document.getElementById('preview').style.display = 'none';
                document.getElementById('dropZone').style.display = 'block';
                document.getElementById('previewImg').src = '';
                document.getElementById('btnEnviarComprobante').disabled = false;
                document.getElementById('btnEnviarComprobante').textContent = '☁️ Subir Imagen';
            }

            function enviarComprobante(event) {
                event.preventDefault();
                const file = document.getElementById('fileComprobante').files[0];
                if (!file) { alert('Por favor selecciona una imagen o PDF'); return; }

                const btn = document.getElementById('btnEnviarComprobante');
                btn.disabled = true;
                btn.textContent = 'Subiendo…';
                document.getElementById('uploadProgress').style.display = 'block';

                // Animación de progreso simulada
                let progress = 0;
                const interval = setInterval(() => {
                    progress = Math.min(progress + 10, 85);
                    document.getElementById('progressBar').style.width = progress + '%';
                }, 200);

                const formData = new FormData(document.getElementById('formComprobante'));

                fetch(`/facturas/${facturaActualId}/upload-comprobante`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(data => {
                        clearInterval(interval);
                        document.getElementById('progressBar').style.width = '100%';
                        setTimeout(() => {
                            if (data.success) {
                                cerrarModalComprobante();
                                location.reload();
                            } else {
                                alert(data.error || 'Error al subir el archivo');
                                btn.disabled = false;
                                btn.textContent = '☁️ Subir Imagen';
                                document.getElementById('uploadProgress').style.display = 'none';
                            }
                        }, 400);
                    })
                    .catch(err => {
                        clearInterval(interval);
                        alert('Error: ' + err.message);
                        btn.disabled = false;
                        btn.textContent = '☁️ Subir Imagen';
                        document.getElementById('uploadProgress').style.display = 'none';
                    });
            }

            // ── FILTROS TABLA ──────────────────────────────────────────────────
            function filtrarTabla() {
                const search  = document.getElementById('searchInput').value.toLowerCase();
                const estado  = document.getElementById('filterEstadoReporte').value;
                const moneda  = document.getElementById('filterMoneda').value;
                const empresa = document.getElementById('filterEmpresa').value;

                document.querySelectorAll('#facturasBody tr[data-estado]').forEach(row => {
                    const ok =
                        (!search  || row.dataset.search.includes(search)) &&
                        (!estado  || row.dataset.estado === estado) &&
                        (!moneda  || row.dataset.moneda === moneda) &&
                        (!empresa || row.dataset.cliente === empresa);

                    row.style.display = ok ? '' : 'none';
                });
            }

            // ── GENERAR PDF con mes/año del período actual ─────────────────────
            function generarReportePDF() {
                const idCliente = document.getElementById('filterEmpresa').value;
                const estado    = document.getElementById('filterEstadoReporte').value;
                const mes       = '{{ $mesActual }}';
                const anio      = '{{ $anioActual }}';

                const params = new URLSearchParams();
                if (idCliente) params.append('id_cliente', idCliente);
                if (estado)    params.append('estado', estado);
                params.append('mes',  mes);
                params.append('anio', anio);

                window.open('{{ route("reportes.pdf") }}?' + params.toString(), '_blank');
            }

            // Cerrar modales al hacer clic fuera
            document.getElementById('modalEditarOverlay')?.addEventListener('click', (e) => {
                if (e.target === e.currentTarget) cerrarModalEditar();
            });
            document.getElementById('modalComprobanteOverlay')?.addEventListener('click', (e) => {
                if (e.target === e.currentTarget) cerrarModalComprobante();
            });
        </script>
    @endpush

@endsection
