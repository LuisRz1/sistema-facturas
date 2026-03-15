<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero — CRC S.A.C.</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            background: #fff;
        }

        .header {
            background-color: #0f172a;
            color: #ffffff;
            text-align: center;
            padding: 18px 24px 14px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .header .sub {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: .4px;
            line-height: 1.7;
        }

        .body { padding: 18px 24px; }

        table { width: 100%; border-collapse: collapse; margin-top: 4px; }

        thead tr { background-color: #0f172a; color: #ffffff; }
        thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        thead th.r { text-align: right; }

        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr.alt { background-color: #f8fafc; }
        tbody td { padding: 6px 8px; font-size: 9.5px; vertical-align: middle; }
        tbody td.r { text-align: right; }
        tbody td.mono { font-family: 'Courier New', monospace; font-size: 9px; }

        .factura-num { font-weight: 800; font-family: 'Courier New', monospace; font-size: 9px; }
        .detrac { color: #d97706; font-weight: 700; font-family: 'Courier New', monospace; }
        .neto   { color: #059669; font-weight: 700; font-family: 'Courier New', monospace; }
        .importe { font-family: 'Courier New', monospace; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .b-PENDIENTE  { background-color:#fef3c7; color:#92400e; }
        .b-POR_VENCER { background-color:#ffedd5; color:#c2410c; }
        .b-VENCIDA    { background-color:#fee2e2; color:#991b1b; }
        .b-PAGADA     { background-color:#d1fae5; color:#065f46; }
        .b-ANULADA    { background-color:#f1f5f9; color:#64748b; }
        .b-OBSERVADA  { background-color:#ede9fe; color:#5b21b6; }

        .resumen-box {
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 18px;
        }
        .resumen-title {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 12px;
            color: #0f172a;
        }
        .resumen-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .resumen-grid td {
            padding: 4px 8px;
            font-size: 9px;
            border: none;
            vertical-align: middle;
        }
        .res-lbl { color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .res-val { font-weight: 800; font-family: 'Courier New', monospace; font-size: 10px; }
        .val-blue  { color: #1d4ed8; }
        .val-green { color: #059669; }
        .val-red   { color: #dc2626; }
        .val-amber { color: #d97706; }
        .val-dark  { color: #0f172a; }

        .group-title {
            font-size: 11px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 8px 0 5px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 8px;
            margin-top: 18px;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .empty-msg {
            text-align: center;
            padding: 30px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte Financiero de Gestión</h1>
    <div class="sub">
        CLIENTE: {{ $clienteNombre }}<br>
        PERÍODO: {{ $periodoLabel }} &nbsp;|&nbsp; ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

<div class="body">

    @if($facturas->isEmpty())
        <div class="empty-msg">No se encontraron facturas con los filtros seleccionados.</div>

    @elseif($facturasAgrupadas)
        {{-- AGRUPADO POR EMPRESA --}}
        @foreach($facturasAgrupadas as $empresa => $facturasPorEmpresa)
            <div class="group-title">{{ $empresa }}</div>
            <table>
                <thead>
                <tr>
                    <th>#</th><th>Emisión</th><th>Factura</th><th>Glosa</th>
                    <th class="r">Importe</th><th class="r">%</th>
                    <th class="r">Recaudación</th><th>Tipo</th>
                    <th>F.Abono</th><th>Estado</th>
                </tr>
                </thead>
                <tbody>
                @foreach($facturasPorEmpresa as $index => $f)
                    @php
                        $recaudacion = $f->monto_recaudacion ?? 0;
                        $porcentaje  = $f->porcentaje_recaudacion ?? 0;
                        $tipoRec     = $f->tipo_recaudacion_actual ?? '—';
                        $bc          = 'b-' . $f->estado;
                    @endphp
                    <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                        <td style="text-align:center;color:#64748b;">{{ $index + 1 }}</td>
                        <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                        <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</td>
                        <td style="font-size:8px;max-width:120px;">{{ $f->glosa ? Str::limit($f->glosa, 25) : '—' }}</td>
                        <td class="r importe">{{ $f->moneda }} {{ number_format($f->importe_total, 2) }}</td>
                        <td class="r">{{ $porcentaje > 0 ? $porcentaje.'%' : '—' }}</td>
                        <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda.' '.number_format($recaudacion, 2) : '—' }}</td>
                        <td style="font-size:8px;font-weight:700;color:#7c3aed;">{{ $tipoRec }}</td>
                        <td class="mono">{{ $f->fecha_abono ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—' }}</td>
                        <td><span class="badge {{ $bc }}">{{ str_replace('_',' ',$f->estado) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach

    @else
        {{-- FORMATO SIMPLE --}}
        <table>
            <thead>
            <tr>
                <th>#</th><th>Emisión</th><th>Factura</th><th>Glosa</th>
                <th class="r">Importe</th><th class="r">%</th>
                <th class="r">Recaudación</th><th>Tipo</th>
                <th>F.Abono</th><th>Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($facturas as $index => $f)
                @php
                    $recaudacion = $f->monto_recaudacion ?? 0;
                    $porcentaje  = $f->porcentaje_recaudacion ?? 0;
                    $tipoRec     = $f->tipo_recaudacion_actual ?? '—';
                    $bc          = 'b-' . $f->estado;
                @endphp
                <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                    <td style="text-align:center;color:#64748b;">{{ $index + 1 }}</td>
                    <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                    <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</td>
                    <td style="font-size:8px;max-width:120px;">{{ $f->glosa ? Str::limit($f->glosa, 25) : '—' }}</td>
                    <td class="r importe">{{ $f->moneda }} {{ number_format($f->importe_total, 2) }}</td>
                    <td class="r">{{ $porcentaje > 0 ? $porcentaje.'%' : '—' }}</td>
                    <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda.' '.number_format($recaudacion, 2) : '—' }}</td>
                    <td style="font-size:8px;font-weight:700;color:#7c3aed;">{{ $tipoRec }}</td>
                    <td class="mono">{{ $f->fecha_abono ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—' }}</td>
                    <td><span class="badge {{ $bc }}">{{ str_replace('_',' ',$f->estado) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    {{-- RESUMEN --}}
    <div class="resumen-box">
        <div class="resumen-title">Resumen del Período</div>
        <table class="resumen-grid">
            <tr>
                <td class="res-lbl">Total Facturas</td>
                <td class="res-val val-blue">{{ $resumen['total_facturas'] }}</td>
                <td width="40"></td>
                <td class="res-lbl">Pagadas</td>
                <td class="res-val val-green">{{ $resumen['pagadas'] }}</td>
                <td width="40"></td>
                <td class="res-lbl">Pendientes/Vencidas</td>
                <td class="res-val val-amber">{{ $resumen['pendientes'] }}</td>
            </tr>
            <tr>
                <td class="res-lbl">Total Bruto</td>
                <td class="res-val val-dark">S/ {{ number_format($resumen['total_bruto'], 2) }}</td>
                <td></td>
                <td class="res-lbl">Total Recaudación</td>
                <td class="res-val val-amber">S/ {{ number_format($resumen['total_recaudacion'], 2) }}</td>
                <td></td>
                <td class="res-lbl">Total Neto Caja</td>
                <td class="res-val val-green">S/ {{ number_format($resumen['total_neto'], 2) }}</td>
            </tr>
            <tr>
                <td class="res-lbl">Saldo por Cobrar</td>
                <td class="res-val val-red">S/ {{ number_format($resumen['saldo_cobrar'], 2) }}</td>
                <td colspan="6"></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Período: {{ $periodoLabel }} &nbsp;·&nbsp;
        Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp;
        Consorcio Rodriguez Caballero S.A.C. &nbsp;·&nbsp; Sistema de Facturación
    </div>
</div>

</body>
</html>
