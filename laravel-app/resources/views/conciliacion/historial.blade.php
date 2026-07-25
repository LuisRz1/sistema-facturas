@extends('layouts.app')
@section('title', 'Conciliacion - Historial')
@section('breadcrumb', 'Conciliacion / Historial de Archivos')

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

    .page-header { animation:fadeDown .5s ease-out; }
    .page-title  { font-size:22px; font-weight:800; color:#0f172a; letter-spacing:-.4px; }
    .page-desc   { font-size:13px; color:#64748b; margin-top:4px; }

    .filter-row {
        display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;
        animation:slideUp .55s ease-out .15s both;
    }
    .filter-row .form-label {
        font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
        color:var(--gold-xd); margin-bottom:4px; display:block;
    }
    .filter-row .form-select,
    .filter-row .form-input {
        height:40px; padding:0 12px; border:1.5px solid var(--gold-b); border-radius:10px;
        font-size:13px; font-family:'DM Sans',sans-serif; background:#fff; color:#0f172a;
        outline:none; transition:border-color .15s; min-width:160px;
    }
    .filter-row .form-select:focus,
    .filter-row .form-input:focus { border-color:var(--gold); box-shadow:0 0 0 2px var(--gold-l); }

    .card {
        background:#fff; border-radius:14px; border:1.5px solid var(--gold-b);
        box-shadow:0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
        overflow:hidden; margin-bottom:20px;
    }
    .card-filter { animation:slideUp .55s ease-out .15s both; }
    .card-table   { animation:slideUp .55s ease-out .25s both; }

    .table-wrap { overflow-x:auto; }
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
    tbody tr:nth-child(1)  { animation-delay:.18s; }
    tbody tr:nth-child(2)  { animation-delay:.22s; }
    tbody tr:nth-child(3)  { animation-delay:.26s; }
    tbody tr:nth-child(4)  { animation-delay:.30s; }
    tbody tr:nth-child(5)  { animation-delay:.34s; }
    tbody tr:nth-child(6)  { animation-delay:.38s; }
    tbody tr:nth-child(7)  { animation-delay:.42s; }
    tbody tr:nth-child(8)  { animation-delay:.46s; }
    tbody tr:nth-child(9)  { animation-delay:.50s; }
    tbody tr:nth-child(10) { animation-delay:.54s; }
    tbody tr:hover { background:var(--gold-l); }
    tbody tr:last-child td { border-bottom:none; }

    .badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:50px; font-size:11px; font-weight:700;
    }
    .badge-procesado   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-procesando   { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
    .badge-error        { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    .badge-default      { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

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

    .text-mono { font-family:'DM Mono',monospace; }
    .text-truncate { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    .empty-state { text-align:center; padding:48px 24px; color:#94a3b8; }
    .empty-state svg { margin:0 auto 16px; opacity:.4; }
    .empty-state p  { font-size:14px; font-weight:500; }

    .pagination-wrap { padding:16px 24px; display:flex; justify-content:center; border-top:1px solid var(--gold-b); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>
            Historial de Importaciones
        </h1>
        <p class="page-desc">Archivos de movimientos bancarios importados</p>
    </div>
</div>

<div class="card card-filter">
    <div style="padding:20px 24px;">
        <form method="GET" class="filter-row">
            <div>
                <label class="form-label">Banco</label>
                <select name="banco" class="form-select">
                    <option value="">Todos los bancos</option>
                    @foreach($bancos as $b)
                        <option value="{{ $b }}" {{ $banco == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-input" value="{{ $fechaDesde }}" style="min-width:150px;">
            </div>
            <div>
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-input" value="{{ $fechaHasta }}" style="min-width:150px;">
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('conciliacion.historial') }}" class="btn btn-outline btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-table">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:24px;">Banco</th>
                    <th>Moneda</th>
                    <th>Nombre Archivo</th>
                    <th>Fecha Importacion</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Total Registros</th>
                    <th style="text-align:right;">Conciliados</th>
                    <th style="text-align:right;">Pendientes</th>
                    <th style="padding-right:24px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($archivos as $arch)
                <tr>
                    <td style="padding-left:24px;font-weight:600;">{{ $arch->banco }}</td>
                    <td><span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:var(--gold-d);">{{ $arch->moneda }}</span></td>
                    <td class="text-truncate" style="max-width:220px;" title="{{ $arch->nombre_archivo }}">{{ $arch->nombre_archivo }}</td>
                    <td style="font-size:12px;color:#64748b;">{{ \Carbon\Carbon::parse($arch->fecha_importacion)->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $estadoClass = match($arch->estado) {
                                'PROCESADO' => 'badge-procesado',
                                'PROCESANDO' => 'badge-procesando',
                                'ERROR' => 'badge-error',
                                default => 'badge-default',
                            };
                            $estadoLabel = match($arch->estado) {
                                'PROCESADO' => 'Procesado',
                                'PROCESANDO' => 'Procesando',
                                'ERROR' => 'Error',
                                default => $arch->estado,
                            };
                        @endphp
                        <span class="badge {{ $estadoClass }}">{{ $estadoLabel }}</span>
                    </td>
                    <td class="text-mono" style="text-align:right;">{{ $arch->total_registros }}</td>
                    <td class="text-mono" style="text-align:right;color:#059669;font-weight:600;">{{ $arch->total_conciliados }}</td>
                    <td class="text-mono" style="text-align:right;color:#d97706;font-weight:600;">{{ $arch->total_pendientes }}</td>
                    <td style="padding-right:24px;">
                        <a href="{{ route('conciliacion.historial.detalle', $arch->id_archivo) }}" class="btn btn-outline btn-sm">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Ver Detalle
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p style="font-weight:600;font-size:15px;color:#0f172a;">No se encontraron archivos importados</p>
                            <p style="font-size:13px;">Ajusta los filtros o importa un nuevo extracto bancario.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($archivos->hasPages())
    <div class="pagination-wrap">
        {{ $archivos->links() }}
    </div>
    @endif
</div>

@stop
