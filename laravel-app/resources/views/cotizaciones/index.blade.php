@extends('layouts.app')
@section('title', 'Cotizaciones / Valorizaciones')
@section('breadcrumb', 'Cotizaciones')

@push('styles')
    <style>
        :root { --gold:#f5c842; --gold-h:#e8b820; --gold-l:#fffbeb; --gold-b:#ead96a; --gold-m:#d4a017; --gold-d:#9a6e10; }
        @keyframes fadeDown  { from{opacity:0;transform:translateY(-12px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideUp   { from{opacity:0;transform:translateY(16px)}  to{opacity:1;transform:translateY(0)} }
        @keyframes rowIn     { from{opacity:0;transform:translateX(-8px)}  to{opacity:1;transform:translateX(0)} }

        .tipo-badge {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:20px; font-size:10px; font-weight:800;
            text-transform:uppercase; letter-spacing:.5px;
        }
        .tipo-maquinaria { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .tipo-agregado   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }

        .kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; animation:fadeDown .5s ease-out; }
        .kpi-box { background:#fff; border:1.5px solid var(--gold-b); border-radius:14px; padding:18px 20px; }
        .kpi-box .kpi-val { font-size:22px; font-weight:800; font-family:'DM Mono',monospace; color:var(--text-primary); }
        .kpi-box .kpi-lbl { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; margin-top:2px; }

        .filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:14px 24px; border-bottom:1px solid var(--border); }
        .filter-bar input, .filter-bar select { height:38px; border:1.5px solid var(--gold-b); border-radius:10px; font-size:13px; background:#fff; outline:none; padding:0 12px; transition:border-color .15s; }
        .filter-bar input:focus, .filter-bar select:focus { border-color:var(--gold-m); box-shadow:0 0 0 3px #f5c84220; }
        .search-wrap { position:relative; flex:1; min-width:220px; }
        .search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--gold-m); pointer-events:none; }
        .search-wrap input { padding-left:34px !important; width:100%; }

        #cotTable tbody tr { animation:rowIn .4s ease-out; }
        .action-btn { width:30px; height:30px; border-radius:7px; border:1px solid var(--gold-b); background:#fff; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; color:var(--text-muted); }
        .action-btn:hover { background:var(--gold-l); border-color:var(--gold-m); color:var(--gold-d); }
        .action-btn.danger:hover { background:#fee2e2; border-color:#fca5a5; color:#dc2626; }

        .empty-state { text-align:center; padding:48px 24px; color:var(--text-muted); }
    </style>
@endpush

@section('content')
    <div class="page-header" style="animation:fadeDown .5s ease-out;">

        <div>
            <h1 class="page-title">Cotizaciones / Valorizaciones</h1>
            <p class="page-desc">Gestiona las valorizaciones de maquinaria y agregados para clientes.</p>
        </div>
        <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nueva Cotización
        </a>


    </div>


    @php
        $totalMaq   = $cotizaciones->where('tipo_cotizacion','MAQUINARIA')->count();
        $totalAgr   = $cotizaciones->where('tipo_cotizacion','AGREGADO')->count();
        $sumaTotal  = $cotizaciones->sum('total');
    @endphp

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-val">{{ $cotizaciones->count() }}</div>
            <div class="kpi-lbl">Total Cotizaciones</div>
        </div>
        <div class="kpi-box" style="border-color:#fde68a;">
            <div class="kpi-val" style="color:#92400e;">{{ $totalMaq }}</div>
            <div class="kpi-lbl">Maquinaria</div>
        </div>
        <div class="kpi-box" style="border-color:#a7f3d0;">
            <div class="kpi-val" style="color:#065f46;">{{ $totalAgr }}</div>
            <div class="kpi-lbl">Agregados</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-val" style="font-size:18px;">S/ {{ number_format($sumaTotal,2) }}</div>
            <div class="kpi-lbl">Monto Total</div>
        </div>
    </div>


    <div class="card" style="animation:slideUp .5s .1s ease-out both;">
        <div class="card-header">
            <div>
                <div class="card-title">Listado de Cotizaciones</div>
                <div class="card-desc">{{ $cotizaciones->count() }} registros</div>
            </div>
        </div>


        <form method="GET" action="{{ route('cotizaciones.index') }}">
            <div class="filter-bar">
                <div class="search-wrap">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar obra, valorización, cliente…">
                </div>
                <select name="tipo" style="min-width:160px;">
                    <option value="">Todos los tipos</option>
                    <option value="MAQUINARIA" {{ $tipo === 'MAQUINARIA' ? 'selected' : '' }}>Maquinaria</option>
                    <option value="AGREGADO"   {{ $tipo === 'AGREGADO'   ? 'selected' : '' }}>Agregados</option>
                </select>
                <select name="id_cliente" style="min-width:200px;">
                    <option value="">Todos los clientes</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id_cliente }}" {{ $cliente == $c->id_cliente ? 'selected' : '' }}>
                            {{ $c->razon_social }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="fecha_desde" value="{{ $desde }}" style="width:145px;">
                <input type="date" name="fecha_hasta" value="{{ $hasta }}" style="width:145px;">
                <button type="submit" class="btn btn-outline" style="border-color:var(--gold-b);color:var(--gold-d);">Filtrar</button>
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>

        </form>

        <div class="filter-bar" style="justify-content:flex-end;border-top:1px solid var(--border);">
            <form method="POST" action="{{ route('cotizaciones.export-excel-bulk') }}">
                @csrf
                <input type="hidden" name="search" value="{{ $search }}">
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <input type="hidden" name="id_cliente" value="{{ $cliente }}">
                <input type="hidden" name="fecha_desde" value="{{ $desde }}">
                <input type="hidden" name="fecha_hasta" value="{{ $hasta }}">
                <button type="submit" class="btn btn-primary">
                    Exportar Excel (según filtros)
                </button>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table id="cotTable">
                <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Valorización</th>
                    <th>Empresa</th>
                    <th>Obra</th>
                    <th>Período</th>
                    <th style="text-align:right;">Base</th>
                    <th style="text-align:right;">IGV</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse($cotizaciones as $cot)
                    <tr>
                        <td>
                            <span class="tipo-badge {{ $cot->tipo_cotizacion === 'MAQUINARIA' ? 'tipo-maquinaria' : 'tipo-agregado' }}">
                                {{ $cot->tipo_cotizacion === 'MAQUINARIA' ? '⚙' : '🪨' }}
                                {{ $cot->tipo_cotizacion }}
                            </span>
                        </td>
                        <td style="font-family:'DM Mono',monospace;font-weight:700;font-size:12px;color:var(--gold-m);">
                            {{ $cot->numero_valorizacion }}
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $cot->razon_social }}</div>
                            <div style="font-size:10px;color:var(--text-muted);font-family:'DM Mono',monospace;">{{ $cot->ruc }}</div>
                        </td>
                        <td style="font-size:13px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $cot->obra }}
                        </td>
                        <td style="font-size:11px;color:var(--text-muted);">
                            {{ \Carbon\Carbon::parse($cot->periodo_inicio)->format('d/m/Y') }}
                            <span style="color:var(--gold-m);">→</span>
                            {{ \Carbon\Carbon::parse($cot->periodo_fin)->format('d/m/Y') }}
                        </td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted);">
                            S/ {{ number_format($cot->base_sin_igv,2) }}
                        </td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;font-size:12px;color:#d97706;">
                            S/ {{ number_format($cot->total_igv,2) }}
                        </td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;font-size:13px;font-weight:700;color:var(--text-primary);">
                            S/ {{ number_format($cot->total,2) }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end;">
                                <a href="{{ route('cotizaciones.show', $cot->id_cotizacion) }}" class="action-btn" title="Ver / Gestionar filas">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('cotizaciones.print', $cot->id_cotizacion) }}" class="action-btn" title="Imprimir" target="_blank">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                <button type="button" class="action-btn danger" title="Eliminar"
                                        onclick="confirmarEliminar({{ $cot->id_cotizacion }}, '{{ addslashes($cot->numero_valorizacion) }}')">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">
                            <div class="empty-state">
                                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="font-weight:600;font-size:15px;color:var(--text-primary);margin-top:10px;">Sin cotizaciones registradas</p>
                                <p style="font-size:13px;margin-top:4px;">Crea tu primera cotización usando el botón superior.</p>
                            </div>
                        </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal confirmar eliminar --}}
    <div class="modal-overlay" id="modalEliminar">
        <div class="modal" style="max-width:420px;">
            <div class="modal-header" style="background:#7f1d1d;">
                <h2>Eliminar Cotización</h2>
                <p id="modalEliminarDesc">¿Estás seguro?</p>
                <button onclick="document.getElementById('modalEliminar').classList.remove('open')"
                        style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p style="font-size:14px;color:var(--text-primary);">Esta acción desactivará la cotización y no podrá recuperarse fácilmente.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="document.getElementById('modalEliminar').classList.remove('open')">Cancelar</button>
                <button class="btn" style="background:#dc2626;color:#fff;" id="btnConfirmarEliminar">Eliminar</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;padding:13px 20px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);transform:translateY(80px);opacity:0;transition:all .3s;max-width:380px;">
        <span id="toastTxt"></span>
    </div>
@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        function showToast(msg, ok = true) {
            const t = document.getElementById('toast');
            document.getElementById('toastTxt').textContent = msg;
            t.style.cssText += ok
                ? ';background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;'
                : ';background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5;';
            t.style.transform = 'translateY(0)';
            t.style.opacity   = '1';
            setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3500);
        }

        let eliminarId = null;
        function confirmarEliminar(id, num) {
            eliminarId = id;
            document.getElementById('modalEliminarDesc').textContent = `¿Eliminar valorización "${num}"?`;
            document.getElementById('modalEliminar').classList.add('open');
        }
        document.getElementById('btnConfirmarEliminar').addEventListener('click', async () => {
            if (!eliminarId) return;
            const res  = await fetch(`/cotizaciones/${eliminarId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            document.getElementById('modalEliminar').classList.remove('open');
            if (data.success) { showToast('Cotización eliminada.'); setTimeout(() => location.reload(), 1200); }
            else showToast(data.message || 'Error', false);
        });
        document.getElementById('modalEliminar').addEventListener('click', e => {
            if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
        });
    </script>
@endpush
