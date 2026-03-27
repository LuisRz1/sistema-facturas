<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Valorización {{ $cotizacion->numero_valorizacion }} — {{ $cotizacion->razon_social }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; background: #fff; }
        .no-print { background: #1e293b; padding: 10px 20px; display: flex; align-items: center; gap: 10px; position: sticky; top: 0; z-index: 10; }
        .btn-print { background: #dc2626; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-back  { background: transparent; color: #94a3b8; border: 1px solid #334155; padding: 8px 14px; border-radius: 6px; font-size: 12px; cursor: pointer; }
        .page { padding: 16px 20px; max-width: 1100px; margin: 0 auto; }

        /* ── HEADER ── */
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .logo-circle { width: 70px; height: 70px; background: #1e293b; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f5c842; font-size: 20px; font-weight: 900; flex-shrink: 0; }
        .company-name { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .empresa-name-big { font-size: 18px; font-weight: 900; text-transform: uppercase; color: #0f172a; }
        .ruc-line { font-size: 11px; color: #374151; margin-top: 2px; font-weight: 600; }
        .tagline { font-size: 8px; color: #2563eb; font-style: italic; margin-top: 4px; max-width: 400px; }
        .header-right { text-align: right; }
        .header-right h1 { font-size: 20px; font-weight: 900; text-transform: uppercase; color: #0f172a; }
        .header-right .ruc-big { font-size: 16px; font-weight: 700; color: #374151; }

        /* ── META FIELDS ── */
        .meta-grid { display: grid; grid-template-columns: auto 1fr auto 1fr; gap: 4px 12px; margin-bottom: 12px; font-size: 10px; align-items: center; }
        .meta-label { font-weight: 800; text-transform: uppercase; letter-spacing: .3px; color: #374151; white-space: nowrap; }
        .meta-val { border-bottom: 1px solid #e2e8f0; padding: 2px 4px; min-width: 120px; font-weight: 600; }
        .meta-val.highlight { background: #d1d5db; font-weight: 800; text-transform: uppercase; }

        /* ── TITLE BLOCK ── */
        .val-title { text-align: center; background: #f1f5f9; padding: 6px 12px; font-size: 13px; font-weight: 800; text-transform: uppercase; border: 1px solid #e2e8f0; margin-bottom: 2px; }
        .val-subtitle { text-align: center; background: #fef9c3; padding: 5px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid #fde68a; margin-bottom: 8px; }

        /* ── TABLE ── */
        table { width: 100%; border-collapse: collapse; }
        thead tr th { background: #374151; color: #fff; padding: 6px 8px; text-align: center; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; border: 1px solid #4b5563; white-space: nowrap; }
        thead tr th.l { text-align: left; }
        thead tr th.r { text-align: right; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody td { padding: 5px 8px; font-size: 9.5px; vertical-align: middle; border: 1px solid #e5e7eb; }
        tbody td.c { text-align: center; }
        tbody td.r { text-align: right; font-family: 'Courier New', monospace; }
        tbody td.mono { font-family: 'Courier New', monospace; }

        /* ── TOTAL ROW ── */
        .total-row td { background: #374151 !important; color: #fff; font-weight: 800; font-size: 10px; border-color: #4b5563; }

        /* ── SUMMARY ── */
        .summary-block { margin-top: 16px; display: flex; justify-content: flex-end; }
        .summary-table { border-collapse: collapse; min-width: 280px; }
        .summary-table td { padding: 6px 12px; font-size: 11px; border: 1px solid #e2e8f0; }
        .summary-table .s-label { font-weight: 700; text-transform: uppercase; letter-spacing: .3px; color: #374151; background: #f8fafc; }
        .summary-table .s-val   { text-align: right; font-family: 'Courier New', monospace; font-weight: 700; color: #0f172a; }
        .summary-table tr.total-summary td { background: #374151; color: #fff; font-weight: 800; font-size: 12px; }

        /* ── SIGNATURES ── */
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 32px; padding-top: 12px; border-top: 2px solid #e2e8f0; }
        .sig-box { text-align: center; }
        .sig-line { border-bottom: 1px solid #0f172a; margin: 32px auto 6px; width: 80%; }
        .sig-name { font-weight: 700; font-size: 10px; text-transform: uppercase; }
        .sig-role { font-size: 9px; color: #64748b; }

        /* ── PRINT ── */
        @media print {
            .no-print { display: none !important; }
            body { font-size: 9px; }
            @page { size: A4 {{ $esMaquinaria ? 'landscape' : 'portrait' }}; margin: 8mm; }
        }
    </style>
</head>
<body>
@php $esMaquinaria = $cotizacion->tipo_cotizacion === 'MAQUINARIA'; @endphp

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨 Imprimir / PDF</button>
    <button class="btn-back" onclick="window.close()">← Cerrar</button>
    <span style="color:#94a3b8;font-size:12px;">Valorización {{ $cotizacion->numero_valorizacion }} — {{ $cotizacion->razon_social }}</span>
</div>

<div class="page">

    {{-- ── HEADER ── --}}
    <div class="header-top">
        <div class="logo-area">
            <div class="logo-circle">CRC</div>
            <div>
                <div class="company-name">Consorcio Rodriguez Caballero SAC</div>
                <div class="empresa-name-big">Consorcio Rodriguez Caballero</div>
                <div class="ruc-line">RUC: 20482304665</div>
                <div class="tagline">Abastecimiento de agua en cisternas, venta de agregados para la construcción,<br>alquiler maquinaria pesada y otras actividades de transporte.</div>
            </div>
        </div>
        <div class="header-right">
            <h1>Consorcio Rodriguez Caballero</h1>
            <div class="ruc-big">RUC: 20482304665</div>
        </div>
    </div>

    <div class="meta-grid">
        <span class="meta-label">Valorización:</span>
        <span class="meta-val highlight">
            {{ $cotizacion->tipo_cotizacion === 'MAQUINARIA' ? 'ALQUILER DE' : '' }}
            {{ Str::upper($cotizacion->obra) }} - {{ $cotizacion->numero_valorizacion }}
        </span>
        <span class="meta-label">Periodo:</span>
        <span class="meta-val">
            {{ strtoupper(\Carbon\Carbon::parse($cotizacion->periodo_inicio)->locale('es')->isoFormat('D [DE] MMMM [DEL] Y')) }}
            AL
            {{ strtoupper(\Carbon\Carbon::parse($cotizacion->periodo_fin)->locale('es')->isoFormat('D [DE] MMMM [DEL] Y')) }}
        </span>

        <span class="meta-label">Empresa:</span>
        <span class="meta-val">{{ strtoupper($cotizacion->razon_social) }}</span>
        <span class="meta-label">RUC:</span>
        <span class="meta-val mono">{{ $cotizacion->ruc }}</span>

        <span class="meta-label">Obra:</span>
        <span class="meta-val" style="grid-column:span 3;">{{ strtoupper($cotizacion->obra) }}</span>
    </div>

    {{-- ── SECTION TITLE ── --}}
    <div class="val-title">{{ strtoupper($cotizacion->razon_social) }}</div>
    <div class="val-subtitle">1.- {{ strtoupper($cotizacion->obra) }}</div>

    {{-- ── TABLE ── --}}
    @if($esMaquinaria)
        <table>
            <thead>
            <tr>
                <th>FECHA</th>
                <th class="l">CHOFER</th>
                <th class="l">CISTERNA/VOLQUETE/MAQUINARIA</th>
                <th>PLACA/DESCRIPCIÓN</th>
                <th class="l">OBRA</th>
                <th>N° PARTE DIARIO</th>
                <th class="r">HI</th>
                <th class="r">HT</th>
                <th class="r">HORAS TRABAJADAS</th>
                <th class="r">HORAS MÍNIMAS</th>
                <th class="r">PRECIO</th>
                <th class="r">TOTAL</th>
            </tr>
            </thead>
            <tbody>
            @foreach($filas as $f)
                <tr>
                    <td class="c">{{ \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
                    <td>{{ strtoupper($f->chofer_nombre) }}</td>
                    <td>{{ strtoupper($f->maquinaria_nombre) }}</td>
                    <td class="c mono">{{ $f->placa ?? '' }}</td>
                    <td>{{ strtoupper($f->obra_maquina ?? $cotizacion->obra) }}</td>
                    <td class="c">{{ $f->n_parte_diario ?? '' }}</td>
                    <td class="r">{{ number_format($f->hora_inicio, 1) }}</td>
                    <td class="r">{{ number_format($f->hora_fin, 1) }}</td>
                    <td class="r">{{ number_format($f->horas_trabajadas, 2) }}</td>
                    <td class="r">{{ number_format($f->hora_minima, 0) }}</td>
                    <td class="r">{{ number_format($f->precio_hora, 2) }}</td>
                    <td class="r" style="font-weight:700;">{{ number_format($f->total_fila, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="8" style="text-align:right;letter-spacing:1px;">TOTAL</td>
                <td class="r">{{ number_format($filas->sum('horas_trabajadas'), 2) }}</td>
                <td></td>
                <td></td>
                <td class="r">S/ {{ number_format($cotizacion->total, 2) }}</td>
            </tr>
            </tbody>
        </table>

    @else
        <table>
            <thead>
            <tr>
                <th>FECHA</th>
                <th class="l">CHOFER</th>
                <th class="l">DETALLE</th>
                <th>PLACA</th>
                <th class="l">OBRA</th>
                <th>N° PARTE DIARIO</th>
                <th class="r">M3</th>
                <th class="r">PRECIO</th>
                <th class="r">TOTAL</th>
                <th>GRR</th>
            </tr>
            </thead>
            <tbody>
            @foreach($filas as $f)
                <tr>
                    <td class="c">{{ \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
                    <td>{{ strtoupper($f->chofer_nombre) }}</td>
                    <td>{{ strtoupper($f->agregado_nombre) }}</td>
                    <td class="c mono">{{ $f->placa ?? '' }}</td>
                    <td>{{ strtoupper($f->obra_agregado ?? $cotizacion->obra) }}</td>
                    <td class="c">{{ $f->n_parte_diario ?? '' }}</td>
                    <td class="r">{{ number_format($f->m3, 0) }}</td>
                    <td class="r">{{ number_format($f->precio_m3, 0) }}</td>
                    <td class="r" style="font-weight:700;">{{ number_format($f->total_fila, 2) }}</td>
                    <td class="c mono" style="font-size:8.5px;">{{ $f->grr ?? '' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align:right;letter-spacing:1px;">TOTAL</td>
                <td class="r">{{ number_format($filas->sum('m3'), 0) }}</td>
                <td></td>
                <td class="r">S/ {{ number_format($cotizacion->total, 2) }}</td>
                <td></td>
            </tr>
            </tbody>
        </table>
    @endif

    {{-- ── SUMMARY ── --}}
    <div class="summary-block">
        <table class="summary-table">
            <tr>
                <td class="s-label">BASE</td>
                <td class="s-val">{{ number_format($cotizacion->base_sin_igv, 2) }}</td>
            </tr>
            <tr>
                <td class="s-label">IGV</td>
                <td class="s-val">{{ number_format($cotizacion->total_igv, 2) }}</td>
            </tr>
            <tr class="total-summary">
                <td class="s-label">TOTAL</td>
                <td class="s-val">{{ number_format($cotizacion->total, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- ── SIGNATURES ── --}}
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">CONSORCIO RODRIGUEZ CABALLERO S.A.C.</div>
            <div class="sig-role">PROVEEDOR</div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">{{ strtoupper($cotizacion->razon_social) }}</div>
            <div class="sig-role">CLIENTE</div>
        </div>
    </div>

</div>

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 500));
</script>
</body>
</html>
