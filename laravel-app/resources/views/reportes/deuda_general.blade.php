<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Deuda General — CRC S.A.C.</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#111; background:#fff; }

        .no-print {
            background:#1e293b; padding:12px 24px;
            display:flex; align-items:center; gap:10px; flex-wrap:wrap;
            position:sticky; top:0; z-index:10;
        }
        .no-print .hint { color:#94a3b8; font-size:12px; white-space:nowrap; }
        .btn-print { background:#dc2626; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .btn-print:hover { background:#b91c1c; }
        .btn-excel { background:#16a34a; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
        .btn-excel:hover { background:#15803d; }
        .btn-close { background:transparent; color:#64748b; border:1px solid #334155; padding:8px 14px; border-radius:6px; font-size:12px; cursor:pointer; white-space:nowrap; }
        .btn-close:hover { background:#334155; color:#fff; }

        .send-inline { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-left:auto; border-left:1px solid #334155; padding-left:14px; }
        .send-inline-label { color:#94a3b8; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
        .send-inline select { height:34px; padding:0 10px; border:1px solid #475569; border-radius:6px; background:#0f172a; color:#e2e8f0; font-size:12px; min-width:190px; cursor:pointer; outline:none; }
        .send-inline select:focus { border-color:#f5c842; }
        .btn-send-wa  { background:#22c55e; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; opacity:.45; white-space:nowrap; }
        .btn-send-wa:not(:disabled)   { opacity:1; }
        .btn-send-wa:not(:disabled):hover { background:#16a34a; transform:translateY(-1px); }
        .btn-send-mail { background:#3b82f6; color:#fff; border:none; padding:7px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; opacity:.45; white-space:nowrap; }
        .btn-send-mail:not(:disabled) { opacity:1; }
        .btn-send-mail:not(:disabled):hover { background:#2563eb; transform:translateY(-1px); }
        .send-result-bar { display:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:700; white-space:nowrap; }
        .send-result-bar.ok    { background:#14532d; color:#86efac; display:block; }
        .send-result-bar.error { background:#7f1d1d; color:#fca5a5; display:block; }

        .header { background:#0f172a; color:#fff; text-align:center; padding:22px 32px 18px; }
        .header h1 { font-size:18px; font-weight:900; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px; }
        .header .sub { font-size:11px; font-weight:700; color:#94a3b8; letter-spacing:.4px; line-height:1.8; }

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

        .body { padding:20px 32px 32px; }

        /* TABLE */
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        thead tr { background:#1e3a5f; color:#fff; }
        thead th { padding:8px 10px; text-align:left; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        thead th.r { text-align:right; }
        thead th.c { text-align:center; }
        tbody tr { border-bottom:1px solid #e0ecff; }
        tbody tr:nth-child(even) { background:#f0f7ff; }
        tbody td { padding:7px 10px; font-size:10px; vertical-align:top; overflow:hidden; text-overflow:ellipsis; }
        tbody td.r { text-align:right; }
        tbody td.c { text-align:center; }

        /* Anchos fijos – 10 columnas en A4 landscape */
        col.col-rank  { width:3%; }
        col.col-emp   { width:19%; }
        col.col-sub   { width:8%; }   /* SubTotal */
        col.col-drec  { width:9%; }   /* Detrac/Rente Total */
        col.col-total { width:9%; }   /* Total */
        col.col-dcob  { width:9%; }   /* Detrac/Rent Cobrada */
        col.col-tcob  { width:10%; }  /* Total Cobrado */
        col.col-pend  { width:10%; }  /* Total Pendiente */
        col.col-cnt   { width:4%; }
        col.col-est   { width:19%; }

        .rank    { color:#94a3b8; font-size:10px; font-weight:700; text-align:center; }
        .empresa { font-weight:700; font-size:11px; color:#0f172a; }
        .ruc     { font-family:'Courier New',monospace; font-size:10px; color:#64748b; margin-top:1px; }
        .deuda-pen  { font-weight:800; font-family:'Courier New',monospace; font-size:11px; color:#dc2626; }
        .deuda-usd  { font-weight:700; font-family:'Courier New',monospace; font-size:10.5px; color:#1d4ed8; }
        .detrac-val { font-family:'Courier New',monospace; font-size:10px; color:#d97706; font-weight:600; }
        .pendiente-val { font-family:'Courier New',monospace; font-size:11px; font-weight:800; color:#0f172a; }

        .badge { display:inline-block; padding:2px 7px; border-radius:10px; font-size:8px; font-weight:800; text-transform:uppercase; letter-spacing:.3px; margin-right:2px; white-space:nowrap; }
        .b-PENDIENTE              { background:#fef3c7; color:#92400e; }
        .b-VENCIDO                { background:#fee2e2; color:#991b1b; }
        .b-PAGO_PARCIAL           { background:#e0e7ff; color:#3730a3; }
        .b-DIFERENCIA_PENDIENTE   { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }

        tr.total-row { background:#0f172a !important; }
        tr.total-row td { color:#fff; font-weight:800; padding:11px 12px; }

        .aviso { display:flex; align-items:center; gap:8px; background:#fef3c7; border:1px solid #fde68a; border-radius:6px; padding:10px 14px; margin-bottom:16px; font-size:11px; color:#92400e; font-weight:600; }
        .footer { margin-top:20px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:12px; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none !important; }
            @page { size:A4 landscape; margin:8mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <span class="hint">Reporte de Deuda General</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / PDF</button>
    <button class="btn-excel" onclick="exportarExcel()">Exportar Excel</button>
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
        <button class="btn-send-wa"   id="btnEnvWA"   onclick="enviarReporte('whatsapp')" disabled>📱 WhatsApp</button>
        <button class="btn-send-mail" id="btnEnvMail" onclick="enviarReporte('correo')"   disabled>✉ Correo</button>
        <div class="send-result-bar" id="sendResultBar"></div>
    </div>
</div>

<div class="header">
    <h1>Reporte de Deuda General</h1>
    <div class="sub">
        CONSORCIO RODRIGUEZ CABALLERO S.A.C. &nbsp;|&nbsp; PERÍODO: {{ $periodoLabel }}<br>
        ESTADO: {{ $estadoLabel ?? 'TODOS LOS PENDIENTES' }}
    </div>
</div>

@php
    $totalNetoPen  = $totalPendientePen;
    $totalSubtotalPen = (float) ($totalSubtotalPen ?? collect($clientes)->sum(fn($c) => (float) ($c['subtotal_pen'] ?? 0)));
    $countEmpresas = count($clientes);
    $countVencidas = collect($clientes)->filter(fn($c) => in_array('VENCIDO', $c['estados']))->count();
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

<div class="body">
    @if(empty($clientes))
        <p style="text-align:center;padding:48px;color:#64748b;">Sin deudas pendientes en el período seleccionado.</p>
    @else
        <div class="aviso">
            Facturas con estado: {{ $estadoLabel ?? 'PENDIENTE · VENCIDO · PAGO PARCIAL · DIFERENCIA PENDIENTE' }}.
            Ordenadas alfabéticamente por razón social. Recaudación (detracción/retención) siempre en PEN.
        </div>

        <table id="tablaDeuda">
            <colgroup>
                <col class="col-rank">
                <col class="col-emp">
                <col class="col-sub">
                <col class="col-drec">
                <col class="col-total">
                <col class="col-dcob">
                <col class="col-tcob">
                <col class="col-pend">
                <col class="col-cnt">
                <col class="col-est">
            </colgroup>
            <thead>
            <tr>
                <th class="c">#</th>
                <th>EMPRESA / CLIENTE</th>
                <th class="r">SUB TOTAL</th>
                <th class="r" style="color:#fde68a;">DETRAC/RENTE TOTAL</th>
                <th class="r">TOTAL (+IGV)</th>
                <th class="r" style="color:#93c5fd;">DETRAC/RENT COBRADA</th>
                <th class="r" style="color:#86efac;">TOTAL COBRADO</th>
                <th class="r" style="color:#fca5a5;">TOTAL PENDIENTE</th>
                <th class="c">N° FACT.</th>
                <th>ESTADOS</th>
            </tr>
            </thead>
            <tbody>
            @php $item = 1; @endphp
            @foreach($clientes as $c)
                @php
                    $tieneVenc  = in_array('VENCIDO', $c['estados']);
                    $tienePEN   = ($c['deuda_pen'] ?? 0) > 0;
                    $tieneUSD   = ($c['deuda_usd'] ?? 0) > 0;
                    $rowStyle   = $tieneVenc ? 'background:#fff5f5 !important;' : '';
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td class="rank">{{ $item++ }}</td>
                    <td>
                        <div class="empresa">{{ $c['razon_social'] }}</div>
                        <div class="ruc">{{ $c['ruc'] }}</div>
                    </td>
                    {{-- SUB TOTAL --}}
                    <td class="r">
                        @if($tienePEN && ($c['subtotal_pen'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#0369a1;font-weight:700;">S/ {{ number_format($c['subtotal_pen'], 2) }}</div>
                        @endif
                        @if($tieneUSD && ($c['subtotal_usd'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#1d4ed8;font-weight:700;">USD {{ number_format($c['subtotal_usd'], 2) }}</div>
                        @endif
                        @if(!$tienePEN && !$tieneUSD)<span style="color:#cbd5e1;">—</span>@endif
                    </td>
                    {{-- DETRAC/RENTE TOTAL (siempre en PEN soles) --}}
                    <td class="r">
                        @if(($c['recaudacion_pen'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#d97706;font-weight:800;">PEN {{ number_format($c['recaudacion_pen'], 2) }}</div>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    {{-- TOTAL (+IGV) --}}
                    <td class="r">
                        @if($tienePEN && $c['deuda_pen'] > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#dc2626;font-weight:800;">S/ {{ number_format($c['deuda_pen'], 2) }}</div>
                            @if(($c['igv_pen'] ?? 0) > 0)
                                <div style="font-size:8px;color:#64748b;">IGV S/ {{ number_format($c['igv_pen'], 2) }}</div>
                            @endif
                        @endif
                        @if($tieneUSD && $c['deuda_usd'] > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#1d4ed8;font-weight:700;">USD {{ number_format($c['deuda_usd'], 2) }}</div>
                            @if(($c['igv_usd'] ?? 0) > 0)
                                <div style="font-size:8px;color:#64748b;">IGV USD {{ number_format($c['igv_usd'], 2) }}</div>
                            @endif
                        @endif
                        @if(!$tienePEN && !$tieneUSD)<span style="color:#cbd5e1;">—</span>@endif
                    </td>
                    {{-- DETRAC/RENT COBRADA (soles; equiv USD abajo si aplica) --}}
                    <td class="r">
                        @if(($c['recaud_cobrada_pen'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#2563eb;font-weight:800;">PEN {{ number_format($c['recaud_cobrada_pen'], 2) }}</div>
                        @endif
                        @if(($c['recaudacion_usd'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#1d4ed8;">USD {{ number_format($c['recaudacion_usd'], 2) }}</div>
                        @endif
                        @if(($c['recaud_cobrada_pen'] ?? 0) == 0 && ($c['recaudacion_usd'] ?? 0) == 0)
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    {{-- TOTAL COBRADO --}}
                    <td class="r">
                        @if(($c['abonado_pen'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#059669;font-weight:800;">S/ {{ number_format($c['abonado_pen'], 2) }}</div>
                        @endif
                        @if(($c['abonado_usd'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#0ea5e9;font-weight:700;">USD {{ number_format($c['abonado_usd'], 2) }}</div>
                        @endif
                        @php $pagadas = ($c['pagadas_pen'] ?? 0) + ($c['pagadas_usd'] ?? 0); @endphp
                        @if($pagadas > 0)
                            <div style="font-size:8px;color:#059669;">{{ $pagadas }} factura(s) pagada(s)</div>
                        @endif
                        @if(($c['abonado_pen'] ?? 0) == 0 && ($c['abonado_usd'] ?? 0) == 0)
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    {{-- TOTAL PENDIENTE --}}
                    <td class="r">
                        @if(($c['pendiente_pen'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#dc2626;font-weight:800;">S/ {{ number_format($c['pendiente_pen'], 2) }}</div>
                        @endif
                        @if(($c['pendiente_usd'] ?? 0) > 0)
                            <div style="font-family:'Courier New',monospace;font-size:10px;color:#ef4444;font-weight:700;">USD {{ number_format($c['pendiente_usd'], 2) }}</div>
                        @endif
                        @if(($c['pendiente_pen'] ?? 0) == 0 && ($c['pendiente_usd'] ?? 0) == 0)
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="c" style="font-weight:700;color:#64748b;">{{ $c['facturas'] }}</td>
                    <td>
                        @foreach($c['estados'] as $estado)
                            @php $badgeKey = str_replace([' '], ['_'], $estado); @endphp
                            <span class="badge b-{{ $badgeKey }}">{{ str_replace('_',' ',$estado) }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach

            {{-- FILA TOTAL GENERAL --}}
            <tr class="total-row">
                <td class="c" style="font-size:9px;color:#94a3b8;">TOTAL</td>
                <td style="font-size:11px;color:#f1f5f9;">{{ $countEmpresas }} EMPRESAS</td>
                {{-- SubTotal --}}
                <td class="r">
                    <div style="font-family:'Courier New',monospace;color:#93c5fd;">S/ {{ number_format($totalSubtotalPen ?? 0, 2) }}</div>
                    @if(($totalSubtotalUsd ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#bfdbfe;">USD {{ number_format($totalSubtotalUsd, 2) }}</div>
                    @endif
                </td>
                {{-- Detrac/Rente Total (PEN) --}}
                <td class="r">
                    <span style="font-family:'Courier New',monospace;color:#fcd34d;">PEN {{ number_format($totalRecaudacionPen, 2) }}</span>
                </td>
                {{-- Total (+IGV) --}}
                <td class="r">
                    <div style="font-family:'Courier New',monospace;color:#fca5a5;">S/ {{ number_format($totalPen, 2) }}</div>
                    <div style="font-size:8px;color:#cbd5e1;">IGV: {{ ($totalIgvPen ?? 0) > 0 ? 'S/ '.number_format($totalIgvPen, 2) : '—' }}</div>
                    @if(($totalUsd ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#bfdbfe;">USD {{ number_format($totalUsd, 2) }}</div>
                        @if(($totalIgvUsd ?? 0) > 0)
                            <div style="font-size:8px;color:#cbd5e1;">IGV USD {{ number_format($totalIgvUsd, 2) }}</div>
                        @endif
                    @endif
                </td>
                {{-- Detrac/Rent Cobrada --}}
                <td class="r">
                    @if(($totalRecaudCobradaPen ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;color:#93c5fd;">PEN {{ number_format($totalRecaudCobradaPen, 2) }}</div>
                    @endif
                    @if(($totalRecaudacionUsd ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#bfdbfe;">USD {{ number_format($totalRecaudacionUsd, 2) }}</div>
                    @endif
                    @if(!($totalRecaudCobradaPen ?? 0) && !($totalRecaudacionUsd ?? 0))
                        <span style="color:#64748b;">—</span>
                    @endif
                </td>
                {{-- Total Cobrado --}}
                <td class="r">
                    @if(($totalAbonadoPen ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;color:#6ee7b7;">S/ {{ number_format($totalAbonadoPen, 2) }}</div>
                    @endif
                    @if(($totalAbonadoUsd ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#7dd3fc;">USD {{ number_format($totalAbonadoUsd, 2) }}</div>
                    @endif
                    @if(!($totalAbonadoPen ?? 0) && !($totalAbonadoUsd ?? 0))
                        <span style="color:#64748b;">—</span>
                    @endif
                </td>
                {{-- Total Pendiente --}}
                <td class="r">
                    <div style="font-family:'Courier New',monospace;font-size:12px;font-weight:900;color:#fca5a5;">S/ {{ number_format($totalPendientePen, 2) }}</div>
                    @if(($totalPendienteUsd ?? 0) > 0)
                        <div style="font-family:'Courier New',monospace;font-size:9.5px;color:#fca5a5;">USD {{ number_format($totalPendienteUsd, 2) }}</div>
                    @endif
                </td>
                <td class="c" style="color:#94a3b8;">{{ collect($clientes)->sum('facturas') }}</td>
                <td></td>
            </tr>
            </tbody>
        </table>

        <div class="footer">
            Período: {{ $periodoLabel }} &nbsp;·&nbsp;
            Estado: {{ $estadoLabel ?? 'TODOS LOS PENDIENTES' }} &nbsp;·&nbsp;
            Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp;
            Consorcio Rodriguez Caballero S.A.C.
        </div>
    @endif
</div>

<script>
    const CSRF           = '{{ csrf_token() }}';
    const RUTA_WA        = '{{ route("reportes.enviar-whatsapp") }}';
    const RUTA_MAIL      = '{{ route("reportes.enviar-correo") }}';
    const RUTA_EXCEL     = '{{ route("reportes.excel") }}';
    const FECHA_DESDE    = '{{ $fechaDesde ?? "" }}';
    const FECHA_HASTA    = '{{ $fechaHasta ?? "" }}';
    const ESTADOS_FILTRO = {!! $estadosFiltroJson !!};
    const TIPO_REPORTE   = 'general';

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
        const body = new URLSearchParams({ usuario_id:sel.value, fecha_desde:FECHA_DESDE, fecha_hasta:FECHA_HASTA, tipo_reporte:TIPO_REPORTE, _token:CSRF });
        ESTADOS_FILTRO.forEach(e => body.append('estados[]', e));
        try {
            const res  = await fetch(canal === 'whatsapp' ? RUTA_WA : RUTA_MAIL, { method:'POST', body });
            const data = await res.json();
            result.className   = 'send-result-bar ' + (data.success ? 'ok' : 'error');
            result.textContent = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || 'Error');
        } catch(err) {
            result.className   = 'send-result-bar error';
            result.textContent = '✗ Error de red: ' + err.message;
        } finally { onUsuarioChange(); }
    }

    function exportarExcel() {
        const params = new URLSearchParams();
        if (FECHA_DESDE) params.append('fecha_desde', FECHA_DESDE);
        if (FECHA_HASTA) params.append('fecha_hasta', FECHA_HASTA);
        ESTADOS_FILTRO.forEach(e => params.append('estados[]', e));
        params.append('tipo_reporte', TIPO_REPORTE);
        window.location.href = `${RUTA_EXCEL}?${params.toString()}`;
    }

    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
