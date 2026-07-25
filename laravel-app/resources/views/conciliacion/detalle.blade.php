@extends('layouts.app')
@section('title', 'Conciliacion - Detalle Archivo')
@section('breadcrumb', 'Conciliacion / Historial / Detalle')

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

    .page-header { animation:fadeDown .5s ease-out; }

    .btn {
        display:inline-flex; align-items:center; gap:6px;
        padding:9px 16px; border-radius:10px; font-size:13px; font-weight:600;
        cursor:pointer; border:none; text-decoration:none; transition:all .15s ease;
        white-space:nowrap;
    }
    .btn-primary { background:var(--gold); color:#1c1600; font-weight:700; border:1.5px solid var(--gold); }
    .btn-primary:hover { background:var(--gold-h); border-color:var(--gold-h); transform:translateY(-1px); }
    .btn-outline { background:#fff; color:var(--gold-d); border:1.5px solid var(--gold-b); }
    .btn-outline:hover { background:var(--gold-l); border-color:var(--gold-m); }
    .btn-sm { padding:6px 12px; font-size:12px; border-radius:6px; }

    .card {
        background:#fff; border-radius:14px; border:1.5px solid var(--gold-b);
        box-shadow:0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow:hidden; margin-bottom:20px;
    }
    .card-header {
        padding:18px 24px; border-bottom:1px solid var(--gold-b);
        display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;
        background:var(--gold-l);
    }
    .card-title { font-size:15px; font-weight:700; color:var(--gold-xd); }
    .card-body { padding:20px 24px; }

    .info-grid {
        display:grid; grid-template-columns:repeat(4,1fr); gap:16px;
    }
    .info-item {
        padding:10px 0;
    }
    .info-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; margin-bottom:4px; }
    .info-value { font-size:14px; font-weight:700; color:#0f172a; }

    .badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:50px; font-size:11px; font-weight:700;
    }
    .badge-procesado   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-error       { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    .badge-default     { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

    .badge-conciliado          { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-tolerancia           { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
    .badge-manual               { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
    .badge-sin_match            { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .badge-ignorado             { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
    .badge-extornado            { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    .badge-duplicado            { background:#e2e8f0; color:#334155; border:1px solid #cbd5e1; }

    .contadores-row {
        display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;
        animation:slideUp .55s ease-out .2s both;
    }
    .contador-chip {
        display:inline-flex; align-items:center; gap:6px;
        padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;
    }
    .contador-chip.conciliado   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .contador-chip.tolerancia   { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
    .contador-chip.manual       { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
    .contador-chip.sin_match    { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .contador-chip.ignorado     { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
    .contador-chip.extornado    { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    .contador-chip.duplicado    { background:#e2e8f0; color:#334155; border:1px solid #cbd5e1; }

    .filter-inline {
        display:flex; align-items:center; gap:12px; padding:16px 24px;
        border-bottom:1px solid var(--gold-b); flex-wrap:wrap;
        animation:slideUp .55s ease-out .3s both;
    }
    .filter-inline .form-label {
        font-size:12px; font-weight:600; color:var(--gold-xd); white-space:nowrap;
    }
    .filter-inline .form-select {
        height:38px; padding:0 12px; border:1.5px solid var(--gold-b); border-radius:10px;
        font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:#0f172a;
        outline:none; transition:border-color .15s; min-width:200px;
    }
    .filter-inline .form-select:focus { border-color:var(--gold); box-shadow:0 0 0 2px var(--gold-l); }

    table { width:100%; border-collapse:collapse; }
    thead tr { background:var(--gold-l); }
    th {
        padding:12px 16px; text-align:left; font-size:10px; font-weight:700;
        text-transform:uppercase; letter-spacing:.8px; color:var(--gold-xd);
        white-space:nowrap; border-bottom:1px solid var(--gold-b);
    }
    td {
        padding:14px 16px; font-size:13.5px; border-bottom:1px solid #fef9e7;
        vertical-align:middle;
    }
    tbody tr { animation:rowIn .4s ease-out; }
    tbody tr:nth-child(1)  { animation-delay:.34s; }
    tbody tr:nth-child(2)  { animation-delay:.38s; }
    tbody tr:nth-child(3)  { animation-delay:.42s; }
    tbody tr:nth-child(4)  { animation-delay:.46s; }
    tbody tr:nth-child(5)  { animation-delay:.50s; }
    tbody tr:nth-child(6)  { animation-delay:.54s; }
    tbody tr:nth-child(7)  { animation-delay:.58s; }
    tbody tr:nth-child(8)  { animation-delay:.62s; }
    tbody tr:nth-child(9)  { animation-delay:.66s; }
    tbody tr:nth-child(10) { animation-delay:.70s; }
    tbody tr:hover { background:var(--gold-l); }
    tbody tr:last-child td { border-bottom:none; }

    .text-mono { font-family:'DM Mono',monospace; }
    .text-truncate { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    .empty-state { text-align:center; padding:48px 24px; color:#94a3b8; }
    .empty-state svg { margin:0 auto 16px; opacity:.4; }
    .empty-state p  { font-size:14px; font-weight:500; }

    .pagination-wrap { padding:16px 24px; display:flex; justify-content:center; border-top:1px solid var(--gold-b); }

    @media (max-width:900px) { .info-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:500px) { .info-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-header" style="margin-bottom:16px;">
    <div>
        <a href="{{ route('conciliacion.historial') }}" class="btn btn-outline btn-sm" style="margin-bottom:12px;">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Volver al Historial
        </a>
        <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-.4px;">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>
            Detalle de Archivo
        </h1>
        <p style="font-size:13px;color:#64748b;margin-top:4px;">Movimientos bancarios del archivo importado</p>
    </div>
</div>

{{-- Info Header Card --}}
<div class="card" style="animation:slideUp .55s ease-out .1s both;">
    <div class="card-header">
        <div>
            <div class="card-title">{{ $archivo->nombre_archivo }}</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">ID Archivo: <span class="text-mono">{{ $archivo->id_archivo }}</span></div>
        </div>
        <span class="badge {{ $archivo->estado === 'PROCESADO' ? 'badge-procesado' : ($archivo->estado === 'ERROR' ? 'badge-error' : 'badge-default') }}">
            {{ $archivo->estado === 'PROCESADO' ? 'Procesado' : ($archivo->estado === 'ERROR' ? 'Error' : $archivo->estado) }}
        </span>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Banco</div>
                <div class="info-value">{{ $archivo->banco }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Moneda</div>
                <div class="info-value"><span class="text-mono">{{ $archivo->moneda }}</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha Importacion</div>
                <div class="info-value" style="font-size:13px;">{{ \Carbon\Carbon::parse($archivo->fecha_importacion)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Registros</div>
                <div class="info-value text-mono">{{ $archivo->total_registros }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Conciliados</div>
                <div class="info-value text-mono" style="color:#059669;">{{ $archivo->total_conciliados }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Pendientes</div>
                <div class="info-value text-mono" style="color:#d97706;">{{ $archivo->total_pendientes }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Tasa Conciliacion</div>
                @php
                    $tasa = $archivo->total_registros > 0
                        ? round(($archivo->total_conciliados / $archivo->total_registros) * 100, 1)
                        : 0;
                @endphp
                <div class="info-value text-mono">{{ $tasa }}%</div>
            </div>
            <div class="info-item">
                <div class="info-label">Archivo Original</div>
                <div class="info-value text-truncate" style="font-size:12px;max-width:250px;" title="{{ $archivo->nombre_archivo }}">
                    {{ $archivo->nombre_archivo }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Contadores por Estado --}}
<div class="contadores-row">
    @php
        $chips = [
            ['key' => 'CONCILIADO',            'class' => 'conciliado', 'label' => 'Conciliado'],
            ['key' => 'CONCILIADO_TOLERANCIA',  'class' => 'tolerancia', 'label' => 'Tolerancia'],
            ['key' => 'CONCILIADO_MANUAL',      'class' => 'manual',    'label' => 'Manual'],
            ['key' => 'SIN_MATCH',              'class' => 'sin_match', 'label' => 'Sin Match'],
            ['key' => 'IGNORADO',               'class' => 'ignorado',  'label' => 'Ignorado'],
            ['key' => 'EXTORNADO',              'class' => 'extornado', 'label' => 'Extornado'],
            ['key' => 'DUPLICADO_OMITIDO',      'class' => 'duplicado', 'label' => 'Duplicado'],
        ];
    @endphp
    @foreach($chips as $chip)
        <span class="contador-chip {{ $chip['class'] }}">
            {{ $chip['label'] }}
            <span style="opacity:.7;">{{ $contadores[$chip['key']] ?? 0 }}</span>
        </span>
    @endforeach
</div>

{{-- Filtro por Estado + Tabla --}}
<div class="card" style="animation:slideUp .55s ease-out .25s both;">
    <div class="card-header">
        <h3 class="card-title">Movimientos Bancarios</h3>
    </div>

    <div class="filter-inline">
        <label class="form-label">Filtrar por Estado:</label>
        <select name="estado" class="form-select" onchange="window.location.href=this.value">
            @php
                $baseUrl = route('conciliacion.historial.detalle', $archivo->id_archivo);
                $currentEstado = request('estado', '');
            @endphp
            <option value="{{ $baseUrl }}" {{ $currentEstado === '' ? 'selected' : '' }}>Todos los estados</option>
            @foreach($estados as $val => $label)
                <option value="{{ $baseUrl }}?estado={{ $val }}" {{ $currentEstado == $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if($estadoFiltro)
            <a href="{{ route('conciliacion.historial.detalle', $archivo->id_archivo) }}" class="btn btn-outline btn-sm">Limpiar filtro</a>
        @endif
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:24px;">Fecha Operacion</th>
                    <th>Descripcion</th>
                    <th style="text-align:right;">Importe</th>
                    <th>Estado</th>
                    <th>Cliente</th>
                    <th>Factura</th>
                    <th style="padding-right:24px;">Referencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr>
                    <td style="padding-left:24px;font-family:'DM Mono',monospace;font-size:12px;">{{ \Carbon\Carbon::parse($mov->fecha_operacion)->format('d/m/Y') }}</td>
                    <td class="text-truncate" style="max-width:250px;" title="{{ $mov->descripcion }}">{{ $mov->descripcion }}</td>
                    <td class="text-mono" style="text-align:right;font-weight:600;{{ $mov->importe >= 0 ? 'color:#059669;' : 'color:#dc2626;' }}">
                        S/ {{ number_format($mov->importe, 2) }}
                    </td>
                    <td>
                        @php
                            $badgeClass = match($mov->estado_conciliacion) {
                                'CONCILIADO' => 'badge-conciliado',
                                'CONCILIADO_TOLERANCIA' => 'badge-tolerancia',
                                'CONCILIADO_MANUAL' => 'badge-manual',
                                'SIN_MATCH' => 'badge-sin_match',
                                'EXTORNADO' => 'badge-extornado',
                                'IGNORADO' => 'badge-ignorado',
                                'DUPLICADO_OMITIDO' => 'badge-duplicado',
                                default => 'badge-default',
                            };
                            $badgeLabel = match($mov->estado_conciliacion) {
                                'CONCILIADO' => 'Conciliado',
                                'CONCILIADO_TOLERANCIA' => 'Tolerancia',
                                'CONCILIADO_MANUAL' => 'Manual',
                                'SIN_MATCH' => 'Sin Match',
                                'EXTORNADO' => 'Extornado',
                                'IGNORADO' => 'Ignorado',
                                'DUPLICADO_OMITIDO' => 'Duplicado',
                                default => $mov->estado_conciliacion ?? 'N/D',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                    <td style="font-size:12px;">{{ $mov->cliente_nombre ?? '—' }}</td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--gold-d);font-weight:600;">{{ $mov->factura_numero ?? '—' }}</td>
                    <td style="padding-right:24px;font-size:11px;color:#94a3b8;">{{ $mov->referencia ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p style="font-weight:600;font-size:15px;color:#0f172a;">No se encontraron movimientos</p>
                            <p style="font-size:13px;">Prueba cambiando el filtro de estado.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($movimientos->hasPages())
    <div class="pagination-wrap">
        {{ $movimientos->links() }}
    </div>
    @endif
</div>

@stop
