@extends('layouts.app')
@section('title', 'Conciliacion - Auditoria')
@section('breadcrumb', 'Conciliacion / Auditoria de Cambios')

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
    tbody tr:nth-child(1)  { animation-delay:.28s; }
    tbody tr:nth-child(2)  { animation-delay:.32s; }
    tbody tr:nth-child(3)  { animation-delay:.36s; }
    tbody tr:nth-child(4)  { animation-delay:.40s; }
    tbody tr:nth-child(5)  { animation-delay:.44s; }
    tbody tr:nth-child(6)  { animation-delay:.48s; }
    tbody tr:nth-child(7)  { animation-delay:.52s; }
    tbody tr:nth-child(8)  { animation-delay:.56s; }
    tbody tr:nth-child(9)  { animation-delay:.60s; }
    tbody tr:nth-child(10) { animation-delay:.64s; }
    tbody tr:hover { background:var(--gold-l); }
    tbody tr:last-child td { border-bottom:none; }

    .badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:50px; font-size:11px; font-weight:700;
    }
    .badge-conciliado          { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
    .badge-tolerancia           { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
    .badge-manual               { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
    .badge-sin_match            { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
    .badge-extornado            { background:#fee2e2; color:#7f1d1d; border:1px solid #fca5a5; }
    .badge-ignorado             { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
    .badge-duplicado            { background:#e2e8f0; color:#334155; border:1px solid #cbd5e1; }
    .badge-anterior             { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }

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

    .transition-arrow {
        display:inline-flex; align-items:center; color:#94a3b8; margin:0 6px;
        font-size:12px;
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">
            <span style="display:inline-block;width:28px;height:3px;background:var(--gold);border-radius:2px;margin-right:8px;vertical-align:middle;margin-bottom:3px;"></span>
            Auditoria de Movimientos
        </h1>
        <p class="page-desc">Historial de cambios de estado en la conciliacion</p>
    </div>
</div>

{{-- Filtros --}}
<div class="card card-filter">
    <div style="padding:20px 24px;">
        <form method="GET" class="filter-row">
            <div>
                <label class="form-label">Usuario</label>
                <select name="usuario_id" class="form-select">
                    <option value="">Todos los usuarios</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id_usuario }}" {{ $usuarioId == $u->id_usuario ? 'selected' : '' }}>
                            {{ $u->nombre }} {{ $u->apellido }}
                        </option>
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
            <div>
                <label class="form-label">Accion (Nuevo Estado)</label>
                <select name="accion" class="form-select">
                    <option value="">Todas las acciones</option>
                    @foreach($acciones as $a)
                        <option value="{{ $a }}" {{ $accion == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('conciliacion.auditoria') }}" class="btn btn-outline btn-sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla de Auditoria --}}
<div class="card card-table">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:24px;">Fecha</th>
                    <th>Usuario</th>
                    <th>Movimiento ID</th>
                    <th>Banco</th>
                    <th>Descripcion</th>
                    <th style="text-align:right;">Importe</th>
                    <th style="text-align:center;">Transicion</th>
                    <th style="padding-right:24px;">Motivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $reg)
                <tr>
                    <td style="padding-left:24px;font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($reg->fecha_transicion)->format('d/m/Y H:i') }}
                    </td>
                    <td style="font-weight:600;">{{ $reg->usuario_nombre }} {{ $reg->usuario_apellido }}</td>
                    <td>
                        <span class="text-mono" style="font-size:12px;color:#64748b;">#{{ $reg->id_movimiento }}</span>
                    </td>
                    <td>{{ $reg->banco }}</td>
                    <td class="text-truncate" style="max-width:200px;" title="{{ $reg->descripcion }}">{{ $reg->descripcion }}</td>
                    <td class="text-mono" style="text-align:right;font-weight:600;">S/ {{ number_format($reg->importe, 2) }}</td>
                    <td style="text-align:center;">
                        @php
                            $estadoAnt = $reg->estado_anterior;
                            $estadoNuevo = $reg->estado_nuevo;

                            $nuevoClass = match($estadoNuevo) {
                                'CONCILIADO' => 'badge-conciliado',
                                'CONCILIADO_TOLERANCIA' => 'badge-tolerancia',
                                'CONCILIADO_MANUAL' => 'badge-manual',
                                'SIN_MATCH' => 'badge-sin_match',
                                'EXTORNADO' => 'badge-extornado',
                                'IGNORADO' => 'badge-ignorado',
                                'DUPLICADO_OMITIDO' => 'badge-duplicado',
                                default => 'badge-anterior',
                            };

                            $nuevoLabel = match($estadoNuevo) {
                                'CONCILIADO' => 'Conciliado',
                                'CONCILIADO_TOLERANCIA' => 'Tolerancia',
                                'CONCILIADO_MANUAL' => 'Manual',
                                'SIN_MATCH' => 'Sin Match',
                                'EXTORNADO' => 'Extornado',
                                'IGNORADO' => 'Ignorado',
                                'DUPLICADO_OMITIDO' => 'Duplicado',
                                default => $estadoNuevo,
                            };
                        @endphp
                        <span class="badge badge-anterior">{{ $estadoAnt ?? '—' }}</span>
                        <span class="transition-arrow">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </span>
                        <span class="badge {{ $nuevoClass }}">{{ $nuevoLabel }}</span>
                    </td>
                    <td style="padding-right:24px;font-size:12px;color:#64748b;" class="text-truncate" title="{{ $reg->motivo }}">
                        {{ $reg->motivo ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <p style="font-weight:600;font-size:15px;color:#0f172a;">No se encontraron registros de auditoria</p>
                            <p style="font-size:13px;">Ajusta los filtros para ver el historial de cambios.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($registros->hasPages())
    <div class="pagination-wrap">
        {{ $registros->links() }}
    </div>
    @endif
</div>

@stop
