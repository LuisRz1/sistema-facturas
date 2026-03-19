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
            background: #1e293b; padding: 12px 24px;
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .no-print .hint { color: #94a3b8; font-size: 12px; white-space: nowrap; }
        .btn-print { background:#1d4ed8; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
        .btn-print:hover { background:#1e40af; }
        .btn-close { background:transparent; color:#64748b; border:1px solid #334155; padding:8px 14px; border-radius:6px; font-size:12px; cursor:pointer; }
        .btn-close:hover { background:#334155; color:#fff; }

        /* ── SEND BAR (no-print) ── */
        .send-bar { display:none; background:#0f2027; border-top:1px solid #334155; padding:12px 24px; align-items:center; gap:12px; flex-wrap:wrap; }
        .send-bar.visible { display:flex; }
        .send-bar .send-label { color:#94a3b8; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
        .btn-wa   { background:#22c55e; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .15s; }
        .btn-wa:hover:not(:disabled)   { background:#16a34a; }
        .btn-mail { background:#3b82f6; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background .15s; }
        .btn-mail:hover:not(:disabled) { background:#2563eb; }
        .btn-wa:disabled, .btn-mail:disabled { opacity:.55; cursor:not-allowed; }
        .contact-info { color:#64748b; font-size:12px; }
        .send-result { display:none; padding:8px 14px; border-radius:6px; font-size:12px; font-weight:700; }
        .send-result.ok    { background:#14532d; color:#86efac; }
        .send-result.error { background:#7f1d1d; color:#fca5a5; }

        /* ── REPORT ── */
        .header { background:#0f172a; color:#fff; text-align:center; padding:22px 32px 18px; }
        .header h1 { font-size:20px; font-weight:900; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px; }
        .header .sub { font-size:11px; font-weight:700; color:#94a3b8; letter-spacing:.5px; line-height:1.8; }

        .body { padding:24px 32px; }

        /* ── KPI ROW ── */
        .kpi-bar { display:flex; gap:0; border-bottom:2px solid #e2e8f0; margin-bottom:20px; }
        .kpi-box { flex:1; padding:14px 18px; border-right:1px solid #e2e8f0; text-align:center; }
        .kpi-box:last-child { border-right:none; }
        .kpi-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:4px; }
        .kpi-val { font-size:18px; font-weight:900; font-family:'Courier New',monospace; }
        .kpi-val.blue  { color:#1d4ed8; }
        .kpi-val.red   { color:#dc2626; }
        .kpi-val.amber { color:#d97706; }
        .kpi-val.green { color:#059669; }

        table { width:100%; border-collapse:collapse; margin-top:4px; }
        thead tr { background:#0f172a; color:#fff; }
        thead th { padding:9px 10px; text-align:left; font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; }
        thead th.r { text-align:right; }
        tbody tr { border-bottom:1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tbody td { padding:8px 10px; font-size:10.5px; vertical-align:middle; }
        tbody td.r { text-align:right; }
        tbody td.mono { font-family:'Courier New',monospace; }

        .factura-num { font-weight:800; font-family:'Courier New',monospace; }
        .detrac { color:#d97706; font-weight:700; font-family:'Courier New',monospace; }
        .neto   { color:#059669; font-weight:700; font-family:'Courier New',monospace; }
        .importe { font-family:'Courier New',monospace; }

        .group-title { font-size:12px; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:.4px; padding:10px 0 6px; border-bottom:2px solid #e2e8f0; margin-bottom:8px; margin-top:20px; }
        .group-title:first-child { margin-top:0; }

        .badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; }
        .b-PENDIENTE             { background:#fef3c7; color:#92400e; }
        .b-VENCIDO               { background:#fee2e2; color:#991b1b; }
        .b-PAGADA                { background:#d1fae5; color:#065f46; }
        .b-PAGO_PARCIAL,
        .b-PAGO\ PARCIAL         { background:#e0e7ff; color:#3730a3; }
        .b-POR_VALIDAR_DETRACCION,
        .b-POR\ VALIDAR\ DETRACCION { background:#fdf4ff; color:#6b21a8; border:1px solid #d8b4fe; }
        .b-ANULADA               { background:#f1f5f9; color:#64748b; }

        .footer { margin-top:24px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:14px; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print, .send-bar { display:none !important; }
            @page { size:A4 landscape; margin:10mm; }
        }
    </style>
</head>
<body>

{{-- ── TOP BAR ── --}}
<div class="no-print">
    <span class="hint">Reporte · Para guardar como PDF usa</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <button class="btn-close" onclick="window.close()">Cerrar</button>
</div>

{{-- ── SEND BAR — solo si hay usuario destino ── --}}
@if(!empty($usuarioDestino))
    <div class="send-bar visible" id="sendBar">
        <span class="send-label">Enviar a {{ $usuarioDestino->nombre }} {{ $usuarioDestino->apellido }}:</span>

        <button class="btn-wa" id="btnWA" onclick="enviarReporte('whatsapp')"
                @if(empty($usuarioDestino->celular)) disabled title="Sin celular registrado" @endif>
            WhatsApp
        </button>

        <button class="btn-mail" id="btnMail" onclick="enviarReporte('correo')"
                @if(empty($usuarioDestino->correo)) disabled title="Sin correo registrado" @endif>
            Correo
        </button>

        <span class="contact-info">
        @if(!empty($usuarioDestino->celular)) {{ $usuarioDestino->celular }}@endif
            @if(!empty($usuarioDestino->celular) && !empty($usuarioDestino->correo)) &nbsp;·&nbsp; @endif
            @if(!empty($usuarioDestino->correo)) {{ $usuarioDestino->correo }}@endif
    </span>

        <div class="send-result" id="sendResult"></div>
    </div>
@endif

{{-- ── HEADER ── --}}
<div class="header">
    <h1>Reporte Financiero de Gestión — Por Cliente</h1>
    <div class="sub">
        PERÍODO: {{ $periodoLabel }} &nbsp;|&nbsp; ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

{{-- ── KPIs ── --}}
@php
    $totalNeto = $resumen['total_bruto'] - $resumen['total_recaudacion'];
@endphp
<div class="kpi-bar">
    <div class="kpi-box"><div class="kpi-lbl">Total Facturas</div><div class="kpi-val blue">{{ $resumen['total_facturas'] }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Importe Bruto</div><div class="kpi-val red">S/ {{ number_format($resumen['total_bruto'],2) }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Total Recaudación</div><div class="kpi-val amber">S/ {{ number_format($resumen['total_recaudacion'],2) }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Saldo por Cobrar</div><div class="kpi-val red" style="font-size:16px;">S/ {{ number_format($resumen['saldo_cobrar'],2) }}</div></div>
</div>

<div class="body">

    @if($facturas->isEmpty())
        <p style="text-align:center;padding:40px;color:#64748b;">No se encontraron facturas con los filtros seleccionados.</p>

    @else
        @foreach($facturasAgrupadas as $empresa => $facturasPorEmpresa)
            @php
                $totalEmpresa = $facturasPorEmpresa->sum('monto_pendiente');
                $recEmpresa   = $facturasPorEmpresa->sum('monto_recaudacion');
            @endphp
            <div class="group-title">
                {{ $empresa }}
                <span style="float:right;font-size:10px;font-weight:700;color:#dc2626;">
                    Pendiente: S/ {{ number_format($totalEmpresa,2) }}
                    @if($recEmpresa > 0)
                        &nbsp;·&nbsp; Recaudación: S/ {{ number_format($recEmpresa,2) }}
                    @endif
                </span>
            </div>
            <table style="margin-bottom:4px;">
                <thead>
                <tr>
                    <th>#</th><th>Emisión</th><th>Vcto.</th><th>Factura</th><th>Glosa</th>
                    <th class="r">Importe</th><th class="r">Recaud.</th><th>Tipo</th>
                    <th class="r">Pendiente</th><th>Estado</th>
                </tr>
                </thead>
                <tbody>
                @foreach($facturasPorEmpresa as $index => $f)
                    @php
                        $recaudacion = $f->monto_recaudacion ?? 0;
                        $tipoRec     = $f->tipo_recaudacion ?? '—';
                        $badgeKey    = str_replace(' ','_', $f->estado);
                    @endphp
                    <tr>
                        <td style="text-align:center;color:#64748b;">{{ $index + 1 }}</td>
                        <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                        <td class="mono">{{ $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
                        <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero,8,'0',STR_PAD_LEFT) }}</td>
                        <td style="font-size:9px;max-width:160px;">{{ $f->glosa ? Str::limit($f->glosa,28) : '—' }}</td>
                        <td class="r importe">{{ $f->moneda }} {{ number_format($f->importe_total,2) }}</td>
                        <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda.' '.number_format($recaudacion,2) : '—' }}</td>
                        <td style="font-size:9px;font-weight:700;color:#7c3aed;">{{ $tipoRec !== '—' ? $tipoRec : '—' }}</td>
                        <td class="r" style="font-weight:800;font-family:'Courier New',monospace;color:#dc2626;">
                            {{ $f->moneda }} {{ number_format($f->monto_pendiente,2) }}
                        </td>
                        <td><span class="badge b-{{ $badgeKey }}">{{ str_replace('_',' ',$f->estado) }}</span></td>
                    </tr>
                @endforeach
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

@if(!empty($usuarioDestino))
    <script>
        const CSRF          = '{{ csrf_token() }}';
        const USUARIO_ID    = {{ $usuarioDestino->id_usuario }};
        const ESTADO_LABEL  = '{{ $estadoLabel }}';
        const PERIODO_LABEL = '{{ $periodoLabel }}';
        const RUTA_WA       = '{{ route("reportes.enviar-whatsapp") }}';
        const RUTA_MAIL     = '{{ route("reportes.enviar-correo") }}';
        const FECHA_DESDE   = '{{ $fechaDesde ?? "" }}';
        const FECHA_HASTA   = '{{ $fechaHasta ?? "" }}';
        const ESTADO        = '{{ $estado ?? "" }}';

        async function enviarReporte(canal) {
            const btnWA   = document.getElementById('btnWA');
            const btnMail = document.getElementById('btnMail');
            const result  = document.getElementById('sendResult');

            btnWA.disabled = btnMail.disabled = true;
            result.style.display = 'none';

            const ruta = canal === 'whatsapp' ? RUTA_WA : RUTA_MAIL;
            const body = new URLSearchParams({
                usuario_id:  USUARIO_ID,
                estado:      ESTADO,
                fecha_desde: FECHA_DESDE,
                fecha_hasta: FECHA_HASTA,
                _token:      CSRF,
            });

            try {
                const res  = await fetch(ruta, { method:'POST', body });
                const data = await res.json();
                result.textContent   = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || 'Error');
                result.className     = 'send-result ' + (data.success ? 'ok' : 'error');
                result.style.display = 'block';
            } catch(err) {
                result.textContent   = '✗ Error de red: ' + err.message;
                result.className     = 'send-result error';
                result.style.display = 'block';
            } finally {
                btnWA.disabled = btnMail.disabled = false;
            }
        }
    </script>
@endif

<script>
    window.addEventListener('load', function() { setTimeout(() => window.print(), 600); });
</script>
</body>
</html>
