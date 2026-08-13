@extends('layouts.app')
@section('title', 'Bandeja de Conciliación')
@section('breadcrumb', 'Conciliación / Bandeja de Movimientos Pendientes')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        body { background: var(--bg) !important; font-family: 'DM Sans', sans-serif; }

        /* ── Animaciones ── */
        @keyframes fadeDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes slideUp  { from { opacity:0; transform:translateY(16px);  } to { opacity:1; transform:translateY(0); } }
        @keyframes rowIn    { from { opacity:0; transform:translateY(6px);   } to { opacity:1; transform:translateY(0); } }
        .page-header { animation: fadeDown .5s ease-out; }
        tbody tr { animation: rowIn .35s ease-out both; }
        @for ($i = 1; $i <= 20; $i++)
            tbody tr:nth-child({{ $i }}) { animation-delay: {{ $i * 0.04 }}s; }
        @endfor

        /* ── Page header ── */
        .page-title {
            font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -.4px;
            display: flex; align-items: center; gap: 10px;
        }
        .page-title::before {
            content: ''; display: inline-block;
            width: 28px; height: 3px; background: var(--gold); border-radius: 2px;
        }
        .page-desc { font-size: 13px; color: #64748b; margin-top: 4px; }

        /* ── Card ── */
        .card-custom {
            background: #fff;
            border: 1.5px solid var(--gold-b);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 4px 12px rgba(0,0,0,.03);
            margin-bottom: 24px;
            overflow: hidden;
            animation: slideUp .5s ease-out .1s both;
        }
        .card-custom .card-header {
            background: var(--gold-l);
            border-bottom: 1px solid var(--gold-b);
            padding: 16px 20px;
            font-weight: 700;
            font-size: 15px;
            color: var(--gold-xd);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-custom .card-header svg { flex-shrink: 0; opacity: .85; }

        /* ── Table ── */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }
        .table-custom thead th {
            background: var(--gold-l);
            color: var(--gold-xd);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 10px 14px;
            border-bottom: 2px solid var(--gold-b);
            white-space: nowrap;
        }
        .table-custom tbody td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--gold-b);
            font-size: 13px;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover { background: var(--gold-l); }

        /* ── Badges ── */
        .badge-pendiente   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-conciliado  { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-descartado  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .badge-error       { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }
        .badge-sin-match {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Importe mono ── */
        .importe-mono { font-family: 'DM Mono', monospace; font-weight: 700; }
        .text-end { text-align: right; }

        /* ── Buttons ── */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 15px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
            text-decoration: none;
        }
        .btn-conciliar {
            background: var(--gold);
            color: var(--gold-xd);
            border: 1.5px solid var(--gold);
        }
        .btn-conciliar:hover { background: var(--gold-h); border-color: var(--gold-h); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,200,66,.3); }
        .btn-descartar {
            background: #fff;
            color: #7f1d1d;
            border: 1.5px solid #fca5a5;
        }
        .btn-descartar:hover { background: #fee2e2; border-color: #f87171; transform: translateY(-1px); }
        .btn-revisar {
            background: #fff;
            color: #1e40af;
            border: 1.5px solid #93c5fd;
        }
        .btn-revisar:hover { background: #dbeafe; border-color: #60a5fa; transform: translateY(-1px); }
        .btn-extornar {
            background: #fff;
            color: #9d174d;
            border: 1.5px solid #f9a8d4;
        }
        .btn-extornar:hover { background: #fce7f3; border-color: #f472b6; transform: translateY(-1px); }

        /* ── Pagination ── */
        .pagination-wrap { display: flex; justify-content: center; padding: 20px 16px; }

        /* ── Modal ── */
        .modal-content {
            border-radius: 14px;
            border: 1.5px solid var(--gold-b);
            box-shadow: 0 12px 48px rgba(0,0,0,.15);
            overflow: hidden;
        }
        .modal-header {
            background: #171204;
            border-bottom: 1px solid #3a2c0b;
            border-radius: 14px 14px 0 0;
            padding: 16px 20px;
        }
        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #f5c842;
        }
        .modal-header .btn-close {
            filter: invert(1) brightness(2);
            opacity: .8;
        }
        .modal-header .btn-close:hover { opacity: 1; }
        .modal-body { padding: 20px; background: #fff; }
        .modal-footer {
            border-top: 1px solid var(--gold-b);
            padding: 16px 20px;
            background: #fff;
            border-radius: 0 0 14px 14px;
        }

        /* ── Form ── */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--gold-xd);
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--gold-b);
            border-radius: 10px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
            color: #0f172a;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-l);
        }
        textarea.form-control { resize: vertical; min-height: 80px; }

        /* ── Select2 ── */
        .select2-container--default .select2-selection--single {
            height: 44px;
            border: 1.5px solid var(--gold-b);
            border-radius: 10px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            font-size: 13px;
            color: #0f172a;
            padding-left: 14px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--gold);
        }
        .select2-dropdown {
            border: 1.5px solid var(--gold-b);
            border-radius: 10px;
            overflow: hidden;
        }
        .select2-results__option--highlighted {
            background: var(--gold-l) !important;
            color: var(--gold-xd) !important;
        }

        /* ── Factura candidate ── */
        .factura-candidate {
            padding: 12px 16px;
            border: 1.5px solid var(--gold-b);
            border-radius: 10px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: all .15s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }
        .factura-candidate:hover { background: var(--gold-l); border-color: var(--gold-m); }
        .factura-candidate.selected {
            background: #d1fae5;
            border-color: #059669;
            box-shadow: 0 0 0 2px rgba(5,150,105,.15);
        }
        .factura-candidate .factura-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .factura-candidate .factura-serie {
            font-family: 'DM Mono', monospace;
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
        }
        .factura-candidate .factura-importe {
            font-family: 'DM Mono', monospace;
            font-weight: 600;
            font-size: 12px;
            color: #64748b;
        }
        .factura-candidate .factura-check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            transition: all .15s ease;
            flex-shrink: 0;
        }
        .factura-candidate.selected .factura-check {
            background: #059669;
            border-color: #059669;
            position: relative;
        }
        .factura-candidate.selected .factura-check::after {
            content: '';
            position: absolute;
            top: 4px; left: 4px;
            width: 10px; height: 6px;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            transform: rotate(-45deg);
        }

        /* ── Movimiento header ── */
        .movimiento-header {
            background: var(--gold-l);
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1.5px solid var(--gold-b);
        }
        .movimiento-header .mov-detalle {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 13px;
        }
        .movimiento-header .mov-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gold-xd);
            letter-spacing: .04em;
            margin-bottom: 2px;
        }

        /* ── Empty ── */
        .empty-message {
            text-align: center;
            padding: 24px 20px;
            color: #64748b;
            font-size: 13px;
        }

        /* ── Toast ── */
        .alert-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 320px;
            animation: slideIn .3s ease-out;
            border-radius: 10px;
            padding: 14px 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }

        /* ── Modal footer buttons ── */
        .btn-confirmar {
            background: #059669;
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-confirmar:hover { background: #047857; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(5,150,105,.3); }
        .btn-cancelar {
            background: #fff;
            color: #475569;
            border: 1.5px solid #e2e8f0;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-cancelar:hover { background: #f1f5f9; border-color: #cbd5e1; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .movimiento-header .mov-detalle { flex-direction: column; gap: 10px; }
        }
    </style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Bandeja de Conciliación</h1>
    <p class="page-desc">Movimientos bancarios que requieren conciliación manual</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-toast" id="alertToast">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-error alert-toast" id="alertToast">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') }}
    </div>
@endif

<div class="card-custom">
    <div class="card-header">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Movimientos SIN MATCH ({{ $movimientos->total() }})
    </div>

    <div style="overflow-x:auto;">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Banco</th>
                    <th>Descripción / Referencia</th>
                    <th>N° Operación</th>
                    <th class="text-end">Importe</th>
                    <th>Score</th>
                    <th>Estado</th>
                    <th style="width:170px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr>
                    <td>
                        <span style="display:block;font-weight:600;">{{ optional($mov->fecha_operacion)->format('d/m/Y') ?? '—' }}</span>
                        <span style="font-size:10px;color:#64748b;">{{ $mov->moneda ?? '' }}</span>
                    </td>
                    <td style="font-weight:600;">{{ $mov->banco }}</td>
                    <td>
                        <div style="max-width:220px;">
                            <div style="font-size:12px;color:#0f172a;">{{ Str::limit($mov->descripcion, 50) }}</div>
                            @if($mov->referencia)
                                <div style="font-size:10px;color:#64748b;">Ref: {{ Str::limit($mov->referencia, 40) }}</div>
                            @endif
                        </div>
                    </td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px;font-weight:500;">{{ $mov->numero_operacion }}</td>
                    <td class="text-end">
                        <span class="importe-mono">{{ number_format($mov->importe, 2) }}</span>
                    </td>
                    <td>
                        @if($mov->score_match)
                            <span style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:#0f172a;">{{ $mov->score_match }}</span>
                        @else
                            <span style="color:#64748b;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-sin-match">
                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                            SIN MATCH
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button"
                                    class="btn-action btn-conciliar"
                                    onclick="abrirModalConciliar({{ $mov->id_movimiento }}, '{{ e($mov->descripcion) }}', '{{ e($mov->numero_operacion) }}', {{ $mov->importe }}, '{{ $mov->banco }}')"
                                    title="Conciliar manualmente">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Conciliar
                            </button>
                            <button type="button"
                                    class="btn-action btn-descartar"
                                    onclick="abrirModalDescartar({{ $mov->id_movimiento }})"
                                    title="Descartar movimiento">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Descartar
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-message">
                            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#d1d5db" stroke-width="1.5" style="display:block;margin:0 auto 12px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <p style="margin:0;font-size:15px;font-weight:600;color:#0f172a;">No hay movimientos pendientes</p>
                            <p style="margin:4px 0 0;font-size:13px;color:#64748b;">Todos los movimientos han sido procesados.</p>
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

{{-- ════════════════════════════════════════════════ --}}
{{-- MODAL DE CONCILIACIÓN MANUAL                    --}}
{{-- ════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalConciliar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Conciliación Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                {{-- Datos del movimiento --}}
                <div class="movimiento-header" id="movimientoHeader">
                    {{-- Llenado por JS --}}
                </div>

                <form id="formConciliarManual">
                    <input type="hidden" id="conciliar_movimiento_id" name="id_movimiento">

                    {{-- Buscador de cliente --}}
                    <div class="form-group">
                        <label for="selectCliente">Buscar Cliente</label>
                        <select id="selectCliente" class="form-control" style="width:100%;">
                            <option value="">— Seleccione un cliente —</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id_cliente }}">
                                    {{ $cliente->razon_social }} ({{ $cliente->ruc }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Lista de facturas pendientes del cliente --}}
                    <div class="form-group">
                        <label>Facturas Pendientes del Cliente</label>
                        <div id="facturasPendientesContainer" style="max-height:320px;overflow-y:auto;padding-right:4px;">
                            <div class="empty-message" id="facturasEmptyMsg">
                                Seleccione un cliente para ver sus facturas pendientes.
                            </div>
                        </div>
                    </div>

                    {{-- Motivo --}}
                    <div class="form-group">
                        <label for="conciliarMotivo">Motivo de la conciliación <span style="color:#dc2626;">*</span></label>
                        <textarea id="conciliarMotivo" class="form-control" rows="3"
                                  placeholder="Explique brevemente el motivo de esta conciliación manual (mínimo 10 caracteres)..."
                                  required></textarea>
                        <small style="color:#64748b;font-size:11px;">Mínimo 10 caracteres</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-confirmar" onclick="ejecutarConciliacion()">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Confirmar Conciliación
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════ --}}
{{-- MODAL DE DESCARTE                               --}}
{{-- ════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalDescartar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:6px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Descartar Movimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div style="background:#fef3c7;border:1.5px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                    </svg>
                    <span>El movimiento será marcado como <strong>descartado</strong> y no será considerado en futuras conciliaciones.</span>
                </div>

                <form id="formDescartar">
                    <input type="hidden" id="descartar_movimiento_id" name="id_movimiento">
                    <div class="form-group">
                        <label for="descartarMotivo">Motivo del descarte <span style="color:#dc2626;">*</span></label>
                        <textarea id="descartarMotivo" class="form-control" rows="3"
                                  placeholder="Explique por qué se descarta este movimiento (mínimo 10 caracteres)..."
                                  required></textarea>
                        <small style="color:#64748b;font-size:11px;">Mínimo 10 caracteres</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn-descartar btn-action" onclick="ejecutarDescarte()" style="background:#fee2e2;color:#7f1d1d;border:1.5px solid #fca5a5;font-size:13px;padding:9px 20px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Descartar Movimiento
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // ── Inicializar Select2 ──
        document.addEventListener('DOMContentLoaded', function () {
            $('#selectCliente').select2({
                dropdownParent: $('#modalConciliar'),
                placeholder: 'Buscar cliente por nombre o RUC...',
                allowClear: true,
                language: {
                    noResults: function () { return 'No se encontraron clientes'; },
                    searching: function () { return 'Buscando...'; }
                }
            });

            // Al cambiar el cliente, cargar sus facturas pendientes
            $('#selectCliente').on('change', function () {
                const clienteId = $(this).val();
                cargarFacturasPendientes(clienteId);
            });

            // Auto-hide alerts
            setTimeout(() => {
                const toast = document.getElementById('alertToast');
                if (toast) { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }
            }, 5000);

            // Limpiar modal al cerrar
            document.getElementById('modalConciliar').addEventListener('hidden.bs.modal', function () {
                facturaSeleccionada = null;
                $('#selectCliente').val(null).trigger('change');
                document.getElementById('conciliar_movimiento_id').value = '';
                document.getElementById('conciliarMotivo').value = '';
                document.getElementById('facturasPendientesContainer').innerHTML = '<div class="empty-message">Seleccione un cliente para ver sus facturas pendientes.</div>';
            });
            document.getElementById('modalDescartar').addEventListener('hidden.bs.modal', function () {
                document.getElementById('descartar_movimiento_id').value = '';
                document.getElementById('descartarMotivo').value = '';
            });
        });

        // ── Cargar facturas pendientes del cliente ──
        function cargarFacturasPendientes(clienteId) {
            const container = document.getElementById('facturasPendientesContainer');

            if (!clienteId) {
                container.innerHTML = '<div class="empty-message">Seleccione un cliente para ver sus facturas pendientes.</div>';
                return;
            }

            container.innerHTML = '<div class="empty-message"><div class="spinner-border spinner-border-sm text-warning" role="status"></div> Cargando facturas...</div>';

            fetch('{{ route("facturas.pago-masivo.facturas-cliente") }}?id_cliente=' + clienteId + '&_=' + Date.now(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.facturas || data.facturas.length === 0) {
                    container.innerHTML = '<div class="empty-message">Este cliente no tiene facturas pendientes.</div>';
                    return;
                }

                let html = '';
                data.facturas.forEach(f => {
                    const serieNum = (f.serie || '') + '-' + (f.numero || '');
                    html += `
                        <div class="factura-candidate" data-factura-id="${f.id_factura}" onclick="seleccionarFactura(this, ${f.id_factura})">
                            <div class="factura-info">
                                <span class="factura-serie">${serieNum}</span>
                                <span style="font-size:11px;color:#64748b;">Emisión: ${f.fecha_emision || '—'} | Estado: ${f.estado || '—'}</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span class="factura-importe">S/ ${parseFloat(f.monto_pendiente || f.importe_total || 0).toFixed(2)}</span>
                                <div class="factura-check"></div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(err => {
                console.error('Error cargando facturas:', err);
                container.innerHTML = '<div class="empty-message" style="color:#dc2626;">Error al cargar facturas. Intente nuevamente.</div>';
            });
        }

        let facturaSeleccionada = null;

        function seleccionarFactura(elemento, facturaId) {
            document.querySelectorAll('.factura-candidate').forEach(el => el.classList.remove('selected'));
            elemento.classList.add('selected');
            facturaSeleccionada = facturaId;
        }

        // ── Abrir modal de conciliación ──
        function abrirModalConciliar(movimientoId, descripcion, numOperacion, importe, banco) {
            facturaSeleccionada = null;
            document.getElementById('conciliar_movimiento_id').value = movimientoId;
            document.getElementById('conciliarMotivo').value = '';

            // Llenar header del movimiento
            document.getElementById('movimientoHeader').innerHTML = `
                <div class="mov-detalle">
                    <div>
                        <div class="mov-label">Banco</div>
                        <div style="font-weight:600;color:#0f172a;">${banco}</div>
                    </div>
                    <div>
                        <div class="mov-label">N° Operación</div>
                        <div style="font-family:'DM Mono',monospace;font-weight:600;color:#0f172a;">${numOperacion}</div>
                    </div>
                    <div>
                        <div class="mov-label">Importe</div>
                        <div class="importe-mono" style="font-size:15px;color:#0f172a;">S/ ${parseFloat(importe).toFixed(2)}</div>
                    </div>
                    <div style="flex:1;min-width:160px;">
                        <div class="mov-label">Descripción</div>
                        <div style="font-size:12px;color:#475569;">${descripcion}</div>
                    </div>
                </div>
            `;

            // Reset UI
            document.querySelectorAll('.factura-candidate').forEach(el => el.classList.remove('selected'));
            document.getElementById('facturasPendientesContainer').innerHTML = '<div class="empty-message">Seleccione un cliente para ver sus facturas pendientes.</div>';
            $('#selectCliente').val(null).trigger('change');

            const modal = new bootstrap.Modal(document.getElementById('modalConciliar'));
            modal.show();
        }

        // ── Ejecutar conciliación ──
        function ejecutarConciliacion() {
            const movimientoId = document.getElementById('conciliar_movimiento_id').value;
            const motivo = document.getElementById('conciliarMotivo').value.trim();

            if (!facturaSeleccionada) {
                mostrarAlerta('Seleccione una factura para conciliar.', 'danger');
                return;
            }

            if (motivo.length < 10) {
                mostrarAlerta('El motivo debe tener al menos 10 caracteres.', 'danger');
                return;
            }

            const btn = document.querySelector('#modalConciliar .btn-confirmar');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';

            fetch('{{ route("conciliacion.movimientos.conciliar", ["id" => "__ID__"]) }}'.replace('__ID__', movimientoId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id_factura: facturaSeleccionada,
                    motivo: motivo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalConciliar')).hide();
                    mostrarAlerta(data.message || 'Movimiento conciliado correctamente.', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarAlerta(data.message || 'Error al conciliar el movimiento.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarAlerta('Error de conexión al intentar conciliar.', 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }

        // ── Abrir modal de descarte ──
        function abrirModalDescartar(movimientoId) {
            document.getElementById('descartar_movimiento_id').value = movimientoId;
            document.getElementById('descartarMotivo').value = '';
            const modal = new bootstrap.Modal(document.getElementById('modalDescartar'));
            modal.show();
        }

        // ── Ejecutar descarte ──
        function ejecutarDescarte() {
            const movimientoId = document.getElementById('descartar_movimiento_id').value;
            const motivo = document.getElementById('descartarMotivo').value.trim();

            if (motivo.length < 10) {
                mostrarAlerta('El motivo debe tener al menos 10 caracteres.', 'danger');
                return;
            }

            const btn = document.querySelector('#modalDescartar .btn-descartar');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';

            fetch('{{ route("conciliacion.movimientos.descartar", ["id" => "__ID__"]) }}'.replace('__ID__', movimientoId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ motivo: motivo })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modalDescartar')).hide();
                    mostrarAlerta(data.message || 'Movimiento descartado correctamente.', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarAlerta(data.message || 'Error al descartar el movimiento.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarAlerta('Error de conexión al intentar descartar.', 'danger');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        }

        // ── Mostrar alerta toast ──
        function mostrarAlerta(mensaje, tipo) {
            // Remover toasts anteriores
            document.querySelectorAll('.alert-toast-dynamic').forEach(el => el.remove());

            const alerta = document.createElement('div');
            alerta.className = `alert alert-${tipo} alert-toast alert-toast-dynamic`;
            alerta.innerHTML = mensaje;
            document.body.appendChild(alerta);
            setTimeout(() => {
                alerta.style.opacity = '0';
                alerta.style.transition = 'opacity .3s';
                setTimeout(() => alerta.remove(), 300);
            }, 4000);
        }
    </script>
@endpush
