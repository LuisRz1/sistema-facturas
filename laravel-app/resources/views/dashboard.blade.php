@extends('layouts.app')
@section('title', 'Panel Principal')
@section('breadcrumb', 'Panel Principal')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════════════════
           TOKENS DE COLOR — Consorcio Rodriguez Caballero S.A.C.
           Paleta: Blanco · Amarillo industrial · Negro
        ══════════════════════════════════════════════════════════ */
        :root {
            --gold:          #F5C518;   /* amarillo primario        */
            --gold-light:    #FFF3B0;   /* amarillo muy suave       */
            --gold-mid:      #FFE166;   /* amarillo medio           */
            --gold-dark:     #C49A00;   /* amarillo oscuro/dorado   */
            --onyx:          #111111;   /* negro profundo           */
            --onyx-80:       #1E1E1E;
            --onyx-60:       #333333;
            --slate:         #4A4A4A;   /* gris carbón              */
            --smoke:         #F8F8F6;   /* blanco cálido fondo      */
            --border-light:  #E8E4D9;
            --border-gold:   #F5C518;

            /* Estados */
            --green:  #15803d;
            --green-bg: #F0FDF4;
            --amber:  #B45309;
            --amber-bg: #FFFBEB;
            --red:    #B91C1C;
            --red-bg: #FEF2F2;
            --blue:   #1E40AF;
            --blue-bg:#EFF6FF;

            /* Tipografía */
            --font-display: 'Cormorant Garamond', serif;
            --font-body:    'Outfit', sans-serif;
            --font-mono:    'DM Mono', monospace;

            /* Radios y sombras */
            --r-sm:  4px;
            --r-md:  8px;
            --r-lg:  12px;
            --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.05);
            --shadow-hover: 0 4px 24px rgba(0,0,0,.10), 0 1px 4px rgba(0,0,0,.06);
        }

        /* ── RESET BASE ───────────────────────────────────────── */
        body {
            font-family: var(--font-body);
            background: var(--smoke);
            color: var(--onyx);
        }

        /* ── PAGE HEADER ──────────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
        }
        .page-title {
            font-family: var(--font-display);
            font-size: 38px;
            font-weight: 700;
            letter-spacing: .01em;
            text-transform: none;
            color: var(--onyx);
            line-height: 1;
            margin: 0 0 5px;
        }
        .page-title span {
            color: var(--gold-dark);
            font-style: italic;
        }
        .page-desc {
            font-size: 12.5px;
            color: var(--slate);
            font-weight: 500;
            margin: 0;
        }

        /* Botones */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 500;
            padding: 9px 18px;
            border-radius: var(--r-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .btn-primary {
            background: var(--onyx);
            color: var(--gold);
            border: 1.5px solid var(--onyx);
        }
        .btn-primary:hover {
            background: var(--onyx-60);
        }
        .btn-outline {
            background: transparent;
            color: var(--onyx);
            border: 1.5px solid var(--border-light);
        }
        .btn-outline:hover {
            border-color: var(--gold);
            color: var(--gold-dark);
        }
        .btn-ghost {
            background: transparent;
            color: var(--slate);
            border: 1px solid transparent;
            padding: 6px 12px;
            font-size: 11px;
        }
        .btn-ghost:hover {
            background: var(--gold-light);
            color: var(--onyx);
        }
        .btn-sm { padding: 6px 14px; font-size: 11px; }

        /* ── PERÍODO SELECTOR ─────────────────────────────────── */
        .period-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding: 14px 22px;
            background: #fff;
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            border-top: 3px solid var(--gold);
            position: relative;
        }
        .period-bar::before {
            content: '';
            position: absolute;
            top: 3px; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, var(--gold) 0%, transparent 80%);
            opacity: .2;
        }
        .period-bar label {
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--slate);
            white-space: nowrap;
        }
        .period-bar input[type="date"] {
            height: 36px;
            padding: 0 12px;
            border: 1.5px solid var(--border-light);
            border-radius: var(--r-sm);
            font-size: 13px;
            font-family: var(--font-mono);
            background: var(--smoke);
            color: var(--onyx);
            outline: none;
            transition: border-color .15s, background .15s;
            cursor: pointer;
        }
        .period-bar input[type="date"]:focus {
            border-color: var(--gold);
            background: #fff;
        }
        .period-bar .sep {
            color: var(--gold-dark);
            font-weight: 900;
            font-size: 16px;
        }
        .period-bar .period-info {
            font-size: 12px;
            color: var(--slate);
            margin-left: auto;
            background: var(--gold-light);
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid var(--gold-mid);
        }
        .period-bar .period-info strong {
            color: var(--onyx);
            font-family: var(--font-mono);
        }

        /* Botones de rango rápido */
        .rango-btn {
            background: transparent;
            color: var(--slate);
            border: 1.5px solid var(--border-light);
            padding: 5px 14px;
            border-radius: var(--r-sm);
            font-size: 10px;
            font-weight: 500;
            font-family: var(--font-body);
            text-transform: uppercase;
            letter-spacing: .12em;
            cursor: pointer;
            transition: all .15s;
        }
        .rango-btn:hover {
            background: var(--gold);
            color: var(--onyx);
            border-color: var(--gold);
        }

        /* ── KPI CARDS ────────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #fff;
            border-radius: var(--r-lg);
            padding: 20px 22px 18px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
            cursor: default;
        }
        .kpi-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        /* Acento superior */
        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--gold);
        }

        /* Número grande decorativo en fondo */
        .kpi-card::before {
            content: attr(data-rank);
            position: absolute;
            bottom: -8px; right: 12px;
            font-family: var(--font-display);
            font-size: 72px;
            font-weight: 900;
            color: var(--onyx);
            opacity: .03;
            line-height: 1;
            pointer-events: none;
            user-select: none;
        }

        .kpi-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .kpi-label {
            font-family: var(--font-body);
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--slate);
        }
        .kpi-icon {
            width: 36px; height: 36px;
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--gold-light);
            color: var(--gold-dark);
            border: 1px solid var(--gold-mid);
        }
        /* Ícono especial para el card principal */
        .kpi-main .kpi-icon {
            background: var(--onyx);
            color: var(--gold);
            border: none;
        }
        .kpi-value {
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 600;
            color: var(--onyx);
            line-height: 1;
            letter-spacing: -.3px;
        }
        .kpi-currency {
            font-size: 16px;
            font-weight: 500;
            color: var(--slate);
            margin-right: 2px;
            vertical-align: middle;
            font-style: italic;
        }
        .kpi-sub {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        .kpi-change {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 3px;
            letter-spacing: .04em;
        }
        .change-up   { background: #ECFDF5; color: #065f46; }
        .change-down { background: #FEF2F2; color: #7f1d1d; }
        .kpi-desc {
            font-size: 11px;
            color: var(--slate);
        }

        /* ── CARD BASE ────────────────────────────────────────── */
        .card {
            background: #fff;
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px 16px;
            border-bottom: 1px solid var(--border-light);
            gap: 12px;
        }
        .card-title {
            font-family: var(--font-body);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: var(--onyx);
        }
        .card-title::before {
            content: '▐ ';
            color: var(--gold);
            font-size: 10px;
        }
        .card-desc {
            font-size: 11.5px;
            color: var(--slate);
            margin-top: 2px;
        }

        /* ── CHARTS ROW ───────────────────────────────────────── */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .chart-wrap {
            position: relative;
            height: 268px;
        }

        /* ── STATUS / DONUT ───────────────────────────────────── */
        .status-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 4px;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 2px;
            flex-shrink: 0;
        }
        .status-bar-bg {
            flex: 1;
            height: 5px;
            background: var(--border-light);
            border-radius: 2px;
            overflow: hidden;
        }
        .status-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 1.1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .status-label {
            font-size: 11.5px;
            color: var(--slate);
            font-weight: 500;
            min-width: 72px;
        }
        .status-val {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 500;
            color: var(--onyx);
            min-width: 28px;
            text-align: right;
        }

        /* ── BOTTOM ROW ───────────────────────────────────────── */
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* ── TOP CLIENTES ─────────────────────────────────────── */
        .client-rank {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .client-rank-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--r-sm);
            background: var(--smoke);
            border: 1px solid transparent;
            transition: border-color .15s, background .15s;
        }
        .client-rank-item:hover {
            background: var(--gold-light);
            border-color: var(--gold-mid);
        }
        .rank-num {
            width: 22px; height: 22px;
            border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-body);
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
            background: var(--border-light);
            color: var(--slate);
        }
        .rank-num.top1 {
            background: var(--gold);
            color: var(--onyx);
        }
        .rank-num.top2 {
            background: var(--onyx);
            color: var(--gold-light);
        }
        .rank-num.top3 {
            background: var(--onyx-60);
            color: #ccc;
        }
        .rank-name {
            flex: 1;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--onyx);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .rank-amount {
            font-family: var(--font-mono);
            font-size: 12.5px;
            font-weight: 500;
            color: var(--onyx);
            white-space: nowrap;
        }
        .rank-count {
            font-size: 10px;
            color: var(--slate);
            white-space: nowrap;
        }

        /* ── ÚLTIMAS FACTURAS ─────────────────────────────────── */
        .recent-list { display: flex; flex-direction: column; }
        .recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid var(--border-light);
            transition: background .12s;
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-icon {
            width: 32px; height: 32px;
            border-radius: var(--r-sm);
            background: var(--smoke);
            border: 1px solid var(--border-light);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--slate);
        }
        .recent-main { flex: 1; min-width: 0; }
        .recent-num {
            font-family: var(--font-mono);
            font-size: 12.5px;
            font-weight: 500;
            color: var(--onyx);
        }
        .recent-desc {
            font-size: 11px;
            color: var(--slate);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .recent-right { text-align: right; flex-shrink: 0; }
        .recent-amount {
            font-family: var(--font-mono);
            font-size: 13px;
            font-weight: 500;
            color: var(--onyx);
        }

        /* ── BADGES ───────────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            font-family: var(--font-body);
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .1em;
            padding: 2px 8px;
            border-radius: 3px;
            white-space: nowrap;
        }
        .badge-pagada    { background: #ECFDF5; color: #14532D; border: 1px solid #BBF7D0; }
        .badge-pendiente { background: var(--gold-light); color: #78350F; border: 1px solid var(--gold-mid); }
        .badge-vencida   { background: #FEF2F2; color: #7F1D1D; border: 1px solid #FECACA; }
        .badge-por_vencer{ background: #FFF7ED; color: #7C2D12; border: 1px solid #FED7AA; }
        .badge-anulada   { background: #F9FAFB; color: #374151; border: 1px solid #E5E7EB; }

        /* ── EMPTY STATE ──────────────────────────────────────── */
        .dash-empty {
            text-align: center;
            padding: 32px 16px;
            color: var(--slate);
        }
        .dash-empty svg { margin: 0 auto 10px; opacity: .25; }
        .dash-empty p { font-size: 13px; }

        /* ── DIVIDER DECORATIVO ───────────────────────────────── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .section-divider span {
            font-family: var(--font-body);
            font-size: 9px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .2em;
            color: var(--slate);
        }
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }

        /* ── RESPONSIVE ───────────────────────────────────────── */
        @media (max-width: 1300px) {
            .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
            .charts-row  { grid-template-columns: 1fr; }
            .bottom-row  { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .kpi-value { font-size: 22px; }
        }

        /* ── ANIMACIÓN ENTRADA ────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .kpi-card  { animation: fadeUp .35s ease both; }
        .kpi-card:nth-child(1) { animation-delay: .05s; }
        .kpi-card:nth-child(2) { animation-delay: .10s; }
        .kpi-card:nth-child(3) { animation-delay: .15s; }
        .kpi-card:nth-child(4) { animation-delay: .20s; }
    </style>
@endpush

@section('content')

    {{-- ── PAGE HEADER ── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Panel <span>Principal</span></h1>
            <p class="page-desc">Resumen financiero del período seleccionado — Consorcio Rodriguez Caballero S.A.C.</p>
        </div>
        <a href="{{ route('facturas.index', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
           class="btn btn-primary">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Ver Todas las Facturas
        </a>
    </div>

    {{-- ── PERÍODO ── --}}
    <form method="GET" action="{{ route('dashboard') }}" id="frmPeriodo">
        <div class="period-bar">
            <label>Período:</label>
            <input type="date" name="fecha_desde" value="{{ $fechaDesde }}"
                   onchange="document.getElementById('frmPeriodo').submit()">
            <span class="sep">→</span>
            <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}"
                   onchange="document.getElementById('frmPeriodo').submit()">

            <div style="display:flex;gap:6px;">
                <button type="button" class="rango-btn" onclick="setRango('mes')">Este mes</button>
                <button type="button" class="rango-btn" onclick="setRango('trimestre')">Trimestre</button>
                <button type="button" class="rango-btn" onclick="setRango('anio')">Este año</button>
            </div>

            <span class="period-info">
                <strong>{{ $kpis->total_facturas }}</strong> facturas ·
                {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} al
                {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
            </span>
        </div>
    </form>

    {{-- ── KPI CARDS ── --}}
    @php
        $totalFacturado   = $kpis->total_facturado  ?? 0;
        $totalCobrado     = $kpis->total_cobrado     ?? 0;
        $totalPorCobrar   = $kpis->total_por_cobrar  ?? 0;
        $totalRecaudacion = $kpis->total_recaudacion ?? 0;
        $pctCobro = $totalFacturado > 0 ? round(($totalCobrado / $totalFacturado) * 100, 1) : 0;
    @endphp

    <div class="kpi-grid">

        {{-- Total Facturado --}}
        <div class="kpi-card kpi-main" data-rank="01">
            <div class="kpi-header">
                <div class="kpi-label">Total Facturado</div>
                <div class="kpi-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value"><span class="kpi-currency">S/</span>{{ number_format($totalFacturado, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">{{ $kpis->total_facturas }} facturas en el período</span>
            </div>
        </div>

        {{-- Cobrado --}}
        <div class="kpi-card" data-rank="02">
            <div class="kpi-header">
                <div class="kpi-label">Cobrado (Abonos)</div>
                <div class="kpi-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value"><span class="kpi-currency">S/</span>{{ number_format($totalCobrado, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-change change-up">↑ {{ $pctCobro }}%</span>
                <span class="kpi-desc">de lo facturado cobrado</span>
            </div>
        </div>

        {{-- Cuentas por cobrar --}}
        <div class="kpi-card" data-rank="03">
            <div class="kpi-header">
                <div class="kpi-label">Cuentas por Cobrar</div>
                <div class="kpi-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value"><span class="kpi-currency">S/</span>{{ number_format($totalPorCobrar, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">{{ $kpis->count_pendientes }} pendientes · {{ $kpis->count_vencidas }} vencidas</span>
            </div>
        </div>

        {{-- Fondo Recaudación --}}
        <div class="kpi-card" data-rank="04">
            <div class="kpi-header">
                <div class="kpi-label">Fondo Recaudación</div>
                <div class="kpi-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
            <div class="kpi-value"><span class="kpi-currency">S/</span>{{ number_format($totalRecaudacion, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">Detracciones + Retenciones</span>
            </div>
        </div>

    </div>

    {{-- ── CHARTS ROW ── --}}
    <div class="section-divider"><span>Análisis</span></div>
    <div class="charts-row">

        {{-- Bar chart tendencias --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Tendencias de Facturación</div>
                    <div class="card-desc">Monto mensual facturado en Soles</div>
                </div>
                <a href="{{ route('reportes.index') }}" class="btn btn-outline btn-sm">Ver reportes</a>
            </div>
            <div style="padding: 20px 24px 24px;">
                <div class="chart-wrap">
                    <canvas id="chartTendencias"></canvas>
                </div>
            </div>
        </div>

        {{-- Donut estado --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Estado de Cobranza</div>
                    <div class="card-desc">Distribución del período</div>
                </div>
            </div>
            <div style="padding: 20px 24px 16px;">
                <div style="display:flex;justify-content:center;margin-bottom:20px;">
                    <div style="width:170px;height:170px;position:relative;">
                        <canvas id="chartDonut"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                            <div style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:600;color:#111;line-height:1;">{{ $kpis->total_facturas }}</div>
                            <div style="font-size:8px;color:var(--slate);text-transform:uppercase;letter-spacing:.18em;font-weight:500;font-family:'Outfit',sans-serif;margin-top:3px;">FACTURAS</div>
                        </div>
                    </div>
                </div>

                @php
                    $statusMap = [
                        'PAGADA'    => ['label' => 'Pagadas',    'color' => '#22c55e'],
                        'PENDIENTE' => ['label' => 'Pendientes', 'color' => '#F5C518'],
                        'VENCIDA'   => ['label' => 'Vencidas',   'color' => '#ef4444'],
                        'POR_VENCER'=> ['label' => 'Por vencer', 'color' => '#f97316'],
                        'ANULADA'   => ['label' => 'Anuladas',   'color' => '#cbd5e1'],
                    ];
                    $totalCount = max(1, $kpis->total_facturas);
                @endphp
                <div class="status-row">
                    @foreach($statusMap as $key => $info)
                        @if(isset($porEstado[$key]))
                            @php $pct = round(($porEstado[$key]->cantidad / $totalCount) * 100, 1); @endphp
                            <div class="status-item">
                                <span class="status-dot" style="background:{{ $info['color'] }};"></span>
                                <span class="status-label">{{ $info['label'] }}</span>
                                <div class="status-bar-bg">
                                    <div class="status-bar-fill" style="width:{{ $pct }}%;background:{{ $info['color'] }};"></div>
                                </div>
                                <span class="status-val">{{ $porEstado[$key]->cantidad }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── BOTTOM ROW ── --}}
    <div class="section-divider" style="margin-top:24px;"><span>Detalle</span></div>
    <div class="bottom-row">

        {{-- Top clientes --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Top Clientes</div>
                    <div class="card-desc">Por monto facturado en el período</div>
                </div>
                <a href="{{ route('clientes.index') }}" class="btn btn-ghost btn-sm">Ver todos</a>
            </div>
            <div style="padding: 16px 24px 24px;">
                @if($topClientes->isEmpty())
                    <div class="dash-empty">
                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p>Sin datos en el período</p>
                    </div>
                @else
                    @php $maxTotal = $topClientes->max('total'); @endphp
                    <div class="client-rank">
                        @foreach($topClientes as $i => $c)
                            <div class="client-rank-item">
                                <div class="rank-num {{ $i===0?'top1':($i===1?'top2':($i===2?'top3':'')) }}">{{ $i+1 }}</div>
                                <div style="flex:1;min-width:0;">
                                    <div class="rank-name" title="{{ $c->razon_social }}">{{ $c->razon_social }}</div>
                                    <div style="margin-top:5px;height:3px;background:var(--border-light);border-radius:2px;overflow:hidden;">
                                        <div style="height:100%;background:var(--gold);width:{{ $maxTotal > 0 ? round(($c->total/$maxTotal)*100) : 0 }}%;border-radius:2px;"></div>
                                    </div>
                                </div>
                                <div style="text-align:right;flex-shrink:0;">
                                    <div class="rank-amount">S/ {{ number_format($c->total, 2) }}</div>
                                    <div class="rank-count">{{ $c->cantidad }} fact.</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Últimas facturas --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Últimas Facturas</div>
                    <div class="card-desc">Actividad reciente del período</div>
                </div>
                <a href="{{ route('facturas.index', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
                   class="btn btn-ghost btn-sm">Ver historial</a>
            </div>
            <div style="padding: 0 24px 16px;">
                @if($ultimasFacturas->isEmpty())
                    <div class="dash-empty">
                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>Sin facturas en el período</p>
                    </div>
                @else
                    <div class="recent-list">
                        @php
                            $badgeMap = [
                                'PENDIENTE'  => 'badge-pendiente',
                                'POR_VENCER' => 'badge-por_vencer',
                                'VENCIDA'    => 'badge-vencida',
                                'PAGADA'     => 'badge-pagada',
                                'ANULADA'    => 'badge-anulada',
                            ];
                        @endphp
                        @foreach($ultimasFacturas as $f)
                            <div class="recent-item">
                                <div class="recent-icon">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="recent-main">
                                    <div class="recent-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</div>
                                    <div class="recent-desc" title="{{ $f->razon_social }}">{{ $f->razon_social }}</div>
                                </div>
                                <div class="recent-right">
                                    <div class="recent-amount">S/ {{ number_format($f->importe_total, 2) }}</div>
                                    <div style="margin-top:4px;">
                                        <span class="badge {{ $badgeMap[$f->estado] ?? 'badge-pendiente' }}">
                                            {{ str_replace('_', ' ', $f->estado) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const tendenciaData = @json($tendencia);
        const porEstadoData = @json($porEstado);

        function mesLabel(mes) {
            const [y, m] = mes.split('-');
            const names = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return names[parseInt(m) - 1] + ' ' + y.slice(2);
        }

        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#4A4A4A';

        // ── BAR CHART ────────────────────────────────────────────────
        const maxVal     = Math.max(...tendenciaData.map(d => d.total));
        const barColors  = tendenciaData.map(d => d.total === maxVal ? '#111111' : '#F5C518');
        const barHover   = tendenciaData.map(d => d.total === maxVal ? '#333333' : '#C49A00');

        new Chart(document.getElementById('chartTendencias'), {
            type: 'bar',
            data: {
                labels: tendenciaData.map(d => mesLabel(d.mes)),
                datasets: [{
                    data: tendenciaData.map(d => d.total),
                    backgroundColor: barColors,
                    hoverBackgroundColor: barHover,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111111',
                        titleColor: '#F5C518',
                        bodyColor: '#ffffff',
                        padding: 12,
                        cornerRadius: 6,
                        titleFont: { family: "'Outfit'", weight: '600', size: 11 },
                        bodyFont:  { family: "'DM Mono'", size: 12 },
                        callbacks: {
                            label: ctx => ' S/ ' + ctx.parsed.y.toLocaleString('es-PE', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 11, weight: '600' } }
                    },
                    y: {
                        grid: { color: '#F0EDE6', drawBorder: false },
                        border: { display: false, dash: [3, 3] },
                        ticks: {
                            font: { size: 11 },
                            callback: v => 'S/ ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                        }
                    }
                }
            }
        });

        // ── DONUT ─────────────────────────────────────────────────────
        const statusColors = {
            'PAGADA':     '#22c55e',
            'PENDIENTE':  '#F5C518',
            'VENCIDA':    '#ef4444',
            'POR_VENCER': '#f97316',
            'ANULADA':    '#cbd5e1',
            'OBSERVADA':  '#a78bfa',
        };

        const donutLabels = Object.keys(porEstadoData);
        const donutData   = donutLabels.map(k => porEstadoData[k].cantidad);
        const donutColors = donutLabels.map(k => statusColors[k] || '#cbd5e1');

        new Chart(document.getElementById('chartDonut'), {
            type: 'doughnut',
            data: {
                labels: donutLabels.map(l => l.replace('_', ' ')),
                datasets: [{
                    data: donutData,
                    backgroundColor: donutColors,
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '74%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111111',
                        titleColor: '#F5C518',
                        bodyColor: '#ffffff',
                        padding: 10,
                        cornerRadius: 6,
                        titleFont: { family: "'Outfit'", weight: '600' },
                    }
                }
            }
        });

        // ── Rango rápido ──────────────────────────────────────────────
        function setRango(tipo) {
            const hoy = new Date();
            const fmt = d => d.toISOString().split('T')[0];
            let desde, hasta = fmt(hoy);
            if (tipo === 'mes')
                desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
            else if (tipo === 'trimestre') {
                const m = Math.floor(hoy.getMonth() / 3) * 3;
                desde = fmt(new Date(hoy.getFullYear(), m, 1));
            } else {
                desde = fmt(new Date(hoy.getFullYear(), 0, 1));
            }
            document.querySelectorAll('#frmPeriodo input[type="date"]')[0].value = desde;
            document.querySelectorAll('#frmPeriodo input[type="date"]')[1].value = hasta;
            document.getElementById('frmPeriodo').submit();
        }
    </script>
@endpush