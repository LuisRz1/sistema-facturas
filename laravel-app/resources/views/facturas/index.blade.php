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

        .btn-wa { background: #d1fae5; color: #059669; }
        .btn-wa:hover { background: #a7f3d0; }
        .btn-mail { background: #dbeafe; color: #1d4ed8; }
        .btn-mail:hover { background: #bfdbfe; }

        .serie-num { font-family: 'DM Mono', monospace; font-weight: 700; font-size: 13px; color: var(--text-primary); }

        .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .tag-wa { background: #dcfce7; color: #16a34a; }
        .tag-mail { background: #dbeafe; color: #2563eb; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Facturas</h1>
            <p class="page-desc">Control de deuda, detracciones y notificaciones automáticas a clientes.</p>
        </div>
        <div class="page-actions">
            {{-- Placeholder para importar Excel --}}
            <a href="{{ route('facturas.importar') }}" class="btn btn-outline">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
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

    {{-- FILTROS DE REPORTE --}}
    <div class="card" style="margin-bottom: 24px; padding: 20px 24px; width: fit-content;">
        <div style="display: flex; align-items: flex-end; gap: 18px; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 6px; width: 400px;">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                    Empresa
                </label>
                <select class="form-input" id="filterEmpresa">
                    <option value="">Todas las Empresas</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id_cliente }}">{{ $cliente->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px; width: 400px;">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);">
                    Estado de Factura
                </label>
                <select class="form-input" id="filterEstadoReporte">
                    <option value="">Todos los Estados</option>
                    <option value="PENDIENTE">Pendiente</option>
                    <option value="POR_VENCER">Por Vencer</option>
                    <option value="VENCIDA">Vencida</option>
                    <option value="PAGADA">Pagada</option>
                    <option value="ANULADA">Anulada</option>
                </select>
            </div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Facturas</div>
                <div class="card-desc">{{ $facturas->count() }} facturas registradas</div>
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
                    <th>TIPO RECAUDACIÓN</th>
                    <th>FECHA ABONO</th>
                    <th>ESTADO</th>
                    <th>ÚLTIMA NOTIF.</th>
                    <th style="text-align:right;">ACCIONES</th>
                </tr>
                </thead>
                <tbody id="facturasBody">
                @forelse($facturas as $factura)
                    @php
                        $ultimaNotif = $factura->notificaciones->first() ?? null;
                        $estadoClass = strtolower(str_replace('_','',$factura->estado));
                        // badge class map
                        $badgeMap = [
                            'PENDIENTE' => 'badge-pendiente',
                            'POR_VENCER' => 'badge-por_vencer',
                            'VENCIDA' => 'badge-vencida',
                            'PAGADA' => 'badge-pagada',
                            'ANULADA' => 'badge-anulada',
                            'OBSERVADA' => 'badge-pendiente',
                        ];
                        $badgeClass = $badgeMap[$factura->estado] ?? 'badge-pendiente';
                        $puedeNotificar = in_array($factura->estado, ['PENDIENTE','POR_VENCER','VENCIDA']);

                        // Datos de recaudación
                        $porcentajeRecaudacion = $factura->porcentaje_recaudacion ?? 0;
                        $montoRecaudacion = $factura->monto_recaudacion ?? 0;
                        $tipoRecaudacion = $factura->tipo_recaudacion_actual;
                    @endphp
                    <tr data-cliente="{{ $factura->id_cliente }}" data-estado="{{ $factura->estado }}" data-moneda="{{ $factura->moneda }}" data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '')) }}">
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
                                        <span class="tag {{ $ultimaNotif->canal === 'WHATSAPP' ? 'tag-wa' : 'tag-mail' }}">
                                            {{ $ultimaNotif->canal }}
                                        </span>
                                        &nbsp;{{ \Carbon\Carbon::parse($ultimaNotif->fecha_creacion)->format('d/m/Y H:i') }}
                                    </div>
                                    <div class="notify-meta">{{ Str::limit($ultimaNotif->observacion, 28) }}</div>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px;">Sin notificaciones</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;flex-wrap:wrap;gap:6px;">
                                <!-- BTN EDITAR -->
                                <button type="button" onclick="abrirModalEditar('{{ $factura->id_factura }}')" class="action-btn" title="Editar factura" style="color:#7c3aed;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <!-- BTN SUBIR COMPROBANTE (solo si PENDIENTE) -->
                                @if($factura->estado === 'PENDIENTE')
                                    <button type="button" onclick="abrirModalComprobante('{{ $factura->id_factura }}')" class="action-btn" title="Subir comprobante de pago" style="color:#d97706;">
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </button>
                                @endif

                                <!-- BOTONES DE NOTIFICACIÓN PENDIENTE -->
                                @if($puedeNotificar)
                                    <form method="POST" action="{{ route('facturas.enviar-whatsapp-manual', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-wa" title="Enviar WhatsApp (Deuda)">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            WA
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-correo-manual', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-mail" title="Enviar Correo (Deuda)">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            ✉
                                        </button>
                                    </form>
                                @endif

                                <!-- BOTONES DE NOTIFICACIÓN PAGADA -->
                                @if($factura->estado === 'PAGADA')
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-whatsapp', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-wa" title="Enviar Reporte (Pagada)" style="background:#c6f6d5;">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('facturas.enviar-factura-pagada-correo', $factura->id_factura) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn-icon-text btn-mail" title="Enviar Reporte (Pagada)" style="background:#bfdbfe;">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin facturas registradas</p>
                                <p style="font-size:13px;margin-top:4px;">Importa un Excel para comenzar.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL EDITAR FACTURA -->
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

    <!-- MODAL SUBIR COMPROBANTE -->
    <div class="modal-overlay" id="modalComprobanteOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Subir Comprobante de Pago</h2>
                <p>Selecciona una imagen del comprobante para marcar como pagada</p>
                <button onclick="cerrarModalComprobante()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formComprobante" onsubmit="enviarComprobante(event)" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="text-align:center;padding:48px 24px;">
                    <div id="dropZone" style="border:2px dashed #cbd5e1;border-radius:12px;padding:40px;cursor:pointer;transition:all .2s;" onmouseover="this.style.borderColor='#1d4ed8';this.style.background='#eff6ff'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fff'">
                        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto;color:#cbd5e1;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p style="margin:16px 0 8px;font-weight:600;">Arrastra o haz clic para seleccionar</p>
                        <p style="font-size:12px;color:#64748b;">Soporta JPG, PNG, GIF (máximo 2MB)</p>
                        <input type="file" name="comprobante" id="fileComprobante" accept="image/*" style="display:none;" onchange="mostrarPreview(event)">
                    </div>
                    <div id="preview" style="display:none;margin-top:20px;">
                        <img id="previewImg" src="" style="max-width:100%;max-height:300px;border-radius:8px;">
                        <button type="button" onclick="limpiarPreview()" style="margin-top:12px;padding:8px 16px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;">Cambiar imagen</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalComprobante()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnEnviarComprobante">Confirmar y Marcar como Pagada</button>
                </div>
            </form>
            <script>
                document.getElementById('dropZone').addEventListener('click', () => document.getElementById('fileComprobante').click());
                document.getElementById('dropZone').addEventListener('dragover', (e) => e.preventDefault());
                document.getElementById('dropZone').addEventListener('drop', (e) => {
                    e.preventDefault();
                    document.getElementById('fileComprobante').files = e.dataTransfer.files;
                    mostrarPreview({target: {files: e.dataTransfer.files}});
                });
            </script>
        </div>
    </div>

    @push('scripts')
        <script>
            let facturaActualId = null;

            // ── MODAL EDITAR ────────────────────────────────────────────────────
            function abrirModalEditar(id) {
                facturaActualId = id;
                document.getElementById('modalEditarOverlay').classList.add('open');
                
                // Cargar datos de la factura
                fetch(`/facturas/${id}/edit`)
                    .then(r => r.json())
                    .then(factura => {
                        document.getElementById('editFechaEmision').value = factura.fecha_emision || '';
                        document.getElementById('editFechaVencimiento').value = factura.fecha_vencimiento || '';
                        document.getElementById('editFechaAbono').value = factura.fecha_abono || '';
                        document.getElementById('editEstado').value = factura.estado || '';
                        document.getElementById('editGlosa').value = factura.glosa || '';
                        document.getElementById('editFormaPago').value = factura.forma_pago || '';
                        document.getElementById('editImporteTotal').value = factura.importe_total || '';
                        document.getElementById('editMontoIgv').value = factura.monto_igv || '';
                        document.getElementById('editSubtotalGravado').value = factura.subtotal_gravado || '';
                    });
            }

            function cerrarModalEditar() {
                document.getElementById('modalEditarOverlay').classList.remove('open');
            }

            function guardarFactura(event) {
                event.preventDefault();
                
                // Recopilar datos del formulario
                const datos = {
                    fecha_emision: document.getElementById('editFechaEmision').value,
                    fecha_vencimiento: document.getElementById('editFechaVencimiento').value,
                    fecha_abono: document.getElementById('editFechaAbono').value,
                    estado: document.getElementById('editEstado').value,
                    glosa: document.getElementById('editGlosa').value,
                    forma_pago: document.getElementById('editFormaPago').value,
                    importe_total: document.getElementById('editImporteTotal').value,
                    monto_igv: document.getElementById('editMontoIgv').value,
                    subtotal_gravado: document.getElementById('editSubtotalGravado').value,
                };

                // Enviar como JSON
                fetch(`/facturas/${facturaActualId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(datos)
                })
                .then(r => {
                    if (!r.ok) throw new Error(`Error ${r.status}`);
                    return r.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Factura guardada correctamente');
                        cerrarModalEditar();
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo guardar'));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al guardar: ' + err.message);
                });
            }

            // ── MODAL COMPROBANTE ────────────────────────────────────────────────
            function abrirModalComprobante(id) {
                facturaActualId = id;
                document.getElementById('modalComprobanteOverlay').classList.add('open');
                limpiarPreview();
            }

            function cerrarModalComprobante() {
                document.getElementById('modalComprobanteOverlay').classList.remove('open');
            }

            function mostrarPreview(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        document.getElementById('previewImg').src = e.target.result;
                        document.getElementById('preview').style.display = 'block';
                        document.getElementById('dropZone').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            }

            function limpiarPreview() {
                document.getElementById('fileComprobante').value = '';
                document.getElementById('preview').style.display = 'none';
                document.getElementById('dropZone').style.display = 'block';
            }

            function enviarComprobante(event) {
                event.preventDefault();
                const formData = new FormData(document.getElementById('formComprobante'));
                const file = document.getElementById('fileComprobante').files[0];

                if (!file) {
                    alert('Por favor selecciona una imagen');
                    return;
                }

                document.getElementById('btnEnviarComprobante').disabled = true;
                document.getElementById('btnEnviarComprobante').textContent = 'Enviando...';

                fetch(`/facturas/${facturaActualId}/upload-comprobante`, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        cerrarModalComprobante();
                        location.reload();
                    } else {
                        alert(data.error || 'Error al subir el comprobante');
                    }
                })
                .catch(err => alert('Error: ' + err))
                .finally(() => {
                    document.getElementById('btnEnviarComprobante').disabled = false;
                    document.getElementById('btnEnviarComprobante').textContent = 'Confirmar y Marcar como Pagada';
                });
            }

            // ── FILTROS ─────────────────────────────────────────────────────────
            function filtrarTabla() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const estado = document.getElementById('filterEstado').value;
                const moneda = document.getElementById('filterMoneda').value;
                const empresa = document.getElementById('filterEmpresa').value;
                const rows = document.querySelectorAll('#facturasBody tr[data-estado]');

                rows.forEach(row => {
                    const matchSearch = !search || row.dataset.search.includes(search);
                    const matchEstado = !estado || row.dataset.estado === estado;
                    const matchMoneda = !moneda || row.dataset.moneda === moneda;
                    const matchEmpresa = !empresa || row.dataset.cliente === empresa;
                    row.style.display = (matchSearch && matchEstado && matchMoneda && matchEmpresa) ? '' : 'none';
                });
            }

            function generarReportePDF() {
                const idCliente = document.getElementById('filterEmpresa').value;
                const estado = document.getElementById('filterEstadoReporte').value;
                
                let url = '{{ route("reportes.pdf") }}?';
                if (idCliente) url += 'id_cliente=' + idCliente;
                if (estado) url += (idCliente ? '&' : '') + 'estado=' + estado;
                
                window.open(url, '_blank');
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
