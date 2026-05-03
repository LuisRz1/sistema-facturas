@extends('layouts.app')

@section('title', 'Historial de Importaciones')

@push('styles')
<style>
    body { background: #fdf8ec !important; }
    .page-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .page-header-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: #fef9c3;
        display: flex; align-items: center; justify-content: center;
        color: #92400e;
        flex-shrink: 0;
    }
    .page-title   { font-size: 20px; font-weight: 700; color: var(--text-primary); }
    .page-subtitle{ font-size: 13px; color: var(--text-secondary); margin-top: 2px; }

    .sinc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sinc-table thead tr { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .sinc-table th {
        padding: 11px 14px; text-align: left;
        font-weight: 600; color: #475569; white-space: nowrap;
    }
    .sinc-table th.center, .sinc-table td.center { text-align: center; }
    .sinc-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .sinc-table tbody tr:hover { background: #fafbff; }
    .sinc-table tbody tr.inactivo { opacity: .65; background: #fafafa; }

    .badge-pill {
        display: inline-block;
        padding: 2px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .badge-completado { background: #d1fae5; color: #065f46; }
    .badge-errores    { background: #fef3c7; color: #92400e; }
    .badge-proceso    { background: #dbeafe; color: #1e40af; }
    .badge-error      { background: #fee2e2; color: #991b1b; }
    .badge-nd         { background: #f1f5f9; color: #64748b; }
    .badge-activo     { background: #d1fae5; color: #065f46; }
    .badge-inactivo   { background: #fee2e2; color: #991b1b; }
    .badge-count      { background: #dbeafe; color: #1d4ed8; }

    .btn-ver        { background: none; border: 1px solid #e2e8f0; color: #475569; }
    .btn-ver:hover  { background: #f1f5f9; }
    .btn-desact     { background: #fee2e2; border: none; color: #991b1b; }
    .btn-desact:hover { background: #fecaca; }
    .btn-react      { background: #d1fae5; border: none; color: #065f46; }
    .btn-react:hover  { background: #a7f3d0; }

    /* Modal override */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.25); display:flex; flex-direction:column; }
    .modal-header { padding:20px 24px 16px; border-bottom:1px solid #f1f5f9; position:relative; }
    .modal-header h2 { font-size:16px; font-weight:700; color:var(--text-primary); margin:0; }
    .modal-header p  { font-size:12px; color:var(--text-secondary); margin:4px 0 0; }
    .modal-body { padding:20px 24px; overflow-y:auto; }

    .empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
    .empty-state svg { margin:0 auto 12px; display:block; opacity:.4; }
    .empty-state p { font-size:14px; font-weight:600; color:#64748b; }
    .empty-state span { font-size:12px; }

    .archivo-cell { max-width: 260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .filename-icon { color:#6366f1; vertical-align:middle; margin-right:4px; }

    .pagination-wrap { padding: 16px 14px; border-top: 1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    .pagination-wrap .pag-info { font-size:12px; color:#64748b; }
    .pagination-wrap .pag-links { display:flex; gap:4px; }
    .pagination-wrap .pag-links a,
    .pagination-wrap .pag-links span {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:30px; height:30px; padding:0 8px;
        border-radius:6px; font-size:12px; font-weight:600;
        border:1px solid #e2e8f0; color:#475569; text-decoration:none;
        transition:all .15s;
    }
    .pagination-wrap .pag-links a:hover { background:#f1f5f9; }
    .pagination-wrap .pag-links span.active-page { background:#f5c842; color:#171204; border-color:#e0ad1a; }
    .pagination-wrap .pag-links span.disabled { opacity:.4; cursor:default; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-icon">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
    </div>
    <div>
        <div class="page-title">Historial de Importaciones</div>
        <div class="page-subtitle">Importaciones desde Nubefact — gestiona la visibilidad de cada lote</div>
    </div>
    <a href="{{ route('facturas.importar') }}" class="btn btn-primary" style="margin-left:auto;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" style="margin-right:4px;vertical-align:middle;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Importación
    </a>
</div>

<div class="card">
    @if($sincronizaciones->total() === 0)
        <div class="empty-state">
            <svg width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <p>Sin importaciones registradas</p>
            <span>Importa tu primer archivo Excel desde el botón "Nueva Importación".</span>
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="sinc-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Archivo</th>
                    <th>Fecha de importación</th>
                    <th class="center">Facturas<br>insertadas</th>
                    <th class="center">Errores</th>
                    <th class="center">Estado</th>
                    <th class="center">Visibilidad</th>
                    <th class="center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sincronizaciones as $sinc)
                <tr id="row{{ $sinc->id_sincronizacion }}" class="{{ !$sinc->activo ? 'inactivo' : '' }}">
                    <td style="color:#94a3b8;font-size:12px;">{{ $sinc->id_sincronizacion }}</td>
                    <td class="archivo-cell" title="{{ $sinc->nombre_archivo }}">
                        <svg class="filename-icon" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $sinc->nombre_archivo ?? '—' }}
                    </td>
                    <td style="white-space:nowrap;color:#64748b;">
                        {{ $sinc->fecha_inicio ? \Carbon\Carbon::parse($sinc->fecha_inicio)->format('d/m/Y H:i') : '—' }}
                        @if($sinc->fecha_fin)
                            <div style="font-size:11px;color:#94a3b8;">
                                Duración: {{ \Carbon\Carbon::parse($sinc->fecha_inicio)->diffForHumans($sinc->fecha_fin, true) }}
                            </div>
                        @endif
                    </td>
                    <td class="center">
                        <span class="badge-pill badge-count">{{ $sinc->total_registros_procesados ?? 0 }}</span>
                    </td>
                    <td class="center">
                        @if(($sinc->total_registros_error ?? 0) > 0)
                            <span class="badge-pill badge-errores">{{ $sinc->total_registros_error }}</span>
                        @else
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td class="center">
                        @php
                            $cls = match($sinc->estado ?? '') {
                                'COMPLETADO'  => 'badge-completado',
                                'CON_ERRORES' => 'badge-errores',
                                'EN_PROCESO'  => 'badge-proceso',
                                'ERROR'       => 'badge-error',
                                default       => 'badge-nd',
                            };
                        @endphp
                        <span class="badge-pill {{ $cls }}">{{ $sinc->estado ?? 'N/D' }}</span>
                    </td>
                    <td class="center">
                        @if($sinc->activo)
                            <span class="badge-pill badge-activo">ACTIVO</span>
                        @else
                            <span class="badge-pill badge-inactivo">INACTIVO</span>
                        @endif
                    </td>
                    <td class="center" style="white-space:nowrap;">
                        <button type="button"
                                class="btn btn-sm btn-ver"
                                onclick="verFacturasSinc({{ $sinc->id_sincronizacion }}, '{{ addslashes($sinc->nombre_archivo ?? '') }}')"
                                style="margin-right:4px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Ver
                        </button>
                        @if($sinc->activo)
                            <button type="button"
                                    class="btn btn-sm btn-desact"
                                    onclick="desactivarSinc({{ $sinc->id_sincronizacion }})">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Desactivar
                            </button>
                        @else
                            <button type="button"
                                    class="btn btn-sm btn-react"
                                    onclick="activarSinc({{ $sinc->id_sincronizacion }})">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Reactivar
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($sincronizaciones->hasPages())
    <div class="pagination-wrap">
        <div class="pag-info">
            Mostrando {{ $sincronizaciones->firstItem() }}–{{ $sincronizaciones->lastItem() }}
            de {{ $sincronizaciones->total() }} importaciones
        </div>
        <div class="pag-links">
            @if($sincronizaciones->onFirstPage())
                <span class="disabled">‹</span>
            @else
                <a href="{{ $sincronizaciones->previousPageUrl() }}">‹</a>
            @endif

            @foreach($sincronizaciones->getUrlRange(1, $sincronizaciones->lastPage()) as $page => $url)
                @if($page == $sincronizaciones->currentPage())
                    <span class="active-page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($sincronizaciones->hasMorePages())
                <a href="{{ $sincronizaciones->nextPageUrl() }}">›</a>
            @else
                <span class="disabled">›</span>
            @endif
        </div>
    </div>
    @endif
    @endif
</div>

{{-- ═══════════ MODAL DETALLE FACTURAS ═══════════ --}}
<div class="modal-overlay" id="modalSincOverlay" onclick="if(event.target===this)cerrarModal()">
    <div class="modal" style="max-width:900px;width:min(900px,96vw);max-height:88vh;">
        <div class="modal-header">
            <h2 id="modalTitulo">Facturas de importación</h2>
            <p id="modalDesc">—</p>
            <button onclick="cerrarModal()" style="position:absolute;right:20px;top:18px;background:none;border:none;font-size:26px;cursor:pointer;color:#94a3b8;line-height:1;">×</button>
        </div>
        <div class="modal-body" id="modalBody">Cargando...</div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" style="display:none;position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:360px;"></div>
@endsection

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function showToast(msg, ok = true) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.background = ok ? '#065f46' : '#991b1b';
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 3500);
    }

    function verFacturasSinc(id, archivo) {
        document.getElementById('modalTitulo').textContent = 'Importación #' + id;
        document.getElementById('modalDesc').textContent   = archivo || 'Cargando...';
        document.getElementById('modalBody').innerHTML     = '<div style="text-align:center;padding:48px;color:#94a3b8;">Cargando facturas...</div>';
        document.getElementById('modalSincOverlay').classList.add('open');

        fetch('/facturas/sincronizaciones/' + id + '/facturas', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('modalDesc').textContent = data.length + ' factura(s) en este lote';
            if (!data.length) {
                document.getElementById('modalBody').innerHTML = '<p style="color:#94a3b8;padding:20px 0;">Sin facturas vinculadas a esta importación.</p>';
                return;
            }

            let html = '<div style="overflow-x:auto;">';
            html += '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
            html += `<thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <th style="padding:9px 12px;text-align:left;color:#475569;font-weight:600;">Serie-Nro</th>
                        <th style="padding:9px 12px;text-align:left;color:#475569;font-weight:600;">Cliente</th>
                        <th style="padding:9px 12px;text-align:left;color:#475569;font-weight:600;">Fecha</th>
                        <th style="padding:9px 12px;text-align:right;color:#475569;font-weight:600;">Importe</th>
                        <th style="padding:9px 12px;text-align:center;color:#475569;font-weight:600;">Estado</th>
                        <th style="padding:9px 12px;text-align:center;color:#475569;font-weight:600;">Vis.</th>
                     </tr></thead><tbody>`;

            const estadoColors = {
                'PENDIENTE': ['#fef9c3','#92400e'],
                'VENCIDO':   ['#fee2e2','#991b1b'],
                'PAGADA':    ['#d1fae5','#065f46'],
                'ANULADO':   ['#f1f5f9','#64748b'],
            };

            data.forEach(f => {
                const num  = String(f.numero).padStart(8, '0');
                const cols = estadoColors[f.estado] ?? ['#f1f5f9','#64748b'];
                const actBadge = f.activo
                    ? '<span style="background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;">✓</span>'
                    : '<span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;">✗</span>';
                const importe = parseFloat(f.importe_total || 0).toLocaleString('es-PE', {minimumFractionDigits:2});
                html += `<tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 12px;font-weight:600;">${f.serie}-${num}</td>
                    <td style="padding:8px 12px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.razon_social}</td>
                    <td style="padding:8px 12px;white-space:nowrap;color:#64748b;">${f.fecha_emision ?? '—'}</td>
                    <td style="padding:8px 12px;text-align:right;font-weight:600;">${f.moneda} ${importe}</td>
                    <td style="padding:8px 12px;text-align:center;"><span style="background:${cols[0]};color:${cols[1]};font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">${f.estado}</span></td>
                    <td style="padding:8px 12px;text-align:center;">${actBadge}</td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            document.getElementById('modalBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('modalBody').innerHTML = '<p style="color:#ef4444;padding:20px 0;">Error al cargar las facturas.</p>';
        });
    }

    function cerrarModal() {
        document.getElementById('modalSincOverlay').classList.remove('open');
    }

    function desactivarSinc(id) {
        if (!confirm('¿Desactivar esta importación?\nLas facturas de este lote dejarán de aparecer en la lista de Gestión de Facturas.')) return;
        fetch('/facturas/sincronizaciones/' + id + '/desactivar', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': CSRF},
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Importación desactivada — ' + data.total + ' factura(s) ocultada(s).');
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
                showToast('Importación reactivada — ' + data.total + ' factura(s) visibles nuevamente.');
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
</script>
@endpush
