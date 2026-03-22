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
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        .no-print .hint { color: #94a3b8; font-size: 12px; white-space: nowrap; }
        .btn-print {
            background:#1d4ed8; color:#fff; border:none; padding:8px 18px;
            border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:6px; white-space:nowrap;
        }
        .btn-print:hover { background:#1e40af; }
        .btn-close {
            background:transparent; color:#64748b; border:1px solid #334155;
            padding:8px 14px; border-radius:6px; font-size:12px; cursor:pointer;
            white-space:nowrap;
        }
        .btn-close:hover { background:#334155; color:#fff; }

        /* ── SELECTOR DE USUARIO INLINE ── */
        .send-inline {
            display:flex; align-items:center; gap:8px; flex-wrap:wrap;
            margin-left:auto; border-left:1px solid #334155; padding-left:14px;
        }
        .send-inline-label {
            color:#94a3b8; font-size:11px; font-weight:700;
            text-transform:uppercase; letter-spacing:.05em; white-space:nowrap;
        }
        .send-inline select {
            height:34px; padding:0 10px; border:1px solid #475569;
            border-radius:6px; background:#0f172a; color:#e2e8f0;
            font-size:12px; min-width:190px; cursor:pointer; outline:none;
        }
        .send-inline select:focus { border-color:#1d4ed8; }
        .btn-send-wa {
            background:#22c55e; color:#fff; border:none; padding:7px 14px;
            border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:5px; white-space:nowrap;
            transition:all .15s; opacity:.45;
        }
        .btn-send-wa:not(:disabled) { opacity:1; }
        .btn-send-wa:not(:disabled):hover { background:#16a34a; transform:translateY(-1px); }
        .btn-send-mail {
            background:#3b82f6; color:#fff; border:none; padding:7px 14px;
            border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; gap:5px; white-space:nowrap;
            transition:all .15s; opacity:.45;
        }
        .btn-send-mail:not(:disabled) { opacity:1; }
        .btn-send-mail:not(:disabled):hover { background:#2563eb; transform:translateY(-1px); }
        .send-result-bar {
            display:none; padding:6px 12px; border-radius:6px;
            font-size:12px; font-weight:700; white-space:nowrap;
        }
        .send-result-bar.ok    { background:#14532d; color:#86efac; display:block; }
        .send-result-bar.error { background:#7f1d1d; color:#fca5a5; display:block; }

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
            .no-print { display:none !important; }
            @page { size:A4 landscape; margin:10mm; }
        }
    </style>
</head>
<body>

{{-- ── TOP BAR con selector de usuario inline ── --}}
<div class="no-print">
    <span class="hint">Reporte · Para guardar como PDF usa</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <button class="btn-close" onclick="window.close()">Cerrar</button>

    {{-- Selector de usuario inline --}}
    <div class="send-inline">
        <span class="send-inline-label">Enviar a:</span>
        <select id="selUsuario" onchange="onUsuarioChange()">
            <option value="">— Seleccionar usuario —</option>
            @foreach($todosUsuarios as $u)
                <option value="{{ $u->id_usuario }}"
                        data-celular="{{ $u->celular ?? '' }}"
                        data-correo="{{ $u->correo ?? '' }}">
                    {{ $u->nombre }} {{ $u->apellido }}
                    @if($u->celular) · {{ $u->celular }}@endif
                </option>
            @endforeach
        </select>
        <button class="btn-send-wa" id="btnEnvWA" onclick="enviarReporte('whatsapp')" disabled>
            WhatsApp
        </button>
        <button class="btn-send-mail" id="btnEnvMail" onclick="enviarReporte('correo')" disabled>
            Correo
        </button>
        <div class="send-result-bar" id="sendResultBar"></div>
    </div>
</div>

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

<script>
    const CSRF          = '{{ csrf_token() }}';
    const RUTA_WA       = '{{ route("reportes.enviar-whatsapp") }}';
    const RUTA_MAIL     = '{{ route("reportes.enviar-correo") }}';
    const FECHA_DESDE   = '{{ $fechaDesde ?? "" }}';
    const FECHA_HASTA   = '{{ $fechaHasta ?? "" }}';
    const ID_CLIENTE    = '{{ $idCliente ?? "" }}';
    const ESTADOS_FILTRO = {!! $estadosFiltroJson !!};

    function onUsuarioChange() {
        const sel    = document.getElementById('selUsuario');
        const opt    = sel.options[sel.selectedIndex];
        const cel    = opt?.dataset?.celular || '';
        const correo = opt?.dataset?.correo  || '';
        const btnWA  = document.getElementById('btnEnvWA');
        const btnMail= document.getElementById('btnEnvMail');

        btnWA.disabled  = !(sel.value && cel);
        btnMail.disabled= !(sel.value && correo);
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

        const body = new URLSearchParams({
            usuario_id:  sel.value,
            fecha_desde: FECHA_DESDE,
            fecha_hasta: FECHA_HASTA,
            _token:      CSRF,
        });
        if (ID_CLIENTE) body.append('id_cliente', ID_CLIENTE);
        ESTADOS_FILTRO.forEach(e => body.append('estados[]', e));

        try {
            const ruta = canal === 'whatsapp' ? RUTA_WA : RUTA_MAIL;
            const res  = await fetch(ruta, { method: 'POST', body });
            const data = await res.json();
            result.className   = 'send-result-bar ' + (data.success ? 'ok' : 'error');
            result.textContent = (data.success ? '✓ ' : '✗ ') + (data.message || data.error || 'Error');
        } catch(err) {
            result.className   = 'send-result-bar error';
            result.textContent = '✗ Error de red: ' + err.message;
        } finally {
            onUsuarioChange();
        }
    }

    window.addEventListener('load', function() { setTimeout(() => window.print(), 600); });
</script>
</body>
</html>
