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

        .kpi-bar { display:flex; gap:0; border-bottom:2px solid #e2e8f0; }
        .kpi-box { flex:1; padding:16px 20px; border-right:1px solid #e2e8f0; text-align:center; }
        .kpi-box:last-child { border-right:none; }
        .kpi-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:4px; }
        .kpi-value { font-size:20px; font-weight:900; font-family:'Courier New',monospace; }
        .kpi-value.red   { color:#dc2626; }
        .kpi-value.amber { color:#d97706; }
        .kpi-value.blue  { color:#1d4ed8; }
        .kpi-value.dark  { color:#0f172a; }

        .body { padding:20px 32px 32px; }

        /* TABLE con layout fijo para no descuadrarse */
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        thead tr { background:#0f172a; color:#fff; }
        thead th { padding:10px 12px; text-align:left; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        thead th.r { text-align:right; }
        thead th.c { text-align:center; }
        tbody tr { border-bottom:1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tbody td { padding:9px 12px; font-size:10.5px; vertical-align:middle; overflow:hidden; text-overflow:ellipsis; }
        tbody td.r { text-align:right; }
        tbody td.c { text-align:center; }

        /* Anchos fijos para las columnas */
        col.col-rank   { width:36px; }
        col.col-emp    { width:25%; }
        col.col-pen    { width:13%; }
        col.col-usd    { width:10%; }
        col.col-rec    { width:12%; }
        col.col-neto   { width:12%; }
        col.col-neto-p { width:10%; }
        col.col-cnt    { width:5%; }
        col.col-est    { width:18%; }

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
            @page { size:A4 portrait; margin:10mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <span class="hint">Reporte de Deuda General</span>
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

<div class="header">
    <h1>Reporte de Deuda General</h1>
    <div class="sub">
        CONSORCIO RODRIGUEZ CABALLERO S.A.C. &nbsp;|&nbsp; PERÍODO: {{ $periodoLabel }}<br>
        ESTADO: {{ $estadoLabel ?? 'TODOS LOS PENDIENTES' }}
    </div>
</div>

@php
    $totalNetoPen  = $totalPendientePen;
    $countEmpresas = count($clientes);
    $countVencidas = collect($clientes)->filter(fn($c) => in_array('VENCIDO', $c['estados']))->count();
    $mostrarUsd    = ($totalUsd ?? 0) > 0;
@endphp

<div class="kpi-bar">
    <div class="kpi-box"><div class="kpi-label">Empresas con Deuda</div><div class="kpi-value blue">{{ $countEmpresas }}</div></div>
    <div class="kpi-box"><div class="kpi-label">Total Deuda Bruta (S/)</div><div class="kpi-value red">S/ {{ number_format($totalPen, 2) }}</div></div>
    @if($mostrarUsd)
        <div class="kpi-box"><div class="kpi-label">Total Deuda Bruta ($)</div><div class="kpi-value blue">$ {{ number_format($totalUsd, 2) }}</div></div>
    @endif
    <div class="kpi-box"><div class="kpi-label">Total Recaudación (S/)</div><div class="kpi-value amber">S/ {{ number_format($totalRecaudacionPen, 2) }}</div></div>
    <div class="kpi-box"><div class="kpi-label">Saldo Pendiente (S/)</div><div class="kpi-value red" style="font-size:18px;">S/ {{ number_format($totalNetoPen, 2) }}</div></div>
    <div class="kpi-box"><div class="kpi-label">Con Facturas Vencidas</div><div class="kpi-value" style="color:#991b1b;">{{ $countVencidas }}</div></div>
</div>

<div class="body">
    @if(empty($clientes))
        <p style="text-align:center;padding:48px;color:#64748b;">Sin deudas pendientes en el período seleccionado.</p>
    @else
        <div class="aviso">
            Facturas con estado: {{ $estadoLabel ?? 'PENDIENTE · VENCIDO · PAGO PARCIAL · DIFERENCIA PENDIENTE' }}.
            Ordenadas de mayor a menor saldo pendiente en soles.
        </div>

        <table id="tablaDeuda">
            <colgroup>
                <col class="col-rank">
                <col class="col-emp">
                <col class="col-pen">
                @if($mostrarUsd)<col class="col-usd">@endif
                <col class="col-rec">
                <col class="col-neto">
                <col class="col-cnt">
                <col class="col-est">
            </colgroup>
            <thead>
            <tr>
                <th class="c">#</th>
                <th>EMPRESA / CLIENTE</th>
                <th class="r">DEUDA BRUTA (S/)</th>
                @if($mostrarUsd)<th class="r">DEUDA ($)</th>@endif
                <th class="r">RECAUDACIÓN (S/)</th>
                <th class="r">SALDO PENDIENTE (S/)</th>
                <th class="c">FACT.</th>
                <th>ESTADOS</th>
            </tr>
            </thead>
            <tbody>
            @php $item = 1; @endphp
            @foreach($clientes as $c)
                @php
                    $tieneVenc = in_array('VENCIDO', $c['estados']);
                    $rowStyle  = $tieneVenc ? 'background:#fff5f5 !important;' : '';
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td class="rank">{{ $item++ }}</td>
                    <td>
                        <div class="empresa">{{ $c['razon_social'] }}</div>
                        <div class="ruc">{{ $c['ruc'] }}</div>
                    </td>
                    <td class="r">
                        @if($c['deuda_pen'] > 0)
                            <span class="deuda-pen">S/ {{ number_format($c['deuda_pen'], 2) }}</span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    @if($mostrarUsd)
                        <td class="r">
                            @if($c['deuda_usd'] > 0)
                                <span class="deuda-usd">$ {{ number_format($c['deuda_usd'], 2) }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="r">
                        @if($c['recaudacion_pen'] > 0)
                            <span class="detrac-val">S/ {{ number_format($c['recaudacion_pen'], 2) }}</span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td class="r">
                        {{-- saldo calculado correctamente en el controller --}}
                        <span class="pendiente-val">S/ {{ number_format($c['pendiente_pen'], 2) }}</span>
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

            {{-- FILA TOTAL --}}
            <tr class="total-row">
                <td class="c" style="font-size:9px;color:#94a3b8;">TOTAL</td>
                <td style="font-size:11px;color:#f1f5f9;">{{ $countEmpresas }} EMPRESAS</td>
                <td class="r"><span style="font-family:'Courier New',monospace;color:#fca5a5;">S/ {{ number_format($totalPen, 2) }}</span></td>
                @if($mostrarUsd)<td class="r"><span style="font-family:'Courier New',monospace;color:#93c5fd;">$ {{ number_format($totalUsd, 2) }}</span></td>@endif
                <td class="r"><span style="font-family:'Courier New',monospace;color:#fcd34d;">S/ {{ number_format($totalRecaudacionPen, 2) }}</span></td>
                <td class="r"><span style="font-family:'Courier New',monospace;font-size:13px;color:#fca5a5;font-weight:900;">S/ {{ number_format($totalNetoPen, 2) }}</span></td>
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
    const FECHA_DESDE    = '{{ $fechaDesde ?? "" }}';
    const FECHA_HASTA    = '{{ $fechaHasta ?? "" }}';
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
        const wb = XLSX.utils.book_new();
        const rows = [
            ['#', 'EMPRESA', 'RUC', 'DEUDA BRUTA (S/)', 'DEUDA ($)', 'RECAUDACIÓN (S/)', 'SALDO PENDIENTE (S/)', 'FACTURAS', 'ESTADOS']
        ];

        document.querySelectorAll('#tablaDeuda tbody tr:not(.total-row)').forEach((tr, i) => {
            const tds = tr.querySelectorAll('td');
            if (!tds.length) return;
            const estados = [...tds[tds.length-1].querySelectorAll('.badge')]
                .map(b => b.textContent.trim()).join(', ');
            rows.push([
                tds[0].textContent.trim(),
                tds[1].querySelector('.empresa')?.textContent.trim() || '',
                tds[1].querySelector('.ruc')?.textContent.trim()     || '',
                tds[2].textContent.replace('S/','').trim() || '—',
                tds.length > 6 ? (tds[3].textContent.replace('$','').trim() || '—') : '—',
                // ajustar índice según si hay columna USD
                tds.length > 7 ? tds[4].textContent.replace('S/','').trim() : tds[3].textContent.replace('S/','').trim(),
                tds.length > 7 ? tds[5].textContent.replace('S/','').trim() : tds[4].textContent.replace('S/','').trim(),
                tds.length > 7 ? tds[6].textContent.trim()                  : tds[5].textContent.trim(),
                estados,
            ]);
        });

        // Fila de totales
        rows.push([]);
        rows.push(['TOTALES', '{{ $countEmpresas }} empresas', '',
            '{{ number_format($totalPen, 2) }}',
            '{{ number_format($totalUsd ?? 0, 2) }}',
            '{{ number_format($totalRecaudacionPen, 2) }}',
            '{{ number_format($totalNetoPen, 2) }}',
            '{{ collect($clientes)->sum("facturas") }}', '']);

        const ws = XLSX.utils.aoa_to_sheet(rows);
        ws['!cols'] = [{wch:4},{wch:30},{wch:13},{wch:16},{wch:14},{wch:16},{wch:18},{wch:8},{wch:35}];

        XLSX.utils.book_append_sheet(wb, ws, 'Deuda General');
        XLSX.writeFile(wb, 'DeudaGeneral_CRC_{{ now()->format("Ymd_Hi") }}.xlsx');
    }

    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
