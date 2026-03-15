<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero — CRC S.A.C.</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #fff; }

        /* ── TOP BAR (no-print) ── */
        .no-print {
            background: #1e293b;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .no-print .hint {
            color: #94a3b8;
            font-size: 12px;
            font-family: Arial, sans-serif;
            white-space: nowrap;
        }

        .btn-print {
            background: #1d4ed8; color: #fff; border: none;
            padding: 8px 18px; border-radius: 6px; font-size: 12px;
            font-weight: 700; cursor: pointer; font-family: Arial, sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-print:hover { background: #1e40af; }

        .btn-close {
            background: transparent; color: #64748b;
            border: 1px solid #334155; padding: 8px 14px;
            border-radius: 6px; font-size: 12px; cursor: pointer;
            font-family: Arial, sans-serif;
        }
        .btn-close:hover { background: #334155; color: #fff; }

        /* ── SEND PANEL (no-print) ── */
        .send-bar {
            display: none;
            background: #0f2027;
            border-top: 1px solid #334155;
            padding: 12px 24px;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-family: Arial, sans-serif;
        }
        .send-bar.visible { display: flex; }

        .send-bar .send-label {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }

        .btn-wa {
            background: #22c55e; color: #fff; border: none;
            padding: 8px 16px; border-radius: 6px; font-size: 12px;
            font-weight: 700; cursor: pointer; font-family: Arial, sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background .15s;
        }
        .btn-wa:hover:not(:disabled) { background: #16a34a; }
        .btn-wa:disabled { opacity: .55; cursor: not-allowed; }

        .btn-mail {
            background: #3b82f6; color: #fff; border: none;
            padding: 8px 16px; border-radius: 6px; font-size: 12px;
            font-weight: 700; cursor: pointer; font-family: Arial, sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background .15s;
        }
        .btn-mail:hover:not(:disabled) { background: #2563eb; }
        .btn-mail:disabled { opacity: .55; cursor: not-allowed; }

        .contact-info {
            color: #64748b;
            font-size: 12px;
        }

        .send-result {
            display: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            font-family: Arial, sans-serif;
        }
        .send-result.ok    { background: #14532d; color: #86efac; }
        .send-result.error { background: #7f1d1d; color: #fca5a5; }

        /* ── REPORT ── */
        .header {
            background: #0f172a; color: #fff;
            text-align: center; padding: 22px 32px 18px;
        }
        .header h1 { font-size: 20px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .header .sub { font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: .5px; line-height: 1.8; }

        .body { padding: 24px 32px; }

        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead tr { background: #0f172a; color: #fff; }
        thead th { padding: 9px 10px; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; }
        thead th.r { text-align: right; }

        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 8px 10px; font-size: 10.5px; vertical-align: middle; }
        tbody td.r { text-align: right; }
        tbody td.mono { font-family: 'Courier New', monospace; }

        .factura-num { font-weight: 800; font-family: 'Courier New', monospace; }
        .detrac { color: #d97706; font-weight: 700; font-family: 'Courier New', monospace; }
        .neto   { color: #059669; font-weight: 700; font-family: 'Courier New', monospace; }
        .importe { font-family: 'Courier New', monospace; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; }
        .b-PENDIENTE  { background:#fef3c7; color:#92400e; }
        .b-POR_VENCER { background:#ffedd5; color:#c2410c; }
        .b-VENCIDA    { background:#fee2e2; color:#991b1b; }
        .b-PAGADA     { background:#d1fae5; color:#065f46; }
        .b-ANULADA    { background:#f1f5f9; color:#64748b; }
        .b-OBSERVADA  { background:#ede9fe; color:#5b21b6; }

        .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 14px; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print, .send-bar { display: none !important; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

{{-- ── TOP BAR ── --}}
<div class="no-print">
    <span class="hint">Vista previa del reporte · Para guardar como PDF usa</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <button class="btn-close" onclick="window.close()">Cerrar</button>
</div>

{{-- ── SEND BAR (solo si hay un cliente específico) ── --}}
@if($idCliente)
    <div class="send-bar visible" id="sendBar">
        <span class="send-label">Enviar este reporte al cliente:</span>

        <button class="btn-wa" id="btnWA" onclick="enviarReporte('whatsapp')"
                @if(!$clienteCelular) disabled title="Sin número de celular registrado" @endif>
             WhatsApp
        </button>

        <button class="btn-mail" id="btnMail" onclick="enviarReporte('correo')"
                @if(!$clienteCorreo) disabled title="Sin correo registrado" @endif>
             Correo
        </button>

        <span class="contact-info">
        @if($clienteCelular) {{ $clienteCelular }}@endif
            @if($clienteCelular && $clienteCorreo) &nbsp;·&nbsp; @endif
            @if($clienteCorreo) {{ $clienteCorreo }}@endif
            @if(!$clienteCelular && !$clienteCorreo)<span style="color:#ef4444;">Sin datos de contacto</span>@endif
    </span>

        <div class="send-result" id="sendResult"></div>
    </div>
@endif

{{-- ── REPORT ── --}}
<div class="header">
    <h1>Reporte Financiero de Gestión</h1>
    <div class="sub">
        CLIENTE: {{ $clienteNombre }} &nbsp;|&nbsp; PERÍODO: {{ $periodoLabel }} &nbsp;|&nbsp; ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

<div class="body">

    @if($facturas->isEmpty())
        <p style="text-align:center;padding:40px;color:#64748b;">
            No se encontraron facturas con los filtros seleccionados.
        </p>

    @elseif($facturasAgrupadas)
        {{-- FORMATO AGRUPADO POR EMPRESA --}}
        @foreach($facturasAgrupadas as $empresa => $facturasPorEmpresa)
            <div style="margin-bottom:32px;page-break-inside:avoid;">
                <h2 style="font-size:13px;font-weight:900;color:#0f172a;margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;padding-bottom:6px;border-bottom:2px solid #e2e8f0;">
                    {{ $empresa }}
                </h2>
                <table>
                    <thead>
                    <tr>
                        <th>Nro.</th><th>Emisión</th><th>Factura</th><th>Glosa</th>
                        <th class="r">Importe</th><th class="r">%</th>
                        <th class="r">Recaudación</th><th>Tipo</th>
                        <th>F. Abono</th><th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($facturasPorEmpresa as $index => $f)
                        @php
                            $recaudacion    = $f->monto_recaudacion ?? 0;
                            $porcentaje     = $f->porcentaje_recaudacion ?? 0;
                            $tipoRecaudacion= $f->tipo_recaudacion_actual ?? '—';
                            $badgeClass     = 'b-' . $f->estado;
                        @endphp
                        <tr>
                            <td style="text-align:center;color:#64748b;">{{ $index + 1 }}</td>
                            <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                            <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</td>
                            <td style="font-size:9px;">{{ $f->glosa ? Str::limit($f->glosa, 22) : '—' }}</td>
                            <td class="r importe">{{ $f->moneda }} {{ number_format($f->importe_total, 2) }}</td>
                            <td class="r importe">{{ $porcentaje > 0 ? $porcentaje . '%' : '—' }}</td>
                            <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda . ' ' . number_format($recaudacion, 2) : '—' }}</td>
                            <td style="font-size:9px;font-weight:600;color:#7c3aed;">{{ $tipoRecaudacion }}</td>
                            <td class="mono">{{ $f->fecha_abono ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', $f->estado) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

    @else
        {{-- FORMATO SIMPLE (cliente específico) --}}
        <table>
            <thead>
            <tr>
                <th>Nro.</th><th>Emisión</th><th>Factura</th><th>Glosa</th>
                <th class="r">Importe</th><th class="r">%</th>
                <th class="r">Recaudación</th><th>Tipo</th>
                <th>F. Abono</th><th>Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($facturas as $index => $f)
                @php
                    $recaudacion    = $f->monto_recaudacion ?? 0;
                    $porcentaje     = $f->porcentaje_recaudacion ?? 0;
                    $tipoRecaudacion= $f->tipo_recaudacion_actual ?? '—';
                    $badgeClass     = 'b-' . $f->estado;
                @endphp
                <tr>
                    <td style="text-align:center;color:#64748b;">{{ $index + 1 }}</td>
                    <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                    <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero, 8, '0', STR_PAD_LEFT) }}</td>
                    <td style="font-size:9px;">{{ $f->glosa ? Str::limit($f->glosa, 22) : '—' }}</td>
                    <td class="r importe">{{ $f->moneda }} {{ number_format($f->importe_total, 2) }}</td>
                    <td class="r importe">{{ $porcentaje > 0 ? $porcentaje . '%' : '—' }}</td>
                    <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda . ' ' . number_format($recaudacion, 2) : '—' }}</td>
                    <td style="font-size:9px;font-weight:600;color:#7c3aed;">{{ $tipoRecaudacion }}</td>
                    <td class="mono">{{ $f->fecha_abono ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—' }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ str_replace('_', ' ', $f->estado) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Período: {{ $periodoLabel }} &nbsp;·&nbsp;
        Generado el {{ now()->format('d/m/Y H:i') }} &nbsp;·&nbsp;
        Consorcio Rodriguez Caballero S.A.C. &nbsp;·&nbsp; Sistema de Facturación
    </div>
</div>

@if($idCliente)
    <script>
        const CSRF     = '{{ csrf_token() }}';
        const ID_CLI   = '{{ $idCliente }}';
        const ESTADO   = '{{ $estado ?? "" }}';
        const F_DESDE  = '{{ $fechaDesde ?? "" }}';
        const F_HASTA  = '{{ $fechaHasta ?? "" }}';
        const RUTA_WA  = '{{ route("reportes.enviar-whatsapp") }}';
        const RUTA_MAIL= '{{ route("reportes.enviar-correo") }}';

        async function enviarReporte(canal) {
            const btnWA   = document.getElementById('btnWA');
            const btnMail = document.getElementById('btnMail');
            const result  = document.getElementById('sendResult');

            btnWA.disabled = btnMail.disabled = true;
            result.style.display = 'none';

            const ruta = canal === 'whatsapp' ? RUTA_WA : RUTA_MAIL;
            const body = new URLSearchParams({
                id_cliente:  ID_CLI,
                estado:      ESTADO,
                fecha_desde: F_DESDE,
                fecha_hasta: F_HASTA,
                _token:      CSRF,
            });

            try {
                const res  = await fetch(ruta, { method: 'POST', body });
                const data = await res.json();
                result.textContent  = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || 'Error desconocido');
                result.className    = 'send-result ' + (data.success ? 'ok' : 'error');
                result.style.display = 'block';
            } catch(err) {
                result.textContent  = '✗ Error de red: ' + err.message;
                result.className    = 'send-result error';
                result.style.display = 'block';
            } finally {
                btnWA.disabled = btnMail.disabled = false;
            }
        }
    </script>
@endif

<script>
    window.addEventListener('load', function() {
        setTimeout(() => window.print(), 600);
    });
</script>
</body>
</html>
