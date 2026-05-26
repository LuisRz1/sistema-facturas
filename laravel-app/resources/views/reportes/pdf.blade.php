<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero — CRC S.A.C.</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#111; background:#fff; }

        /* ── TOP BAR ── */
        .no-print {
            background:#1e293b; padding:12px 24px;
            display:flex; align-items:center; gap:10px; flex-wrap:wrap;
            position:sticky; top:0; z-index:10;
        }
        .no-print .hint { color:#94a3b8; font-size:12px; white-space:nowrap; }
        .btn-print {
            background:#1d4ed8; color:#fff; border:none; padding:8px 16px;
            border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:6px; white-space:nowrap;
        }
        .btn-print:hover { background:#1e40af; }
        .btn-excel {
            background:#16a34a; color:#fff; border:none; padding:8px 16px;
            border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:6px; white-space:nowrap;
        }
        .btn-excel:hover { background:#15803d; }
        /* Panel de opciones Excel */
        .excel-panel {
            display:none; position:absolute; top:100%; left:0; margin-top:4px;
            background:#fff; border:1px solid #d1d5db; border-radius:8px;
            box-shadow:0 8px 24px rgba(0,0,0,.15); z-index:999; min-width:280px; padding:8px 0;
        }
        .excel-panel.open { display:block; }
        .excel-panel-item {
            display:flex; align-items:flex-start; gap:10px; padding:10px 16px;
            cursor:pointer; border-bottom:1px solid #f3f4f6; transition:background .1s;
        }
        .excel-panel-item:last-child { border-bottom:none; }
        .excel-panel-item:hover { background:#f0fdf4; }
        .excel-panel-item .ico { font-size:18px; flex-shrink:0; margin-top:1px; }
        .excel-panel-item .desc strong { display:block; font-size:12px; font-weight:700; color:#1f2937; }
        .excel-panel-item .desc span  { display:block; font-size:10px; color:#6b7280; margin-top:1px; }
        .btn-close {
            background:transparent; color:#64748b; border:1px solid #334155;
            padding:8px 14px; border-radius:6px; font-size:12px; cursor:pointer; white-space:nowrap;
        }
        .btn-close:hover { background:#334155; color:#fff; }

        /* ── SELECTOR USUARIO INLINE ── */
        .send-inline {
            display:flex; align-items:center; gap:8px; flex-wrap:wrap;
            margin-left:auto; border-left:1px solid #334155; padding-left:14px;
        }
        .send-inline-label { color:#94a3b8; font-size:11px; font-weight:700; text-transform:uppercase; white-space:nowrap; }
        .send-inline select {
            height:34px; padding:0 10px; border:1px solid #475569; border-radius:6px;
            background:#0f172a; color:#e2e8f0; font-size:12px; min-width:190px; outline:none;
        }
        .btn-send-wa  { background:#22c55e; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; opacity:.45; }
        .btn-send-wa:not(:disabled)   { opacity:1; }
        .btn-send-wa:not(:disabled):hover { background:#16a34a; }
        .btn-send-mail { background:#3b82f6; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; opacity:.45; }
        .btn-send-mail:not(:disabled) { opacity:1; }
        .btn-send-mail:not(:disabled):hover { background:#2563eb; }
        .send-result-bar { display:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:700; white-space:nowrap; }
        .send-result-bar.ok    { background:#14532d; color:#86efac; display:block; }
        .send-result-bar.error { background:#7f1d1d; color:#fca5a5; display:block; }

        /* ── LEYENDA NC HUÉRFANAS ── */
        .leyenda-nc {
            background:#fef9e0; border:1px solid #fde68a; border-left:3px solid #f59e0b;
            padding:8px 14px; margin-bottom:10px; font-size:10px; color:#92400e;
            display:flex; align-items:center; gap:8px;
        }
        .leyenda-nc strong { font-weight:800; }

        /* ── HEADER ── */
        .header { background:#0f172a; color:#fff; text-align:center; padding:22px 32px 18px; }
        .header h1 { font-size:20px; font-weight:900; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px; }
        .header .sub { font-size:11px; font-weight:700; color:#94a3b8; line-height:1.8; }

        /* ── DASHBOARD CARDS (DOMPDF friendly) ── */
        .stats-grid {
            width:100%;
            border-collapse:separate;
            border-spacing:12px 10px;
            margin:16px 0 18px;
            table-layout:fixed;
        }
        .stats-grid td {
            border:1px solid #e2e8f0;
            border-radius:10px;
            background:#ffffff;
            padding:10px 12px;
            vertical-align:middle;
        }
        .stats-grid.three td { width:33.33%; }
        .stats-grid.two td { width:50%; }
        .stat-label { font-size:8.8px; font-weight:800; text-transform:uppercase; letter-spacing:.55px; color:#64748b; }
        .stat-value {
            font-size:16px;
            font-weight:900;
            font-family:'Courier New',monospace;
            color:#0f172a;
            margin-top:4px;
            white-space:nowrap;
        }
        .stat-sub { font-size:7.5px; color:#94a3b8; margin-top:2px; }
        .sc-blue   { border-color:#bfdbfe !important; }
        .sc-amber  { border-color:#fde68a !important; }
        .sc-green  { border-color:#bbf7d0 !important; }
        .sc-red    { border-color:#fecaca !important; }
        .sc-purple { border-color:#ddd6fe !important; }
        .si-blue   { background:#dbeafe; color:#2563eb; }
        .si-amber  { background:#fef3c7; color:#d97706; }
        .si-green  { background:#dcfce7; color:#059669; }
        .si-red    { background:#fee2e2; color:#dc2626; }
        .si-purple { background:#ede9fe; color:#7c3aed; }

        /* ── TABLE ── */
        .body { padding:24px 32px; }
        .empresa-table { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:4px; }

        .empresa-table thead tr { background:#0f172a; color:#fff; }
        .empresa-table thead th {
            padding:6px 4px; text-align:left; font-size:8.5px; font-weight:700;
            text-transform:uppercase; letter-spacing:.5px; overflow:hidden;
            white-space:nowrap; text-overflow:ellipsis;
        }
        .empresa-table thead th.r { text-align:right; }
        .empresa-table tbody tr { border-bottom:1px solid #f1f5f9; }
        .empresa-table tbody tr:nth-child(even) { background:#f8fafc; }
        .empresa-table tbody td {
            padding:6px 4px; font-size:9.5px; vertical-align:middle;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .empresa-table tbody td.r { text-align:right; }
        .empresa-table tbody td.mono { font-family:'Courier New',monospace; font-size:9.5px; }
        .td-estado { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; line-height:1.15; }
        .td-glosa { white-space:normal !important; overflow:visible !important; text-overflow:clip !important; line-height:1.2; }
        .doc-relacion {
            display:block;
            margin-top:3px;
            font-size:8.2px;
            font-weight:700;
            color:#7c3aed;
            white-space:normal;
            word-break:break-word;
            line-height:1.15;
        }

        /* Fila NC huérfana: tachado igual que en módulo de facturas */
        .empresa-table tbody tr.nc-huerfana {
            text-decoration: line-through;
            opacity: 0.55;
            background: #fafafa !important;
        }
        .empresa-table tbody tr.nc-huerfana td {
            color: #9ca3af !important;
        }
        /* Badge indicador inline para la columna estado */
        .badge-nc-huerfana {
            display:inline-block; padding:1px 5px; border-radius:4px;
            font-size:7.5px; font-weight:800; text-transform:uppercase;
            background:#fef3c7; color:#92400e; border:1px solid #fde68a;
            margin-left:3px; vertical-align:middle; text-decoration:none;
        }

        /* Fila de totales por empresa */
        .empresa-table tbody tr.total-empresa {
            background:#1e293b !important; border-top:2px solid #334155;
        }
        .empresa-table tbody tr.total-empresa td {
            color:#fff; font-weight:800; font-size:10px; padding:8px 6px;
        }
        .empresa-table tbody tr.total-empresa td.r { text-align:right; }

        .factura-num { font-weight:800; font-family:'Courier New',monospace; }
        .detrac  { color:#f59e0b; font-weight:700; font-family:'Courier New',monospace; }
        .abonado { color:#10b981; font-weight:700; font-family:'Courier New',monospace; }
        .pendiente-cell { color:#f87171; font-weight:700; font-family:'Courier New',monospace; }
        .igv-note { display:block; font-size:7.5px; color:#64748b; margin-top:1px; font-family:Arial, Helvetica, sans-serif; }

        .badge {
            display:inline-block; padding:2px 6px; border-radius:20px; font-size:8px;
            font-weight:800; text-transform:uppercase; letter-spacing:.35px;
            max-width:100%; white-space:normal; word-break:break-word; line-height:1.1;
        }
        .b-PENDIENTE             { background:#fef3c7; color:#92400e; }
        .b-VENCIDO               { background:#fee2e2; color:#991b1b; }
        .b-PAGADA                { background:#d1fae5; color:#065f46; }
        .b-PAGO_PARCIAL, .b-PAGO\ PARCIAL { background:#e0e7ff; color:#3730a3; }
        .b-DIFERENCIA_PENDIENTE, .b-DIFERENCIA\ PENDIENTE { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }
        .b-ANULADO               { background:#f1f5f9; color:#475569; }

        .group-title {
            font-size:12px; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:.4px;
            padding:10px 0 6px; border-bottom:2px solid #e2e8f0; margin-bottom:4px; margin-top:20px;
        }
        .group-title:first-child { margin-top:0; }

        .footer { margin-top:24px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:14px; }

        /* Orientación horizontal para imprimir y previsualizar */
        @page { size:A4 landscape; margin:5mm 6mm; }

        /* Vista previa en pantalla: ancho mínimo para apreciar todas las columnas */
        body { min-width:1100px; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; min-width:unset; }
            .no-print { display:none !important; }
            .body { padding:6px 10px; }
            .header { padding:12px 20px 10px; }
            .header h1 { font-size:15px; }

            /* Quitar clipping — los números DEBEN verse completos */
            .empresa-table thead th {
                font-size:7px !important;
                padding:5px 3px !important;
                white-space:normal !important;
                overflow:visible !important;
                text-overflow:clip !important;
                line-height:1.15;
            }
            .empresa-table tbody td {
                font-size:8px !important;
                padding:4px 3px !important;
                overflow:visible !important;
                text-overflow:clip !important;
                white-space:normal !important;
            }
            .empresa-table tbody td.mono {
                font-size:7.5px !important;
                white-space:nowrap !important;   /* números: nunca partir */
            }
            .empresa-table tbody tr.total-empresa td {
                font-size:8px !important;
                padding:5px 3px !important;
            }
            .badge { font-size:6.5px !important; padding:2px 4px !important; }
            .igv-note { font-size:6.5px !important; }
            .doc-relacion { font-size:7px !important; }
            .group-title { font-size:10px; padding:6px 0 4px; margin-top:12px; }
            .stat-value { font-size:13px; }
            .stat-label { font-size:7.5px; }
            .stats-grid { border-spacing:8px 6px; margin:10px 0 12px; }
        }

        /* Anchos fijos — 14 columnas (suma 100%) */
        col.col-rank   { width:1%; }
        col.col-emi    { width:6%; }
        col.col-vcto   { width:5%; }
        col.col-fact   { width:8%; }
        col.col-glosa  { width:8%; }
        col.col-sub    { width:8%; }
        col.col-pen    { width:6%; }
        col.col-rec    { width:7%; }
        col.col-total  { width:9%; }
        col.col-tipo   { width:5%; }
        col.col-abo    { width:7%; }
        col.col-fabo   { width:12%; }
        col.col-pend   { width:10%; }
        col.col-est    { width:8%; }
    </style>

</head>
<body>

{{-- ── TOP BAR ── --}}
<div class="no-print">
    <span class="hint">Reporte por empresa</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / PDF</button>

    <div style="position:relative;display:inline-block;">
        <button class="btn-excel" onclick="toggleExcelPanel(event)">📊 Exportar Excel ▾</button>
        <div class="excel-panel" id="excelPanel">
            <div class="excel-panel-item" onclick="exportarExcel('por_cliente')">
                <span class="ico">🗂️</span>
                <div class="desc">
                    <strong>Por cliente</strong>
                    <span>Hoja resumen + una hoja por empresa</span>
                </div>
            </div>
            <div class="excel-panel-item" onclick="exportarExcel('una_hoja')">
                <span class="ico">📋</span>
                <div class="desc">
                    <strong>Todo en una hoja</strong>
                    <span>Todas las facturas en una sola hoja, agrupadas por cliente</span>
                </div>
            </div>
            <div class="excel-panel-item" onclick="exportarExcel('resumen')">
                <span class="ico">📊</span>
                <div class="desc">
                    <strong>Resumen de clientes</strong>
                    <span>Una fila por cliente con totales y estados</span>
                </div>
            </div>
        </div>
    </div>
    <button class="btn-close" onclick="window.close()">Cerrar</button>

    <div class="send-inline">
        <span class="send-inline-label">Enviar a:</span>
        <select id="selUsuario" onchange="onUsuarioChange()">
            <option value="">— Seleccionar usuario —</option>
            @foreach($todosUsuarios as $u)
                <option value="{{ $u->id_usuario }}"
                        data-celular="{{ $u->celular ?? '' }}"
                        data-correo="{{ $u->correo ?? '' }}">
                    {{ $u->nombre }} {{ $u->apellido }}{{ $u->celular ? ' · '.$u->celular : '' }}
                </option>
            @endforeach
        </select>
        <button class="btn-send-wa"   id="btnEnvWA"   onclick="enviarReporte('whatsapp')" disabled>WhatsApp</button>
        <button class="btn-send-mail" id="btnEnvMail" onclick="enviarReporte('correo')"   disabled>Correo</button>
        <div class="send-result-bar" id="sendResultBar"></div>
    </div>
</div>

{{-- ── HEADER ── --}}
<div class="header">
    <h1>Reporte Financiero de Gestión — Por Empresa</h1>
    <div class="sub">
        PERÍODO: {{ $periodoLabel }} &nbsp;|&nbsp; ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

@php
    $totalFacturado     = (float) ($dashboard['total_facturado'] ?? 0);
    $saldoPendiente     = (float) ($dashboard['saldo_pendiente'] ?? 0);
    $cobrado            = (float) ($dashboard['cobrado'] ?? 0);
    $montoRecaudacion   = (float) ($dashboard['monto_recaudacion'] ?? 0);
    $recaudDepositada   = (float) ($dashboard['recaud_depositada'] ?? 0);
    $recaudSinConfirmar = (float) ($dashboard['recaud_sin_confirmar'] ?? 0);
@endphp

<table class="stats-grid three">
    <tr>
        <td class="sc-blue">
            <div class="stat-label">Total Facturado</div>
            <div class="stat-value">S/ {{ number_format($totalFacturado, 2) }}</div>
        </td>
        <td class="sc-amber">
            <div class="stat-label">Saldo Pendiente</div>
            <div class="stat-value">S/ {{ number_format($saldoPendiente, 2) }}</div>
        </td>
        <td class="sc-green">
            <div class="stat-label">Cobrado</div>
            <div class="stat-value">S/ {{ number_format($cobrado, 2) }}</div>
        </td>
    </tr>
</table>

<table class="stats-grid two" style="margin-top:0;">
    <tr>
        <td class="sc-red">
            <div class="stat-label">Monto de Recaudación</div>
            <div class="stat-value">S/ {{ number_format($montoRecaudacion, 2) }}</div>
        </td>
        <td class="sc-purple">
            <div class="stat-label" style="color:#7c3aed;">Recaud. Depositada</div>
            <div class="stat-value" style="color:#7c3aed;">S/ {{ number_format($recaudDepositada, 2) }}</div>
            @if($recaudSinConfirmar > 0)
                <div class="stat-sub">Sin confirmar: S/ {{ number_format($recaudSinConfirmar, 2) }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="body" id="contenidoReporte">

    @if($facturas->isEmpty())
        <p style="text-align:center;padding:40px;color:#64748b;">No se encontraron facturas.</p>

    @else
        {{-- Leyenda si hay NCs huérfanas --}}
        @if(!empty($orphanFacturaIds))
            <div class="leyenda-nc">
                <span>⚠</span>
                <span>
                    Las filas <strong>tachadas</strong> son notas de crédito cuya factura original no existe en el sistema.
                    <strong>No se incluyen en los totales</strong> ni en el saldo por cobrar.
                </span>
            </div>
        @endif

        @foreach($facturasAgrupadas as $empresa => $facturasPorEmpresa)
            @php
                $facturasPorEmpresaParaTotales = $facturasAgrupParaTotales[$empresa] ?? collect();
                $totEmpresa      = $facturasPorEmpresaParaTotales->sum('importe_total');
                $totSubEmpresa   = $facturasPorEmpresaParaTotales->sum('subtotal_gravado');
                $totRecEmpresa   = $facturasPorEmpresaParaTotales->sum('monto_recaudacion');
                $totAbono        = $facturasPorEmpresaParaTotales->sum('monto_abonado');
                $totPendEmpresa  = $facturasPorEmpresaParaTotales->sum(function ($fTot) {
                    return $fTot->estado === 'DIFERENCIA PENDIENTE'
                        ? max(0, ($fTot->importe_total ?? 0) - ($fTot->monto_recaudacion ?? 0))
                        : ($fTot->pendiente_display ?? $fTot->monto_pendiente ?? 0);
                });
            @endphp

            <div class="group-title">{{ $empresa }}</div>
            <table class="empresa-table">
                <colgroup>
                    <col class="col-rank">  {{-- # --}}
                    <col class="col-emi">   {{-- Emisión --}}
                    <col class="col-vcto">  {{-- Vcto --}}
                    <col class="col-fact">  {{-- Factura --}}
                    <col class="col-glosa"> {{-- Glosa --}}
                    <col class="col-sub">   {{-- SubTotal --}}
                    <col class="col-pen">   {{-- DETRAC/RENTE --}}
                    <col class="col-rec">   {{-- F.DETRAC --}}
                    <col class="col-total"> {{-- Total --}}
                    <col class="col-tipo">  {{-- Tipo --}}
                    <col class="col-abo">   {{-- Abonado --}}
                    <col class="col-fabo">  {{-- F.Abono --}}
                    <col class="col-pend">  {{-- Pendiente --}}
                    <col class="col-est">   {{-- Estado --}}
                </colgroup>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Emisión</th>
                    <th>Vcto.</th>
                    <th>Factura</th>
                    <th>Glosa</th>
                    <th class="r">Sub Total</th>
                    <th class="r">DETRAC/RENTE</th>
                    <th>F.DETRAC/F.RETEN</th>
                    <th class="r">Total</th>
                    <th>Tipo</th>
                    <th class="r">Abonado</th>
                    <th>Pagos (fecha / monto)</th>
                    <th class="r">Pendiente</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                @foreach($facturasPorEmpresa as $idx => $f)
                    @php
                        $esNcHuerfana     = in_array((int) $f->id_factura, $orphanFacturaIds);
                        $recaudacion      = $f->monto_recaudacion ?? 0;
                        $tipoRec          = $f->tipo_recaudacion  ?? '—';
                        $badgeKey         = str_replace([' '], ['_'], $f->estado);
                        // Pendiente corregido: para DIFERENCIA PENDIENTE restar la recaudación
                        $pendienteDisplay = $esNcHuerfana ? 0 :
                            ($f->estado === 'DIFERENCIA PENDIENTE'
                                ? max(0, ($f->importe_total ?? 0) - ($recaudacion))
                                : ($f->pendiente_display ?? $f->monto_pendiente ?? 0));
                        $pagosFila = $pagosMap->get($f->id_factura, collect());
                    @endphp
                    <tr class="{{ $esNcHuerfana ? 'nc-huerfana' : '' }}">
                        <td style="text-align:center;color:#64748b;font-size:9px;">{{ $idx + 1 }}</td>
                        <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                        <td class="mono">{{ $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
                        <td class="factura-num">
                            {{ $f->serie }}-{{ str_pad($f->numero, 6, '0', STR_PAD_LEFT) }}
                            @if(!empty($f->doc_relacion))
                                <span class="doc-relacion">{{ $f->doc_relacion }}</span>
                            @endif
                        </td>
                        <td class="td-glosa" style="font-size:9px;">{{ $f->glosa ?? '—' }}</td>
                        {{-- Sub Total = subtotal_gravado (base sin IGV) --}}
                        <td class="r mono">
                            @if(!$esNcHuerfana && ($f->subtotal_gravado ?? 0) > 0)
                                {{ $f->moneda }} {{ number_format($f->subtotal_gravado, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda.' '.number_format($recaudacion, 2) : '—' }}</td>
                        <td class="mono" style="font-size:8.5px;color:#d97706;">
                            {{ $f->fecha_recaudacion ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—' }}
                        </td>
                        {{-- Total = importe_total (antes "Importe"). IGV se muestra debajo. --}}
                        <td class="r mono">
                            {{ $f->moneda }} {{ number_format($f->importe_total, 2) }}
                            <span class="igv-note">
                                IGV: {{ ($f->monto_igv ?? 0) > 0 ? $f->moneda.' '.number_format($f->monto_igv, 2) : '—' }}
                            </span>
                        </td>
                        <td style="font-size:8.5px;font-weight:700;color:#7c3aed;">{{ $tipoRec !== '—' ? $tipoRec : '—' }}</td>
                        <td class="r abonado">
                            {{ ($f->monto_abonado ?? 0) > 0 ? $f->moneda.' '.number_format($f->monto_abonado, 2) : '—' }}
                        </td>
                        {{-- Pagos apilados: fecha / monto / banco por línea --}}
                        <td class="mono" style="font-size:8px;color:#059669;line-height:1.6;word-break:keep-all;overflow-wrap:break-word;">
                            @if($pagosFila->isEmpty())
                                <span style="color:#9ca3af;">—</span>
                            @else
                                @foreach($pagosFila as $pago)
                                    <div>{{ $pago->fecha_pago ? \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') : '—' }}&nbsp;&nbsp;{{ $f->moneda }}&nbsp;{{ number_format((float)$pago->monto_pagado, 2) }}
                                    @if(!empty($pago->banco_origen))<span style="color:#7c3aed;font-size:7.5px;"> · {{ $pago->banco_origen }}</span>@endif
                                    @if(!empty($pago->cuenta_pago))<span style="color:#64748b;font-size:7.5px;"> → {{ $pago->cuenta_pago }}</span>@endif
                                    </div>
                                @endforeach
                            @endif
                        </td>
                        <td class="r {{ $esNcHuerfana ? '' : 'pendiente-cell' }}">
                            @if($esNcHuerfana)
                                <span style="color:#9ca3af;">—</span>
                            @else
                                {{ $f->moneda }} {{ number_format($pendienteDisplay, 2) }}
                                @if($f->estado === 'DIFERENCIA PENDIENTE' && $recaudacion > 0)
                                    <div style="font-size:7.5px;color:#7c3aed;font-weight:600;">det. descontada</div>
                                @endif
                            @endif
                        </td>
                        <td class="td-estado">
                            <span class="badge b-{{ $badgeKey }}">{{ str_replace('_', ' ', $f->estado) }}</span>
                            @if($esNcHuerfana)
                                <span class="badge-nc-huerfana">sin factura</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- FILA TOTALES POR EMPRESA --}}
                <tr class="total-empresa">
                    <td colspan="5" style="font-size:10px;letter-spacing:.3px;">
                        SUBTOTAL — {{ $facturasPorEmpresaParaTotales->count() }} factura(s)
                    </td>
                    <td class="r" style="color:#fde68a;">{{ $totSubEmpresa > 0 ? 'S/ '.number_format($totSubEmpresa, 2) : '—' }}</td>
                    <td class="r" style="color:#fde68a;">{{ $totRecEmpresa > 0 ? 'S/ '.number_format($totRecEmpresa, 2) : '—' }}</td>
                    <td></td>
                    <td class="r" style="color:#fed7aa;">S/ {{ number_format($totEmpresa, 2) }}</td>
                    <td></td>
                    <td class="r" style="color:#a7f3d0;">{{ $totAbono > 0 ? 'S/ '.number_format($totAbono, 2) : '—' }}</td>
                    <td></td>
                    <td class="r" style="color:#fca5a5;font-size:11px;">S/ {{ number_format($totPendEmpresa, 2) }}</td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        @endforeach
    @endif

    <div class="footer">
        Período: {{ $periodoLabel }} &nbsp;·&nbsp;
        Estado: {{ $estadoLabel }} &nbsp;·&nbsp;
        Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp;
        Consorcio Rodriguez Caballero S.A.C.
    </div>
</div>

<script>
    const CSRF           = '{{ csrf_token() }}';
    const RUTA_WA        = '{{ route("reportes.enviar-whatsapp") }}';
    const RUTA_MAIL      = '{{ route("reportes.enviar-correo") }}';
    const FECHA_DESDE    = '{{ $fechaDesde ?? "" }}';
    const FECHA_HASTA    = '{{ $fechaHasta ?? "" }}';
    const ID_CLIENTE     = '{{ $idCliente ?? "" }}';
    const ESTADOS_FILTRO = {!! $estadosFiltroJson !!};
    const RUTA_EXCEL     = '{{ route("reportes.excel") }}';
    const TIPO_REPORTE   = 'detallado';
    // IDs de NCs huérfanas para exclusión en exportación Excel
    const ORPHAN_IDS     = {!! json_encode($orphanFacturaIds) !!};

    function onUsuarioChange() {
        const sel    = document.getElementById('selUsuario');
        const opt    = sel.options[sel.selectedIndex];
        const cel    = opt?.dataset?.celular || '';
        const correo = opt?.dataset?.correo  || '';
        document.getElementById('btnEnvWA').disabled   = !(sel.value && cel);
        document.getElementById('btnEnvMail').disabled = !(sel.value && correo);
        document.getElementById('sendResultBar').className = 'send-result-bar';
        document.getElementById('sendResultBar').textContent = '';
    }

    async function enviarReporte(canal) {
        const sel  = document.getElementById('selUsuario');
        if (!sel.value) return;
        const btnWA  = document.getElementById('btnEnvWA');
        const btnMail= document.getElementById('btnEnvMail');
        const result = document.getElementById('sendResultBar');
        btnWA.disabled = btnMail.disabled = true;
        result.className = 'send-result-bar';
        result.textContent = 'Enviando…';
        const body = new URLSearchParams({ usuario_id: sel.value, fecha_desde: FECHA_DESDE, fecha_hasta: FECHA_HASTA, tipo_reporte: TIPO_REPORTE, _token: CSRF });
        if (ID_CLIENTE) body.append('id_cliente', ID_CLIENTE);
        ESTADOS_FILTRO.forEach(e => body.append('estados[]', e));
        try {
            const res  = await fetch(canal === 'whatsapp' ? RUTA_WA : RUTA_MAIL, { method: 'POST', body });
            const data = await res.json();
            result.className   = 'send-result-bar ' + (data.success ? 'ok' : 'error');
            result.textContent = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || 'Error');
        } catch(err) {
            result.className   = 'send-result-bar error';
            result.textContent = '✗ Error de red: ' + err.message;
        } finally { onUsuarioChange(); }
    }

    function toggleExcelPanel(e) {
        e.stopPropagation();
        document.getElementById('excelPanel').classList.toggle('open');
    }
    document.addEventListener('click', () => document.getElementById('excelPanel')?.classList.remove('open'));

    function exportarExcel(modo) {
        document.getElementById('excelPanel').classList.remove('open');
        const params = new URLSearchParams();
        if (ID_CLIENTE)  params.append('id_cliente',  ID_CLIENTE);
        if (FECHA_DESDE) params.append('fecha_desde', FECHA_DESDE);
        if (FECHA_HASTA) params.append('fecha_hasta', FECHA_HASTA);
        ESTADOS_FILTRO.forEach(e => params.append('estados[]', e));
        params.append('modo', modo || 'por_cliente');
        window.location.href = `${RUTA_EXCEL}?${params.toString()}`;
    }

    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
