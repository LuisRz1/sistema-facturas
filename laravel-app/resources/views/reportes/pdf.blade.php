<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero — CRC S.A.C.</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            background: #fff;
        }

        /* ── CABECERA ── */
        .header {
            background: #0f172a;
            color: #fff;
            text-align: center;
            padding: 22px 32px 18px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .header .sub {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: .5px;
            line-height: 1.7;
        }

        /* ── CUERPO ── */
        .body { padding: 28px 32px; }

        /* ── TABLA ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        thead tr {
            background: #0f172a;
            color: #fff;
        }
        thead th {
            padding: 9px 10px;
            text-align: left;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        thead th.r { text-align: right; }

        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td {
            padding: 8px 10px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        tbody td.r { text-align: right; }
        tbody td.mono { font-family: 'Courier New', monospace; }

        .factura-num { font-weight: 800; font-family: 'Courier New', monospace; }
        .detrac { color: #d97706; font-weight: 700; font-family: 'Courier New', monospace; }
        .neto   { color: #059669; font-weight: 700; font-family: 'Courier New', monospace; }
        .importe { font-family: 'Courier New', monospace; }

        /* Badges de estado */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .b-PENDIENTE  { background:#fef3c7; color:#92400e; }
        .b-POR_VENCER { background:#ffedd5; color:#c2410c; }
        .b-VENCIDA    { background:#fee2e2; color:#991b1b; }
        .b-PAGADA     { background:#d1fae5; color:#065f46; }
        .b-ANULADA    { background:#f1f5f9; color:#64748b; }
        .b-OBSERVADA  { background:#ede9fe; color:#5b21b6; }

        /* ── RESUMEN ── */
        .resumen {
            margin-top: 28px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 24px;
        }
        .resumen-title {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 16px;
            color: #0f172a;
        }
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .resumen-col { display: flex; flex-direction: column; gap: 10px; }
        .resumen-row { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .resumen-lbl { font-size: 10px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
        .resumen-val { font-size: 12px; font-weight: 800; font-family: 'Courier New', monospace; }
        .val-blue  { color: #1d4ed8; }
        .val-red   { color: #dc2626; }
        .val-green { color: #059669; }
        .val-dark  { color: #0f172a; }

        /* ── FOOTER ── */
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
        }

        /* ── PRINT ── */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

{{-- Botón de imprimir (no aparece en el PDF) --}}
<div class="no-print" style="background:#1e293b;padding:10px 24px;display:flex;align-items:center;gap:12px;">
    <span style="color:#94a3b8;font-size:12px;">Vista previa del reporte · Para guardar como PDF usa</span>
    <button onclick="window.print()"
            style="background:#1d4ed8;color:#fff;border:none;padding:7px 18px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">
        🖨 Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()"
            style="background:transparent;color:#64748b;border:1px solid #334155;padding:7px 14px;border-radius:6px;font-size:12px;cursor:pointer;">
        Cerrar
    </button>
</div>

{{-- CABECERA --}}
<div class="header">
    <h1>Reporte Financiero de Gestión</h1>
    <div class="sub">
        CLIENTE: {{ $clienteNombre }} &nbsp;|&nbsp; FILTRO ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

<div class="body">

    {{-- TABLA PRINCIPAL --}}
    @if($facturas->isEmpty())
        <p style="text-align:center;padding:40px;color:#64748b;">
            No se encontraron facturas con los filtros seleccionados.
        </p>
    @else
        <table>
            <thead>
            <tr>
                <th>Emisión</th>
                <th>Factura</th>
                <th>Glosa / Concepto</th>
                <th class="r">Imp. Bruto</th>
                <th class="r">Detracción</th>
                <th class="r">Neto Caja</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($facturas as $f)
                @php
                    $detrac    = $f->monto_recaudacion ?? 0;
                    $netoCaja  = $f->importe_total - $detrac;
                    $moneda    = $f->moneda === 'USD' ? 'USD' : 'S/';
                    $badgeClass = 'b-' . $f->estado;
                @endphp
                <tr>
                    <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                    <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</td>
                    <td style="max-width:220px;">{{ $f->glosa ?? '—' }}</td>
                    <td class="r importe">{{ $moneda }} {{ number_format($f->importe_total, 2) }}</td>
                    <td class="r detrac">{{ $detrac > 0 ? $moneda . ' ' . number_format($detrac, 2) : '—' }}</td>
                    <td class="r neto">{{ $moneda }} {{ number_format($netoCaja, 2) }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', $f->estado) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- RESUMEN --}}
        <div class="resumen">
            <div class="resumen-title">Resumen de Cartera (Vista Actual)</div>
            <div class="resumen-grid">
                <div class="resumen-col">
                    <div class="resumen-row">
                        <span class="resumen-lbl">Facturas Totales</span>
                        <span class="resumen-val val-dark">{{ $resumen['total_facturas'] }}</span>
                    </div>
                    <div class="resumen-row">
                        <span class="resumen-lbl">Facturas Pendientes</span>
                        <span class="resumen-val val-dark">{{ $resumen['pendientes'] }}</span>
                    </div>
                    <div class="resumen-row">
                        <span class="resumen-lbl">Facturas Pagadas</span>
                        <span class="resumen-val val-dark">{{ $resumen['pagadas'] }}</span>
                    </div>
                </div>
                <div class="resumen-col">
                    <div class="resumen-row">
                        <span class="resumen-lbl">Total Bruto</span>
                        <span class="resumen-val val-blue">S/ {{ number_format($resumen['total_bruto'], 2) }}</span>
                    </div>
                    <div class="resumen-row">
                        <span class="resumen-lbl">Total Detracciones</span>
                        <span class="resumen-val val-dark">S/ {{ number_format($resumen['total_recaudacion'], 2) }}</span>
                    </div>
                </div>
                <div class="resumen-col">
                    <div class="resumen-row">
                        <span class="resumen-lbl">Saldo por Cobrar</span>
                        <span class="resumen-val val-red">S/ {{ number_format($resumen['saldo_cobrar'], 2) }}</span>
                    </div>
                    <div class="resumen-row">
                        <span class="resumen-lbl">Total Neto Caja</span>
                        <span class="resumen-val val-green">S/ {{ number_format($resumen['total_neto'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp; Consorcio Rodriguez Caballero S.A.C. &nbsp;·&nbsp; Sistema de Facturación
    </div>
</div>

{{-- Auto-abrir diálogo de impresión --}}
<script>
    window.addEventListener('load', function() {
        // Pequeño delay para que cargue el CSS antes de imprimir
        setTimeout(() => window.print(), 600);
    });
</script>

</body>
</html>
