@extends('layouts.app')

@section('title', 'Conciliacion Bancaria')
@section('breadcrumb', 'Conciliacion')

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
    .page-header { animation:fadeDown .5s ease-out; }

    .page-title {
        font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -.4px;
    }
    .page-desc { font-size: 13px; color: #64748b; margin-top: 4px; }

    .quick-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .qa-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        border: 1.5px solid var(--gold-b); background: #fff;
        color: var(--gold-d); text-decoration: none;
        transition: all .15s ease; white-space: nowrap;
    }
    .qa-btn:hover { background: var(--gold-l); border-color: var(--gold-m); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.06); }
    .qa-btn.primary { background: var(--gold); color: #1c1600; border-color: var(--gold); font-weight: 800; }
    .qa-btn.primary:hover { background: var(--gold-h); border-color: var(--gold-h); }

    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; animation: slideUp .55s ease-out .15s both; }
    .kpi-card {
        background: #fff; border: 1.5px solid var(--gold-b); border-radius: 14px;
        padding: 22px; position: relative; overflow: hidden;
        transition: box-shadow .2s, transform .2s;
    }
    .kpi-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 14px 14px 0 0; }
    .kpi-blue::before  { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
    .kpi-green::before { background: linear-gradient(90deg, #059669, #34d399); }
    .kpi-amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
    .kpi-purple::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }

    .kpi-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
    .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .kpi-blue   .kpi-icon { background: #dbeafe; color: #1d4ed8; }
    .kpi-green  .kpi-icon { background: #d1fae5; color: #059669; }
    .kpi-amber  .kpi-icon { background: #fef3c7; color: #d97706; }
    .kpi-purple .kpi-icon { background: #ede9fe; color: #7c3aed; }

    .kpi-value { font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1; font-family: 'DM Mono', monospace; letter-spacing: -.5px; }
    .kpi-sub { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .kpi-change { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
    .change-up   { background: #d1fae5; color: #065f46; }
    .change-down { background: #fee2e2; color: #7f1d1d; }
    .kpi-desc { font-size: 11px; color: #64748b; }

    .section-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
    .card {
        background: #fff; border-radius: 14px; border: 1.5px solid var(--gold-b);
        box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03); overflow: hidden;
    }
    .card-header {
        padding: 18px 24px; border-bottom: 1px solid var(--gold-b);
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
        background: var(--gold-l);
    }
    .card-title { font-size: 15px; font-weight: 700; color: var(--gold-xd); }
    .card-body { padding: 20px 24px; }
    .card-body.no-padding { padding: 0; }

    .banco-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gold-b); }
    .banco-row:last-child { border-bottom: none; }
    .banco-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .banco-info { flex: 1; min-width: 0; }
    .banco-name { font-size: 13px; font-weight: 700; color: #1e293b; }
    .banco-meta { font-size: 11px; color: #64748b; }
    .banco-amount { font-size: 14px; font-weight: 700; font-family: 'DM Mono', monospace; color: #0f172a; white-space: nowrap; }

    .import-list { display: flex; flex-direction: column; }
    .import-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--gold-b); text-decoration: none; color: inherit; transition: background .15s; }
    .import-item:hover { background: var(--gold-l); }
    .import-item:last-child { border-bottom: none; }
    .import-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .import-icon.bcp   { background: #dbeafe; color: #1d4ed8; }
    .import-icon.interbank { background: #d1fae5; color: #059669; }
    .import-main { flex: 1; min-width: 0; }
    .import-name { font-size: 13px; font-weight: 700; color: #1e293b; }
    .import-date { font-size: 11px; color: #64748b; }
    .import-right { text-align: right; flex-shrink: 0; }
    .import-count { font-size: 13px; font-weight: 700; font-family: 'DM Mono', monospace; }
    .import-status { font-size: 10px; font-weight: 700; }

    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700;
    }
    .badge-pendiente  { background: #fef3c7; color: #92400e; }
    .badge-procesado,
    .badge-completado { background: #d1fae5; color: #065f46; }
    .badge-error      { background: #fee2e2; color: #7f1d1d; }
    .badge-info       { background: var(--gold-l); color: var(--gold-xd); border: 1px solid var(--gold-b); }

    .status-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .status-bar-bg { flex: 1; height: 6px; background: var(--gold-l); border-radius: 10px; overflow: hidden; }
    .status-val { font-size: 12px; font-weight: 700; font-family: 'DM Mono', monospace; min-width: 40px; text-align: right; color: var(--gold-xd); }

    .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
    .empty-state svg { margin-bottom: 12px; opacity: .4; }
    .empty-state p { font-size: 13px; }

    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: none; text-decoration: none; transition: all .15s ease;
    }
    .btn-primary { background: var(--gold); color: #1c1600; font-weight: 700; border: 1.5px solid var(--gold); }
    .btn-primary:hover { background: var(--gold-h); border-color: var(--gold-h); transform: translateY(-1px); }
    .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 6px; }

    .text-mono { font-family: 'DM Mono', monospace; }

    @media (max-width: 1300px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } .section-row { grid-template-columns: 1fr; } }
    @media (max-width: 700px)  { .kpi-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>
            Conciliacion Bancaria
        </h1>
        <p class="page-desc">Panel de control de conciliacion automatica de movimientos bancarios</p>
    </div>
    <div class="quick-actions">
        <a href="{{ route('conciliacion.importar') }}" class="qa-btn primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Importar
        </a>
        <a href="{{ route('conciliacion.bandeja') }}" class="qa-btn">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Bandeja
        </a>
        <a href="{{ route('conciliacion.historial') }}" class="qa-btn">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Historial
        </a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card kpi-blue">
        <div class="kpi-header">
            <div class="kpi-label">Total Conciliado (Mes)</div>
            <div class="kpi-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        </div>
        <div class="kpi-value">{{ $kpiTotalConciliado ?? 'S/ 0.00' }}</div>
        <div class="kpi-sub"><span class="kpi-desc">{{ $kpiTotalMovimientos ?? 0 }} movimientos</span></div>
    </div>
    <div class="kpi-card kpi-green">
        <div class="kpi-header">
            <div class="kpi-label">Tasa Automatizacion</div>
            <div class="kpi-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
        </div>
        <div class="kpi-value">{{ $kpiTasaAuto ?? '0' }}%</div>
        <div class="kpi-sub"><span class="kpi-change change-up">Auto</span><span class="kpi-desc">{{ $kpiAutoCount ?? 0 }} auto / {{ $kpiManualCount ?? 0 }} manual</span></div>
    </div>
    <div class="kpi-card kpi-amber">
        <div class="kpi-header">
            <div class="kpi-label">Bancos Activos</div>
            <div class="kpi-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
        </div>
        <div class="kpi-value">{{ $kpiBancosCount ?? 2 }}</div>
        <div class="kpi-sub"><span class="kpi-desc">BCP &middot; Interbank</span></div>
    </div>
    <div class="kpi-card kpi-purple">
        <div class="kpi-header">
            <div class="kpi-label">Pendientes</div>
            <div class="kpi-icon"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div>
        </div>
        <div class="kpi-value">{{ $kpiPendientes ?? 0 }}</div>
        <div class="kpi-sub">
            @if(($kpiPendientes ?? 0) > 0)
                <span class="badge badge-pendiente">{{ $kpiPendientes ?? 0 }} pendientes</span>
            @else
                <span class="kpi-desc">Todo al dia</span>
            @endif
            <a href="{{ route('conciliacion.bandeja') }}" style="font-size:11px;color:#7c3aed;font-weight:700;text-decoration:none;">Ver bandeja</a>
        </div>
    </div>
</div>

<div class="section-row">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Monto Conciliado por Banco</h3><span class="badge badge-info">{{ $mesActual ?? date('F Y') }}</span></div>
        <div class="card-body">
            @if(!empty($montosPorBanco) && count($montosPorBanco) > 0)
                @foreach($montosPorBanco as $bancoItem)
                    <div class="banco-row">
                        <div class="banco-dot" style="background: {{ $bancoItem['color'] ?? '#3b82f6' }};"></div>
                        <div class="banco-info">
                            <div class="banco-name">{{ $bancoItem['banco'] ?? 'BCP' }}</div>
                            <div class="banco-meta">{{ $bancoItem['moneda'] ?? 'PEN' }} &middot; {{ $bancoItem['movimientos'] ?? 0 }} movs.</div>
                        </div>
                        <div class="banco-amount">{{ $bancoItem['monto'] ?? 'S/ 0.00' }}</div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <p>Sin datos de conciliacion este mes.</p>
                    <a href="{{ route('conciliacion.importar') }}" class="btn btn-primary btn-sm" style="margin-top:12px;">Importar extracto</a>
                </div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Ultimas Importaciones</h3><a href="{{ route('conciliacion.historial') }}" class="badge badge-info" style="text-decoration:none;">Ver todo</a></div>
        <div class="card-body no-padding">
            @if(!empty($ultimasImportaciones) && count($ultimasImportaciones) > 0)
                <div class="import-list" style="padding: 0 24px;">
                    @foreach($ultimasImportaciones as $imp)
                        <a href="{{ route('conciliacion.historial.detalle', ['id' => $imp['id'] ?? 0]) }}" class="import-item">
                            <div class="import-icon {{ strtolower($imp['banco'] ?? 'bcp') }}">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="import-main">
                                <div class="import-name">{{ $imp['nombre'] ?? 'archivo.xlsx' }}</div>
                                <div class="import-date">{{ $imp['fecha'] ?? '' }}</div>
                            </div>
                            <div class="import-right">
                                <div class="import-count">{{ $imp['total_registros'] ?? 0 }}</div>
                                <span class="badge badge-{{ strtolower($imp['estado'] ?? 'pendiente') }}">{{ $imp['estado'] ?? 'PENDIENTE' }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p>No hay importaciones aun.</p>
                    <a href="{{ route('conciliacion.importar') }}" class="btn btn-primary btn-sm" style="margin-top:12px;">Importar extracto</a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="section-row" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Distribucion de Estados</h3></div>
        <div class="card-body">
            @if(!empty($distribucionEstados) && count($distribucionEstados) > 0)
                @foreach($distribucionEstados as $estado)
                    @php $pct = ($estado['total'] ?? 0) > 0 && ($kpiTotalMovimientos ?? 1) > 0 ? round(($estado['total'] / max($kpiTotalMovimientos, 1)) * 100) : 0; @endphp
                    <div class="status-item">
                        <div style="width:10px;height:10px;border-radius:50%;background:{{ $estado['color'] ?? '#94a3b8' }};flex-shrink:0;"></div>
                        <span style="font-size:12px;color:#64748b;min-width:90px;">{{ $estado['estado'] ?? '--' }}</span>
                        <div class="status-bar-bg"><div style="height:100%;border-radius:10px;width:{{ $pct }}%;background:{{ $estado['color'] ?? '#94a3b8' }};"></div></div>
                        <span class="status-val">{{ $estado['total'] ?? 0 }}</span>
                    </div>
                @endforeach
            @else
                <div class="empty-state"><p>Sin datos de distribucion.</p></div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Resumen Mensual</h3></div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gold-b);"><span style="font-size:13px;color:#64748b;">Archivos Procesados</span><span class="text-mono" style="font-weight:700;">{{ $kpiArchivosMes ?? 0 }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gold-b);"><span style="font-size:13px;color:#64748b;">Total Movimientos</span><span class="text-mono" style="font-weight:700;">{{ $kpiTotalMovimientos ?? 0 }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gold-b);"><span style="font-size:13px;color:#64748b;">Conciliados Auto</span><span class="text-mono" style="font-weight:700;color:#059669;">{{ $kpiAutoCount ?? 0 }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;"><span style="font-size:13px;color:#64748b;">Pendientes Manual</span><span class="text-mono" style="font-weight:700;color:#d97706;">{{ $kpiPendientes ?? 0 }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
