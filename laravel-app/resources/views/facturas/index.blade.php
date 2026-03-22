@extends('layouts.app')
@section('title', 'Gestión de Facturas')
@section('breadcrumb', 'Gestión de Facturas')

@push('styles')
    <style>
        /* ═════════════════════════════════════════════════════════════════ */
        /* VARIABLES DORADAS */
        /* ═════════════════════════════════════════════════════════════════ */
        :root {
            --gold: #f5c842;
            --gold-h: #e8b820;
            --gold-l: #fffbeb;
            --gold-b: #fce8a8;
            --gold-m: #fdd457;
            --gold-d: #ca9d1f;
            --gold-xd: #7a5d0f;
            --bg: #fdf8ec;
        }

        /* ═════════════════════════════════════════════════════════════════ */
        /* KEYFRAMES E ANIMACIONES */
        /* ═════════════════════════════════════════════════════════════════ */
        @keyframes fadeDown {
            from { opacity:0; transform:translateY(-12px); }
            to { opacity:1; transform:translateY(0); }
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(16px); }
            to { opacity:1; transform:translateY(0); }
        }
        @keyframes rowIn {
            from { opacity:0; transform:translateX(-8px); }
            to { opacity:1; transform:translateX(0); }
        }
        @keyframes chipPop {
            0% { opacity:0; transform:scale(0.85); }
            100% { opacity:1; transform:scale(1); }
        }

        /* ═════════════════════════════════════════════════════════════════ */
        /* ESTILOS GENERALES */
        /* ═════════════════════════════════════════════════════════════════ */
        .page-header { animation:fadeDown .5s ease-out; }

        /* ── FILTROS ── */
        .filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; animation:slideUp .55s ease-out .15s both; }
        .filter-row .search-input-wrap { max-width:280px; border:1.5px solid var(--gold-b); border-radius:10px; padding:8px 12px; background:#fff; display:flex; align-items:center; gap:8px; }
        .filter-row .search-input-wrap svg { color:var(--gold); flex-shrink:0; }
        .filter-row .form-input { border:none; background:transparent; outline:none; flex:1; font-size:13px; }
        .filter-row .form-select { width:auto; min-width:160px; height:40px; border:1.5px solid var(--gold-b); border-radius:10px; background:#fff; color:var(--text-primary); font-size:13px; cursor:pointer; transition:border-color .15s; }
        .filter-row .form-select:focus { border-color:var(--gold); }

        /* ── TABLE ── */
        #facturasBody tr { animation:rowIn .4s ease-out; }
        #facturasBody tr:nth-child(1) { animation-delay:.18s; }
        #facturasBody tr:nth-child(2) { animation-delay:.23s; }
        #facturasBody tr:nth-child(3) { animation-delay:.28s; }
        #facturasBody tr:nth-child(4) { animation-delay:.33s; }
        #facturasBody tr:nth-child(5) { animation-delay:.38s; }
        #facturasBody tr:nth-child(n+6) { animation-delay:0; }

        .actions-cell { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
        .action-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:none; cursor:pointer; transition:all .15s; color:var(--gold); background:transparent; }
        .action-btn:hover { background:var(--gold-l); transform:scale(1.08); }
        .client-cell { display:flex; flex-direction:column; gap:2px; }
        .client-name { font-weight:600; font-size:13.5px; color:var(--text-primary); }
        .client-ruc  { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); background:var(--gold-l); padding:2px 6px; border-radius:4px; display:inline-block; width:fit-content; }
        .amount-main { font-weight:700; font-family:'DM Mono',monospace; font-size:13px; color:var(--text-primary); }
        .amount-sub  { font-size:11px; color:var(--text-muted); font-family:'DM Mono',monospace; margin-top:2px; }
        .notify-cell { display:flex; flex-direction:column; gap:4px; }
        .notify-meta { font-size:11px; color:var(--text-muted); }
        .btn-icon-text { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:600; border:none; cursor:pointer; transition:all .15s; }
        .btn-wa   { background:#d1fae5; color:#059669; }
        .btn-wa:hover { background:#a7f3d0; transform:translateY(-1px); }
        .btn-mail { background:#dbeafe; color:#1d4ed8; }
        .btn-mail:hover { background:#bfdbfe; transform:translateY(-1px); }
        .serie-num { font-family:'DM Mono',monospace; font-weight:700; font-size:13px; color:var(--gold); background:var(--gold-l); padding:3px 8px; border-radius:6px; display:inline-block; }
        .tag { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
        .tag-wa   { background:#dcfce7; color:#16a34a; }
        .tag-mail { background:#dbeafe; color:#2563eb; }

        /* ── RANGO FECHAS ── */
        .date-range-wrap { display:flex; align-items:center; gap:10px; background:#fff; border:1.5px solid var(--gold-b); border-radius:10px; padding:12px 20px; margin-bottom:20px; flex-wrap:wrap; animation:slideUp .55s ease-out .15s both; }
        .date-range-wrap label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-xd); white-space:nowrap; }
        .date-range-wrap input[type="date"] { height:38px; padding:0 12px; border:1.5px solid var(--gold-b); border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:var(--text-primary); outline:none; transition:border-color .15s; cursor:pointer; }
        .date-range-wrap input[type="date"]:focus { border-color:var(--gold); box-shadow:0 0 0 2px var(--gold-l); }
        .date-range-wrap .sep { color:var(--gold); font-size:14px; font-weight:600; }
        .date-range-wrap .btn-ghost { border-color:var(--gold-b); color:var(--gold); }
        .date-range-wrap .btn-ghost:hover { background:var(--gold-l); border-color:var(--gold); }

        /* ── IMAGEN ── */
        .img-preview-thumb { width:36px; height:36px; object-fit:cover; border-radius:5px; border:1px solid var(--gold-b); cursor:pointer; }

        /* ── ESTADOS BADGE (esquema coherente) ── */
        .badge-pendiente         { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .badge-vencido           { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
        .badge-pagada            { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
        .badge-pago_parcial      { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
        .badge-por_validar_det   { background:#fdf4ff; color:#7e22ce; border:1.5px solid #e9d5ff; }
        .badge-diferencia_pend   { background:#fce7f3; color:#9d174d; border:1.5px solid #fbcfe8; }
        .badge-anulada           { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

        /* ── LEYENDA DE ESTADOS ── */
        .estados-legend {
            display:flex; gap:10px; flex-wrap:wrap; align-items:center;
            background:#fff; border:1.5px solid var(--gold-b); border-radius:10px;
            padding:12px 20px; margin-bottom:16px; font-size:11px;
            animation:slideUp .55s ease-out both;
        }
        .estados-legend .legend-title {
            font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
            color:var(--gold-xd); margin-right:4px; white-space:nowrap;
        }
        .legend-item { display:flex; align-items:center; gap:6px; }
        .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

        /* ── STATS CARDS ── */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:20px; }
        .stat-card {
            background:#fff; border:1.5px solid var(--gold-b); border-radius:12px; padding:16px;
            display:flex; align-items:center; gap:12px; transition:all .2s; animation:chipPop .4s ease-out both;
        }
        .stat-card:nth-child(1) { animation-delay:.1s; }
        .stat-card:nth-child(2) { animation-delay:.17s; }
        .stat-card:nth-child(3) { animation-delay:.24s; }
        .stat-card:nth-child(4) { animation-delay:.31s; }
        .stat-card:hover { border-color:var(--gold); box-shadow:0 4px 12px rgba(245,200,66,0.12); transform:translateY(-2px); }
        .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-card.blue .stat-icon { background:#dbeafe; color:#1d4ed8; }
        .stat-card.amber .stat-icon { background:#fef3c7; color:var(--gold); }
        .stat-card.green .stat-icon { background:#d1fae5; color:#059669; }
        .stat-card.red .stat-icon { background:#fee2e2; color:#dc2626; }
        .stat-label { font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
        .stat-value { font-size:16px; font-weight:700; color:var(--text-primary); font-family:'DM Mono',monospace; }

        /* ── MONTO PENDIENTE ── */
        .monto-pendiente-cell { color:#dc2626; font-weight:700; font-family:'DM Mono',monospace; font-size:12px; }
        .monto-pendiente-zero { color:#059669; font-family:'DM Mono',monospace; font-size:12px; }

        /* ── MODAL PAGO ── */
        .pago-section { background:var(--gold-l); border-radius:10px; padding:18px; margin-bottom:16px; border:1px solid var(--gold-b); }
        .pago-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-xd); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .calc-display { background:#fff; border:1.5px solid var(--gold-b); border-radius:8px; padding:14px; margin-top:12px; font-size:13px; }
        .calc-row { display:flex; justify-content:space-between; align-items:center; padding:6px 0; color:var(--text-primary); }
        .calc-row.total { border-top:2px solid var(--gold); margin-top:8px; padding-top:10px; font-weight:800; font-size:14px; }
        .calc-row.pending { color:#dc2626; }
        .calc-row.paid { color:#059669; }

        /* Tipo recaudacion selector */
        .tipo-rec-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin-bottom:10px; }
        .tipo-rec-card { border:2px solid var(--gold-b); border-radius:8px; padding:10px 8px; text-align:center; cursor:pointer; transition:all .15s; background:#fff; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); }
        .tipo-rec-card:hover { border-color:var(--gold); background:var(--gold-l); }
        .tipo-rec-card.active-det { border-color:var(--gold); background:var(--gold-l); color:var(--gold-xd); }
        .tipo-rec-card.active-auto { border-color:#059669; background:#d1fae5; color:#065f46; }
        .tipo-rec-card.active-ret { border-color:#7c3aed; background:#ede9fe; color:#5b21b6; }

        /* ── BOTÓN GENERAR REPORTE ── */
        .btn-generar-reporte { background:var(--gold); color:#000; border:none; font-weight:700; }
        .btn-generar-reporte:hover { background:var(--gold-h); }

        /* ── FIX SCROLL MODAL PAGO ── */
        #modalPagoOverlay .modal-body { min-height:0; max-height:calc(90vh - 160px); overflow-y:auto; }

        /* ── MODAL REPORTE NUEVO ── */
        .reporte-tipo-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px; }
        .reporte-tipo-card { position:relative; border:2px solid var(--gold-b); border-radius:10px; padding:18px 14px 14px; cursor:pointer; transition:all .18s; background:#fff; }
        .reporte-tipo-card:hover { border-color:var(--gold); background:var(--gold-l); }
        .reporte-tipo-card.active { border-color:var(--gold); background:var(--gold-l); }
        .rtc-icon  { font-size:22px; margin-bottom:8px; display:block; }
        .rtc-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--text-primary); display:block; }
        .rtc-desc  { font-size:11px; color:var(--text-muted); margin-top:4px; line-height:1.4; }
        .reporte-tipo-card.active .rtc-title { color:var(--gold-xd); }
        .rtc-check { position:absolute; top:10px; right:12px; width:18px; height:18px; border-radius:50%; border:2px solid var(--gold-b); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:900; }
        .reporte-tipo-card.active .rtc-check { border-color:var(--gold); background:var(--gold); color:#000; }
        .estado-chip-wrap { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
        .estado-chip { padding:6px 14px; border-radius:20px; border:1.5px solid var(--gold-b); font-size:11px; font-weight:700; cursor:pointer; transition:all .15s; background:#fff; color:var(--text-muted); }
        .estado-chip:hover { border-color:var(--gold); }
        .estado-chip.active { border-color:var(--gold); background:var(--gold-l); color:var(--gold-xd); }
        .chip-pendiente.active { border-color:#d97706 !important; background:#fef3c7 !important; color:#92400e !important; }
        .chip-vencido.active   { border-color:#dc2626 !important; background:#fee2e2 !important; color:#7f1d1d !important; }
        .chip-parcial.active   { border-color:#4f46e5 !important; background:#e0e7ff !important; color:#3730a3 !important; }
        .chip-det.active       { border-color:#7c3aed !important; background:#fdf4ff !important; color:#6b21a8 !important; }
        .chip-todos.active     { border-color:#059669 !important; background:#d1fae5 !important; color:#065f46 !important; }

        /* Modal de reporte a usuario */
        .usuario-option { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--gold-b); border-radius:8px; cursor:pointer; transition:all .15s; margin-bottom:8px; background:#fff; }
        .usuario-option:hover { border-color:var(--gold); background:var(--gold-l); }
        .usuario-option.selected { border-color:var(--gold); background:var(--gold-l); }
        .usuario-avatar-sm { width:32px; height:32px; border-radius:50%; background:var(--gold); color:#000; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
        .u-check { width:20px; height:20px; border-radius:5px; border:2px solid var(--gold-b); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; flex-shrink:0; transition:all .15s; }
        .usuario-option.selected .u-check { border-color:var(--gold); background:var(--gold); color:#000; }
        .btn-pdf-filtros { background:var(--gold); color:#000; border:none; font-weight:700; }
        .btn-pdf-filtros:hover { background:var(--gold-h); }

        /* ── MODAL HEADER DORADO ── */
        .modal-header { background:linear-gradient(135deg, var(--gold) 0%, var(--gold-h) 100%); border-top:3px solid var(--gold-xd); }
        .modal-header h2 { color:#000; font-weight:700; }
        .modal-header p { color:rgba(0,0,0,.7); }
        .modal-header button { color:#000; opacity:.7; }
        .modal-header button:hover { opacity:1; }

        /* ── INSTRUCCIONES Y HINTS ── */
        #validarDetraccionWrap { display:none; margin-bottom:12px; padding:10px 14px; background:var(--gold-l); border-radius:8px; border:1px solid var(--gold-b); color:var(--gold-xd); }
        #validarDetraccionWrap label { color:var(--gold-xd); font-weight:600; }
        #validarDetraccionWrap input[type="checkbox"] { accent-color:var(--gold); }

        /* ── DROP ZONE ── */
        #dropZonePago { border:2px dashed var(--gold-b); border-radius:10px; padding:24px; text-align:center; cursor:pointer; transition:all .2s; background:#fff; }
        #dropZonePago:hover { border-color:var(--gold); background:var(--gold-l); }
        #dropZonePago svg { color:var(--gold); }

        .inline-alert { position:fixed; bottom:24px; right:24px; z-index:9999; padding:14px 20px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,.15); transform:translateY(80px); opacity:0; transition:all .3s cubic-bezier(.16,1,.3,1); max-width:400px; }
        .inline-alert.show  { transform:translateY(0); opacity:1; }
        .inline-alert.ok    { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
        .inline-alert.error { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Facturas</h1>
            <p class="page-desc">Control de facturas, pagos y notificaciones a clientes.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('facturas.importar') }}" class="btn btn-outline" style="border-color:var(--gold); color:var(--gold);">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar Excel
            </a>
            {{-- ← AGREGA ESTO --}}
            <a href="{{ route('detracciones.index') }}" class="btn btn-outline" style="border-color:#7c3aed;color:#7c3aed;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Validar Detracciones
            </a>
            <button type="button" class="btn-pdf-filtros" onclick="generarPDFFiltros()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF con Filtros
            </button>
            <button type="button" class="btn-generar-reporte" onclick="abrirModalReporte()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generar Reporte
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
            <span class="legend-dot" style="background:#be185d;"></span>
            <span class="badge badge-diferencia_pend" style="font-size:10px;">DIFERENCIA PENDIENTE</span>
            <span style="font-size:11px;color:var(--text-muted);">— Detracción validada, falta diferencia</span>
        </div>
    </div>

    {{-- ── STATS ── --}}
    @php
        // Incluir ANULADO solo si está ligado a otra factura
        $facturasParaTotales = $facturas->filter(function ($f) {
            if ($f->estado === 'ANULADO') {
                return \DB::table('credito')->where('id_factura', $f->id_factura)->exists();
            }
            return true;
        });
        $total        = $facturasParaTotales->sum('importe_total');
        $pendiente    = $facturasParaTotales->whereIn('estado',['PENDIENTE','VENCIDO','DIFERENCIA PENDIENTE'])->sum('monto_pendiente');
        $pagada       = $facturasParaTotales->where('estado','PAGADA')->sum('importe_total');
        $parcial      = $facturasParaTotales->where('estado','PAGO PARCIAL')->sum('monto_pendiente');
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
            <span style="font-size:12px;color:var(--gold-xd);margin-left:6px;">
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
                    <option value="DIFERENCIA PENDIENTE">Diferencia Pendiente</option>
                </select>
                <select class="form-select" id="filterMoneda" onchange="filtrarTabla()">
                    <option value="">Todas las monedas</option>
                    <option value="PEN">Soles (PEN)</option>
                    <option value="USD">Dólares (USD)</option>
                </select>
                <select class="form-select" id="filterRecaudacion" onchange="filtrarTabla()" style="min-width:180px;">
                    <option value="">Toda recaudación</option>
                    <option value="DETRACCION">Detracción</option>
                    <option value="AUTODETRACCION">Autodetracción</option>
                    <option value="RETENCION">Retención</option>
                    <option value="SIN">Sin recaudación</option>
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
                    <th>PENDIENTE</th>
                    <th>CUENTA PAGO</th>
                    <th>ABONADO</th>
                    <th>ESTADO</th>
                    <th>CREADO POR</th>
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
                            'DIFERENCIA PENDIENTE'  => 'badge-diferencia_pend',
                            'ANULADA'               => 'badge-anulada',
                        ];
                        $badgeClass = $badgeMap[$estado] ?? 'badge-pendiente';
                        $montoRecaudacion = $factura->monto_recaudacion ?? 0;
                        $porcentaje       = $factura->porcentaje_recaudacion ?? 0;
                        $tipoRecaudacion  = $factura->tipo_recaudacion;
                        $montoAbonado     = $factura->monto_abonado ?? 0;
                        $montoPendiente   = $factura->monto_pendiente ?? $factura->importe_total;
                        $tieneComprobante = false; // ruta_comprobante_pago no existe en el esquema actual
                        $puedeNotificarDeuda = in_array($estado, ['PENDIENTE','VENCIDO','PAGO PARCIAL','POR VALIDAR DETRACCION','DIFERENCIA PENDIENTE']);
                        $ultimaNotifWa     = $factura->ultima_notif_wa ?? null;
                        $ultimaNotifCorreo = $factura->ultima_notif_correo ?? null;
                        // Verificar si ANULADO está ligado a otra factura
                        $anuladoLigado = $estado === 'ANULADO' && \DB::table('credito')->where('id_factura', $factura->id_factura)->exists();
                        $esAnuladoHuerfano = $estado === 'ANULADO' && !$anuladoLigado;
                    @endphp
                    <tr data-cliente="{{ $factura->id_cliente }}" data-estado="{{ $estado }}"
                        data-moneda="{{ $factura->moneda }}"
                        data-recaudacion="{{ $tipoRecaudacion ?: 'SIN' }}"
                        data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '').($factura->usuario_nombre ?? '')) }}"
                        @if($esAnuladoHuerfano) style="text-decoration: line-through; opacity: 0.6;" @endif>

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
                                    {{ $tipoRecaudacion ?? '' }}
                                </div>
                                @if(!empty($factura->fecha_recaudacion))
                                    <div style="font-size:10px;color:#059669;font-weight:600;margin-top:2px;">
                                        {{ \Carbon\Carbon::parse($factura->fecha_recaudacion)->format('d/m/Y') }}
                                    </div>
                                @elseif($porcentaje > 0)
                                    <div style="font-size:10px;color:#92400e;font-weight:600;">{{ $porcentaje }}%</div>
                                @endif
                            @else
                                <span style="font-size:12px;color:var(--text-muted);">—</span>
                            @endif
                        </td>

                        {{-- PENDIENTE (6ta celda) --}}
                        <td style="text-align:right;">
                            @if($estado === 'PAGADA')
                                <span class="monto-pendiente-zero">✓ Cancelado</span>
                            @elseif($montoPendiente > 0)
                                <div class="monto-pendiente-cell">{{ $factura->moneda }} {{ number_format($montoPendiente,2) }}</div>
                            @else
                                <span class="monto-pendiente-zero">—</span>
                            @endif
                        </td>

                        {{-- CUENTA PAGO (7ma celda) --}}
                        <td style="text-align:left;font-size:12px;">
                            @if($factura->cuenta_pago)
                                <div title="{{ $factura->cuenta_pago }}" style="color:#1f2937;font-weight:600;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $factura->cuenta_pago }}
                                </div>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>

                        {{-- ABONADO (8va celda) --}}
                        <td style="text-align:right;">
                            @php
                                // Verificar si esta factura es una nota de crédito
                                $creditoInfo = DB::table('credito')->where('id_factura', $factura->id_factura)->first();
                                $creditoAsociado = DB::table('credito')
                                    ->where('serie_doc_modificado', $factura->serie)
                                    ->where('numero_doc_modificado', $factura->numero)
                                    ->first();
                            @endphp
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
                            
                            {{-- Mostrar relación con nota de crédito --}}
                            @if($creditoInfo)
                                {{-- Esta es una nota de crédito: mostrar FC01-XXX / FF01-XXX --}}
                                <div style="font-size:10px;color:#7c3aed;font-weight:600;margin-top:3px;">
                                    {{ $factura->serie }}-{{ str_pad($factura->numero,8,'0',STR_PAD_LEFT) }} / {{ $creditoInfo->serie_doc_modificado }}-{{ str_pad($creditoInfo->numero_doc_modificado,8,'0',STR_PAD_LEFT) }}
                                </div>
                            @elseif($creditoAsociado)
                                {{-- Esta factura tiene una nota de crédito asociada: mostrar FF01-XXX / FC01-XXX --}}
                                <div style="font-size:10px;color:#7c3aed;font-weight:600;margin-top:3px;">
                                    {{ $factura->serie }}-{{ str_pad($factura->numero,8,'0',STR_PAD_LEFT) }} / {{ $creditoAsociado->serie }}-{{ str_pad($creditoAsociado->numero,8,'0',STR_PAD_LEFT) }}
                                </div>
                            @endif
                        </td>

                        {{-- ESTADO (9na celda) --}}
                        <td><span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$estado) }}</span></td>
                        {{-- CREADO POR (10ma celda) --}}
                        <td>
                            @if($factura->usuario_nombre)
                                <div style="font-size:12px;font-weight:600;color:var(--text-primary);">
                                    {{ $factura->usuario_nombre }} {{ $factura->usuario_apellido }}
                                </div>
                            @else
                                <span style="font-size:11px;color:var(--text-muted);">—</span>
                            @endif
                        </td>

                        {{-- NOTIFICACIONES (11va celda) --}}
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
                                        onclick="abrirModalPago('{{ $factura->id_factura }}', {{ $factura->importe_total }}, '{{ $factura->moneda }}', {{ $montoAbonado }}, {{ $montoRecaudacion }}, {{ $porcentaje }}, '{{ $tipoRecaudacion }}', '{{ $estado }}', '{{ $factura->cuenta_pago ?? '' }}', '{{ $factura->fecha_recaudacion ?? '' }}')"                                        class="action-btn"
                                        title="{{ $estado === 'PAGADA' ? 'Ver/Actualizar pago' : 'Registrar pago' }}"
                                        style="color:{{ $montoAbonado > 0 ? '#1d4ed8' : '#d97706' }};">
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
                    <tr><td colspan="10"><div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin facturas en el período seleccionado</p>
                                <p style="font-size:13px;margin-top:4px;">Cambia el rango de fechas o importa facturas.</p>
                            </div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ TOAST DE CONFIRMACIÓN ═══════════ --}}
    <div class="inline-alert" id="toastFactura">
        <svg id="toastFacturaIco" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"></svg>
        <span id="toastFacturaTxt"></span>
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
                                <label class="form-label">Monto Abonado</label>
                                <input type="number" id="pagoMontoAbonado" name="monto_abonado" step="0.01" min="0" class="form-input" placeholder="0.00 (opcional)" oninput="recalcularPago()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha de Abono</label>
                                <input type="date" id="pagoFechaAbono" name="fecha_abono" class="form-input">
                            </div>
                        </div>
                        <div class="form-group" style="margin-top:14px;">
                            <label class="form-label">Cuenta de Pago (Referencia)</label>
                            <input type="text" id="pagoCuentaPago" name="cuenta_pago" class="form-input" placeholder="Ej: Cta. BCP S/ 123456789 o Banco XXX">
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

                        {{-- dentro de #camposRecaudacion, después del grid de porcentaje/monto --}}
                        <div id="camposRecaudacion" style="display:none;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div class="form-group">
                                <label class="form-label">Porcentaje (%)</label>
                                <input type="number" id="pagoPorcentaje" name="porcentaje_recaudacion" step="0.01" min="0" max="100" class="form-input" placeholder="10.00" oninput="calcularRecaudacion()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monto Recaudación</label>
                                <input type="number" id="pagoTotalRecaudacion" name="total_recaudacion" step="0.01" min="0" class="form-input" placeholder="0.00" oninput="_recalcularAbonoAutodet(); recalcularPago();">
                            </div>
                            {{-- NUEVO --}}
                            <div class="form-group" style="grid-column:1/-1;">
                                <label class="form-label">Fecha de Depósito / Recaudación</label>
                                <input type="date" id="pagoFechaRecaudacion" name="fecha_recaudacion" class="form-input" style="border-color:var(--gold-b);">
                                <span style="font-size:11px;color:var(--text-muted);margin-top:3px;display:block;">Fecha en que se depositó la detracción o retención</span>
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
                        <div id="estadoPreview" style="margin-top:10px;padding:8px 12px;border-radius:6px;background:var(--gold-l);font-size:12px;font-weight:700;text-align:center;color:var(--gold-xd);"></div>
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
                            <img id="previewPagoImg" src="" style="max-width:100%;max-height:200px;border-radius:8px;border:1.5px solid var(--gold-b);">
                            <p id="previewPagoPdf" style="display:none;padding:10px;background:var(--gold-l);border-radius:8px;font-size:13px;color:var(--gold-xd);">📄 PDF adjunto</p>
                            <button type="button" onclick="limpiarPreviewPago()" style="margin-top:8px;padding:6px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;transition:all .15s;" onmouseover="this.background='#fca5a5'" onmouseout="this.background='#fee2e2'">Quitar</button>
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
                <h2>Editar Factura</h2><p id="editModalSubtitle">Actualiza los datos de la factura</p>
                <button onclick="cerrarModalEditar()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditarFactura" onsubmit="guardarFactura(event)" style="display:flex;flex-direction:column;max-height:calc(90vh - 80px);overflow:hidden;">
                @csrf @method('PUT')
                <div class="modal-body" style="overflow-y:auto;padding:24px;flex:1;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group"><label class="form-label">Fecha Emisión</label><input type="date" name="fecha_emision" id="editFechaEmision" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Fecha Vencimiento</label><input type="date" name="fecha_vencimiento" id="editFechaVencimiento" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Estado</label>
                            <select name="estado" id="editEstado" class="form-input">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="VENCIDO">Vencido</option>
                                <option value="PAGADA">Pagada</option>
                                <option value="PAGO PARCIAL">Pago Parcial</option>
                                <option value="POR VALIDAR DETRACCION">Por Validar Detracción</option>
                                <option value="DIFERENCIA PENDIENTE">Diferencia Pendiente</option>
                                <option value="ANULADA">Anulada</option>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Forma de Pago</label><input type="text" name="forma_pago" id="editFormaPago" class="form-input"></div>
                        <div class="form-group" style="grid-column:1/-1;"><label class="form-label">Glosa</label><textarea name="glosa" id="editGlosa" class="form-input" style="resize:vertical;min-height:60px;height:60px;"></textarea></div>
                        <div class="form-group"><label class="form-label">Importe Total</label><input type="number" name="importe_total" id="editImporteTotal" step="0.01" class="form-input"></div>
                        <div class="form-group"><label class="form-label">IGV</label><input type="number" name="monto_igv" id="editMontoIgv" step="0.01" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Subtotal Gravado</label><input type="number" name="subtotal_gravado" id="editSubtotalGravado" step="0.01" class="form-input"></div>
                        <div class="form-group">
                            <label class="form-label">Monto Pendiente</label>
                            <input type="number" name="monto_pendiente" id="editMontoPendiente" step="0.01" min="0" class="form-input" readonly style="background:#f8fafc;cursor:not-allowed;color:var(--text-muted);">
                            <span style="font-size:11px;color:var(--text-muted);margin-top:4px;display:block;">Se recalcula automáticamente</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="flex-shrink:0;">
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

    {{-- ═══════════ MODAL GENERAR REPORTE ═══════════ --}}
    <div class="modal-overlay" id="modalReporteOverlay">
        <div class="modal" style="max-width:640px;">
            <div class="modal-header">
                <h2>Generar Reporte</h2>
                <p>Configura el reporte — puedes seleccionar varios estados y varios destinatarios</p>
                <button onclick="cerrarModalReporte()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="min-height:0;max-height:calc(90vh - 180px);overflow-y:auto;">

                {{-- ① TIPO DE REPORTE --}}
                <div style="margin-bottom:20px;">
                    <label class="form-label" style="margin-bottom:6px;">① Tipo de Reporte</label>
                    <div class="reporte-tipo-grid">
                        <div class="reporte-tipo-card active" id="rTipoGeneral" onclick="selReporteTipo('general')">
                            <span class="rtc-check" id="rChkGeneral">✓</span>
                            <span class="rtc-icon"></span>
                            <span class="rtc-title">Deuda General</span>
                            <p class="rtc-desc">Resumen por cliente sin desglose de facturas individuales</p>
                        </div>
                        <div class="reporte-tipo-card" id="rTipoDetallado" onclick="selReporteTipo('detallado')">
                            <span class="rtc-check" id="rChkDetallado"></span>
                            <span class="rtc-icon"></span>
                            <span class="rtc-title">Por Cliente con Facturas</span>
                            <p class="rtc-desc">Facturas detalladas agrupadas por cliente con montos y estados</p>
                        </div>
                    </div>
                </div>

                {{-- ② ESTADOS (multi-select) --}}
                <div style="margin-bottom:20px;">
                    <label class="form-label" style="margin-bottom:4px;">② Estados a incluir <span style="font-weight:400;color:var(--gold-xd);">(selecciona uno o varios — puedes combinarlos)</span></label>
                    <div class="estado-chip-wrap" style="margin-top:8px;">
                        <span class="estado-chip chip-todos active" id="rChipTodos"      onclick="toggleEstado('',this)">✦ Todos Pendientes</span>
                        <span class="estado-chip chip-pendiente"   id="rChipPendiente"   onclick="toggleEstado('PENDIENTE',this)">Pendiente</span>
                        <span class="estado-chip chip-vencido"     id="rChipVencido"     onclick="toggleEstado('VENCIDO',this)">Vencido</span>
                        <span class="estado-chip chip-parcial"     id="rChipParcial"     onclick="toggleEstado('PAGO PARCIAL',this)">Pago Parcial</span>
                        <span class="estado-chip chip-det"         id="rChipDet"         onclick="toggleEstado('POR VALIDAR DETRACCION',this)">Por Validar Det.</span>
                    </div>
                    <p id="rEstadoHint" style="font-size:11px;color:var(--gold-xd);margin-top:6px;">
                        "Todos Pendientes" incluye los 4 estados. Al seleccionar uno específico, se desactiva "Todos".
                    </p>
                </div>

                {{-- ③ PERÍODO --}}
                <div style="margin-bottom:20px;">
                    <label class="form-label" style="margin-bottom:6px;">③ Período <span style="font-weight:400;color:var(--gold-xd);">(opcional — vacío = todos los períodos)</span></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;">
                        <input type="date" id="rDesde" class="form-input" style="border-color:var(--gold-b);">
                        <input type="date" id="rHasta" class="form-input" style="border-color:var(--gold-b);">
                    </div>
                </div>

                {{-- ④ USUARIOS DESTINO (multi-select) --}}
                <div>
                    <label class="form-label" style="margin-bottom:4px;">④ Enviar a <span style="font-weight:400;color:var(--gold-xd);">(opcional — selecciona uno o varios usuarios)</span></label>
                    <p style="font-size:11px;color:var(--gold-xd);margin-bottom:10px;">El PDF se abrirá con botones para enviar por WhatsApp / correo a todos los seleccionados. Sin selección solo abre el PDF.</p>
                    <div style="max-height:220px;overflow-y:auto;border:1.5px solid var(--gold-b);border-radius:8px;padding:8px;background:#fff;">
                        @foreach($usuarios as $u)
                            <div class="usuario-option" onclick="toggleUsuario({{ $u->id_usuario }}, '{{ $u->celular }}', '{{ $u->correo ?? '' }}', this)" data-id="{{ $u->id_usuario }}">
                                <div class="usuario-avatar-sm">{{ strtoupper(substr($u->nombre,0,1)) }}</div>
                                <div style="flex:1;">
                                    <div style="font-weight:600;font-size:13px;">{{ $u->nombre }} {{ $u->apellido }}</div>
                                    <div style="font-size:11px;color:var(--text-muted);">{{ $u->celular }}{{ $u->correo ? ' · '.$u->correo : '' }}</div>
                                </div>
                                <div class="u-check"></div>
                            </div>
                        @endforeach
                        @if($usuarios->isEmpty())
                            <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:16px;">No hay usuarios con celular registrado.</p>
                        @endif
                    </div>
                    <div id="rUsuariosResumen" style="font-size:12px;color:var(--gold-xd);font-weight:600;margin-top:8px;display:none;">
                        <!-- se actualiza dinámicamente -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModalReporte()" class="btn btn-ghost">Cancelar</button>
                <button type="button" onclick="generarReporte()" class="btn btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Abrir Reporte PDF
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
                const search      = document.getElementById('searchInput').value.toLowerCase();
                const estado      = document.getElementById('filterEstado').value;
                const moneda      = document.getElementById('filterMoneda').value;
                const empresa     = document.getElementById('filterEmpresa').value;
                const recaudacion = document.getElementById('filterRecaudacion').value;
                document.querySelectorAll('#facturasBody tr[data-estado]').forEach(row => {
                    // Para filtro SIN: filas sin tipo_recaudacion (valor 'SIN')
                    const rowRec = row.dataset.recaudacion || 'SIN';
                    const okRec  = !recaudacion || rowRec === recaudacion;
                    const ok = (!search    || row.dataset.search.includes(search))
                        && (!estado    || row.dataset.estado   === estado)
                        && (!moneda    || row.dataset.moneda   === moneda)
                        && (!empresa   || row.dataset.cliente  === empresa)
                        && okRec;
                    row.style.display = ok ? '' : 'none';
                });
            }

            // ── REPORTES ─────────────────────────────────────────────────────

            // PDF rápido usando los filtros actuales de la tabla
            function generarPDFFiltros() {
                const empresa     = document.getElementById('filterEmpresa').value;
                const estado      = document.getElementById('filterEstado').value;
                const desde       = document.getElementById('inputDesde').value;
                const hasta       = document.getElementById('inputHasta').value;
                const recaudacion = document.getElementById('filterRecaudacion').value;
                const params = new URLSearchParams();
                if (empresa)     params.append('id_cliente', empresa);
                if (estado)      params.append('estado', estado);
                if (desde)       params.append('fecha_desde', desde);
                if (hasta)       params.append('fecha_hasta', hasta);
                if (recaudacion && recaudacion !== 'SIN') params.append('tipo_recaudacion', recaudacion);
                if (recaudacion === 'SIN') params.append('sin_recaudacion', '1');
                window.open('{{ route("reportes.pdf") }}?' + params.toString(), '_blank');
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL GENERAR REPORTE — multi-select estados + multi-select usuarios
            // ══════════════════════════════════════════════════════════════════
            let rTipo     = 'general';
            let rEstados  = new Set();  // vacío = todos pendientes
            let rUsuarios = new Map();  // id → { id, celular, correo, nombre }

            function abrirModalReporte() {
                rTipo    = 'general';
                rEstados = new Set();
                rUsuarios = new Map();
                document.getElementById('rDesde').value = '';
                document.getElementById('rHasta').value = '';
                selReporteTipo('general');
                // Reset chips — activar "Todos"
                document.querySelectorAll('.estado-chip').forEach(c => c.classList.remove('active'));
                document.getElementById('rChipTodos').classList.add('active');
                // Reset usuarios
                document.querySelectorAll('#modalReporteOverlay .usuario-option').forEach(el => {
                    el.classList.remove('selected');
                    const chk = el.querySelector('.u-check');
                    if (chk) chk.textContent = '';
                });
                document.getElementById('rUsuariosResumen').style.display = 'none';
                document.getElementById('modalReporteOverlay').classList.add('open');
            }

            function cerrarModalReporte() {
                document.getElementById('modalReporteOverlay').classList.remove('open');
            }

            function selReporteTipo(tipo) {
                rTipo = tipo;
                document.getElementById('rTipoGeneral').classList.toggle('active', tipo === 'general');
                document.getElementById('rTipoDetallado').classList.toggle('active', tipo === 'detallado');
                document.getElementById('rChkGeneral').textContent   = tipo === 'general'   ? '✓' : '';
                document.getElementById('rChkDetallado').textContent = tipo === 'detallado' ? '✓' : '';
            }

            function toggleEstado(estado, el) {
                const chipTodos = document.getElementById('rChipTodos');

                if (estado === '') {
                    // "Todos Pendientes" — desactivar todo lo demás
                    rEstados.clear();
                    document.querySelectorAll('.estado-chip').forEach(c => c.classList.remove('active'));
                    chipTodos.classList.add('active');
                    return;
                }

                // Desactivar "Todos" si estaba activo
                chipTodos.classList.remove('active');
                rEstados.delete(''); // por si acaso

                if (rEstados.has(estado)) {
                    rEstados.delete(estado);
                    el.classList.remove('active');
                } else {
                    rEstados.add(estado);
                    el.classList.add('active');
                }

                // Si quedó todo vacío, volver a "Todos"
                if (rEstados.size === 0) {
                    chipTodos.classList.add('active');
                }
            }

            function toggleUsuario(id, celular, correo, el) {
                const chk = el.querySelector('.u-check');
                if (rUsuarios.has(id)) {
                    rUsuarios.delete(id);
                    el.classList.remove('selected');
                    if (chk) chk.textContent = '';
                } else {
                    // Extraer nombre del DOM
                    const nombre = el.querySelector('div[style*="font-weight:600"]')?.textContent?.trim() || '';
                    rUsuarios.set(id, { id, celular, correo, nombre });
                    el.classList.add('selected');
                    if (chk) chk.textContent = '✓';
                }
                // Actualizar resumen
                const resumenEl = document.getElementById('rUsuariosResumen');
                if (rUsuarios.size > 0) {
                    const nombres = [...rUsuarios.values()].map(u => u.nombre).join(', ');
                    resumenEl.textContent = `✓ ${rUsuarios.size} usuario(s) seleccionado(s): ${nombres}`;
                    resumenEl.style.display = 'block';
                } else {
                    resumenEl.style.display = 'none';
                }
            }

            function generarReporte() {
                const desde = document.getElementById('rDesde').value;
                const hasta  = document.getElementById('rHasta').value;

                // Construir params — usando URLSearchParams con arrays
                const params = new URLSearchParams();
                if (desde) params.append('fecha_desde', desde);
                if (hasta)  params.append('fecha_hasta', hasta);

                // Estados: vacío = todos pendientes, si hay selección specific → pasar cada uno
                if (rEstados.size > 0) {
                    rEstados.forEach(e => params.append('estados[]', e));
                }

                // Usuarios: pasar IDs
                if (rUsuarios.size > 0) {
                    rUsuarios.forEach((u, id) => params.append('usuario_ids[]', id));
                }

                let url;
                if (rTipo === 'general') {
                    url = '{{ route("reportes.deuda-general") }}?' + params.toString();
                } else {
                    url = '{{ route("reportes.pdf") }}?' + params.toString();
                }
                window.open(url, '_blank');
                cerrarModalReporte();
            }

            // ══════════════════════════════════════════════════════════════════
            // MODAL REGISTRAR PAGO
            // ══════════════════════════════════════════════════════════════════
            function abrirModalPago(id, importe, moneda, montoAbonado, totalRec, pctRec, tipoRec, estado, cuentaPago, fechaRec) {
                facturaActualId = id;
                facturaImporte  = parseFloat(importe);
                facturaMoneda   = moneda;

                document.getElementById('modalPagoSubtitle').textContent = `Factura #${id} · ${moneda} ${parseFloat(importe).toFixed(2)}`;
                
                document.getElementById('pagoFechaAbono').value = '{{ now()->format("Y-m-d") }}';
                document.getElementById('pagoCuentaPago').value = cuentaPago || '';
                document.getElementById('pagoFechaRecaudacion').value = fechaRec || '';
                document.getElementById('chkValidarDetraccion').checked = false;

                // Para AUTODETRACCION: mostrar recaudación pero NO llenar abono
                if (tipoRec === 'AUTODETRACCION') {
                    // Mostrar monto recaudación y porcentaje actuales
                    document.getElementById('pagoTotalRecaudacion').value = totalRec > 0 ? totalRec : '';
                    document.getElementById('pagoPorcentaje').value = pctRec > 0 ? pctRec : '';
                    // NO llenar abono (debe quedar vacío a menos que el usuario lo ingrese)
                    document.getElementById('pagoMontoAbonado').value = '';
                } else {
                    // Para otros tipos: precarga normal
                    document.getElementById('pagoMontoAbonado').value = montoAbonado > 0 ? montoAbonado : '';
                    document.getElementById('pagoTotalRecaudacion').value = totalRec > 0 ? totalRec : '';
                    document.getElementById('pagoPorcentaje').value = pctRec > 0 ? pctRec : '';
                }

                seleccionarTipoRec(tipoRec || '');

                document.getElementById('validarDetraccionWrap').style.display =
                    (tipoRec === 'DETRACCION' && (estado === 'POR VALIDAR DETRACCION' || estado === 'PENDIENTE')) ? 'block' : 'none';

                recalcularPago();
                document.getElementById('modalPagoOverlay').classList.add('open');
            }

            function cerrarModalPago() {
                document.getElementById('modalPagoOverlay').classList.remove('open');
                document.getElementById('pagoMontoAbonado').value        = '';
                document.getElementById('pagoFechaAbono').value          = '';
                document.getElementById('pagoCuentaPago').value          = '';
                document.getElementById('pagoTotalRecaudacion').value    = '';
                document.getElementById('pagoPorcentaje').value          = '';
                document.getElementById('pagoTipoRecaudacion').value     = '';
                document.getElementById('pagoFechaRecaudacion').value    = '';  // ← NUEVO
                limpiarPreviewPago();
            }

            function showToastFactura(msg, ok = true) {
                const el  = document.getElementById('toastFactura');
                const ico = document.getElementById('toastFacturaIco');
                document.getElementById('toastFacturaTxt').textContent = msg;
                el.className = 'inline-alert ' + (ok ? 'ok' : 'error');
                ico.innerHTML = ok
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>';
                el.classList.add('show');
                setTimeout(() => el.classList.remove('show'), 3500);
            }

            function seleccionarTipoRec(tipo) {
                document.getElementById('pagoTipoRecaudacion').value = tipo;

                // Reset todos los botones
                ['btnTipoNinguna','btnTipoDet','btnTipoAuto','btnTipoRet'].forEach(id => {
                    const el = document.getElementById(id);
                    el.className = 'tipo-rec-card';
                    el.style.borderColor = '';
                    el.style.background  = '';
                    el.style.color       = '';
                });

                const camposRec        = document.getElementById('camposRecaudacion');
                const validarWrap      = document.getElementById('validarDetraccionWrap');
                const montoAbonadoEl   = document.getElementById('pagoMontoAbonado');
                const totalRecEl       = document.getElementById('pagoTotalRecaudacion');
                const pctEl            = document.getElementById('pagoPorcentaje');

                if (tipo === 'DETRACCION') {
                    document.getElementById('btnTipoDet').classList.add('active-det');
                    camposRec.style.display  = 'grid';
                    // Habilitar campos para DETRACCION
                    montoAbonadoEl.disabled = false;
                    totalRecEl.disabled = false;
                    pctEl.disabled = false;

                } else if (tipo === 'AUTODETRACCION') {
                    document.getElementById('btnTipoAuto').classList.add('active-auto');
                    camposRec.style.display  = 'grid';
                    validarWrap.style.display = 'none';
                    // AUTODETRACCION: habilitar campos para editar
                    // Pero el abono NO se calcula automáticamente
                    montoAbonadoEl.disabled = false;
                    totalRecEl.disabled = false;
                    pctEl.disabled = false;

                } else if (tipo === 'RETENCION') {
                    document.getElementById('btnTipoRet').classList.add('active-ret');
                    camposRec.style.display  = 'grid';
                    validarWrap.style.display = 'none';
                    // Habilitar campos para RETENCION
                    montoAbonadoEl.disabled = false;
                    totalRecEl.disabled = false;
                    pctEl.disabled = false;

                } else {
                    // Sin recaudación
                    const btnNin = document.getElementById('btnTipoNinguna');
                    btnNin.style.borderColor = '#1d4ed8';
                    btnNin.style.background  = '#dbeafe';
                    btnNin.style.color       = '#1d4ed8';
                    camposRec.style.display  = 'none';
                    validarWrap.style.display = 'none';
                    totalRecEl.value = '';
                    pctEl.value = '';
                    montoAbonadoEl.value = '';
                    // Habilitar campos (para que sean accesibles si cambia de tipo después)
                    montoAbonadoEl.disabled = false;
                    totalRecEl.disabled = false;
                    pctEl.disabled = false;
                }

                recalcularPago();
            }

            // Para AUTODETRACCION: NO calcular monto_abonado automático
            // El usuario puede ingresar abono manualmente si lo desea
            // La recaudación reduce directamente el monto_pendiente
            function _recalcularAbonoAutodet() {
                // para AUTODETRACCION: NO hacer nada
                // El abono no se calcula implícito, solo la recaudación
                return;
            }

            function calcularRecaudacion() {
                const pct     = parseFloat(document.getElementById('pagoPorcentaje').value) || 0;
                if (pct > 0 && facturaImporte > 0) {
                    document.getElementById('pagoTotalRecaudacion').value = (facturaImporte * pct / 100).toFixed(2);
                }
                // Si es autodetracción, sincronizar monto_abonado
                _recalcularAbonoAutodet();
                recalcularPago();
            }

            function recalcularPago() {
                const tipoRec     = document.getElementById('pagoTipoRecaudacion').value;
                const validarDet  = document.getElementById('chkValidarDetraccion').checked;
                const recaudacion = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const moneda      = facturaMoneda;

                // Para AUTODETRACCION el monto_abonado ya se calcula automáticamente
                const abonado = parseFloat(document.getElementById('pagoMontoAbonado').value) || 0;
                const pendiente = Math.max(0, facturaImporte - abonado - recaudacion);

                document.getElementById('calcImporte').textContent     = `${moneda} ${facturaImporte.toFixed(2)}`;
                document.getElementById('calcAbonado').textContent     = `${moneda} ${abonado.toFixed(2)}`;
                document.getElementById('calcRecaudacion').textContent = `${moneda} ${recaudacion.toFixed(2)}`;
                document.getElementById('calcPendiente').textContent   = `${moneda} ${pendiente.toFixed(2)}`;

                // Preview del estado
                let estadoPreview = '', estadoColor = '';

                if (tipoRec === 'DETRACCION' && !validarDet && abonado === 0) {
                    estadoPreview = 'Estado: POR VALIDAR DETRACCIÓN';
                    estadoColor   = '#fdf4ff';
                } else if (tipoRec === 'AUTODETRACCION') {
                    // Autodetracción: muestra diferencia pendiente, NO calcula abono implícito
                    if (recaudacion > 0) {
                        estadoPreview = 'Estado: DIFERENCIA PENDIENTE — Pendiente después de autodetracción';
                        estadoColor   = '#fce7f3';
                    } else {
                        estadoPreview = 'Estado: PENDIENTE';
                        estadoColor   = '#fef3c7';
                    }
                } else if (abonado === 0 && recaudacion === 0) {
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
                const validarDet        = document.getElementById('chkValidarDetraccion').checked;
                const fechaAbono        = document.getElementById('pagoFechaAbono').value || null;
                const cuentaPago        = document.getElementById('pagoCuentaPago').value || null;
                let fechaRecaudacion    = document.getElementById('pagoFechaRecaudacion').value || null;
                if (validarDet && !fechaRecaudacion) {
                    fechaRecaudacion = new Date().toISOString().split('T')[0];
                }

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
                            cuenta_pago:            cuentaPago,
                            fecha_recaudacion:      fechaRecaudacion,  // ← NUEVO
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
                    document.getElementById('editModalSubtitle').textContent =
                        `Editando: ${f.serie}-${String(f.numero).padStart(8,'0')}`;
                    document.getElementById('editFechaEmision').value    = f.fecha_emision     || '';
                    document.getElementById('editFechaVencimiento').value= f.fecha_vencimiento || '';
                    document.getElementById('editEstado').value          = f.estado            || '';
                    document.getElementById('editGlosa').value           = f.glosa             || '';
                    document.getElementById('editFormaPago').value       = f.forma_pago        || '';
                    document.getElementById('editImporteTotal').value    = f.importe_total     || '';
                    document.getElementById('editMontoIgv').value        = f.monto_igv         || '';
                    document.getElementById('editMontoPendiente').value  = f.monto_pendiente   || '';
                    document.getElementById('editSubtotalGravado').value = f.subtotal_gravado  || '';
                    // Recalcular monto_pendiente si cambia el importe (sin tocar monto_abonado — se gestiona en modal pago)
                    document.getElementById('editImporteTotal').oninput = function() {
                        const imp = parseFloat(this.value) || 0;
                        const abo = parseFloat(f.monto_abonado) || 0;
                        document.getElementById('editMontoPendiente').value = Math.max(0, imp - abo).toFixed(2);
                    };
                });
            }
            function cerrarModalEditar() { document.getElementById('modalEditarOverlay').classList.remove('open'); }
            function guardarFactura(event) {
                event.preventDefault();
                const datos = {
                    fecha_emision:document.getElementById('editFechaEmision').value,
                    fecha_vencimiento:document.getElementById('editFechaVencimiento').value,
                    estado:document.getElementById('editEstado').value,
                    glosa:document.getElementById('editGlosa').value,
                    forma_pago:document.getElementById('editFormaPago').value,
                    importe_total:document.getElementById('editImporteTotal').value,
                    monto_igv:document.getElementById('editMontoIgv').value,
                    subtotal_gravado:document.getElementById('editSubtotalGravado').value,
                };
                fetch(`/facturas/${facturaActualId}`,{method:'PUT',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},body:JSON.stringify(datos)})
                    .then(r=>r.json()).then(data=>{
                        if(data.success){
                            cerrarModalEditar();
                            const facNum = data.factura_num ? ` ${data.factura_num}` : '';
                            showToastFactura(`✓ Factura${facNum} actualizada correctamente.`);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showToastFactura((data.message||'No se pudo guardar'), false);
                        }
                    })
                    .catch(err=>showToastFactura('Error de red: '+err.message, false));
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

            // ── Cierre de modales con click fuera ────────────────────────────
            ['modalPagoOverlay','modalEditarOverlay','modalEditarClienteOverlay','modalReporteOverlay'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', e => { if(e.target === e.currentTarget) e.currentTarget.classList.remove('open'); });
            });
        </script>
    @endpush

@endsection
