@extends('layouts.app')
@section('title', 'Gestión de Facturas')
@section('breadcrumb', 'Gestión de Facturas')

@push('styles')
    <style>
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
        body { background: var(--bg) !important; }

        @keyframes fadeDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes slideUp  { from { opacity:0; transform:translateY(16px);  } to { opacity:1; transform:translateY(0); } }
        @keyframes rowIn    { from { opacity:0; transform:translateX(-8px);  } to { opacity:1; transform:translateX(0); } }
        @keyframes chipPop  { 0% { opacity:0; transform:scale(0.85); } 100% { opacity:1; transform:scale(1); } }

        /* ── RESALTADO ÚLTIMA FACTURA EDITADA ── */
        .fila-last-edited {
            background: linear-gradient(90deg, #fef3c7 0%, #fde68a 40%, #fef3c7 100%) !important;
            border-left: 3px solid #f5c842 !important;
            animation: highlightFade 7s ease-out forwards;
        }
        .fila-masivo-updated {
            background: linear-gradient(90deg, #ecfdf3 0%, #bbf7d0 40%, #ecfdf3 100%) !important;
            border-left: 3px solid #16a34a !important;
            animation: highlightMassivoFade 10s ease-out forwards;
        }
        @keyframes highlightFade {
            0%   { background: linear-gradient(90deg, #fde68a 0%, #fbbf24 40%, #fde68a 100%) !important; }
            60%  { background: linear-gradient(90deg, #fef3c7 0%, #fde68a 40%, #fef3c7 100%) !important; }
            100% { background: transparent !important; border-left-color: transparent !important; }
        }
        @keyframes highlightMassivoFade {
            0%   { background: linear-gradient(90deg, #bbf7d0 0%, #86efac 40%, #bbf7d0 100%) !important; }
            60%  { background: linear-gradient(90deg, #ecfdf3 0%, #bbf7d0 40%, #ecfdf3 100%) !important; }
            100% { background: transparent !important; border-left-color: transparent !important; }
        }

        .page-header { animation:fadeDown .5s ease-out; }

        .filter-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; animation:slideUp .55s ease-out .15s both; }
        .filter-row .search-input-wrap { max-width:280px; border:1.5px solid var(--gold-b); border-radius:10px; padding:8px 12px; background:#fff; display:flex; align-items:center; gap:8px; }
        .filter-row .search-input-wrap svg { color:var(--gold); flex-shrink:0; }
        .filter-row .form-input  { border:none; background:transparent; outline:none; flex:1; font-size:13px; }
        .filter-row .form-select { width:auto; min-width:160px; height:40px; border:1.5px solid var(--gold-b); border-radius:10px; background:#fff; color:var(--text-primary); font-size:13px; cursor:pointer; transition:border-color .15s; }
        .filter-row .form-select:focus { border-color:var(--gold); }

        #facturasBody tr { animation:rowIn .4s ease-out; }
        #facturasBody tr:nth-child(1) { animation-delay:.18s; }
        #facturasBody tr:nth-child(2) { animation-delay:.23s; }
        #facturasBody tr:nth-child(3) { animation-delay:.28s; }
        #facturasBody tr:nth-child(4) { animation-delay:.33s; }
        #facturasBody tr:nth-child(5) { animation-delay:.38s; }

        .actions-cell { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
        .action-btn   { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:none; cursor:pointer; transition:all .15s; color:var(--gold); background:transparent; }
        .action-btn:hover { background:var(--gold-l); transform:scale(1.08); }
        .client-cell  { display:flex; flex-direction:column; gap:2px; }
        .client-name  { font-weight:600; font-size:13.5px; color:var(--text-primary); }
        .client-ruc   { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); background:var(--gold-l); padding:2px 6px; border-radius:4px; display:inline-block; width:fit-content; }
        .amount-main  { font-weight:700; font-family:'DM Mono',monospace; font-size:13px; color:var(--text-primary); }
        .amount-sub   { font-size:11px; color:var(--text-muted); font-family:'DM Mono',monospace; margin-top:2px; }
        .notify-cell  { display:flex; flex-direction:column; gap:4px; }
        .notify-meta  { font-size:11px; color:var(--text-muted); }
        .btn-icon-text { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:600; border:none; cursor:pointer; transition:all .15s; }
        .btn-wa        { background:#d1fae5; color:#059669; }
        .btn-wa:hover  { background:#a7f3d0; transform:translateY(-1px); }
        .btn-mail      { background:#dbeafe; color:#1d4ed8; }
        .btn-mail:hover { background:#bfdbfe; transform:translateY(-1px); }
        .serie-num     { font-family:'DM Mono',monospace; font-weight:700; font-size:13px; color:#1f2937; background:var(--gold-l); padding:3px 8px; border-radius:6px; display:inline-block; border:1px solid var(--gold-b); }
        .tag           { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
        .tag-wa        { background:#dcfce7; color:#16a34a; }
        .tag-mail      { background:#dbeafe; color:#2563eb; }

        .date-range-wrap { display:flex; align-items:center; gap:10px; background:#fff; border:1.5px solid var(--gold-b); border-radius:10px; padding:12px 20px; margin-bottom:20px; flex-wrap:wrap; animation:slideUp .55s ease-out .15s both; }
        .date-range-wrap label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-xd); white-space:nowrap; }
        .date-range-wrap input[type="date"] { height:38px; padding:0 12px; border:1.5px solid var(--gold-b); border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:var(--text-primary); outline:none; transition:border-color .15s; cursor:pointer; }
        .date-range-wrap input[type="date"]:focus { border-color:var(--gold); box-shadow:0 0 0 2px var(--gold-l); }
        .date-range-wrap .sep { color:var(--gold); font-size:14px; font-weight:600; }
        .date-range-wrap .btn-ghost { border-color:var(--gold-b); color:var(--gold); }
        .date-range-wrap .btn-ghost:hover { background:var(--gold-l); border-color:var(--gold); }

        .badge-pendiente        { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .badge-vencido          { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
        .badge-pagada           { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
        .badge-pago_parcial     { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
        .badge-por_validar_det  { background:#fdf4ff; color:#7e22ce; border:1.5px solid #e9d5ff; }
        .badge-diferencia_pend  { background:#fce7f3; color:#9d174d; border:1.5px solid #fbcfe8; }
        .badge-anulada          { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

        .estados-legend { display:flex; gap:10px; flex-wrap:wrap; align-items:center; background:#fff; border:1.5px solid var(--gold-b); border-radius:10px; padding:12px 20px; margin-bottom:16px; font-size:11px; animation:slideUp .55s ease-out both; }
        .estados-legend .legend-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-xd); margin-right:4px; white-space:nowrap; }
        .legend-item { display:flex; align-items:center; gap:6px; }
        .legend-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:20px; }
        .stat-card  { background:#fff; border:1.5px solid var(--gold-b); border-radius:12px; padding:16px; display:flex; align-items:center; gap:12px; transition:all .2s; animation:chipPop .4s ease-out both; }
        .stat-card:nth-child(1) { animation-delay:.1s; }
        .stat-card:nth-child(2) { animation-delay:.17s; }
        .stat-card:nth-child(3) { animation-delay:.24s; }
        .stat-card:nth-child(4) { animation-delay:.31s; }
        .stat-card:nth-child(5) { animation-delay:.38s; }
        .stat-card:hover { border-color:var(--gold); box-shadow:0 4px 12px rgba(245,200,66,0.12); transform:translateY(-2px); }
        .stat-icon  { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .stat-card.blue  .stat-icon { background:#dbeafe; color:#1d4ed8; }
        .stat-card.amber .stat-icon { background:#fef3c7; color:var(--gold); }
        .stat-card.green .stat-icon { background:#d1fae5; color:#059669; }
        .stat-card.red   .stat-icon { background:#fee2e2; color:#dc2626; }
        .stat-card.purple .stat-icon { background:#ede9fe; color:#7c3aed; }
        .stat-label { font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
        .stat-value { font-size:16px; font-weight:700; color:var(--text-primary); font-family:'DM Mono',monospace; }
        .stat-sub   { font-size:10px; color:#9ca3af; margin-top:2px; }

        .monto-pendiente-cell { color:#dc2626; font-weight:700; font-family:'DM Mono',monospace; font-size:12px; }
        .monto-pendiente-zero { color:#059669; font-family:'DM Mono',monospace; font-size:12px; }

        .pago-section       { background:var(--gold-l); border-radius:10px; padding:18px; margin-bottom:16px; border:1px solid var(--gold-b); }
        .pago-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--gold-xd); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .calc-display { background:#fff; border:1.5px solid var(--gold-b); border-radius:8px; padding:14px; margin-top:12px; font-size:13px; }
        .calc-row     { display:flex; justify-content:space-between; align-items:center; padding:6px 0; color:var(--text-primary); }
        .calc-row.total { border-top:2px solid var(--gold); margin-top:8px; padding-top:10px; font-weight:800; font-size:14px; }
        .calc-row.pending { color:#dc2626; }
        .calc-row.paid    { color:#059669; }

        .tipo-rec-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin-bottom:10px; }
        .tipo-rec-card { border:2px solid var(--gold-b); border-radius:8px; padding:10px 8px; text-align:center; cursor:pointer; transition:all .15s; background:#fff; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-muted); }
        .tipo-rec-card:hover { border-color:var(--gold); background:var(--gold-l); }
        .tipo-rec-card.active-det  { border-color:var(--gold); background:var(--gold-l); color:var(--gold-xd); }
        .tipo-rec-card.active-auto { border-color:#059669; background:#d1fae5; color:#065f46; }
        .tipo-rec-card.active-ret  { border-color:#7c3aed; background:#ede9fe; color:#5b21b6; }

        .btn-generar-reporte { background:var(--gold); color:#000; border:none; font-weight:700; }
        .btn-generar-reporte:hover { background:var(--gold-h); }

        #modalPagoOverlay .modal-body { min-height:0; max-height:calc(90vh - 160px); overflow-y:auto; }

        .reporte-tipo-grid  { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px; }
        .reporte-tipo-card  { position:relative; border:2px solid var(--gold-b); border-radius:10px; padding:18px 14px 14px; cursor:pointer; transition:all .18s; background:#fff; }
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
        .chip-pagada.active    { border-color:#059669 !important; background:#d1fae5 !important; color:#065f46 !important; }
        .chip-det.active       { border-color:#7c3aed !important; background:#fdf4ff !important; color:#6b21a8 !important; }
        .chip-todos.active     { border-color:#059669 !important; background:#d1fae5 !important; color:#065f46 !important; }

        .usuario-option   { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1.5px solid var(--gold-b); border-radius:8px; cursor:pointer; transition:all .15s; margin-bottom:8px; background:#fff; }
        .usuario-option:hover { border-color:var(--gold); background:var(--gold-l); }
        .usuario-option.selected { border-color:var(--gold); background:var(--gold-l); }
        .usuario-avatar-sm { width:32px; height:32px; border-radius:50%; background:var(--gold); color:#000; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; }
        .u-check { width:20px; height:20px; border-radius:5px; border:2px solid var(--gold-b); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; flex-shrink:0; transition:all .15s; }
        .usuario-option.selected .u-check { border-color:var(--gold); background:var(--gold); color:#000; }
        .btn-pdf-filtros { background:var(--gold); color:#000; border:none; font-weight:700; }
        .btn-pdf-filtros:hover { background:var(--gold-h); }

        .modal-header { background:linear-gradient(135deg, var(--gold) 0%, var(--gold-h) 100%); border-top:3px solid var(--gold-xd); }
        .modal-header h2 { color:#000; font-weight:700; }
        .modal-header p  { color:rgba(0,0,0,.7); }
        .modal-header button { color:#000; opacity:.7; }
        .modal-header button:hover { opacity:1; }

        #validarDetraccionWrap { display:none; margin-bottom:12px; padding:10px 14px; background:var(--gold-l); border-radius:8px; border:1px solid var(--gold-b); color:var(--gold-xd); }
        #validarDetraccionWrap label { color:var(--gold-xd); font-weight:600; }
        #validarDetraccionWrap input[type="checkbox"] { accent-color:var(--gold); }

        /* Dropdown Validar Recaudación */
        .recaudacion-dropdown { position:relative; display:inline-flex; }
        .recaudacion-dropdown-menu {
            display:none; position:absolute; top:calc(100% + 6px); left:0;
            background:#fff; border:1.5px solid #e2e8f0; border-radius:10px;
            box-shadow:0 8px 24px rgba(0,0,0,.12); min-width:200px; z-index:200;
            overflow:hidden;
        }
        .recaudacion-dropdown-menu.open { display:block; }
        .recaudacion-dropdown-menu a {
            display:flex; align-items:center; gap:10px;
            padding:11px 16px; font-size:13px; font-weight:600; text-decoration:none;
            color:var(--text-primary); transition:background .12s;
            border-bottom:1px solid #f1f5f9;
        }
        .recaudacion-dropdown-menu a:last-child { border-bottom:none; }
        .recaudacion-dropdown-menu a:hover { background:#f8fafc; }

        #dropZonePago { border:2px dashed var(--gold-b); border-radius:10px; padding:24px; text-align:center; cursor:pointer; transition:all .2s; background:#fff; }
        #dropZonePago:hover { border-color:var(--gold); background:var(--gold-l); }
        #dropZonePago svg { color:var(--gold); }

        .inline-alert { position:fixed; bottom:24px; right:24px; z-index:9999; padding:14px 20px; border-radius:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,.15); transform:translateY(80px); opacity:0; transition:all .3s cubic-bezier(.16,1,.3,1); max-width:400px; }
        .inline-alert.show  { transform:translateY(0); opacity:1; }
        .inline-alert.ok    { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
        .inline-alert.error { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }

        .tipo-vista-switch {
            display: inline-flex;
            gap: 8px;
            margin-bottom: 14px;
            background: #fff;
            border: 1.5px solid var(--gold-b);
            border-radius: 10px;
            padding: 6px;
        }
        .tipo-vista-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1.5px solid transparent;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--gold-xd);
            text-decoration: none;
            background: transparent;
            transition: all .15s;
        }
        .tipo-vista-btn:hover {
            background: var(--gold-l);
            border-color: var(--gold-b);
        }
        .tipo-vista-btn.active {
            background: var(--gold);
            border-color: var(--gold-h);
            color: #000;
        }

        .masivo-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .masivo-table th,
        .masivo-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f3e8c1;
            font-size: 12px;
        }
        .masivo-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--gold-xd);
            background: #fffaf0;
        }
        .masivo-total-box {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }
        .masivo-kpi {
            background: #fff;
            border: 1.5px solid var(--gold-b);
            border-radius: 8px;
            padding: 10px;
        }
        .masivo-kpi .lbl { font-size: 10px; color: var(--text-muted); text-transform: uppercase; }
        .masivo-kpi .val { font-family: 'DM Mono', monospace; font-size: 14px; font-weight: 700; margin-top: 4px; }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Gestión de Facturas</h1>
            <p class="page-desc">Control de facturas, pagos y notificaciones a clientes.</p>
        </div>
        <div class="page-actions">
            {{-- Importar Facturas (Nubefact, detección automática) --}}
            <a href="{{ route('facturas.importar') }}" class="btn btn-outline" style="border-color:var(--gold); color:var(--gold);">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar Facturas
            </a>

            {{-- Validar Recaudación (dropdown: Detracciones / Retención) --}}
            <div class="recaudacion-dropdown" id="recaudacionDropdown">
                <button type="button"
                        onclick="toggleRecaudacionMenu()"
                        class="btn btn-outline"
                        style="border-color:#0ea5e9;color:#0ea5e9;display:inline-flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Validar Recaudación
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="recaudacion-dropdown-menu" id="recaudacionMenu">
                    <a href="{{ route('detracciones.index') }}" style="color:#d97706;">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Detracciones
                    </a>
                    <a href="{{ route('facturas.importar.retenciones') }}" style="color:#7c3aed;">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Retención
                    </a>
                </div>
            </div>
            <button type="button" class="btn-pdf-filtros" onclick="generarPDFFiltros()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF con Filtros
            </button>
            <button type="button" class="btn-generar-reporte" onclick="abrirModalReporte()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Generar Reporte
            </button>
            <button type="button" class="btn-generar-reporte" onclick="abrirModalPagoMasivo()" style="padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Pago Masivo
            </button>
        </div>
    </div>

    <div class="tipo-vista-switch">
        <a href="{{ route('facturas.pj', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
           class="tipo-vista-btn {{ $tipoClienteVista === 'PERSONA JURIDICA' ? 'active' : '' }}">
            Personas Jurídicas
        </a>
        <a href="{{ route('facturas.pn', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
           class="tipo-vista-btn {{ $tipoClienteVista === 'PERSONA NATURAL' ? 'active' : '' }}">
            Personas Naturales
        </a>
    </div>

    {{-- ── BOTÓN LEYENDA ── --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:10px;">
        <button type="button" onclick="document.getElementById('modalLeyenda').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;
                       font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid #fce8a8;
                       background:#fffbeb;color:#92400e;transition:all .15s;">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Ver Leyenda de Estados
        </button>
    </div>

    {{-- ── BANNER IMPORTACIÓN EXITOSA ── --}}
    @if(session('resumen_importacion'))
        @php $ri = session('resumen_importacion'); @endphp
        <div style="display:flex;align-items:center;gap:14px;background:#d1fae5;border:1.5px solid #6ee7b7;border-radius:12px;padding:14px 20px;margin-bottom:16px;animation:slideUp .4s ease-out;">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5" style="flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            <div style="flex:1;">
                <strong style="color:#065f46;font-size:13px;">
                    ✓ Importación completada — {{ $ri['insertadas'] }} factura(s) insertada(s)
                    @if(($ri['duplicadas'] ?? 0) > 0)
                        , {{ $ri['duplicadas'] }} duplicadas omitidas
                    @endif
                </strong>

                <div style="font-size:11px;color:#047857;margin-top:2px;">
                    Mostrando facturas del período importado · Tipo de recaudación:
                    <strong>{{ $ri['tipo_recaudacion'] }}</strong>

                    @if(!empty($ri['errores']))
                        — {{ count($ri['errores']) }} fila(s) con error
                    @endif
                </div>
            </div>

            <span style="background:#059669;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;white-space:nowrap;">
            {{ $ri['tipo_recaudacion'] }}
        </span>
        </div>
    @endif

    {{-- ── STATS (5 cards) ── --}}
    @php
        $total              = $facturasParaTotales->sum('importe_total');
        $pendiente          = $facturasParaTotales->whereIn('estado',['PENDIENTE','VENCIDO','DIFERENCIA PENDIENTE'])->sum('monto_pendiente');
        $pagada             = $facturasParaTotales->where('estado','PAGADA')->sum('importe_total');
        $recaudacionTotal   = $facturasParaTotales->sum('monto_recaudacion') ?? 0;
        // Recaudación depositada = la que ya tiene fecha_recaudacion confirmada
        $recaudacionPagada  = $facturasParaTotales->filter(fn($f) => !empty($f->fecha_recaudacion))->sum('monto_recaudacion') ?? 0;
        $totalPendienteReal = $pendiente;
        // Separados por moneda
        $pendientePEN = $facturasParaTotales->where('moneda','PEN')->whereIn('estado',['PENDIENTE','VENCIDO','DIFERENCIA PENDIENTE'])->sum('monto_pendiente');
        $pendienteUSD = $facturasParaTotales->where('moneda','USD')->whereIn('estado',['PENDIENTE','VENCIDO','DIFERENCIA PENDIENTE'])->sum('monto_pendiente');
    @endphp
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            <div><div class="stat-label">Total Facturado</div><div class="stat-value">S/ {{ number_format($total,2) }}</div></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div>
                <div class="stat-label">Saldo Pendiente</div>
                <div class="stat-value">S/ {{ number_format($pendientePEN,2) }}</div>
                @if($pendienteUSD > 0)
                    <div class="stat-sub">USD {{ number_format($pendienteUSD,2) }}</div>
                @endif
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
            <div><div class="stat-label">Cobrado</div><div class="stat-value">S/ {{ number_format($pagada,2) }}</div></div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
            <div><div class="stat-label">Monto de Recaudación</div><div class="stat-value">S/ {{ number_format($recaudacionTotal,2) }}</div></div>
        </div>
        <div class="stat-card purple" style="border-left-color:#7c3aed;">
            <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <div class="stat-label" style="color:#7c3aed;">Recaud. Depositada</div>
                <div class="stat-value" style="color:#7c3aed;">S/ {{ number_format($recaudacionPagada,2) }}</div>
                @php $recaudPendReg = $recaudacionTotal - $recaudacionPagada; @endphp
                @if($recaudPendReg > 0)
                    <div class="stat-sub">Sin confirmar: S/ {{ number_format($recaudPendReg,2) }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── FILTRO FECHAS ── --}}
    <form method="GET" action="{{ route($facturasRoute ?? 'facturas.index') }}" id="frmFiltros">
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
                        $badgeClass       = $badgeMap[$estado] ?? 'badge-pendiente';
                        $montoRecaudacion = $factura->monto_recaudacion ?? 0;
                        $porcentaje       = $factura->porcentaje_recaudacion ?? 0;
                        $tipoRecaudacion  = $factura->tipo_recaudacion;
                        $montoAbonado     = $factura->monto_abonado ?? 0;
                        $montoPendiente   = $factura->monto_pendiente ?? $factura->importe_total;
                        $puedeNotificarDeuda = in_array($estado, ['PENDIENTE','VENCIDO','POR VALIDAR DETRACCION','DIFERENCIA PENDIENTE']);
                        $ultimaNotifWa     = $factura->ultima_notif_wa ?? null;
                        $ultimaNotifCorreo = $factura->ultima_notif_correo ?? null;

                        $esAnuladoHuerfano = ($factura->estado === 'ANULADO')
                            || in_array((int) $factura->id_factura, $orphanFacturaIds);

                        $creditoInfo     = DB::table('credito')->where('id_factura', $factura->id_factura)->first();
                        $creditoAsociado = DB::table('credito')
                            ->where('serie_doc_modificado', $factura->serie)
                            ->where('numero_doc_modificado', $factura->numero)
                            ->first();

                        // Resaltado de última factura editada (desde session flash)
                        $isLastEdited = session('last_edited_factura_id') == $factura->id_factura;
                    @endphp
                    <tr data-cliente="{{ $factura->id_cliente }}"
                        data-estado="{{ $estado }}"
                        data-moneda="{{ $factura->moneda }}"
                        data-recaudacion="{{ $tipoRecaudacion ?: 'SIN' }}"
                        data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '').($factura->usuario_nombre ?? '')) }}"
                        data-id="{{ $factura->id_factura }}"
                        @if($isLastEdited) class="fila-last-edited" @endif
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
                                <div style="font-size:10px;color:#92400e;font-weight:600;">{{ $tipoRecaudacion ?? '' }}</div>
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

                        <td style="text-align:right;">
                            @if($estado === 'PAGADA')
                                <span class="monto-pendiente-zero">✓ Cancelado</span>
                            @elseif($montoPendiente > 0)
                                <div class="monto-pendiente-cell">{{ $factura->moneda }} {{ number_format($montoPendiente,2) }}</div>
                            @else
                                <span class="monto-pendiente-zero">—</span>
                            @endif
                        </td>

                        <td style="text-align:left;font-size:12px;">
                            <span style="color:var(--text-muted);">—</span>
                        </td>

                        <td style="text-align:right;">
                            @if($montoAbonado > 0)
                                <button type="button"
                                    onclick="abrirModalVerPagos({{ $factura->id_factura }}, '{{ $factura->moneda }}')"
                                    style="background:none;border:none;padding:0;cursor:pointer;text-align:right;width:100%;"
                                    title="Ver detalle de pagos">
                                    <div style="font-weight:700;font-family:'DM Mono',monospace;font-size:12px;color:#059669;text-decoration:underline dotted #059669;">
                                        {{ $factura->moneda }} {{ number_format($montoAbonado,2) }}
                                    </div>
                                </button>

                            @if($creditoInfo)
                                <div style="font-size:10px;color:#7c3aed;font-weight:600;margin-top:3px;">
                                    NC: {{ $factura->serie }}-{{ str_pad($factura->numero,8,'0',STR_PAD_LEFT) }}
                                    → {{ $creditoInfo->serie_doc_modificado }}-{{ str_pad($creditoInfo->numero_doc_modificado,8,'0',STR_PAD_LEFT) }}
                                </div>
                            @elseif($creditoAsociado)
                                <div style="font-size:10px;color:#7c3aed;font-weight:600;margin-top:3px;">
                                    NC: {{ $creditoAsociado->id_factura ? DB::table('factura')->where('id_factura',$creditoAsociado->id_factura)->value('serie') : '—' }}
                                    → {{ $factura->serie }}-{{ str_pad($factura->numero,8,'0',STR_PAD_LEFT) }}
                                </div>
                            @endif
                            @endif
                        </td>

                        <td><span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$estado) }}</span></td>

                        <td>
                            @if($factura->usuario_nombre)
                                <div style="font-size:12px;font-weight:600;color:var(--text-primary);">
                                    {{ $factura->usuario_nombre }} {{ $factura->usuario_apellido }}
                                </div>
                            @else
                                <span style="font-size:11px;color:var(--text-muted);">—</span>
                            @endif
                        </td>

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
                                <button type="button" onclick="abrirModalEditar('{{ $factura->id_factura }}')" class="action-btn" title="Editar datos factura" style="color:#7c3aed;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <button type="button"
                                        data-factura-id="{{ (int) $factura->id_factura }}"
                                        data-importe="{{ (float) $factura->importe_total }}"
                                        data-moneda="{{ e((string) $factura->moneda) }}"
                                        data-monto-abonado="{{ (float) $montoAbonado }}"
                                        data-monto-recaudacion="{{ (float) $montoRecaudacion }}"
                                        data-porcentaje="{{ (float) $porcentaje }}"
                                        data-tipo-rec="{{ e((string) $tipoRecaudacion) }}"
                                        data-estado="{{ e((string) $estado) }}"
                                        data-fecha-recaudacion="{{ e((string) ($factura->fecha_recaudacion ?? '')) }}"
                                        onclick="abrirModalPagoDesdeBtn(this)"
                                        class="action-btn"
                                        title="{{ $estado === 'PAGADA' ? 'Ver/Actualizar pago' : 'Registrar pago' }}"
                                        style="color:{{ $montoAbonado > 0 ? '#1d4ed8' : '#d97706' }};">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </button>

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
                    <tr><td colspan="12"><div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);">Sin facturas en el período seleccionado</p>
                                <p style="font-size:13px;margin-top:4px;">Cambia el rango de fechas o importa facturas.</p>
                            </div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ HISTORIAL DE IMPORTACIONES ═══════════ --}}
    @if(isset($sincronizaciones) && $sincronizaciones->count() > 0)
    <div class="card" style="margin-top:20px;" id="seccionHistorialImport">
        <div class="card-header" style="cursor:pointer;user-select:none;" onclick="toggleHistorialImport()">
            <div>
                <div class="card-title" style="display:flex;align-items:center;gap:8px;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Historial de Importaciones
                    <span style="background:#e0e7ff;color:#3730a3;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">{{ $sincronizaciones->count() }}</span>
                </div>
                <div class="card-desc">Importaciones desde Nubefact — click para expandir/colapsar</div>
            </div>
            <svg id="historialChevron" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-left:auto;transition:transform .25s;transform:rotate(-90deg)"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </div>

        <div id="historialImportBody" style="display:none;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:10px 14px;text-align:left;font-weight:600;color:#475569;">#</th>
                            <th style="padding:10px 14px;text-align:left;font-weight:600;color:#475569;">Archivo</th>
                            <th style="padding:10px 14px;text-align:left;font-weight:600;color:#475569;">Fecha</th>
                            <th style="padding:10px 14px;text-align:center;font-weight:600;color:#475569;">Facturas</th>
                            <th style="padding:10px 14px;text-align:center;font-weight:600;color:#475569;">Estado</th>
                            <th style="padding:10px 14px;text-align:center;font-weight:600;color:#475569;">Visibilidad</th>
                            <th style="padding:10px 14px;text-align:center;font-weight:600;color:#475569;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($sincronizaciones as $sinc)
                        <tr id="sincRow{{ $sinc->id_sincronizacion }}" style="border-bottom:1px solid #f1f5f9;{{ !$sinc->activo ? 'opacity:.6;background:#fafafa;' : '' }}">
                            <td style="padding:10px 14px;color:#94a3b8;font-size:12px;">{{ $sinc->id_sincronizacion }}</td>
                            <td style="padding:10px 14px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $sinc->nombre_archivo }}">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2" style="margin-right:4px;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $sinc->nombre_archivo }}
                            </td>
                            <td style="padding:10px 14px;color:#64748b;white-space:nowrap;">
                                {{ $sinc->fecha_inicio ? \Carbon\Carbon::parse($sinc->fecha_inicio)->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td style="padding:10px 14px;text-align:center;">
                                <span style="background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:700;padding:2px 10px;border-radius:20px;">{{ $sinc->total_registros_procesados ?? 0 }}</span>
                            </td>
                            <td style="padding:10px 14px;text-align:center;">
                                @php
                                    $estadoColor = match($sinc->estado ?? '') {
                                        'COMPLETADO'  => ['#d1fae5','#065f46'],
                                        'CON_ERRORES' => ['#fef3c7','#92400e'],
                                        'EN_PROCESO'  => ['#dbeafe','#1e40af'],
                                        default       => ['#f1f5f9','#64748b'],
                                    };
                                @endphp
                                <span style="background:{{ $estadoColor[0] }};color:{{ $estadoColor[1] }};font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;">{{ $sinc->estado ?? 'N/D' }}</span>
                            </td>
                            <td style="padding:10px 14px;text-align:center;">
                                @if($sinc->activo)
                                    <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;">ACTIVO</span>
                                @else
                                    <span style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;">INACTIVO</span>
                                @endif
                            </td>
                            <td style="padding:10px 14px;text-align:center;white-space:nowrap;">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="verFacturasSinc({{ $sinc->id_sincronizacion }})" title="Ver facturas">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Ver
                                </button>
                                @if($sinc->activo)
                                    <button type="button" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;" onclick="desactivarSinc({{ $sinc->id_sincronizacion }})" title="Desactivar importación">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Desactivar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm" style="background:#d1fae5;color:#065f46;border:none;" onclick="activarSinc({{ $sinc->id_sincronizacion }})" title="Reactivar importación">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Reactivar
                                    </button>
                                @endif
                            </td>
                        </tr>
                        {{-- Fila expandible con facturas del lote --}}
                        <tr id="sincDetalle{{ $sinc->id_sincronizacion }}" style="display:none;">
                            <td colspan="7" style="padding:0 14px 16px 32px;background:#f8fafc;">
                                <div id="sincDetalleContent{{ $sinc->id_sincronizacion }}" style="font-size:12px;color:#64748b;">Cargando...</div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════ MODAL DETALLE IMPORTACIÓN ═══════════ --}}
    <div class="modal-overlay" id="modalSincOverlay" onclick="if(event.target===this)cerrarModalSinc()">
        <div class="modal" style="max-width:860px;width:min(860px,96vw);max-height:88vh;display:flex;flex-direction:column;">
            <div class="modal-header">
                <h2 id="modalSincTitulo">Facturas de importación</h2>
                <p id="modalSincDesc">—</p>
                <button onclick="cerrarModalSinc()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#000;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="overflow-y:auto;flex:1;padding:0 24px 24px;">
                <div id="modalSincBody">Cargando...</div>
            </div>
        </div>
    </div>

    {{-- ═══════════ TOAST ═══════════ --}}
    <div class="inline-alert" id="toastFactura">
        <svg id="toastFacturaIco" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"></svg>
        <span id="toastFacturaTxt"></span>
    </div>

    {{-- ═══════════ MODAL PAGO MASIVO ═══════════ --}}
    <div class="modal-overlay" id="modalPagoMasivoOverlay">
        <div class="modal" style="max-width:900px;width:min(900px,96vw);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header">
                <h2>Registrar Pago Masivo</h2>
                <p>Distribuye una sola transferencia en múltiples facturas del mismo cliente</p>
                <button onclick="cerrarModalPagoMasivo()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#000;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formPagoMasivo" onsubmit="guardarPagoMasivo(event)" style="display:flex;flex-direction:column;min-height:0;flex:1;">
                @csrf
                <div class="modal-body" style="padding:24px;overflow-y:auto;min-height:0;flex:1;">
                    <div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label">Cliente</label>
                            <select id="pmCliente" class="form-input" onchange="cargarFacturasPagoMasivo()" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id_cliente }}">{{ $c->razon_social }} ({{ $c->ruc }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Monto Transferencia</label>
                            <input type="number" id="pmMontoTotal" class="form-input" step="0.01" min="0.01" placeholder="0.00" oninput="recalcularPagoMasivo()" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha Abono</label>
                            <input type="date" id="pmFechaAbono" class="form-input" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label class="form-label">Cuenta de Pago</label>
                            <input type="text" id="pmCuentaPago" class="form-input" placeholder="Ej: BCP / BBVA / Interbank / Yape">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Comprobante (opcional)</label>
                            <input type="file" id="pmComprobante" class="form-input" accept="image/*,application/pdf">
                        </div>
                    </div>

                    <div style="margin-top:14px;border:1.5px solid var(--gold-b);border-radius:10px;padding:10px;background:#fff;max-height:320px;overflow:auto;">
                        <table class="masivo-table">
                            <thead>
                            <tr>
                                <th style="width:52px;">Sel.</th>
                                <th>Factura</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Pendiente</th>
                                <th style="width:170px;">Monto a aplicar</th>
                            </tr>
                            </thead>
                            <tbody id="pmFacturasBody">
                            <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">Selecciona un cliente para cargar facturas pendientes</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="masivo-total-box">
                        <div class="masivo-kpi"><div class="lbl">Transferencia</div><div class="val" id="pmKpiTransfer">S/ 0.00</div></div>
                        <div class="masivo-kpi"><div class="lbl">Asignado</div><div class="val" id="pmKpiAsignado">S/ 0.00</div></div>
                        <div class="masivo-kpi"><div class="lbl">Diferencia</div><div class="val" id="pmKpiDiferencia">S/ 0.00</div></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f3e8c1;background:#fff;position:sticky;bottom:0;">
                    <button type="button" onclick="cerrarModalPagoMasivo()" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarPagoMasivo">Guardar Pago Masivo</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ MODAL RESUMEN PAGO MASIVO ═══════════ --}}
    <div class="modal-overlay" id="modalPagoMasivoResumenOverlay">
        <div class="modal" style="max-width:980px;width:min(980px,96vw);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header">
                <h2>Resumen de Pago Masivo</h2>
                <p>Facturas actualizadas en esta operación</p>
                <button onclick="cerrarResumenPagoMasivo()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#000;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="padding:20px;overflow-y:auto;min-height:0;flex:1;">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
                    <div class="masivo-kpi"><div class="lbl">Facturas actualizadas</div><div class="val" id="pmrKpiFacturas">0</div></div>
                    <div class="masivo-kpi"><div class="lbl">Monto aplicado</div><div class="val" id="pmrKpiAplicado">S/ 0.00</div></div>
                    <div class="masivo-kpi"><div class="lbl">Saldo restante</div><div class="val" id="pmrKpiPendiente">S/ 0.00</div></div>
                </div>

                <div style="border:1.5px solid var(--gold-b);border-radius:10px;background:#fff;overflow:auto;max-height:52vh;">
                    <table class="masivo-table">
                        <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Estado</th>
                            <th>Estado Nuevo</th>
                            <th style="text-align:right;">Aplicado</th>
                            <th style="text-align:right;">Pendiente Antes</th>
                            <th style="text-align:right;">Pendiente Nuevo</th>
                        </tr>
                        </thead>
                        <tbody id="pmResumenBody">
                        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:16px;">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f3e8c1;background:#fff;">
                <button type="button" onclick="cerrarResumenPagoMasivo()" class="btn btn-ghost">Cerrar</button>
                <button type="button" onclick="cerrarResumenPagoMasivo(true)" class="btn btn-primary">Cerrar y Recargar</button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODAL VER PAGOS (solo lectura) ═══════════ --}}
    <div class="modal-overlay" id="modalVerPagosOverlay">
        <div class="modal" style="max-width:600px;width:min(600px,96vw);max-height:80vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#059669,#047857);">
                <h2>Historial de Pagos</h2>
                <p id="modalVerPagosSubtitle" style="font-size:13px;opacity:.85;"></p>
                <button onclick="cerrarModalVerPagos()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;line-height:1;">×</button>
            </div>
            <div class="modal-body" style="padding:20px 24px;overflow-y:auto;flex:1;min-height:0;">
                <div id="verPagosLoading" style="display:none;text-align:center;color:#6b7280;padding:20px;">Cargando...</div>
                <div id="verPagosVacio" style="display:none;text-align:center;color:#9ca3af;padding:24px;">
                    No hay pagos registrados para esta factura.
                </div>
                <table id="verPagosTable" style="display:none;width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="background:#f0fdf4;border-bottom:2px solid #bbf7d0;">
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">#</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">Fecha</th>
                            <th style="padding:8px 10px;text-align:right;font-weight:700;color:#065f46;">Monto</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">Banco Origen</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">Cuenta Destino</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">N° Operación</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">Forma</th>
                            <th style="padding:8px 10px;text-align:left;font-weight:700;color:#065f46;">Observación</th>
                            <th style="padding:8px 10px;text-align:center;font-weight:700;color:#065f46;">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody id="verPagosTbody"></tbody>
                    <tfoot>
                        <tr style="background:#f0fdf4;border-top:2px solid #bbf7d0;font-weight:700;">
                            <td colspan="2" style="padding:8px 10px;color:#065f46;">TOTAL ABONADO</td>
                            <td id="verPagosTotal" style="padding:8px 10px;text-align:right;font-family:'DM Mono',monospace;color:#059669;"></td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModalVerPagos()" class="btn btn-ghost">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODAL EDITAR ABONO ═══════════ --}}
    <div class="modal-overlay" id="modalEditarPagoOverlay">
        <div class="modal" style="max-width:520px;width:min(520px,96vw);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1d4ed8,#1e40af);">
                <h2>Editar Abono</h2>
                <p style="font-size:13px;opacity:.85;">Actualiza los datos del abono registrado</p>
                <button onclick="cerrarModalEditarPago()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;line-height:1;">×</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <input type="hidden" id="editPagoId">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group">
                        <label class="form-label">Monto *</label>
                        <input type="number" id="editPagoMonto" step="0.01" min="0.01" class="form-input" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha *</label>
                        <input type="date" id="editPagoFecha" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Banco de Origen</label>
                        <input type="text" id="editPagoBanco" class="form-input" placeholder="Ej: BCP, Interbank...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cuenta Destino</label>
                        <input type="text" id="editPagoCuenta" class="form-input" placeholder="N° cuenta">
                    </div>
                    <div class="form-group">
                        <label class="form-label">N° Operación</label>
                        <input type="text" id="editPagoNumOp" class="form-input" placeholder="Número de operación">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pago</label>
                        <input type="text" id="editPagoForma" class="form-input" placeholder="Transferencia, depósito...">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Observación</label>
                        <input type="text" id="editPagoObs" class="form-input" placeholder="Observación o referencia">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="gap:10px;">
                <button type="button" onclick="cerrarModalEditarPago()" class="btn btn-ghost">Cancelar</button>
                <button type="button" id="btnGuardarEditarPago" onclick="guardarEditarPago()" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODAL PAGOS (múltiples abonos por factura) ═══════════ --}}
    <div class="modal-overlay" id="modalPagoOverlay">
        <div class="modal" style="max-width:860px;width:min(860px,96vw);max-height:93vh;display:flex;flex-direction:column;overflow:hidden;">
            <div class="modal-header">
                <h2>Registro de Pagos</h2>
                <p id="modalPagoSubtitle" style="font-size:13px;opacity:.85;">Factura · Administra los abonos</p>
                <button onclick="cerrarModalPago()" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;line-height:1;">×</button>
            </div>
            <div class="modal-body" style="padding:20px 24px 24px;overflow-y:auto;flex:1;min-height:0;">

                {{-- ── Barra de progreso ── --}}
                <div style="background:#fff;border:1.5px solid var(--gold-b);border-radius:12px;padding:16px 18px;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;">
                        <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--gold-xd);">Progreso de cobro</span>
                        <span id="prPctLabel" style="font-size:12px;font-weight:800;color:#059669;">0%</span>
                    </div>
                    <div style="height:12px;border-radius:6px;background:#f1f5f9;overflow:hidden;margin-bottom:14px;position:relative;">
                        <div id="prBarPagado"  style="position:absolute;left:0;top:0;height:100%;background:#059669;transition:width .4s;width:0%;border-radius:6px;"></div>
                        <div id="prBarCola"    style="position:absolute;top:0;height:100%;background:#3b82f6;transition:all .4s;width:0%;left:0%;border-radius:0 6px 6px 0;"></div>
                        <div id="prBarOver"    style="position:absolute;top:0;height:100%;background:#ef4444;transition:all .4s;width:0%;left:0%;border-radius:0 6px 6px 0;"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center;">
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:2px;">Total Factura</div>
                            <div id="prImporte" style="font-family:'DM Mono',monospace;font-weight:700;font-size:13px;">S/ 0.00</div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#059669;margin-bottom:2px;">Ya Pagado</div>
                            <div id="prPagado" style="font-family:'DM Mono',monospace;font-weight:700;font-size:13px;color:#059669;">S/ 0.00</div>
                        </div>
                        <div id="prPendienteBox">
                            <div id="prPendienteLabel" style="font-size:10px;font-weight:700;text-transform:uppercase;color:#dc2626;margin-bottom:2px;">Saldo Pendiente</div>
                            <div id="prPendiente" style="font-family:'DM Mono',monospace;font-weight:700;font-size:15px;color:#dc2626;">S/ 0.00</div>
                        </div>
                    </div>
                    <div id="prRecaudacionRow" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;justify-content:space-between;align-items:center;font-size:11px;color:#92400e;">
                        <span>Recaudación (Det./Ret.)</span>
                        <span id="prRecaudacion" style="font-family:'DM Mono',monospace;font-weight:700;">S/ 0.00</span>
                    </div>
                    <div id="prColaRow" style="display:none;margin-top:8px;padding-top:8px;border-top:1px dashed #bfdbfe;justify-content:space-between;align-items:center;font-size:11px;color:#1d4ed8;">
                        <span style="font-weight:600;">En cola (pendiente guardar)</span>
                        <span id="prCola" style="font-family:'DM Mono',monospace;font-weight:700;">S/ 0.00</span>
                    </div>
                </div>

                {{-- ── Alerta overflow ── --}}
                <div id="alertaOverflow" style="display:none;margin-bottom:14px;padding:10px 14px;background:#fee2e2;border:1.5px solid #fca5a5;border-radius:8px;align-items:center;gap:10px;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2.5" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#dc2626;">Los pagos en cola superan el saldo pendiente</div>
                        <div id="alertaOverflowMsg" style="font-size:11px;color:#b91c1c;margin-top:2px;"></div>
                    </div>
                </div>

                {{-- ── Abonos registrados ── --}}
                <div class="pago-section">
                    <div class="pago-section-title">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Abonos Registrados
                    </div>
                    <div id="listaPagosLoading" style="text-align:center;color:#9ca3af;padding:12px 0;font-size:12px;">Cargando...</div>
                    <div id="listaPagosVacio" style="display:none;text-align:center;color:#9ca3af;padding:12px 0;font-size:12px;">Sin abonos registrados aún</div>
                    <div style="overflow-x:auto;">
                        <table id="listaPagosTable" style="display:none;width:100%;border-collapse:collapse;font-size:12px;">
                            <thead>
                            <tr style="background:var(--gold-l);border-bottom:1.5px solid var(--gold-b);">
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">#</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">Fecha</th>
                                <th style="padding:7px 8px;text-align:right;font-weight:700;color:var(--gold-xd);">Monto</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">Banco Origen</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">Cuenta Destino</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">N° Operación</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">Forma</th>
                                <th style="padding:7px 8px;text-align:left;font-weight:700;color:var(--gold-xd);">Observación</th>
                                <th style="padding:7px 8px;text-align:center;font-weight:700;color:var(--gold-xd);">Comp.</th>
                                <th style="padding:7px 8px;width:64px;"></th>
                            </tr>
                            </thead>
                            <tbody id="listaPagosTbody"></tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Cola de nuevos abonos ── --}}
                <div class="pago-section" style="border-color:#93c5fd;background:#f0f9ff;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div class="pago-section-title" style="margin-bottom:0;color:#1e40af;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Nuevos Abonos
                        </div>
                        <button type="button" onclick="agregarFilaPago()"
                            style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#1d4ed8;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;transition:background .15s;"
                            onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Agregar abono
                        </button>
                    </div>
                    <div id="colaAbonos"></div>
                    <div id="colaVacia" style="text-align:center;padding:14px 0;color:#93c5fd;font-size:12px;font-weight:600;">
                        Pulsa "Agregar abono" para registrar un pago
                    </div>
                </div>

                {{-- ── Recaudación ── --}}
                <div class="pago-section" id="seccionRecaudacion">
                    <div class="pago-section-title">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Recaudación (Detracción / Autodetracción / Retención)
                    </div>
                    <div class="tipo-rec-grid">
                        <div class="tipo-rec-card" id="btnTipoNinguna" onclick="seleccionarTipoRec('')">Sin recaudación</div>
                        <div class="tipo-rec-card" id="btnTipoDet"     onclick="seleccionarTipoRec('DETRACCION')">Detracción</div>
                        <div class="tipo-rec-card" id="btnTipoAuto"    onclick="seleccionarTipoRec('AUTODETRACCION')">Autodetracción</div>
                        <div class="tipo-rec-card" id="btnTipoRet"     onclick="seleccionarTipoRec('RETENCION')">Retención</div>
                    </div>
                    <input type="hidden" id="pagoTipoRecaudacion" value="">

                    <div id="validarDetraccionWrap" style="display:none;margin-bottom:12px;padding:10px 14px;background:#fef3c7;border-radius:8px;border:1px solid #fde68a;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:600;color:#92400e;">
                            <input type="checkbox" id="chkValidarDetraccion" value="1" style="width:16px;height:16px;accent-color:#d97706;">
                            Confirmo que esta factura SÍ aplica detracción
                        </label>
                        <p style="font-size:11px;color:#92400e;margin-top:6px;margin-left:26px;">Al marcar esta opción, se validará la detracción y cambiará el estado de la factura.</p>
                    </div>

                    <div id="camposRecaudacion" style="display:none;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label">Porcentaje (%)</label>
                            <input type="number" id="pagoPorcentaje" step="0.01" min="0" max="100" class="form-input" placeholder="10.00" oninput="calcularRecaudacion()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Monto Recaudación</label>
                            <input type="number" id="pagoTotalRecaudacion" step="0.01" min="0" class="form-input" placeholder="0.00">
                            <span id="recaudUsdNote" style="display:none;font-size:11px;color:#1d4ed8;margin-top:3px;display:block;">
                                Factura en dólares — ingresa el monto directamente en USD.
                            </span>
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="form-label">Fecha de Depósito / Recaudación</label>
                            <input type="date" id="pagoFechaRecaudacion" class="form-input" style="border-color:var(--gold-b);">
                            <span style="font-size:11px;color:var(--text-muted);margin-top:3px;display:block;">Fecha en que se depositó la detracción o retención</span>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="gap:10px;">
                <button type="button" onclick="cerrarModalPago()" class="btn btn-ghost">Cerrar</button>
                <button type="button" id="btnGuardarPago" onclick="guardarPago()" class="btn btn-primary" style="min-width:160px;">
                    <span id="btnGuardarPagoTxt">Guardar pagos</span>
                </button>
            </div>
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

                <div style="margin-bottom:20px;">
                    <label class="form-label" style="margin-bottom:4px;">② Estados a incluir <span style="font-weight:400;color:var(--gold-xd);">(selecciona uno o varios)</span></label>
                    <div class="estado-chip-wrap" style="margin-top:8px;">
                        <span class="estado-chip chip-todos active"   id="rChipTodos"    onclick="toggleEstado('',this)">✦ Todos Pendientes</span>
                        <span class="estado-chip chip-pendiente"      id="rChipPendiente" onclick="toggleEstado('PENDIENTE',this)">Pendiente</span>
                        <span class="estado-chip chip-vencido"        id="rChipVencido"   onclick="toggleEstado('VENCIDO',this)">Vencido</span>
                        <span class="estado-chip chip-pagada"         id="rChipPagada"    onclick="toggleEstado('PAGADA',this)">Pagada</span>
                        <span class="estado-chip chip-det"         id="rChipDet"         onclick="toggleEstado('DIFERENCIA PENDIENTE',this)">Diferencia Pendiente</span>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="form-label" style="margin-bottom:6px;">③ Período <span style="font-weight:400;color:var(--gold-xd);">(opcional)</span></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;">
                        <input type="date" id="rDesde" class="form-input" style="border-color:var(--gold-b);">
                        <input type="date" id="rHasta" class="form-input" style="border-color:var(--gold-b);">
                    </div>
                </div>

                <div>
                    <label class="form-label" style="margin-bottom:4px;">④ Enviar a <span style="font-weight:400;color:var(--gold-xd);">(opcional)</span></label>
                    <p style="font-size:11px;color:var(--gold-xd);margin-bottom:10px;">El PDF se abrirá con botones para enviar a los seleccionados.</p>
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
                    </div>
                    <div id="rUsuariosResumen" style="font-size:12px;color:var(--gold-xd);font-weight:600;margin-top:8px;display:none;"></div>
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
            // ── Dropdown Validar Recaudación ──────────────────────────────────────
            function toggleRecaudacionMenu() {
                var menu = document.getElementById('recaudacionMenu');
                menu.classList.toggle('open');
            }
            document.addEventListener('click', function(e) {
                var dropdown = document.getElementById('recaudacionDropdown');
                if (dropdown && !dropdown.contains(e.target)) {
                    document.getElementById('recaudacionMenu').classList.remove('open');
                }
            });

            let facturaActualId = null;
            let facturaImporte  = 0;
            let facturaMoneda   = 'S/';
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const PM_FETCH_URL = '{{ route("facturas.pago-masivo.facturas-cliente") }}';
            const PM_SAVE_URL = '{{ route("facturas.pago-masivo.procesar") }}';
            const PM_TIPO_CLIENTE = @json($tipoClienteVista);
            const PM_HIGHLIGHT_KEY = 'facturas_pago_masivo_ids';
            let pagoMasivoFacturas = [];

            function guardarIdsPagoMasivo(ids = []) {
                const limpios = (Array.isArray(ids) ? ids : [])
                    .map(v => Number(v))
                    .filter(v => Number.isInteger(v) && v > 0);
                if (!limpios.length) return;
                sessionStorage.setItem(PM_HIGHLIGHT_KEY, JSON.stringify([...new Set(limpios)]));
            }

            function aplicarResaltadoPagoMasivo() {
                const raw = sessionStorage.getItem(PM_HIGHLIGHT_KEY);
                if (!raw) return false;

                let ids = [];
                try {
                    ids = JSON.parse(raw) || [];
                } catch (e) {
                    sessionStorage.removeItem(PM_HIGHLIGHT_KEY);
                    return false;
                }

                if (!Array.isArray(ids) || !ids.length) {
                    sessionStorage.removeItem(PM_HIGHLIGHT_KEY);
                    return false;
                }

                let firstRow = null;
                ids.forEach(id => {
                    const row = document.querySelector(`#facturasBody tr[data-id="${Number(id)}"]`);
                    if (row) {
                        row.classList.add('fila-masivo-updated');
                        if (!firstRow) firstRow = row;
                    }
                });

                sessionStorage.removeItem(PM_HIGHLIGHT_KEY);

                if (firstRow) {
                    setTimeout(() => {
                        firstRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 300);
                    showToastFactura('✓ Facturas de pago masivo resaltadas.');
                }

                return !!firstRow;
            }

            // ── Auto-scroll a la última factura editada ──────────────────────────
            document.addEventListener('DOMContentLoaded', function() {
                const tuvoMasivo = aplicarResaltadoPagoMasivo();
                if (tuvoMasivo) return;
                const highlighted = document.querySelector('.fila-last-edited');
                if (highlighted) {
                    setTimeout(() => {
                        highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 400);
                }
            });

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

            function filtrarTabla() {
                const search      = document.getElementById('searchInput').value.toLowerCase();
                const estado      = document.getElementById('filterEstado').value;
                const moneda      = document.getElementById('filterMoneda').value;
                const empresa     = document.getElementById('filterEmpresa').value;
                const recaudacion = document.getElementById('filterRecaudacion').value;
                let visibles = 0;
                document.querySelectorAll('#facturasBody tr[data-estado]').forEach(row => {
                    const rowRec = row.dataset.recaudacion || 'SIN';
                    const okRec  = !recaudacion || rowRec === recaudacion;
                    const ok = (!search  || row.dataset.search.includes(search))
                        && (!estado  || row.dataset.estado  === estado)
                        && (!moneda  || row.dataset.moneda  === moneda)
                        && (!empresa || row.dataset.cliente === empresa)
                        && okRec;
                    row.style.display = ok ? '' : 'none';
                    if (ok) visibles++;
                });
                const hayFiltro = search || estado || moneda || empresa || recaudacion;
                const cardDesc = document.querySelector('.card-desc');
                if (cardDesc) {
                    cardDesc.textContent = hayFiltro
                        ? visibles + ' factura' + (visibles !== 1 ? 's' : '') + ' (filtrado)'
                        : '{{ $facturas->count() }} facturas en el período seleccionado';
                }
            }

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
                window.open('{{ route("reportes.pdf") }}?' + params.toString(), '_blank');
            }

            // ── Modal Reporte ─────────────────────────────────────────────────
            let rTipo = 'general', rEstados = new Set(), rUsuarios = new Map();

            function abrirModalReporte() {
                rTipo = 'general'; rEstados = new Set(); rUsuarios = new Map();
                document.getElementById('rDesde').value = '';
                document.getElementById('rHasta').value = '';
                selReporteTipo('general');
                document.querySelectorAll('.estado-chip').forEach(c => c.classList.remove('active'));
                document.getElementById('rChipTodos').classList.add('active');
                document.querySelectorAll('#modalReporteOverlay .usuario-option').forEach(el => {
                    el.classList.remove('selected');
                    const chk = el.querySelector('.u-check');
                    if (chk) chk.textContent = '';
                });
                document.getElementById('rUsuariosResumen').style.display = 'none';
                document.getElementById('modalReporteOverlay').classList.add('open');
            }
            function cerrarModalReporte() { document.getElementById('modalReporteOverlay').classList.remove('open'); }

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
                    rEstados.clear();
                    document.querySelectorAll('.estado-chip').forEach(c => c.classList.remove('active'));
                    chipTodos.classList.add('active');
                    return;
                }
                chipTodos.classList.remove('active');
                if (rEstados.has(estado)) { rEstados.delete(estado); el.classList.remove('active'); }
                else { rEstados.add(estado); el.classList.add('active'); }
                if (rEstados.size === 0) chipTodos.classList.add('active');
            }

            function toggleUsuario(id, celular, correo, el) {
                const chk = el.querySelector('.u-check');
                if (rUsuarios.has(id)) {
                    rUsuarios.delete(id); el.classList.remove('selected');
                    if (chk) chk.textContent = '';
                } else {
                    const nombre = el.querySelector('div[style*="font-weight:600"]')?.textContent?.trim() || '';
                    rUsuarios.set(id, { id, celular, correo, nombre });
                    el.classList.add('selected');
                    if (chk) chk.textContent = '✓';
                }
                const resumenEl = document.getElementById('rUsuariosResumen');
                if (rUsuarios.size > 0) {
                    resumenEl.textContent = `✓ ${rUsuarios.size} usuario(s): ${[...rUsuarios.values()].map(u => u.nombre).join(', ')}`;
                    resumenEl.style.display = 'block';
                } else { resumenEl.style.display = 'none'; }
            }

            function generarReporte() {
                const desde = document.getElementById('rDesde').value;
                const hasta  = document.getElementById('rHasta').value;
                const params = new URLSearchParams();
                if (desde) params.append('fecha_desde', desde);
                if (hasta)  params.append('fecha_hasta', hasta);
                if (rEstados.size > 0) rEstados.forEach(e => params.append('estados[]', e));
                if (rUsuarios.size > 0) rUsuarios.forEach((u, id) => params.append('usuario_ids[]', id));
                let url = rTipo === 'general'
                    ? '{{ route("reportes.deuda-general") }}?' + params.toString()
                    : '{{ route("reportes.pdf") }}?' + params.toString();
                window.open(url, '_blank');
                cerrarModalReporte();
            }

            // ── Modal Pago Masivo ───────────────────────────────────────────
            function abrirModalPagoMasivo() {
                document.getElementById('pmCliente').value = '';
                document.getElementById('pmMontoTotal').value = '';
                document.getElementById('pmFechaAbono').value = '{{ now()->format("Y-m-d") }}';
                document.getElementById('pmCuentaPago').value = '';
                document.getElementById('pmComprobante').value = '';
                pagoMasivoFacturas = [];
                renderFacturasPagoMasivo();
                recalcularPagoMasivo();
                document.getElementById('modalPagoMasivoOverlay').classList.add('open');
            }

            function cerrarModalPagoMasivo() {
                document.getElementById('modalPagoMasivoOverlay').classList.remove('open');
            }

            function abrirResumenPagoMasivo(resumen = []) {
                const rows = Array.isArray(resumen) ? resumen : [];
                const tbody = document.getElementById('pmResumenBody');
                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:16px;">No hubo cambios para mostrar</td></tr>';
                    document.getElementById('pmrKpiFacturas').textContent = '0';
                    document.getElementById('pmrKpiAplicado').textContent = 'S/ 0.00';
                    document.getElementById('pmrKpiPendiente').textContent = 'S/ 0.00';
                    document.getElementById('modalPagoMasivoResumenOverlay').classList.add('open');
                    return;
                }

                const totalAplicado = rows.reduce((acc, r) => acc + Number(r.monto_aplicado || 0), 0);
                const totalPendienteNuevo = rows.reduce((acc, r) => acc + Number(r.pendiente_nuevo || 0), 0);

                document.getElementById('pmrKpiFacturas').textContent = String(rows.length);
                document.getElementById('pmrKpiAplicado').textContent = `S/ ${totalAplicado.toFixed(2)}`;
                document.getElementById('pmrKpiPendiente').textContent = `S/ ${totalPendienteNuevo.toFixed(2)}`;

                tbody.innerHTML = rows.map(r => {
                    const montoAplicado = Number(r.monto_aplicado || 0).toFixed(2);
                    const pendienteAntes = Number(r.pendiente_anterior || 0).toFixed(2);
                    const pendienteNuevo = Number(r.pendiente_nuevo || 0).toFixed(2);
                    return `<tr>
                        <td><strong>${r.factura || '-'}</strong></td>
                        <td>${r.estado_anterior || '-'}</td>
                        <td><strong>${r.estado_nuevo || '-'}</strong></td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">S/ ${montoAplicado}</td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">S/ ${pendienteAntes}</td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">S/ ${pendienteNuevo}</td>
                    </tr>`;
                }).join('');

                document.getElementById('modalPagoMasivoResumenOverlay').classList.add('open');
            }

            function cerrarResumenPagoMasivo(recargar = false) {
                document.getElementById('modalPagoMasivoResumenOverlay').classList.remove('open');
                if (recargar) {
                    location.reload();
                }
            }

            async function cargarFacturasPagoMasivo() {
                const idCliente = document.getElementById('pmCliente').value;
                const tbody = document.getElementById('pmFacturasBody');
                if (!idCliente) {
                    pagoMasivoFacturas = [];
                    renderFacturasPagoMasivo();
                    return;
                }

                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">Cargando facturas pendientes...</td></tr>';
                const params = new URLSearchParams({
                    id_cliente: idCliente,
                    fecha_desde: document.getElementById('inputDesde').value || '',
                    fecha_hasta: document.getElementById('inputHasta').value || '',
                });
                if (PM_TIPO_CLIENTE) params.append('tipo_cliente', PM_TIPO_CLIENTE);

                try {
                    const res = await fetch(`${PM_FETCH_URL}?${params.toString()}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'No se pudieron cargar facturas.');

                    pagoMasivoFacturas = (data.facturas || []).map(f => ({
                        id_factura: Number(f.id_factura),
                        serie: f.serie,
                        numero: f.numero,
                        estado: f.estado,
                        moneda: f.moneda || 'S/',
                        pendiente: Number(f.monto_pendiente || 0),
                        selected: false,
                        monto: 0,
                    }));
                    renderFacturasPagoMasivo();
                    recalcularPagoMasivo();
                } catch (e) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#dc2626;padding:16px;">${e.message}</td></tr>`;
                }
            }

            function renderFacturasPagoMasivo() {
                const tbody = document.getElementById('pmFacturasBody');
                if (!pagoMasivoFacturas.length) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">No hay facturas pendientes para este cliente/rango</td></tr>';
                    return;
                }

                tbody.innerHTML = pagoMasivoFacturas.map((f, idx) => {
                    const doc = `${f.serie}-${String(f.numero).padStart(8, '0')}`;
                    const pend = `S/ ${Number(f.pendiente).toFixed(2)}`;
                    return `<tr>
                        <td style="text-align:center;"><input type="checkbox" ${f.selected ? 'checked' : ''} onchange="toggleFacturaMasiva(${idx}, this.checked)"></td>
                        <td><strong>${doc}</strong></td>
                        <td>${f.estado}</td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">${pend}</td>
                        <td><input type="number" min="0" step="0.01" value="${f.monto ? Number(f.monto).toFixed(2) : ''}" ${f.selected ? '' : 'disabled'} class="form-input" oninput="setMontoFacturaMasiva(${idx}, this.value)"></td>
                    </tr>`;
                }).join('');
            }

            function toggleFacturaMasiva(idx, checked) {
                const f = pagoMasivoFacturas[idx];
                if (!f) return;
                f.selected = checked;
                f.monto = checked ? Number(f.pendiente.toFixed(2)) : 0;
                renderFacturasPagoMasivo();
                recalcularPagoMasivo();
            }

            function setMontoFacturaMasiva(idx, value) {
                const f = pagoMasivoFacturas[idx];
                if (!f) return;
                const v = Number(value || 0);
                f.monto = Math.max(0, Math.min(v, f.pendiente));
                recalcularPagoMasivo();
            }

            function recalcularPagoMasivo() {
                const totalTransfer = Number(document.getElementById('pmMontoTotal').value || 0);
                const totalAsignado = pagoMasivoFacturas
                    .filter(f => f.selected)
                    .reduce((acc, f) => acc + Number(f.monto || 0), 0);
                const diff = totalTransfer - totalAsignado;

                document.getElementById('pmKpiTransfer').textContent = `S/ ${totalTransfer.toFixed(2)}`;
                document.getElementById('pmKpiAsignado').textContent = `S/ ${totalAsignado.toFixed(2)}`;
                const diffEl = document.getElementById('pmKpiDiferencia');
                diffEl.textContent = `S/ ${diff.toFixed(2)}`;
                diffEl.style.color = Math.abs(diff) < 0.005 ? '#059669' : '#dc2626';
            }

            async function guardarPagoMasivo(event) {
                event.preventDefault();
                const btn = document.getElementById('btnGuardarPagoMasivo');
                btn.disabled = true;
                btn.textContent = 'Guardando...';

                const idCliente = document.getElementById('pmCliente').value;
                const montoTotal = Number(document.getElementById('pmMontoTotal').value || 0);
                const fechaAbono = document.getElementById('pmFechaAbono').value;
                const cuentaPago = (document.getElementById('pmCuentaPago').value || '').trim();
                const detalles = pagoMasivoFacturas
                    .filter(f => f.selected)
                    .map(f => ({ id_factura: f.id_factura, monto: Number(Number(f.monto || 0).toFixed(2)) }));

                const toCents = n => Math.round((Number(n) || 0) * 100);
                const suma = detalles.reduce((acc, d) => acc + Number(d.monto), 0);
                if (!detalles.length) {
                    alert('Selecciona al menos una factura para el pago masivo.');
                    btn.disabled = false; btn.textContent = 'Guardar Pago Masivo';
                    return;
                }
                if (toCents(suma) !== toCents(montoTotal)) {
                    alert('La suma de facturas seleccionadas debe coincidir con el monto total abonado.');
                    btn.disabled = false; btn.textContent = 'Guardar Pago Masivo';
                    return;
                }

                const formData = new FormData();
                formData.append('_token', CSRF);
                formData.append('id_cliente', idCliente);
                formData.append('monto_total', montoTotal.toFixed(2));
                formData.append('fecha_abono', fechaAbono);
                formData.append('cuenta_pago', cuentaPago);
                formData.append('detalles', JSON.stringify(detalles));
                const comp = document.getElementById('pmComprobante').files[0];
                if (comp) formData.append('comprobante', comp);

                try {
                    const res = await fetch(PM_SAVE_URL, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'No se pudo registrar el pago masivo.');

                    const idsActualizados = Array.isArray(data.resumen)
                        ? data.resumen.map(r => Number(r.id_factura)).filter(n => Number.isInteger(n) && n > 0)
                        : detalles.map(d => Number(d.id_factura)).filter(n => Number.isInteger(n) && n > 0);
                    guardarIdsPagoMasivo(idsActualizados);

                    cerrarModalPagoMasivo();
                    showToastFactura(`✓ ${data.facturas_actualizadas || detalles.length} factura(s) actualizadas por pago masivo.`);
                    abrirResumenPagoMasivo(data.resumen || []);
                } catch (e) {
                    alert('Error: ' + e.message);
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Guardar Pago Masivo';
                }
            }

            // ── Modal Pago (multi-abono) ──────────────────────────────────────
            let pagoListaCargada = [];
            let colaPagos        = [];   // [{idx, monto, fecha, cuenta, numeroOp, bancoOrigen, formaPago, observacion, file}]
            let colaIdx          = 0;

            function abrirModalPagoDesdeBtn(btn) {
                abrirModalPago(
                    parseInt(btn.dataset.facturaId || '0', 10),
                    parseFloat(btn.dataset.importe || '0'),
                    btn.dataset.moneda || '',
                    parseFloat(btn.dataset.montoAbonado || '0'),
                    parseFloat(btn.dataset.montoRecaudacion || '0'),
                    parseFloat(btn.dataset.porcentaje || '0'),
                    btn.dataset.tipoRec || '',
                    btn.dataset.estado || '',
                    btn.dataset.fechaRecaudacion || ''
                );
            }

            function abrirModalPago(id, importe, moneda, montoAbonado, totalRec, pctRec, tipoRec, estado, fechaRec) {
                facturaActualId = id;
                facturaImporte  = parseFloat(importe);
                facturaMoneda   = moneda;
                colaPagos       = [];
                colaIdx         = 0;
                document.getElementById('modalPagoSubtitle').textContent = `Factura #${id} — ${moneda} ${parseFloat(importe).toFixed(2)}`;

                // Recaudación
                document.getElementById('pagoFechaRecaudacion').value = fechaRec || '';
                document.getElementById('chkValidarDetraccion').checked = false;
                document.getElementById('pagoTotalRecaudacion').value  = totalRec > 0 ? totalRec : '';
                document.getElementById('pagoPorcentaje').value        = pctRec   > 0 ? pctRec   : '';
                seleccionarTipoRec(tipoRec || '');
                document.getElementById('validarDetraccionWrap').style.display =
                    (tipoRec === 'DETRACCION' && (estado === 'POR VALIDAR DETRACCION' || estado === 'PENDIENTE')) ? 'block' : 'none';

                renderCola();
                actualizarResumenPago(montoAbonado, totalRec, 0);
                document.getElementById('modalPagoOverlay').classList.add('open');
                cargarListaPagos(id);
            }

            // ── Barra de progreso ──────────────────────────────────────────────
            function actualizarResumenPago(montoAbonado, totalRec, totalCola) {
                const sym    = (facturaMoneda || '').includes('USD') ? 'USD' : 'S/';
                const pagado = Number(montoAbonado);
                const rec    = Number(totalRec);
                const cola   = Number(totalCola);
                const pend   = Math.max(0, facturaImporte - pagado - rec);
                const over   = Math.max(0, pagado + rec + cola - facturaImporte);
                const pct    = facturaImporte > 0 ? Math.min(100, (pagado + rec) / facturaImporte * 100) : 0;
                const pctCola= facturaImporte > 0 ? Math.min(100 - pct, cola / facturaImporte * 100) : 0;

                document.getElementById('prImporte').textContent  = `${sym} ${facturaImporte.toFixed(2)}`;
                document.getElementById('prPagado').textContent   = `${sym} ${pagado.toFixed(2)}`;
                document.getElementById('prPendiente').textContent = `${sym} ${pend.toFixed(2)}`;
                document.getElementById('prPctLabel').textContent  = Math.round(pct + pctCola) + '%';

                // Barras
                document.getElementById('prBarPagado').style.width = pct + '%';
                if (over > 0) {
                    document.getElementById('prBarCola').style.display = 'none';
                    document.getElementById('prBarOver').style.left  = pct + '%';
                    document.getElementById('prBarOver').style.width = Math.min(pctCola, 100 - pct) + '%';
                    document.getElementById('prBarOver').style.display = 'block';
                } else {
                    document.getElementById('prBarOver').style.display = 'none';
                    document.getElementById('prBarCola').style.left  = pct + '%';
                    document.getElementById('prBarCola').style.width = pctCola + '%';
                    document.getElementById('prBarCola').style.display = 'block';
                }

                // Recaudación mini
                const recRow = document.getElementById('prRecaudacionRow');
                if (rec > 0) {
                    recRow.style.display = 'flex';
                    document.getElementById('prRecaudacion').textContent = `${sym} ${rec.toFixed(2)}`;
                } else {
                    recRow.style.display = 'none';
                }
                // Cola mini
                const colaRow = document.getElementById('prColaRow');
                if (cola > 0) {
                    colaRow.style.display = 'flex';
                    document.getElementById('prCola').textContent = `${sym} ${cola.toFixed(2)}`;
                } else {
                    colaRow.style.display = 'none';
                }

                // Alerta overflow
                const alertEl = document.getElementById('alertaOverflow');
                if (over > 0) {
                    alertEl.style.display = 'flex';
                    document.getElementById('alertaOverflowMsg').textContent =
                        `Los abonos en cola exceden el saldo en ${sym} ${over.toFixed(2)}. Ajusta los montos.`;
                    document.getElementById('btnGuardarPago').disabled = true;
                    document.getElementById('prPendienteLabel').textContent = '⚠ Excedido';
                    document.getElementById('prPendiente').style.color = '#dc2626';
                    document.getElementById('prPctLabel').style.color = '#dc2626';
                } else {
                    alertEl.style.display = 'none';
                    document.getElementById('btnGuardarPago').disabled = colaPagos.length === 0;
                    document.getElementById('prPendienteLabel').textContent = 'Saldo Pendiente';
                    document.getElementById('prPendiente').style.color = pend === 0 ? '#059669' : '#dc2626';
                    document.getElementById('prPctLabel').style.color = pct + pctCola >= 100 ? '#059669' : '#1d4ed8';
                }

                // Label botón guardar
                const n = colaPagos.length;
                document.getElementById('btnGuardarPagoTxt').textContent = n > 0
                    ? `Guardar ${n} abono${n > 1 ? 's' : ''} (${sym} ${cola.toFixed(2)})`
                    : 'Guardar pagos';
            }

            function calcularTotalCola() {
                return colaPagos.reduce((s, p) => s + (parseFloat(p.monto) || 0), 0);
            }

            // ── Lista de pagos existentes ──────────────────────────────────────
            async function cargarListaPagos(id) {
                document.getElementById('listaPagosLoading').style.display = 'block';
                document.getElementById('listaPagosLoading').textContent   = 'Cargando...';
                document.getElementById('listaPagosVacio').style.display   = 'none';
                document.getElementById('listaPagosTable').style.display   = 'none';
                try {
                    const res  = await fetch(`/facturas/${id}/pagos`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Error al cargar pagos');
                    pagoListaCargada = data.pagos || [];
                    renderListaPagos();
                    // Actualizar resumen con el total real del servidor
                    const montoAbonado = data.monto_abonado ?? pagoListaCargada.reduce((s,p)=>s+Number(p.monto_pagado),0);
                    const totalRec     = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                    actualizarResumenPago(montoAbonado, totalRec, calcularTotalCola());
                } catch (e) {
                    document.getElementById('listaPagosLoading').textContent = 'Error: ' + e.message;
                }
            }

            function renderListaPagos() {
                document.getElementById('listaPagosLoading').style.display = 'none';
                if (!pagoListaCargada.length) {
                    document.getElementById('listaPagosVacio').style.display = 'block';
                    document.getElementById('listaPagosTable').style.display = 'none';
                    return;
                }
                document.getElementById('listaPagosTable').style.display = 'table';
                const tbody = document.getElementById('listaPagosTbody');
                const sym = (facturaMoneda || '').includes('USD') ? 'USD' : 'S/';
                tbody.innerHTML = pagoListaCargada.map((p, i) => {
                    const fechaStr = p.fecha_pago
                        ? new Date(p.fecha_pago + 'T00:00:00').toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' })
                        : '—';
                    const comp = p.comprobante_url
                        ? `<a href="${p.comprobante_url}" target="_blank" style="color:#1d4ed8;font-weight:600;font-size:11px;">Ver</a>`
                        : '<span style="color:#9ca3af;">—</span>';
                    return `<tr style="border-bottom:1px solid #f3e8c1;">
                        <td style="padding:7px 8px;color:#9ca3af;">${i+1}</td>
                        <td style="padding:7px 8px;white-space:nowrap;">${fechaStr}</td>
                        <td style="padding:7px 8px;text-align:right;font-family:'DM Mono',monospace;font-weight:700;color:#059669;">${sym} ${Number(p.monto_pagado).toFixed(2)}</td>
                        <td style="padding:7px 8px;font-size:11px;">${p.banco_origen||'—'}</td>
                        <td style="padding:7px 8px;">${p.cuenta_pago||'—'}</td>
                        <td style="padding:7px 8px;font-family:'DM Mono',monospace;font-size:11px;">${p.numero_operacion||'—'}</td>
                        <td style="padding:7px 8px;">${p.forma_pago||'—'}</td>
                        <td style="padding:7px 8px;font-size:11px;color:#64748b;">${p.observacion||'—'}</td>
                        <td style="padding:7px 8px;text-align:center;">${comp}</td>
                        <td style="padding:7px 8px;text-align:center;white-space:nowrap;">
                            <button onclick="abrirEditarPago(${p.id_pago})"
                                style="background:#dbeafe;color:#1d4ed8;border:none;border-radius:5px;cursor:pointer;padding:3px 7px;font-size:11px;font-weight:700;margin-right:4px;" title="Editar">✎</button>
                            <button onclick="eliminarPagoItem(${p.id_pago})"
                                style="background:#fee2e2;color:#dc2626;border:none;border-radius:5px;cursor:pointer;padding:3px 7px;font-size:11px;font-weight:700;" title="Eliminar">✕</button>
                        </td>
                    </tr>`;
                }).join('');
            }

            async function eliminarPagoItem(idPago) {
                if (!confirm('¿Eliminar este abono? Se recalcularán los totales.')) return;
                try {
                    const res  = await fetch(`/facturas/${facturaActualId}/pagos/${idPago}`, {
                        method : 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'No se pudo eliminar');
                    const totalRec = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                    actualizarResumenPago(data.monto_abonado, totalRec, calcularTotalCola());
                    await cargarListaPagos(facturaActualId);
                    showToastFactura('✓ Abono eliminado y totales recalculados.');
                } catch (e) { alert('Error: ' + e.message); }
            }

            function abrirEditarPago(idPago) {
                const p = pagoListaCargada.find(x => x.id_pago == idPago);
                if (!p) return;
                document.getElementById('editPagoId').value          = idPago;
                document.getElementById('editPagoMonto').value       = Number(p.monto_pagado).toFixed(2);
                document.getElementById('editPagoFecha').value       = p.fecha_pago || '';
                document.getElementById('editPagoBanco').value       = p.banco_origen || '';
                document.getElementById('editPagoCuenta').value      = p.cuenta_pago || '';
                document.getElementById('editPagoNumOp').value       = p.numero_operacion || '';
                document.getElementById('editPagoForma').value       = p.forma_pago || '';
                document.getElementById('editPagoObs').value         = p.observacion || '';
                document.getElementById('modalEditarPagoOverlay').classList.add('open');
            }

            function cerrarModalEditarPago() {
                document.getElementById('modalEditarPagoOverlay').classList.remove('open');
            }

            async function guardarEditarPago() {
                const idPago = document.getElementById('editPagoId').value;
                const btn    = document.getElementById('btnGuardarEditarPago');
                btn.disabled = true;
                try {
                    const body = new URLSearchParams({
                        _token:           CSRF,
                        _method:          'PUT',
                        monto_pagado:     document.getElementById('editPagoMonto').value,
                        fecha_pago:       document.getElementById('editPagoFecha').value,
                        banco_origen:     document.getElementById('editPagoBanco').value,
                        cuenta_pago:      document.getElementById('editPagoCuenta').value,
                        numero_operacion: document.getElementById('editPagoNumOp').value,
                        forma_pago:       document.getElementById('editPagoForma').value,
                        observacion:      document.getElementById('editPagoObs').value,
                    });
                    const res  = await fetch(`/facturas/${facturaActualId}/pagos/${idPago}`, {
                        method : 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body,
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'No se pudo editar');
                    cerrarModalEditarPago();
                    const totalRec = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                    actualizarResumenPago(data.monto_abonado, totalRec, calcularTotalCola());
                    await cargarListaPagos(facturaActualId);
                    showToastFactura('✓ Abono actualizado correctamente.');
                } catch (e) { alert('Error: ' + e.message); }
                finally { btn.disabled = false; }
            }

            // ── Cola de nuevos abonos ──────────────────────────────────────────
            function agregarFilaPago() {
                const hoy = '{{ now()->format("Y-m-d") }}';
                const idx = ++colaIdx;
                colaPagos.push({ idx, monto:'', fecha:hoy, cuenta:'', cuentaPreset:'', cuentaOtro:'', numeroOp:'', bancoOrigen:'', formaPago:'', observacion:'', file:null });
                renderCola();
                // Enfocar el campo monto de la nueva fila
                setTimeout(() => {
                    const inp = document.getElementById(`col_monto_${idx}`);
                    if (inp) inp.focus();
                }, 50);
            }

            function eliminarFilaCola(idx) {
                colaPagos = colaPagos.filter(p => p.idx !== idx);
                renderCola();
                const totalRec = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                // read current monto_abonado from prPagado
                const pagadoText = document.getElementById('prPagado').textContent.replace(/[^0-9.]/g,'');
                actualizarResumenPago(parseFloat(pagadoText)||0, totalRec, calcularTotalCola());
            }

            function renderCola() {
                const wrap = document.getElementById('colaAbonos');
                const vacio = document.getElementById('colaVacia');
                if (!colaPagos.length) { wrap.innerHTML = ''; vacio.style.display = 'block'; return; }
                vacio.style.display = 'none';
                wrap.innerHTML = colaPagos.map(p => `
                <div id="cola_row_${p.idx}" style="background:#fff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-bottom:10px;position:relative;">
                    <button type="button" onclick="eliminarFilaCola(${p.idx})"
                        style="position:absolute;top:10px;right:10px;background:#fee2e2;color:#dc2626;border:none;border-radius:5px;cursor:pointer;padding:3px 8px;font-size:12px;font-weight:700;line-height:1;" title="Quitar fila">✕</button>

                    <div style="display:grid;grid-template-columns:140px 140px 1fr 1fr;gap:10px;align-items:end;">
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#1e40af;display:block;margin-bottom:4px;">Monto *</label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:9px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:700;color:#1d4ed8;">S/</span>
                                <input type="number" id="col_monto_${p.idx}" step="0.01" min="0.01" value="${p.monto}"
                                    class="form-input" style="padding-left:28px;font-weight:700;font-size:14px;color:#1d4ed8;border-color:#93c5fd;"
                                    placeholder="0.00" oninput="onColaMonto(${p.idx},this.value)">
                            </div>
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">Fecha *</label>
                            <input type="date" id="col_fecha_${p.idx}" value="${p.fecha}" class="form-input"
                                oninput="onColaField(${p.idx},'fecha',this.value)">
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">Cuenta destino</label>
                            <select id="col_cuentaPreset_${p.idx}" class="form-input" onchange="onColaCuentaPreset(${p.idx},this.value)">
                                <option value="">Seleccionar...</option>
                                <option value="BBVA"${p.cuentaPreset==='BBVA'?' selected':''}>BBVA</option>
                                <option value="BCP"${p.cuentaPreset==='BCP'?' selected':''}>BCP</option>
                                <option value="INTERBANK SOLES"${p.cuentaPreset==='INTERBANK SOLES'?' selected':''}>Interbank Soles</option>
                                <option value="INTERBANK DOLARES"${p.cuentaPreset==='INTERBANK DOLARES'?' selected':''}>Interbank Dólares</option>
                                <option value="YAPE"${p.cuentaPreset==='YAPE'?' selected':''}>Yape</option>
                                <option value="OTROS"${p.cuentaPreset==='OTROS'?' selected':''}>Otros</option>
                            </select>
                            ${p.cuentaPreset==='OTROS'?`<input type="text" id="col_cuentaOtro_${p.idx}" value="${p.cuentaOtro}" class="form-input" style="margin-top:6px;" placeholder="Especifica la cuenta" oninput="onColaField(${p.idx},'cuentaOtro',this.value);onColaField(${p.idx},'cuenta',this.value)">`:''}
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">N° Operación</label>
                            <input type="text" id="col_numOp_${p.idx}" value="${p.numeroOp}" class="form-input" placeholder="Ej: 000123456"
                                oninput="onColaField(${p.idx},'numeroOp',this.value)">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:10px;align-items:end;">
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">Banco origen</label>
                            <input type="text" id="col_banco_${p.idx}" value="${p.bancoOrigen}" class="form-input" placeholder="Ej: BCP"
                                oninput="onColaField(${p.idx},'bancoOrigen',this.value)">
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">Forma de pago</label>
                            <select id="col_forma_${p.idx}" class="form-input" onchange="onColaField(${p.idx},'formaPago',this.value)">
                                <option value=""${!p.formaPago?' selected':''}>Seleccionar...</option>
                                <option value="Transferencia"${p.formaPago==='Transferencia'?' selected':''}>Transferencia</option>
                                <option value="Efectivo"${p.formaPago==='Efectivo'?' selected':''}>Efectivo</option>
                                <option value="Cheque"${p.formaPago==='Cheque'?' selected':''}>Cheque</option>
                                <option value="Yape"${p.formaPago==='Yape'?' selected':''}>Yape</option>
                                <option value="Plin"${p.formaPago==='Plin'?' selected':''}>Plin</option>
                                <option value="Detracción"${p.formaPago==='Detracción'?' selected':''}>Detracción</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:#374151;display:block;margin-bottom:4px;">Comprobante</label>
                            <label id="col_fileLabel_${p.idx}"
                                style="display:flex;align-items:center;gap:6px;padding:7px 10px;border:1.5px dashed #93c5fd;border-radius:7px;cursor:pointer;background:#fff;font-size:11px;font-weight:600;color:#1d4ed8;transition:all .15s;"
                                onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#fff'">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span id="col_fileTxt_${p.idx}">${p.file?'📎 '+p.file.name:'Adjuntar archivo'}</span>
                                <input type="file" accept="image/*,application/pdf" style="display:none;"
                                    onchange="onColaFile(${p.idx},this)">
                            </label>
                        </div>
                    </div>
                    <div style="margin-top:8px;">
                        <input type="text" id="col_obs_${p.idx}" value="${p.observacion}" class="form-input" placeholder="Observación (opcional)"
                            style="font-size:12px;" oninput="onColaField(${p.idx},'observacion',this.value)">
                    </div>
                </div>`).join('');
            }

            function onColaMonto(idx, val) {
                const p = colaPagos.find(x => x.idx === idx);
                if (p) p.monto = val;
                const totalRec = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const pagadoText = document.getElementById('prPagado').textContent.replace(/[^0-9.]/g,'');
                actualizarResumenPago(parseFloat(pagadoText)||0, totalRec, calcularTotalCola());
            }

            function onColaField(idx, field, val) {
                const p = colaPagos.find(x => x.idx === idx);
                if (p) p[field] = val;
            }

            function onColaCuentaPreset(idx, val) {
                const p = colaPagos.find(x => x.idx === idx);
                if (!p) return;
                p.cuentaPreset = val;
                p.cuenta       = val === 'OTROS' ? '' : val;
                p.cuentaOtro   = '';
                renderCola();
                // Restore focus
                if (val === 'OTROS') {
                    setTimeout(() => { const el = document.getElementById(`col_cuentaOtro_${idx}`); if(el) el.focus(); }, 30);
                }
                const totalRec = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const pagadoText = document.getElementById('prPagado').textContent.replace(/[^0-9.]/g,'');
                actualizarResumenPago(parseFloat(pagadoText)||0, totalRec, calcularTotalCola());
            }

            function onColaFile(idx, input) {
                const p = colaPagos.find(x => x.idx === idx);
                if (!p || !input.files[0]) return;
                p.file = input.files[0];
                const txt = document.getElementById(`col_fileTxt_${idx}`);
                if (txt) txt.textContent = '📎 ' + p.file.name;
            }

            function cerrarModalPago() {
                document.getElementById('modalPagoOverlay').classList.remove('open');
                pagoListaCargada = [];
                colaPagos        = [];
                colaIdx          = 0;
                document.getElementById('colaAbonos').innerHTML = '';
                document.getElementById('colaVacia').style.display = 'block';
            }

            // ── Modal Ver Pagos (solo lectura) ─────────────────────────────
            async function abrirModalVerPagos(idFactura, moneda) {
                const overlay = document.getElementById('modalVerPagosOverlay');
                document.getElementById('modalVerPagosSubtitle').textContent = `Factura #${idFactura}`;
                document.getElementById('verPagosLoading').style.display = 'block';
                document.getElementById('verPagosLoading').textContent   = 'Cargando...';
                document.getElementById('verPagosVacio').style.display   = 'none';
                document.getElementById('verPagosTable').style.display   = 'none';
                overlay.classList.add('open');
                try {
                    const res  = await fetch(`/facturas/${idFactura}/pagos`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Error');
                    const pagos = data.pagos || [];
                    document.getElementById('verPagosLoading').style.display = 'none';
                    if (!pagos.length) {
                        document.getElementById('verPagosVacio').style.display = 'block';
                        return;
                    }
                    const sym = (moneda || '').includes('USD') ? 'USD' : 'S/';
                    let total = 0;
                    document.getElementById('verPagosTbody').innerHTML = pagos.map((p, i) => {
                        total += Number(p.monto_pagado);
                        const fecha = p.fecha_pago
                            ? new Date(p.fecha_pago + 'T00:00:00').toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' })
                            : '—';
                        const comp = p.comprobante_url
                            ? `<a href="${p.comprobante_url}" target="_blank" style="color:#059669;font-weight:600;">Ver</a>`
                            : '—';
                        return `<tr style="border-bottom:1px solid #d1fae5;">
                            <td style="padding:7px 10px;color:#9ca3af;">${i+1}</td>
                            <td style="padding:7px 10px;white-space:nowrap;">${fecha}</td>
                            <td style="padding:7px 10px;text-align:right;font-family:'DM Mono',monospace;font-weight:700;color:#059669;">${sym} ${Number(p.monto_pagado).toFixed(2)}</td>
                            <td style="padding:7px 10px;font-size:11px;">${p.banco_origen||'—'}</td>
                            <td style="padding:7px 10px;">${p.cuenta_pago||'—'}</td>
                            <td style="padding:7px 10px;font-family:'DM Mono',monospace;font-size:11px;">${p.numero_operacion||'—'}</td>
                            <td style="padding:7px 10px;">${p.forma_pago||'—'}</td>
                            <td style="padding:7px 10px;font-size:11px;color:#64748b;">${p.observacion||'—'}</td>
                            <td style="padding:7px 10px;text-align:center;">${comp}</td>
                        </tr>`;
                    }).join('');
                    document.getElementById('verPagosTotal').textContent = `${sym} ${total.toFixed(2)}`;
                    document.getElementById('verPagosTable').style.display = 'table';
                } catch (e) {
                    document.getElementById('verPagosLoading').textContent = 'Error: ' + e.message;
                }
            }

            function cerrarModalVerPagos() {
                document.getElementById('modalVerPagosOverlay').classList.remove('open');
            }

            async function guardarPago() {
                if (!colaPagos.length) { alert('Agrega al menos un abono antes de guardar.'); return; }

                // Validar montos
                const invalidas = colaPagos.filter(p => !(parseFloat(p.monto) > 0));
                if (invalidas.length) { alert('Todos los abonos deben tener un monto mayor a 0.'); return; }

                const btn = document.getElementById('btnGuardarPago');
                btn.disabled = true;

                const totalRec    = parseFloat(document.getElementById('pagoTotalRecaudacion').value) || 0;
                const tipoRec     = document.getElementById('pagoTipoRecaudacion').value;
                const validarDet  = document.getElementById('chkValidarDetraccion').checked;
                const fechaRec    = document.getElementById('pagoFechaRecaudacion').value || '';
                const pctRec      = parseFloat(document.getElementById('pagoPorcentaje').value) || 0;

                let ultimoData = null;
                for (let i = 0; i < colaPagos.length; i++) {
                    const p = colaPagos[i];
                    document.getElementById('btnGuardarPagoTxt').textContent = `Guardando ${i+1}/${colaPagos.length}…`;

                    const formData = new FormData();
                    formData.append('_token',           CSRF);
                    formData.append('monto_pagado',     parseFloat(p.monto).toFixed(2));
                    formData.append('fecha_pago',       p.fecha || new Date().toISOString().split('T')[0]);
                    formData.append('cuenta_pago',      p.cuentaPreset === 'OTROS' ? (p.cuentaOtro||'') : (p.cuentaPreset||''));
                    formData.append('numero_operacion', p.numeroOp     || '');
                    formData.append('banco_origen',     p.bancoOrigen  || '');
                    formData.append('forma_pago_abono', p.formaPago    || '');
                    formData.append('observacion',      p.observacion  || '');
                    if (p.file) formData.append('comprobante', p.file);
                    // Recaudación solo en el último abono si hay datos
                    if (i === colaPagos.length - 1) {
                        formData.append('total_recaudacion',      totalRec.toFixed(2));
                        formData.append('porcentaje_recaudacion', pctRec.toString());
                        formData.append('tipo_recaudacion',       tipoRec || '');
                        formData.append('fecha_recaudacion',      (validarDet && !fechaRec) ? new Date().toISOString().split('T')[0] : fechaRec);
                        formData.append('validar_detraccion',     validarDet ? '1' : '0');
                    }

                    try {
                        const res  = await fetch(`/facturas/${facturaActualId}/pago`, {
                            method : 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body   : formData,
                        });
                        ultimoData = await res.json();
                        if (!ultimoData.success) throw new Error(ultimoData.message || 'Error al guardar pago');
                    } catch (e) {
                        alert(`Error en abono ${i+1}: ${e.message}`);
                        btn.disabled = false;
                        document.getElementById('btnGuardarPagoTxt').textContent = 'Guardar pagos';
                        return;
                    }
                }

                showToastFactura(`✓ ${colaPagos.length} abono(s) guardados correctamente.`);
                cerrarModalPago();
                location.reload();
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
                ['btnTipoNinguna','btnTipoDet','btnTipoAuto','btnTipoRet'].forEach(id => {
                    const el = document.getElementById(id);
                    el.className = 'tipo-rec-card';
                    el.style.borderColor = el.style.background = el.style.color = '';
                });
                const camposRec   = document.getElementById('camposRecaudacion');
                const validarWrap = document.getElementById('validarDetraccionWrap');
                const usdNote     = document.getElementById('recaudUsdNote');
                const isUSD       = (facturaMoneda || '').includes('USD');
                if (tipo === 'DETRACCION') {
                    document.getElementById('btnTipoDet').classList.add('active-det');
                    camposRec.style.display = 'grid';
                } else if (tipo === 'AUTODETRACCION') {
                    document.getElementById('btnTipoAuto').classList.add('active-auto');
                    camposRec.style.display = 'grid';
                    validarWrap.style.display = 'none';
                } else if (tipo === 'RETENCION') {
                    document.getElementById('btnTipoRet').classList.add('active-ret');
                    camposRec.style.display = 'grid';
                    validarWrap.style.display = 'none';
                } else {
                    const btn = document.getElementById('btnTipoNinguna');
                    btn.style.borderColor = '#1d4ed8'; btn.style.background = '#dbeafe'; btn.style.color = '#1d4ed8';
                    camposRec.style.display = 'none';
                    validarWrap.style.display = 'none';
                    document.getElementById('pagoTotalRecaudacion').value = '';
                    document.getElementById('pagoPorcentaje').value = '';
                }
                if (usdNote) usdNote.style.display = (isUSD && tipo !== 'NINGUNA') ? 'block' : 'none';
            }

            function calcularRecaudacion() {
                const isUSD = (facturaMoneda || '').includes('USD');
                if (isUSD) return; // USD: user enters amount manually
                const pct = parseFloat(document.getElementById('pagoPorcentaje').value) || 0;
                if (pct > 0 && facturaImporte > 0) {
                    document.getElementById('pagoTotalRecaudacion').value = (facturaImporte * pct / 100).toFixed(2);
                }
            }

            // ── Modal Editar Factura ──────────────────────────────────────────
            function abrirModalEditar(id) {
                facturaActualId = id;
                document.getElementById('modalEditarOverlay').classList.add('open');
                fetch(`/facturas/${id}/edit`).then(r=>r.json()).then(f=>{
                    document.getElementById('editModalSubtitle').textContent = `Editando: ${f.serie}-${String(f.numero).padStart(8,'0')}`;
                    document.getElementById('editFechaEmision').value    = f.fecha_emision     || '';
                    document.getElementById('editFechaVencimiento').value= f.fecha_vencimiento || '';
                    document.getElementById('editEstado').value          = f.estado            || '';
                    document.getElementById('editGlosa').value           = f.glosa             || '';
                    document.getElementById('editFormaPago').value       = f.forma_pago        || '';
                    document.getElementById('editImporteTotal').value    = f.importe_total     || '';
                    document.getElementById('editMontoIgv').value        = f.monto_igv         || '';
                    document.getElementById('editMontoPendiente').value  = f.monto_pendiente   || '';
                    document.getElementById('editSubtotalGravado').value = f.subtotal_gravado  || '';
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
                    fecha_emision:     document.getElementById('editFechaEmision').value,
                    fecha_vencimiento: document.getElementById('editFechaVencimiento').value,
                    estado:            document.getElementById('editEstado').value,
                    glosa:             document.getElementById('editGlosa').value,
                    forma_pago:        document.getElementById('editFormaPago').value,
                    importe_total:     document.getElementById('editImporteTotal').value,
                    monto_igv:         document.getElementById('editMontoIgv').value,
                    subtotal_gravado:  document.getElementById('editSubtotalGravado').value
                };
                fetch(`/facturas/${facturaActualId}`, {
                    method:'PUT',
                    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
                    body:JSON.stringify(datos)
                })
                    .then(r=>r.json())
                    .then(data=>{
                        if(data.success){
                            cerrarModalEditar();
                            showToastFactura(`✓ Factura ${data.factura_num||''} actualizada.`);
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            showToastFactura(data.message||'No se pudo guardar', false);
                        }
                    })
                    .catch(err=>showToastFactura('Error: '+err.message, false));
            }

            // ── Modal Editar Cliente ──────────────────────────────────────────
            function abrirModalEditarCliente(id) {
                facturaActualId = id;
                document.getElementById('modalEditarClienteOverlay').classList.add('open');
                fetch(`/facturas/${id}/cliente`).then(r=>r.json()).then(c=>{
                    document.getElementById('editRazonSocial').value     = c.razon_social    || '';
                    document.getElementById('editRuc').value             = c.ruc             || '';
                    document.getElementById('editCelular').value         = c.celular         || '';
                    document.getElementById('editCorreo').value          = c.correo          || '';
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
                    direccion_fiscal: document.getElementById('editDireccionFiscal').value
                };
                fetch(`/facturas/${facturaActualId}/cliente`, {
                    method:'PUT',
                    headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
                    body:JSON.stringify(datos)
                })
                    .then(r=>r.json())
                    .then(data=>{ if(data.success){ cerrarModalEditarCliente(); location.reload(); } else alert('Error: '+(data.message||'')); })
                    .catch(err=>alert('Error: '+err.message));
            }

            ['modalPagoMasivoOverlay','modalPagoOverlay','modalEditarOverlay','modalEditarClienteOverlay','modalReporteOverlay','modalVerPagosOverlay'].forEach(id => {
                document.getElementById(id)?.addEventListener('click', e => { if(e.target === e.currentTarget) e.currentTarget.classList.remove('open'); });
            });

            // ── Historial de importaciones ────────────────────────────────

            function toggleHistorialImport() {
                const body    = document.getElementById('historialImportBody');
                const chevron = document.getElementById('historialChevron');
                if (!body) return;
                const open = body.style.display !== 'none';
                body.style.display    = open ? 'none' : '';
                chevron.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
            }

            function verFacturasSinc(id) {
                document.getElementById('modalSincTitulo').textContent = 'Facturas de importación #' + id;
                document.getElementById('modalSincDesc').textContent   = 'Cargando...';
                document.getElementById('modalSincBody').innerHTML     = '<div style="text-align:center;padding:40px;color:#94a3b8;">Cargando...</div>';
                document.getElementById('modalSincOverlay').classList.add('open');

                fetch('/facturas/sincronizaciones/' + id + '/facturas', {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('modalSincDesc').textContent = data.length + ' factura(s) en este lote';
                    if (!data.length) {
                        document.getElementById('modalSincBody').innerHTML = '<p style="color:#94a3b8;padding:20px;">Sin facturas vinculadas.</p>';
                        return;
                    }
                    let html = '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
                    html += '<thead><tr style="background:#f8fafc;"><th style="padding:8px 10px;text-align:left;color:#475569;">Serie-Nro</th><th style="padding:8px 10px;text-align:left;color:#475569;">Cliente</th><th style="padding:8px 10px;text-align:left;color:#475569;">Fecha</th><th style="padding:8px 10px;text-align:right;color:#475569;">Total</th><th style="padding:8px 10px;text-align:center;color:#475569;">Estado</th><th style="padding:8px 10px;text-align:center;color:#475569;">Vis.</th></tr></thead><tbody>';
                    data.forEach(f => {
                        const num = String(f.numero).padStart(8,'0');
                        const actBadge = f.activo ? '<span style="background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;">✓</span>'
                                                  : '<span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;">✗</span>';
                        html += `<tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-weight:600;">${f.serie}-${num}</td><td style="padding:7px 10px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.razon_social}</td><td style="padding:7px 10px;white-space:nowrap;">${f.fecha_emision ?? '—'}</td><td style="padding:7px 10px;text-align:right;">${f.moneda} ${parseFloat(f.importe_total).toLocaleString('es-PE',{minimumFractionDigits:2})}</td><td style="padding:7px 10px;text-align:center;"><span style="font-size:11px;">${f.estado}</span></td><td style="padding:7px 10px;text-align:center;">${actBadge}</td></tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('modalSincBody').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('modalSincBody').innerHTML = '<p style="color:#ef4444;padding:20px;">Error al cargar las facturas.</p>';
                });
            }

            function cerrarModalSinc() {
                document.getElementById('modalSincOverlay').classList.remove('open');
            }

            function desactivarSinc(id) {
                if (!confirm('¿Desactivar esta importación? Las facturas de este lote dejarán de aparecer en la lista.')) return;
                fetch('/facturas/sincronizaciones/' + id + '/desactivar', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': CSRF},
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToastFactura('Importación desactivada — ' + data.total + ' factura(s) ocultada(s).');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        alert('Error: ' + (data.error ?? 'Error desconocido'));
                    }
                })
                .catch(() => alert('Error al comunicarse con el servidor.'));
            }

            function activarSinc(id) {
                fetch('/facturas/sincronizaciones/' + id + '/activar', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': CSRF},
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToastFactura('Importación reactivada — ' + data.total + ' factura(s) visibles nuevamente.');
                        setTimeout(() => location.reload(), 1200);
                    } else if (data.conflictos) {
                        let msg = (data.error ?? 'Conflictos detectados') + '\n\n';
                        data.conflictos.forEach(c => {
                            msg += '• ' + c.factura + ' → ya está en: ' + c.en_importacion + '\n';
                        });
                        alert(msg);
                    } else {
                        alert('Error: ' + (data.error ?? 'Error desconocido'));
                    }
                })
                .catch(() => alert('Error al comunicarse con el servidor.'));
            }

            // Resaltar la importación recién creada si viene del redirect
            @if(session('resumen_importacion.id_sincronizacion'))
            document.addEventListener('DOMContentLoaded', function() {
                const sincId = {{ session('resumen_importacion.id_sincronizacion') }};
                const row = document.getElementById('sincRow' + sincId);
                if (row) {
                    // Abrir el acordeón y hacer scroll
                    const body = document.getElementById('historialImportBody');
                    if (body) { body.style.display = ''; document.getElementById('historialChevron').style.transform = 'rotate(0deg)'; }
                    row.style.background = '#fef9c3';
                    row.scrollIntoView({ behavior:'smooth', block:'center' });
                    setTimeout(() => row.style.background = '', 3000);
                }
            });
            @endif
        </script>
    @endpush

    {{-- ══ MODAL LEYENDA ══ --}}
    <div class="modal-overlay" id="modalLeyenda">
        <div class="modal" style="max-width:560px;">
            <div class="modal-header" style="background:linear-gradient(135deg,#f5c842 0%,#e8b820 100%);">
                <h2 style="color:#000;font-size:17px;">Leyenda de Estados</h2>
                <p style="color:rgba(0,0,0,.6);">Significado de cada estado de factura en el sistema</p>
                <button onclick="document.getElementById('modalLeyenda').classList.remove('open')"
                        style="position:absolute;right:20px;top:20px;background:none;border:none;color:#000;cursor:pointer;font-size:24px;opacity:.6;">×</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <div style="display:flex;flex-direction:column;gap:12px;">

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:#fef3c7;border-radius:10px;border:1px solid #fde68a;">
                        <span class="badge badge-pendiente" style="flex-shrink:0;margin-top:1px;">PENDIENTE</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#92400e;">Sin monto abonado registrado</div>
                            <div style="font-size:12px;color:#92400e;margin-top:2px;">La factura no tiene ningún pago ni detracción registrada. Puede estar dentro o fuera del plazo de vencimiento.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:#fee2e2;border-radius:10px;border:1px solid #fca5a5;">
                        <span class="badge badge-vencido" style="flex-shrink:0;margin-top:1px;">VENCIDO</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#7f1d1d;">Plazo de pago superado</div>
                            <div style="font-size:12px;color:#7f1d1d;margin-top:2px;">La fecha de vencimiento ya pasó y la factura sigue sin pago registrado. Requiere gestión de cobranza urgente.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:#d1fae5;border-radius:10px;border:1px solid #a7f3d0;">
                        <span class="badge badge-pagada" style="flex-shrink:0;margin-top:1px;">PAGADA</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#065f46;">Abono + recaudación = importe total</div>
                            <div style="font-size:12px;color:#065f46;margin-top:2px;">La suma de los abonos y la detracción/retención cubre el importe total. Factura completamente cancelada.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:#fce7f3;border-radius:10px;border:1.5px solid #fbcfe8;">
                        <span class="badge badge-diferencia_pend" style="flex-shrink:0;margin-top:1px;">DIFERENCIA PENDIENTE</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#9d174d;">Detracción/retención validada o pago parcial registrado</div>
                            <div style="font-size:12px;color:#9d174d;margin-top:2px;">Se registró un abono pero queda saldo pendiente, o la detracción/retención ya fue depositada y se espera cobrar la diferencia.</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:#f1f5f9;border-radius:10px;border:1px solid #cbd5e1;">
                        <span class="badge badge-anulada" style="flex-shrink:0;margin-top:1px;">ANULADO</span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#475569;">Factura anulada o nota de crédito</div>
                            <div style="font-size:12px;color:#475569;margin-top:2px;">La factura fue anulada en Nubefact, o es una Nota de Crédito (serie FC01). Las filas tachadas son NC sin factura original en el sistema.</div>
                        </div>
                    </div>

                </div>

                {{-- Nota sobre recaudación --}}
                <div style="margin-top:16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:12px;color:#92400e;">
                    <strong>Tipos de Recaudación:</strong><br>
                    • <strong>Detracción</strong>: El cliente deposita un % al Banco de la Nación antes de pagar. Se valida con el Excel del BN.<br>
                    • <strong>Retención</strong>: El cliente retiene un % al momento del pago y lo declara a SUNAT.<br>
                    • <strong>Autodetracción</strong>: El propio emisor hace el depósito de detracción.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary"
                        onclick="document.getElementById('modalLeyenda').classList.remove('open')">
                    Entendido
                </button>
            </div>
        </div>
    </div>
@endsection
