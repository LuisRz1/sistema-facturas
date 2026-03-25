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

        /* ── HEADER ── */
        .header { background:#0f172a; color:#fff; text-align:center; padding:22px 32px 18px; }
        .header h1 { font-size:20px; font-weight:900; letter-spacing:1px; text-transform:uppercase; margin-bottom:8px; }
        .header .sub { font-size:11px; font-weight:700; color:#94a3b8; line-height:1.8; }

        /* ── KPI BAR ── */
        .kpi-bar { display:flex; gap:0; border-bottom:2px solid #e2e8f0; margin-bottom:20px; }
        .kpi-box { flex:1; padding:14px 18px; border-right:1px solid #e2e8f0; text-align:center; }
        .kpi-box:last-child { border-right:none; }
        .kpi-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:4px; }
        .kpi-val { font-size:18px; font-weight:900; font-family:'Courier New',monospace; }
        .kpi-val.blue  { color:#1d4ed8; }
        .kpi-val.red   { color:#dc2626; }
        .kpi-val.amber { color:#d97706; }

        /* ── TABLE LAYOUT FIJO para evitar descuadre ── */
        .body { padding:24px 32px; }
        .empresa-table { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:4px; }
        .empresa-table colgroup col:nth-child(1)  { width:3%; }   /* # */
        .empresa-table colgroup col:nth-child(2)  { width:7%; }   /* Emisión */
        .empresa-table colgroup col:nth-child(3)  { width:7%; }   /* Vcto */
        .empresa-table colgroup col:nth-child(4)  { width:10%; }  /* Factura */
        .empresa-table colgroup col:nth-child(5)  { width:10%; }  /* Glosa */
        .empresa-table colgroup col:nth-child(6)  { width:8%; }   /* Importe */
        .empresa-table colgroup col:nth-child(7)  { width:8%; }   /* Det. */
        .empresa-table colgroup col:nth-child(8)  { width:7%; }   /* F.Det */
        .empresa-table colgroup col:nth-child(9)  { width:7%; }   /* Tipo */
        .empresa-table colgroup col:nth-child(10) { width:8%; }   /* Abonado */
        .empresa-table colgroup col:nth-child(11) { width:7%; }   /* F.Abono */
        .empresa-table colgroup col:nth-child(12) { width:8%; }   /* Pendiente */
        .empresa-table colgroup col:nth-child(13) { width:10%; }  /* Estado */

        .empresa-table thead tr { background:#0f172a; color:#fff; }
        .empresa-table thead th {
            padding:7px 6px; text-align:left; font-size:8.5px; font-weight:700;
            text-transform:uppercase; letter-spacing:.5px; overflow:hidden;
            white-space:nowrap; text-overflow:ellipsis;
        }
        .empresa-table thead th.r { text-align:right; }
        .empresa-table tbody tr { border-bottom:1px solid #f1f5f9; }
        .empresa-table tbody tr:nth-child(even) { background:#f8fafc; }
        .empresa-table tbody td {
            padding:7px 6px; font-size:10px; vertical-align:middle;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .empresa-table tbody td.r { text-align:right; }
        .empresa-table tbody td.mono { font-family:'Courier New',monospace; font-size:9.5px; }

        /* Fila de totales por empresa */
        .empresa-table tbody tr.total-empresa {
            background:#1e293b !important; border-top:2px solid #334155;
        }
        .empresa-table tbody tr.total-empresa td {
            color:#fff; font-weight:800; font-size:10px; padding:8px 6px;
        }
        .empresa-table tbody tr.total-empresa td.r { text-align:right; }

        .factura-num { font-weight:800; font-family:'Courier New',monospace; }
        .detrac  { color:#d97706; font-weight:700; font-family:'Courier New',monospace; }
        .abonado { color:#059669; font-weight:700; font-family:'Courier New',monospace; }
        .pendiente-cell { color:#dc2626; font-weight:700; font-family:'Courier New',monospace; }

        .badge { display:inline-block; padding:2px 6px; border-radius:20px; font-size:8px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; }
        .b-PENDIENTE             { background:#fef3c7; color:#92400e; }
        .b-VENCIDO               { background:#fee2e2; color:#991b1b; }
        .b-PAGADA                { background:#d1fae5; color:#065f46; }
        .b-PAGO_PARCIAL, .b-PAGO\ PARCIAL { background:#e0e7ff; color:#3730a3; }
        .b-DIFERENCIA_PENDIENTE, .b-DIFERENCIA\ PENDIENTE { background:#fce7f3; color:#9d174d; border:1px solid #fbcfe8; }

        .group-title {
            font-size:12px; font-weight:900; color:#0f172a; text-transform:uppercase; letter-spacing:.4px;
            padding:10px 0 6px; border-bottom:2px solid #e2e8f0; margin-bottom:4px; margin-top:20px;
        }
        .group-title:first-child { margin-top:0; }

        .footer { margin-top:24px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:14px; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .no-print { display:none !important; }
            @page { size:A4 landscape; margin:8mm; }
        }
    </style>
</head>
<body>

{{-- ── TOP BAR ── --}}
<div class="no-print">
    <span class="hint">Reporte por empresa</span>
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / PDF</button>
    <button class="btn-excel" onclick="exportarExcel()">📊 Exportar Excel</button>
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

{{-- ── HEADER ── --}}
<div class="header">
    <h1>Reporte Financiero de Gestión — Por Empresa</h1>
    <div class="sub">
        PERÍODO: {{ $periodoLabel }} &nbsp;|&nbsp; ESTADO: {{ $estadoLabel }}<br>
        CONSORCIO RODRIGUEZ CABALLERO S.A.C.
    </div>
</div>

{{-- ── KPIs ── --}}
<div class="kpi-bar">
    <div class="kpi-box"><div class="kpi-lbl">Total Facturas</div><div class="kpi-val blue">{{ $resumen['total_facturas'] }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Importe Bruto</div><div class="kpi-val red">S/ {{ number_format($resumen['total_bruto'],2) }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Total Recaudación</div><div class="kpi-val amber">S/ {{ number_format($resumen['total_recaudacion'],2) }}</div></div>
    <div class="kpi-box"><div class="kpi-lbl">Saldo por Cobrar</div><div class="kpi-val red" style="font-size:16px;">S/ {{ number_format($resumen['saldo_cobrar'],2) }}</div></div>
</div>

<div class="body" id="contenidoReporte">

    @if($facturas->isEmpty())
        <p style="text-align:center;padding:40px;color:#64748b;">No se encontraron facturas.</p>

    @else
        @foreach($facturasAgrupadas as $empresa => $facturasPorEmpresa)
            @php
                /* Totales por empresa: usar facturasAgrupParaTotales para NO incluir huérfanos */
                $facturasPorEmpresaParaTotales = $facturasAgrupParaTotales[$empresa] ?? collect();
                $totEmpresa      = $facturasPorEmpresaParaTotales->sum('importe_total');
                $totRecEmpresa   = $facturasPorEmpresaParaTotales->sum('monto_recaudacion');
                $totAbono        = $facturasPorEmpresaParaTotales->sum('monto_abonado');
                $totPendEmpresa  = $facturasPorEmpresaParaTotales->sum('pendiente_display');
            @endphp

            <div class="group-title">{{ $empresa }}</div>

            <table class="empresa-table">
                <colgroup>
                    <col><col><col><col><col>
                    <col><col><col><col><col>
                    <col><col><col>
                </colgroup>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Emisión</th>
                    <th>Vcto.</th>
                    <th>Factura</th>
                    <th>Glosa</th>
                    <th class="r">Importe</th>
                    <th class="r">Detrac.</th>
                    <th>F.Detrac</th>
                    <th>Tipo</th>
                    <th class="r">Abonado</th>
                    <th>F.Abono</th>
                    <th class="r">Pendiente</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                @foreach($facturasPorEmpresa as $idx => $f)
                    @php
                        $recaudacion     = $f->monto_recaudacion ?? 0;
                        $tipoRec         = $f->tipo_recaudacion  ?? '—';
                        $badgeKey        = str_replace([' '], ['_'], $f->estado);
                        /* Cuando la detraccion no fue validada: pendiente = importe_total */
                        $pendienteDisplay = $f->pendiente_display ?? (($f->estado === 'DIFERENCIA PENDIENTE') ? $f->importe_total : $f->monto_pendiente);
                        /* Verificar si ANULADO está ligado a otra factura Y la factura original existe */
                        $anuladoLigado = false;
                        if ($f->estado === 'ANULADO') {
                            $credito = \DB::table('credito')->where('id_factura', $f->id_factura)->first();
                            if ($credito) {
                                $anuladoLigado = \DB::table('factura')
                                    ->where('serie', $credito->serie_doc_modificado)
                                    ->where('numero', $credito->numero_doc_modificado)
                                    ->exists();
                            }
                        }
                        $esAnuladoHuerfano = $f->estado === 'ANULADO' && !$anuladoLigado;
                    @endphp
                    <tr @if($esAnuladoHuerfano) style="text-decoration: line-through; opacity: 0.6;" @endif>
                        <td style="text-align:center;color:#64748b;font-size:9px;">{{ $idx+1 }}</td>
                        <td class="mono">{{ $f->fecha_emision ? \Carbon\Carbon::parse($f->fecha_emision)->format('d/m/Y') : '—' }}</td>
                        <td class="mono">{{ $f->fecha_vencimiento ? \Carbon\Carbon::parse($f->fecha_vencimiento)->format('d/m/Y') : '—' }}</td>
                        <td class="factura-num">{{ $f->serie }}-{{ str_pad($f->numero,8,'0',STR_PAD_LEFT) }}</td>
                        <td style="font-size:9px;">{{ $f->glosa ? Str::limit($f->glosa,22) : '—' }}</td>
                        <td class="r mono">{{ $f->moneda }} {{ number_format($f->importe_total,2) }}</td>
                        <td class="r detrac">{{ $recaudacion > 0 ? $f->moneda.' '.number_format($recaudacion,2) : '—' }}</td>
                        <td class="mono" style="font-size:8.5px;color:#d97706;">
                            {{ $f->fecha_recaudacion ? \Carbon\Carbon::parse($f->fecha_recaudacion)->format('d/m/Y') : '—' }}
                        </td>
                        <td style="font-size:8.5px;font-weight:700;color:#7c3aed;">{{ $tipoRec !== '—' ? $tipoRec : '—' }}</td>
                        <td class="r abonado">
                            {{ ($f->monto_abonado ?? 0) > 0 ? $f->moneda.' '.number_format($f->monto_abonado,2) : '—' }}
                        </td>
                        <td class="mono" style="font-size:8.5px;color:#059669;">
                            {{ $f->fecha_abono ? \Carbon\Carbon::parse($f->fecha_abono)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="r pendiente-cell">
                            {{ $f->moneda }} {{ number_format($pendienteDisplay,2) }}
                            @if($f->estado === 'DIFERENCIA PENDIENTE')
                                <div style="font-size:7.5px;color:#7c3aed;font-weight:600;">det. no valid.</div>
                            @endif
                        </td>
                        <td><span class="badge b-{{ $badgeKey }}">{{ str_replace('_',' ',$f->estado) }}</span></td>
                    </tr>
                @endforeach

                {{-- FILA TOTALES POR EMPRESA ── sin "Pendiente: X" en el título --}}
                <tr class="total-empresa">
                    <td colspan="5" style="font-size:10px;letter-spacing:.3px;">SUBTOTAL — {{ $facturasPorEmpresaParaTotales->count() }} factura(s)</td>
                    <td class="r" style="color:#fca5a5;">{{ number_format($totEmpresa,2) }}</td>
                    <td class="r" style="color:#fcd34d;">{{ $totRecEmpresa > 0 ? number_format($totRecEmpresa,2) : '—' }}</td>
                    <td></td>
                    <td></td>
                    <td class="r" style="color:#6ee7b7;">{{ $totAbono > 0 ? number_format($totAbono,2) : '—' }}</td>
                    <td></td>
                    <td class="r" style="color:#fca5a5;font-size:11px;">{{ number_format($totPendEmpresa,2) }}</td>
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
        const body = new URLSearchParams({ usuario_id:sel.value, fecha_desde:FECHA_DESDE, fecha_hasta:FECHA_HASTA, _token:CSRF });
        if (ID_CLIENTE) body.append('id_cliente', ID_CLIENTE);
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

    /* ── Exportar Excel ── */
    function exportarExcel() {
        const wb = XLSX.utils.book_new();
        // Recopilar datos de todas las empresas
        const rows = [
            ['#','EMPRESA','EMISIÓN','VCTO','FACTURA','GLOSA','IMPORTE','DETRACCIÓN','F.DETRACCION','TIPO','ABONADO','F.ABONO','PENDIENTE','ESTADO']
        ];

        document.querySelectorAll('.empresa-table').forEach((tabla, tIdx) => {
            const empresa = tabla.previousElementSibling?.textContent?.trim() || `Empresa ${tIdx+1}`;
            tabla.querySelectorAll('tbody tr:not(.total-empresa)').forEach(tr => {
                const celdas = tr.querySelectorAll('td');
                if (celdas.length < 13) return;
                rows.push([
                    celdas[0].textContent.trim(),
                    empresa,
                    celdas[1].textContent.trim(),
                    celdas[2].textContent.trim(),
                    celdas[3].textContent.trim(),
                    celdas[4].textContent.trim(),
                    celdas[5].textContent.trim(),
                    celdas[6].textContent.trim(),
                    celdas[7].textContent.trim(),
                    celdas[8].textContent.trim(),
                    celdas[9].textContent.trim(),
                    celdas[10].textContent.trim(),
                    celdas[11].textContent.replace('det. no valid.','').trim(),
                    celdas[12].textContent.trim(),
                ]);
            });
            // Fila de subtotal
            const totRow = tabla.querySelector('tbody tr.total-empresa');
            if (totRow) {
                const cTot = totRow.querySelectorAll('td');
                rows.push(['','SUBTOTAL — '+empresa,'','','','',
                    cTot[5]?.textContent.trim()||'',
                    cTot[6]?.textContent.trim()||'','','',
                    cTot[9]?.textContent.trim()||'','',
                    cTot[11]?.textContent.trim()||'','']);
            }
            rows.push([]); // fila vacía entre empresas
        });

        const ws = XLSX.utils.aoa_to_sheet(rows);
        // Anchos de columna
        ws['!cols'] = [
            {wch:4},{wch:30},{wch:12},{wch:12},{wch:16},{wch:22},
            {wch:12},{wch:12},{wch:12},{wch:14},{wch:12},{wch:12},{wch:12},{wch:20}
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
        const filename = 'Reporte_CRC_{{ now()->format("Ymd_Hi") }}.xlsx';
        XLSX.writeFile(wb, filename);
    }

    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
