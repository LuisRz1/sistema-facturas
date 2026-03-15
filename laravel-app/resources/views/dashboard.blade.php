@extends('layouts.app')
@section('title', 'Panel Principal')
@section('breadcrumb', 'Panel Principal')

@push('styles')
    <style>
        /* ── PERÍODO SELECTOR ─────────────────────────────────────── */
        .period-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding: 14px 20px;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent);
        }
        .period-bar label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .period-bar input[type="date"] {
            height: 38px;
            padding: 0 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            background: #fff;
            color: var(--text-primary);
            outline: none;
            transition: border-color .15s;
            cursor: pointer;
        }
        .period-bar input[type="date"]:focus { border-color: var(--accent); }
        .period-bar .sep { color: var(--text-muted); font-weight: 700; }
        .period-bar .period-info {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: auto;
        }
        .period-bar .period-info strong { color: var(--text-primary); }

        /* ── KPI CARDS ────────────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }
        .kpi-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 22px 22px 18px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
            cursor: default;
        }
        .kpi-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-blue::before  { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
        .kpi-green::before { background: linear-gradient(90deg, #059669, #34d399); }
        .kpi-amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .kpi-red::before   { background: linear-gradient(90deg, #dc2626, #f87171); }

        .kpi-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .kpi-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
        }
        .kpi-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kpi-blue  .kpi-icon { background: #dbeafe; color: #1d4ed8; }
        .kpi-green .kpi-icon { background: #d1fae5; color: #059669; }
        .kpi-amber .kpi-icon { background: #fef3c7; color: #d97706; }
        .kpi-red   .kpi-icon { background: #fee2e2; color: #dc2626; }
        .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            font-family: 'DM Mono', monospace;
            letter-spacing: -.5px;
        }
        .kpi-sub {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }
        .kpi-change {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }
        .change-up   { background: #d1fae5; color: #065f46; }
        .change-down { background: #fee2e2; color: #7f1d1d; }
        .kpi-desc { font-size: 11px; color: var(--text-muted); }

        /* ── CHARTS ROW ───────────────────────────────────────────── */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }

        /* ── BOTTOM ROW ───────────────────────────────────────────── */
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        /* ── CHART WRAPPER ────────────────────────────────────────── */
        .chart-wrap {
            position: relative;
            height: 280px;
        }

        /* ── STATUS ROW ───────────────────────────────────────────── */
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
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .status-bar-bg {
            flex: 1;
            height: 6px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }
        .status-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .status-label { font-size: 12px; color: var(--text-muted); min-width: 70px; }
        .status-val   { font-size: 12px; font-weight: 700; font-family: 'DM Mono', monospace; min-width: 50px; text-align: right; }

        /* ── TOP CLIENTS ──────────────────────────────────────────── */
        .client-rank {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .client-rank-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .rank-num {
            width: 22px; height: 22px;
            border-radius: 6px;
            background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 800;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        .rank-num.top1 { background: #fef3c7; color: #92400e; }
        .rank-num.top2 { background: #f3f4f6; color: #374151; }
        .rank-num.top3 { background: #fef3c7; color: #b45309; }
        .rank-name {
            flex: 1;
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .rank-amount {
            font-size: 12px;
            font-weight: 700;
            font-family: 'DM Mono', monospace;
            color: var(--text-primary);
            white-space: nowrap;
        }
        .rank-count {
            font-size: 10px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* ── ÚLTIMAS FACTURAS ─────────────────────────────────────── */
        .recent-list {
            display: flex;
            flex-direction: column;
        }
        .recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #f1f5f9;
            transition: background .15s;
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-icon {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--text-muted);
        }
        .recent-main { flex: 1; min-width: 0; }
        .recent-num  { font-size: 12.5px; font-weight: 700; font-family: 'DM Mono', monospace; color: var(--text-primary); }
        .recent-desc { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .recent-right { text-align: right; flex-shrink: 0; }
        .recent-amount { font-size: 13px; font-weight: 700; font-family: 'DM Mono', monospace; }
        .recent-date { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

        /* ── EMPTY STATE ──────────────────────────────────────────── */
        .dash-empty {
            text-align: center;
            padding: 32px 16px;
            color: var(--text-muted);
        }
        .dash-empty svg { margin: 0 auto 10px; opacity: .3; }
        .dash-empty p { font-size: 13px; }

        @media (max-width: 1300px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr; }
            .bottom-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 700px) {
            .kpi-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title">Panel Principal</h1>
            <p class="page-desc">Resumen financiero del período seleccionado — Consorcio Rodriguez Caballero S.A.C.</p>
        </div>
        <a href="{{ route('facturas.index', ['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}"
           class="btn btn-outline">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('mes')">Este mes</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('trimestre')">Trimestre</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('anio')">Este año</button>
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
        $totalFacturado  = $kpis->total_facturado  ?? 0;
        $totalCobrado    = $kpis->total_cobrado    ?? 0;
        $totalPorCobrar  = $kpis->total_por_cobrar ?? 0;
        $totalRecaudacion= $kpis->total_recaudacion?? 0;
        $pctCobro = $totalFacturado > 0 ? round(($totalCobrado / $totalFacturado) * 100, 1) : 0;
    @endphp
    <div class="kpi-grid">
        <div class="kpi-card kpi-blue">
            <div class="kpi-header">
                <div>
                    <div class="kpi-label">Total Facturado</div>
                </div>
                <div class="kpi-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalFacturado, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">{{ $kpis->total_facturas }} facturas en el período</span>
            </div>
        </div>

        <div class="kpi-card kpi-green">
            <div class="kpi-header">
                <div>
                    <div class="kpi-label">Cobrado (Abonos)</div>
                </div>
                <div class="kpi-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalCobrado, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-change change-up">↑ {{ $pctCobro }}%</span>
                <span class="kpi-desc">de lo facturado cobrado</span>
            </div>
        </div>

        <div class="kpi-card kpi-amber">
            <div class="kpi-header">
                <div>
                    <div class="kpi-label">Cuentas por Cobrar</div>
                </div>
                <div class="kpi-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalPorCobrar, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">{{ $kpis->count_pendientes }} facturas pendientes · {{ $kpis->count_vencidas }} vencidas</span>
            </div>
        </div>

        <div class="kpi-card kpi-red">
            <div class="kpi-header">
                <div>
                    <div class="kpi-label">Fondo Recaudación</div>
                </div>
                <div class="kpi-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
            <div class="kpi-value">S/ {{ number_format($totalRecaudacion, 2) }}</div>
            <div class="kpi-sub">
                <span class="kpi-desc">Detracciones + Retenciones del período</span>
            </div>
        </div>
    </div>

    {{-- ── CHARTS ROW ── --}}
    <div class="charts-row">
        {{-- Bar chart tendencias --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Tendencias de Facturación</div>
                    <div class="card-desc">Monto mensual facturado (en Soles)</div>
                </div>
                <a href="{{ route('reportes.index') }}" class="btn btn-outline btn-sm">Ver reportes</a>
            </div>
            <div style="padding: 16px 24px 24px;">
                <div class="chart-wrap">
                    <canvas id="chartTendencias"></canvas>
                </div>
            </div>
        </div>

        {{-- Estado donut + bars --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Estado de Cobranza</div>
                    <div class="card-desc">Distribución del período</div>
                </div>
            </div>
            <div style="padding: 16px 24px 8px;">
                <div style="display:flex;justify-content:center;margin-bottom:20px;">
                    <div style="width:180px;height:180px;position:relative;">
                        <canvas id="chartDonut"></canvas>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                            <div style="font-size:20px;font-weight:800;font-family:'DM Mono',monospace;">{{ $kpis->total_facturas }}</div>
                            <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">facturas</div>
                        </div>
                    </div>
                </div>
                @php
                    $statusMap = [
                        'PAGADA'    => ['label' => 'Pagadas',   'color' => '#22c55e', 'bg' => '#d1fae5'],
                        'PENDIENTE' => ['label' => 'Pendientes','color' => '#f59e0b', 'bg' => '#fef3c7'],
                        'VENCIDA'   => ['label' => 'Vencidas',  'color' => '#ef4444', 'bg' => '#fee2e2'],
                        'POR_VENCER'=> ['label' => 'Por vencer','color' => '#f97316', 'bg' => '#ffedd5'],
                        'ANULADA'   => ['label' => 'Anuladas',  'color' => '#94a3b8', 'bg' => '#f1f5f9'],
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
                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
                                    <div style="margin-top:4px;height:4px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                                        <div style="height:100%;background:{{ $i===0?'#1d4ed8':($i===1?'#64748b':'#94a3b8') }};width:{{ $maxTotal > 0 ? round(($c->total/$maxTotal)*100) : 0 }}%;border-radius:10px;"></div>
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
                        <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="recent-main">
                                    <div class="recent-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</div>
                                    <div class="recent-desc" title="{{ $f->razon_social }}">{{ $f->razon_social }}</div>
                                </div>
                                <div class="recent-right">
                                    <div class="recent-amount">S/ {{ number_format($f->importe_total, 2) }}</div>
                                    <div style="margin-top:3px;">
                            <span class="badge {{ $badgeMap[$f->estado] ?? 'badge-pendiente' }}" style="font-size:9px;padding:2px 7px;">
                                {{ str_replace('_',' ',$f->estado) }}
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
        // ── Datos PHP → JS ────────────────────────────────────────────
        const tendenciaData = @json($tendencia);
        const porEstadoData = @json($porEstado);

        // ── Helpers ───────────────────────────────────────────────────
        function mesLabel(mes) {
            const [y, m] = mes.split('-');
            const names = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return names[parseInt(m) - 1] + ' ' + y.slice(2);
        }

        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.defaults.color = '#64748b';

        // ── BAR CHART: Tendencias ─────────────────────────────────────
        const maxVal = Math.max(...tendenciaData.map(d => d.total));
        const barColors = tendenciaData.map(d => d.total === maxVal ? '#1d4ed8' : '#93c5fd');

        new Chart(document.getElementById('chartTendencias'), {
            type: 'bar',
            data: {
                labels: tendenciaData.map(d => mesLabel(d.mes)),
                datasets: [{
                    data: tendenciaData.map(d => d.total),
                    backgroundColor: barColors,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#fff',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: ctx => ' S/ ' + ctx.parsed.y.toLocaleString('es-PE', {minimumFractionDigits:2})
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        border: { display: false, dash: [4,4] },
                        ticks: {
                            font: { size: 11 },
                            callback: v => 'S/ ' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                        }
                    }
                }
            }
        });

        // ── DONUT CHART: Estados ──────────────────────────────────────
        const statusColors = {
            'PAGADA':     '#22c55e',
            'PENDIENTE':  '#f59e0b',
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
                labels: donutLabels.map(l => l.replace('_',' ')),
                datasets: [{
                    data: donutData,
                    backgroundColor: donutColors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            }
        });

        // ── Rango rápido ──────────────────────────────────────────────
        function setRango(tipo) {
            const hoy = new Date();
            const fmt = d => d.toISOString().split('T')[0];
            let desde, hasta = fmt(hoy);
            if (tipo === 'mes')       desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
            else if (tipo === 'trimestre') { const m = Math.floor(hoy.getMonth()/3)*3; desde = fmt(new Date(hoy.getFullYear(), m, 1)); }
            else                      desde = fmt(new Date(hoy.getFullYear(), 0, 1));
            document.querySelectorAll('#frmPeriodo input[type="date"]')[0].value = desde;
            document.querySelectorAll('#frmPeriodo input[type="date"]')[1].value = hasta;
            document.getElementById('frmPeriodo').submit();
        }
    </script>
@endpush
