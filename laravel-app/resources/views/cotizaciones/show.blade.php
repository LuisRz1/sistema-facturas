@extends('layouts.app')
@section('title', 'Cotización #' . $cotizacion->numero_valorizacion)
@section('breadcrumb', 'Cotización')

@php
    $esMaquinaria = $cotizacion->tipo_cotizacion === 'MAQUINARIA';
    $CSRF         = csrf_token();
@endphp

@push('styles')
    <style>
        :root{--gold:#f5c842;--gold-b:#ead96a;--gold-m:#d4a017;--gold-l:#fffbeb;--gold-d:#9a6e10;}

        .cot-header-card{background:#fff;border:1.5px solid var(--gold-b);border-radius:16px;margin-bottom:20px;overflow:hidden;}
        .cot-header-top{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:20px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px;}
        .cot-header-top h2{color:#fff;font-size:17px;font-weight:800;}
        .cot-header-top p{color:#94a3b8;font-size:12px;margin-top:2px;}
        .tipo-pill{padding:4px 14px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;}
        .pill-maq{background:#fef3c7;color:#92400e;}
        .pill-agr{background:#d1fae5;color:#065f46;}
        .cot-header-info{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border-top:1px solid var(--gold-b);}
        .cot-info-cell{padding:14px 20px;border-right:1px solid var(--gold-b);}
        .cot-info-cell:last-child{border-right:none;}
        .cot-info-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);font-weight:700;margin-bottom:4px;}
        .cot-info-val{font-size:13px;font-weight:700;color:var(--text-primary);}

        .totales-bar{display:flex;gap:0;background:#fff;border:1.5px solid var(--gold-b);border-radius:12px;margin-bottom:20px;overflow:hidden;}
        .tot-box{flex:1;padding:16px 20px;border-right:1px solid var(--gold-b);text-align:center;}
        .tot-box:last-child{border-right:none;background:var(--gold-l);}
        .tot-lbl{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);font-weight:700;}
        .tot-val{font-size:18px;font-weight:900;font-family:'DM Mono',monospace;margin-top:4px;}
        .tot-base{color:var(--text-muted);}
        .tot-igv{color:#d97706;}
        .tot-total{color:#0f172a;}

        .row-table{width:100%;border-collapse:collapse;font-size:12px;}
        .row-table thead tr{background:#0f172a;color:#fff;}
        .row-table thead th{padding:9px 10px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;}
        .row-table thead th.r{text-align:right;}
        .row-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s;}
        .row-table tbody tr:hover{background:#fffdf5;}
        .row-table tbody td{padding:9px 10px;vertical-align:middle;}
        .row-table tbody td.r{text-align:right;font-family:'DM Mono',monospace;}
        .row-table tbody td.mono{font-family:'DM Mono',monospace;}

        .phantom-row{background:#fef3c7 !important;border:2px dashed #fbbf24 !important;}
        .phantom-row td{color:#92400e !important;font-style:italic;}
        .phantom-badge{background:#fbbf24;color:#78350f;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:800;text-transform:uppercase;margin-right:4px;}

        .add-row-form{background:var(--gold-l);border:1.5px solid var(--gold-b);border-radius:12px;padding:18px 20px;margin-top:16px;}
        .add-row-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--gold-d);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
        .row-inputs{display:grid;gap:10px;}
        .row-inputs.maq-grid{grid-template-columns:130px 1fr 1fr 1fr 1fr 90px 90px 90px 90px 100px 90px;}
        .row-inputs.agr-grid{grid-template-columns:130px 1fr 1fr 1fr 1fr 90px 90px 90px 100px 100px;}
        .row-input{height:36px;border:1.5px solid var(--gold-b);border-radius:8px;background:#fff;font-size:12px;font-family:'DM Sans',sans-serif;color:var(--text-primary);outline:none;padding:0 10px;transition:border-color .15s;width:100%;}
        .row-input:focus{border-color:var(--gold-m);box-shadow:0 0 0 3px #f5c84220;}
        .row-input.calculated{background:#f8fafc;color:var(--text-muted);cursor:not-allowed;}
        .row-input-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:3px;}
        .file-btn{height:36px;border:1.5px solid var(--gold-b);border-radius:8px;background:#fff;padding:0 12px;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;color:var(--gold-d);}
        .file-btn:hover{background:var(--gold-l);border-color:var(--gold-m);}
        .file-btn.uploaded{border-color:#059669;background:#d1fae5;color:#065f46;}

        .tbl-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--gold-b);background:#fff;display:inline-flex;align-items:center;justify-content:middle;cursor:pointer;transition:all .15s;color:var(--text-muted);}
        .tbl-btn:hover{background:var(--gold-l);border-color:var(--gold-m);color:var(--gold-d);}
        .tbl-btn.del:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}

        .field-row{display:grid;gap:12px;}
        .field-row.cols2{grid-template-columns:1fr 1fr;}
        .field-row.cols3{grid-template-columns:1fr 1fr 1fr;}

        .total-fila-cell{font-weight:800;color:#0f172a;}

        .file-pill-sm{display:inline-flex;align-items:center;gap:4px;background:#d1fae5;border:1px solid #a7f3d0;border-radius:5px;padding:2px 7px;font-size:10px;font-weight:600;color:#065f46;cursor:pointer;}
        .file-pill-sm.pdf{background:#dbeafe;border-color:#93c5fd;color:#1d4ed8;}

        @keyframes rowFadeIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
        .new-row{animation:rowFadeIn .35s ease-out;}

        .sum-row td{background:#f8fafc;font-weight:800;border-top:2px solid var(--gold-b) !important;}
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('cotizaciones.index') }}">Cotizaciones</a>
                <span>›</span>
                <span>{{ $cotizacion->numero_valorizacion }} — {{ Str::limit($cotizacion->obra, 40) }}</span>
            </div>
            <h1 class="page-title" style="font-size:22px;">
                Valorización {{ $cotizacion->numero_valorizacion }}
            </h1>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ route('cotizaciones.export-excel', $cotizacion->id_cotizacion) }}"
               class="btn btn-outline" style="border-color:#16a34a;color:#16a34a;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exportar Excel
            </a>
            <button class="btn btn-primary" onclick="abrirEditarHeader()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar Encabezado
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ══ HEADER CARD ══ --}}
    <div class="cot-header-card">
        <div class="cot-header-top">
            <div>
                <h2>{{ $cotizacion->razon_social }}</h2>
                <p>RUC: {{ $cotizacion->ruc }}</p>
            </div>
            <span class="tipo-pill {{ $esMaquinaria ? 'pill-maq' : 'pill-agr' }}">
                {{ $cotizacion->tipo_cotizacion }}
            </span>
        </div>
        <div class="cot-header-info">
            <div class="cot-info-cell">
                <div class="cot-info-lbl">Valorización</div>
                <div class="cot-info-val" style="color:var(--gold-m);font-family:'DM Mono',monospace;">
                    {{ $cotizacion->numero_valorizacion }}
                </div>
            </div>
            <div class="cot-info-cell" style="grid-column:span 2;">
                <div class="cot-info-lbl">Obra</div>
                <div class="cot-info-val">{{ $cotizacion->obra }}</div>
            </div>
            <div class="cot-info-cell">
                <div class="cot-info-lbl">Período inicio</div>
                <div class="cot-info-val">{{ \Carbon\Carbon::parse($cotizacion->periodo_inicio)->format('d/m/Y') }}</div>
            </div>
            <div class="cot-info-cell">
                <div class="cot-info-lbl">Período fin</div>
                <div class="cot-info-val">{{ \Carbon\Carbon::parse($cotizacion->periodo_fin)->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ══ TOTALES ══ --}}
    <div class="totales-bar">
        <div class="tot-box">
            <div class="tot-lbl">Filas registradas</div>
            <div class="tot-val" style="color:var(--text-primary);font-size:22px;" id="totFilas">{{ $filas->count() }}</div>
        </div>
        <div class="tot-box">
            <div class="tot-lbl">Base sin IGV</div>
            <div class="tot-val tot-base" id="totBase">S/ {{ number_format($cotizacion->base_sin_igv,2) }}</div>
        </div>
        <div class="tot-box">
            <div class="tot-lbl">IGV (18%)</div>
            <div class="tot-val tot-igv" id="totIgv">S/ {{ number_format($cotizacion->total_igv,2) }}</div>
        </div>
        <div class="tot-box">
            <div class="tot-lbl">Total General</div>
            <div class="tot-val tot-total" id="totTotal">S/ {{ number_format($cotizacion->total,2) }}</div>
        </div>
    </div>

    {{-- ══ ROW TABLE ══ --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <div>
                <div class="card-title">Detalle de Filas — {{ $cotizacion->tipo_cotizacion }}</div>
                <div class="card-desc" id="filaCountDesc">{{ $filas->count() }} fila(s) registradas</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="row-table" id="rowTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Chofer</th>
                    @if($esMaquinaria)
                        <th>Maquinaria</th>
                        <th>Placa</th>
                        <th>Obra</th>
                        <th>N° Parte</th>
                        <th class="r">HI</th>
                        <th class="r">HT</th>
                        <th class="r">H. Trab.</th>
                        <th class="r">H. Min.</th>
                        <th class="r">Precio</th>
                        <th class="r">Total</th>
                    @else
                        <th>Agregado</th>
                        <th>Placa</th>
                        <th>Obra</th>
                        <th>N° Parte</th>
                        <th class="r">M³</th>
                        <th class="r">Precio/M³</th>
                        <th class="r">Total</th>
                        <th>GRR</th>
                    @endif
                    <th style="text-align:right;">Acc.</th>
                </tr>
                </thead>
                <tbody id="rowTbody">
                @forelse($filas as $idx => $f)
                    <tr data-id="{{ $f->_row_id }}" data-idx="{{ $idx + 1 }}">
                        <td style="color:var(--text-muted);font-size:10px;text-align:center;">{{ $idx + 1 }}</td>
                        <td class="mono">{{ \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $f->chofer_nombre }}</td>
                        @if($esMaquinaria)
                            <td>{{ $f->maquinaria_nombre }}</td>
                            <td class="mono">{{ $f->placa ?? '—' }}</td>
                            <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $f->obra_maquina ?? '—' }}</td>
                            <td>
                                @if($f->n_parte_diario)
                                    <span style="font-size:11px;font-weight:600;">{{ $f->n_parte_diario }}</span>
                                @endif
                                @if($f->ruta_parte_diario ?? null)
                                    <a href="{{ Storage::url($f->ruta_parte_diario) }}" target="_blank" class="file-pill-sm" style="margin-left:4px;">📷</a>
                                @endif
                            </td>
                            <td class="r">{{ number_format($f->hora_inicio,1) }}</td>
                            <td class="r">{{ number_format($f->hora_fin,1) }}</td>
                            <td class="r" style="color:#059669;">{{ number_format($f->horas_trabajadas,2) }}</td>
                            <td class="r">{{ number_format($f->hora_minima,1) }}</td>
                            <td class="r">{{ number_format($f->precio_hora,2) }}</td>
                            <td class="r total-fila-cell">{{ number_format($f->total_fila,2) }}</td>
                        @else
                            <td>{{ $f->agregado_nombre }}</td>
                            <td class="mono">{{ $f->placa ?? '—' }}</td>
                            <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $f->obra_agregado ?? '—' }}</td>
                            <td>
                                @if($f->n_parte_diario)
                                    <span style="font-size:11px;font-weight:600;">{{ $f->n_parte_diario }}</span>
                                @endif
                                @if($f->ruta_parte_diario ?? null)
                                    <a href="{{ Storage::url($f->ruta_parte_diario) }}" target="_blank" class="file-pill-sm" style="margin-left:4px;">📷</a>
                                @endif
                            </td>
                            <td class="r">{{ number_format($f->m3,2) }}</td>
                            <td class="r">{{ number_format($f->precio_m3,2) }}</td>
                            <td class="r total-fila-cell">{{ number_format($f->total_fila,2) }}</td>
                            <td>
                                @if($f->grr)
                                    <span style="font-size:11px;font-weight:600;">{{ $f->grr }}</span>
                                @endif
                                @if($f->ruta_grr ?? null)
                                    <a href="{{ Storage::url($f->ruta_grr) }}" target="_blank" class="file-pill-sm pdf" style="margin-left:4px;">📄 PDF</a>
                                @endif
                            </td>
                        @endif
                        <td>
                            <div style="display:flex;gap:4px;justify-content:flex-end;">
                                <button class="tbl-btn" title="Editar fila" onclick="abrirEditarFila({{ $f->_row_id }})">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="tbl-btn del" title="Eliminar fila" onclick="confirmarEliminarFila({{ $f->_row_id }})">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyRow"><td colspan="{{ $esMaquinaria ? 14 : 13 }}" style="text-align:center;padding:32px;color:var(--text-muted);font-size:13px;">
                            Sin filas. Usa el formulario de abajo para agregar la primera.
                        </td></tr>
                @endforelse
                @if($filas->count() > 0)
                    <tr class="sum-row" id="sumRow">
                        <td colspan="{{ $esMaquinaria ? 12 : 10 }}" style="text-align:right;font-size:12px;letter-spacing:.4px;text-transform:uppercase;">TOTAL</td>
                        <td class="r" style="font-size:14px;color:var(--gold-d);" id="sumTotalFila">
                            {{ number_format($filas->sum('total_fila'),2) }}
                        </td>
                        <td></td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        {{-- ══ ADD ROW FORM ══ --}}
        <div style="padding:16px 20px;">
            <div class="add-row-form">
                <div class="add-row-title">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Agregar Nueva Fila
                </div>

                <form id="addRowForm" enctype="multipart/form-data" onsubmit="addRow(event)">

                    @if($esMaquinaria)
                        <div style="display:grid;grid-template-columns:140px 1fr 1fr 1fr 1fr 80px 80px 80px 80px 90px 90px;gap:8px;margin-bottom:10px;">
                            <div>
                                <div class="row-input-lbl">Fecha *</div>
                                <input type="date" class="row-input" id="rFecha" name="fecha" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <div class="row-input-lbl">Chofer *</div>
                                <select class="row-input" id="rChofer" name="id_chofer" required>
                                    <option value="">— Chofer —</option>
                                    @foreach($choferes as $ch)
                                        <option value="{{ $ch->id_chofer }}">
                                            {{ trim($ch->nombres . ' ' . ($ch->apellido_paterno ?? '') . ' ' . ($ch->apellido_materno ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="row-input-lbl">Maquinaria *</div>
                                <select class="row-input" id="rMaquinaria" name="id_maquinaria" required>
                                    <option value="">— Maquinaria —</option>
                                    @foreach($maquinarias as $m)
                                        <option value="{{ $m->id_maquinaria }}" {{ (int)($cotizacion->id_maquinaria ?? 0) === (int)$m->id_maquinaria ? 'selected' : '' }}>
                                            {{ $m->nombre }}{{ $m->numero_maquina ? ' — ' . $m->numero_maquina : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="row-input-lbl">Obra</div>
                                <input type="text" class="row-input" id="rObra" name="obra_maquina" value="{{ $cotizacion->obra }}" placeholder="Obra">
                            </div>
                            <div>
                                <div class="row-input-lbl">Placa</div>
                                <input type="text" class="row-input" id="rPlaca" name="placa" placeholder="BRD846">
                            </div>
                            <div>
                                <div class="row-input-lbl">HI *</div>
                                <input type="number" class="row-input" id="rHI" name="hora_inicio" step="0.01" placeholder="2916.8" required onblur="onHIBlur()" oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">HT *</div>
                                <input type="number" class="row-input" id="rHT" name="hora_fin" step="0.01" placeholder="2917.65" required oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">H.Trab.</div>
                                <input type="number" class="row-input calculated" id="rHTrab" step="0.01" readonly placeholder="0.00">
                            </div>
                            <div>
                                <div class="row-input-lbl">H.Min. *</div>
                                <input type="number" class="row-input" id="rHMin" name="hora_minima" step="0.01" value="3" required oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">Precio/H *</div>
                                <input type="number" class="row-input" id="rPrecio" name="precio_hora" step="0.01" placeholder="245.00" required oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">Total Fila</div>
                                <input type="number" class="row-input calculated" id="rTotal" step="0.01" readonly placeholder="0.00">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;">
                            <div>
                                <div class="row-input-lbl">N° Parte Diario</div>
                                <input type="text" class="row-input" id="rNParte" name="n_parte_diario" placeholder="33594">
                            </div>
                            <div>
                                <div class="row-input-lbl">Imagen Parte Diario</div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <button type="button" class="file-btn" id="btnImgParte" onclick="document.getElementById('inputImgParte').click()">
                                        📷 Adjuntar imagen
                                    </button>
                                    <input type="file" id="inputImgParte" name="imagen_parte_diario" accept="image/*" style="display:none;" onchange="onFileSelected(this,'btnImgParte','📷')">
                                </div>
                            </div>
                            <div></div>
                            <div>
                                <button type="submit" class="btn btn-primary" id="btnAddRow">+ Agregar Fila</button>
                            </div>
                        </div>

                    @else
                        <div style="display:grid;grid-template-columns:140px 1fr 1fr 1fr 1fr 80px 90px 90px;gap:8px;margin-bottom:10px;">
                            <div>
                                <div class="row-input-lbl">Fecha *</div>
                                <input type="date" class="row-input" id="rFecha" name="fecha" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div>
                                <div class="row-input-lbl">Chofer *</div>
                                <select class="row-input" id="rChofer" name="id_chofer" required>
                                    <option value="">— Chofer —</option>
                                    @foreach($choferes as $ch)
                                        <option value="{{ $ch->id_chofer }}">
                                            {{ trim($ch->nombres . ' ' . ($ch->apellido_paterno ?? '') . ' ' . ($ch->apellido_materno ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="row-input-lbl">Agregado *</div>
                                <select class="row-input" id="rAgregado" name="id_agregado" required>
                                    <option value="">— Agregado —</option>
                                    @foreach($agregados as $a)
                                        <option value="{{ $a->id_agregado }}" {{ (int)($cotizacion->id_agregado ?? 0) === (int)$a->id_agregado ? 'selected' : '' }}>
                                            {{ $a->nombre }}{{ $a->numero_agregado ? ' (' . $a->numero_agregado . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="row-input-lbl">Obra</div>
                                <input type="text" class="row-input" id="rObra" name="obra_agregado" value="{{ $cotizacion->obra }}" placeholder="Obra">
                            </div>
                            <div>
                                <div class="row-input-lbl">Placa</div>
                                <input type="text" class="row-input" id="rPlaca" name="placa" placeholder="CAU782">
                            </div>
                            <div>
                                <div class="row-input-lbl">M³ *</div>
                                <input type="number" class="row-input" id="rM3" name="m3" step="0.01" placeholder="24" required oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">Precio/M³ *</div>
                                <input type="number" class="row-input" id="rPrecioM3" name="precio_m3" step="0.01" placeholder="300.00" required oninput="calcTotalFila()">
                            </div>
                            <div>
                                <div class="row-input-lbl">Total Fila</div>
                                <input type="number" class="row-input calculated" id="rTotal" step="0.01" readonly placeholder="0.00">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:8px;align-items:end;">
                            <div>
                                <div class="row-input-lbl">N° Parte Diario</div>
                                <input type="text" class="row-input" id="rNParte" name="n_parte_diario" placeholder="33594">
                            </div>
                            <div>
                                <div class="row-input-lbl">N° GRR</div>
                                <input type="text" class="row-input" id="rGRR" name="grr" placeholder="TTT3-3526">
                            </div>
                            <div>
                                <div class="row-input-lbl">Imagen Parte Diario</div>
                                <button type="button" class="file-btn" id="btnImgParte" onclick="document.getElementById('inputImgParte').click()">📷 Adjuntar</button>
                                <input type="file" id="inputImgParte" name="imagen_parte_diario" accept="image/*" style="display:none;" onchange="onFileSelected(this,'btnImgParte','📷')">
                            </div>
                            <div>
                                <div class="row-input-lbl">PDF GRR</div>
                                <button type="button" class="file-btn" id="btnPdfGrr" onclick="document.getElementById('inputPdfGrr').click()">📄 Adjuntar PDF</button>
                                <input type="file" id="inputPdfGrr" name="archivo_grr" accept="application/pdf" style="display:none;" onchange="onFileSelected(this,'btnPdfGrr','📄')">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary" id="btnAddRow">+ Agregar Fila</button>
                            </div>
                        </div>
                    @endif

                </form>
            </div>

            <div id="phantomAlert" style="display:none;margin-top:14px;background:#fef3c7;border:2px dashed #fbbf24;border-radius:10px;padding:14px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="flex:1;">
                        <div style="font-weight:800;font-size:13px;color:#92400e;margin-bottom:4px;">
                            👻 Fila fantasma detectada — Hay un salto en las horas del horómetro
                        </div>
                        <div id="phantomDesc" style="font-size:12px;color:#78350f;"></div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn" style="background:#fbbf24;color:#78350f;font-size:12px;padding:7px 14px;" id="btnAceptarPhantom" onclick="aceptarPhantom()">✓ Agregar fila fantasma</button>
                        <button class="btn btn-ghost" style="font-size:12px;padding:7px 14px;" onclick="document.getElementById('phantomAlert').style.display='none';">Ignorar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ MODAL ELIMINAR FILA ══ --}}
    <div class="modal-overlay" id="modalDelFila">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header" style="background:#7f1d1d;">
                <h2>Eliminar Fila</h2>
                <p>Esta acción no se puede deshacer.</p>
                <button onclick="document.getElementById('modalDelFila').classList.remove('open')" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p style="font-size:14px;">¿Confirmas que deseas eliminar esta fila de la cotización?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="document.getElementById('modalDelFila').classList.remove('open')">Cancelar</button>
                <button class="btn" style="background:#dc2626;color:#fff;" id="btnConfDelFila">Eliminar</button>
            </div>
        </div>
    </div>

    {{-- ══ MODAL EDITAR FILA ══ --}}
    <div class="modal-overlay" id="modalEditFila">
        <div class="modal" style="max-width:720px;">
            <div class="modal-header">
                <h2>Editar Fila</h2>
                <p>Modifica los datos de la fila seleccionada</p>
                <button onclick="document.getElementById('modalEditFila').classList.remove('open')" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form id="formEditFila" onsubmit="guardarEditFila(event)" enctype="multipart/form-data">
                <div class="modal-body" id="editFilaBody" style="padding:24px;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalEditFila').classList.remove('open')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ MODAL EDITAR HEADER ══ --}}
    <div class="modal-overlay" id="modalEditHeader">
        <div class="modal" style="max-width:640px;">
            <div class="modal-header">
                <h2>Editar Encabezado</h2>
                <p>Modifica los datos generales de la cotización</p>
                <button onclick="document.getElementById('modalEditHeader').classList.remove('open')" style="position:absolute;right:20px;top:20px;background:none;border:none;color:#fff;cursor:pointer;font-size:24px;">×</button>
            </div>
            <form onsubmit="guardarHeader(event)">
                <div class="modal-body" style="padding:24px;">
                    <input type="hidden" id="editHeaderTipo" name="tipo_cotizacion" value="{{ $cotizacion->tipo_cotizacion }}">
                    <div class="field-row cols2" style="margin-bottom:14px;">
                        <div class="form-group">
                            <label class="form-label">Empresa *</label>
                            <select name="id_cliente" id="editHeaderCliente" class="form-input">
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id_cliente }}" {{ $cotizacion->id_cliente == $c->id_cliente ? 'selected' : '' }}>
                                        {{ $c->razon_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">N° Valorización *</label>
                            <input type="text" name="numero_valorizacion" class="form-input" id="editHeaderNum" value="{{ $cotizacion->numero_valorizacion }}">
                        </div>
                    </div>
                    @if($esMaquinaria)
                        <div class="form-group" style="margin-bottom:14px;">
                            <label class="form-label">Maquinaria General *</label>
                            <select name="id_maquinaria" class="form-input" required>
                                <option value="">— Seleccionar maquinaria —</option>
                                @foreach($maquinarias as $m)
                                    <option value="{{ $m->id_maquinaria }}" {{ (int)($cotizacion->id_maquinaria ?? 0) === (int)$m->id_maquinaria ? 'selected' : '' }}>
                                        {{ $m->nombre }}{{ $m->numero_maquina ? ' — ' . $m->numero_maquina : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="form-group" style="margin-bottom:14px;">
                            <label class="form-label">Agregado General *</label>
                            <select name="id_agregado" class="form-input" required>
                                <option value="">— Seleccionar agregado —</option>
                                @foreach($agregados as $a)
                                    <option value="{{ $a->id_agregado }}" {{ (int)($cotizacion->id_agregado ?? 0) === (int)$a->id_agregado ? 'selected' : '' }}>
                                        {{ $a->nombre }}{{ $a->numero_agregado ? ' (' . $a->numero_agregado . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="form-group" style="margin-bottom:14px;">
                        <label class="form-label">Obra *</label>
                        <input type="text" name="obra" class="form-input" id="editHeaderObra" value="{{ $cotizacion->obra }}">
                    </div>
                    <div class="field-row cols2">
                        <div class="form-group">
                            <label class="form-label">Período Inicio *</label>
                            <input type="date" name="periodo_inicio" class="form-input" id="editHeaderDesde" value="{{ $cotizacion->periodo_inicio }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Período Fin *</label>
                            <input type="date" name="periodo_fin" class="form-input" id="editHeaderHasta" value="{{ $cotizacion->periodo_fin }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalEditHeader').classList.remove('open')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;padding:13px 20px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);transform:translateY(80px);opacity:0;transition:all .3s;max-width:400px;">
        <span id="toastTxt"></span>
    </div>

    {{-- Data for JS --}}
    <script>
        const COT_ID      = {{ $cotizacion->id_cotizacion }};
        const ES_MAQUINARIA = {{ $esMaquinaria ? 'true' : 'false' }};
        const CSRF        = '{{ $CSRF }}';
        const BASE_URL    = '/cotizaciones/' + COT_ID + '/rows';

        // NOMBRES COMPLETOS de choferes: nombres + apellido_paterno + apellido_materno
        const CHOFERES = @json($choferes->map(fn($c) => [
            'id'     => $c->id_chofer,
            'nombre' => trim($c->nombres . ' ' . ($c->apellido_paterno ?? '') . ' ' . ($c->apellido_materno ?? '')),
        ]));

        // MAQUINARIAS con número de máquina
        const MAQUINARIAS = @json($maquinarias->map(fn($m) => [
            'id'     => $m->id_maquinaria,
            'nombre' => $m->nombre . ($m->numero_maquina ? ' — ' . $m->numero_maquina : ''),
        ]));

        // AGREGADOS con código
        const AGREGADOS = @json($agregados->map(fn($a) => [
            'id'     => $a->id_agregado,
            'nombre' => $a->nombre . ($a->numero_agregado ? ' (' . $a->numero_agregado . ')' : ''),
        ]));

        const ROWS_DATA = @json($filas->map(fn($f) => (array) $f));
    </script>
@endsection

@push('scripts')
    <script>
        function showToast(msg, ok = true) {
            const t = document.getElementById('toast');
            document.getElementById('toastTxt').textContent = msg;
            t.style.background = ok ? '#d1fae5' : '#fee2e2';
            t.style.color      = ok ? '#065f46' : '#7f1d1d';
            t.style.border     = ok ? '1px solid #6ee7b7' : '1px solid #fca5a5';
            t.style.transform  = 'translateY(0)'; t.style.opacity = '1';
            setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3500);
        }

        function calcTotalFila() {
            if (ES_MAQUINARIA) {
                const hi   = parseFloat(document.getElementById('rHI')?.value)    || 0;
                const ht   = parseFloat(document.getElementById('rHT')?.value)    || 0;
                const hmin = parseFloat(document.getElementById('rHMin')?.value)  || 0;
                const prec = parseFloat(document.getElementById('rPrecio')?.value) || 0;
                const trab = Math.max(0, ht - hi);
                const efec = hmin > 0 ? Math.max(trab, hmin) : trab;
                const tot  = efec * prec;
                const htEl  = document.getElementById('rHTrab');
                const totEl = document.getElementById('rTotal');
                if (htEl)  htEl.value  = trab.toFixed(2);
                if (totEl) totEl.value = tot.toFixed(2);
            } else {
                const m3    = parseFloat(document.getElementById('rM3')?.value)      || 0;
                const prec  = parseFloat(document.getElementById('rPrecioM3')?.value) || 0;
                const totEl = document.getElementById('rTotal');
                if (totEl) totEl.value = (m3 * prec).toFixed(2);
            }
        }

        function onFileSelected(input, btnId, icon) {
            const btn = document.getElementById(btnId);
            if (input.files && input.files.length > 0) {
                btn.textContent = icon + ' ' + input.files[0].name.substring(0, 20);
                btn.classList.add('uploaded');
            } else {
                btn.classList.remove('uploaded');
            }
        }

        let phantomData = null;

        function onHIBlur() {
            if (!ES_MAQUINARIA) return;
            const rows = document.querySelectorAll('#rowTbody tr[data-id]');
            if (rows.length === 0) return;
            const lastRow = rows[rows.length - 1];
            const cells   = lastRow.querySelectorAll('td');
            const htValue = parseFloat(cells[8]?.textContent) || 0;
            const hiValue = parseFloat(document.getElementById('rHI')?.value) || 0;
            if (htValue > 0 && hiValue > htValue + 0.05) {
                const gap = (hiValue - htValue).toFixed(2);
                phantomData = { hora_inicio: htValue, hora_fin: hiValue, gap };
                document.getElementById('phantomDesc').textContent = `Horómetro previo terminó en ${htValue.toFixed(1)} y el actual inicia en ${hiValue.toFixed(1)} — Gap: ${gap} hrs`;
                document.getElementById('phantomAlert').style.display = 'block';
            } else {
                document.getElementById('phantomAlert').style.display = 'none';
                phantomData = null;
            }
        }

        async function aceptarPhantom() {
            if (!phantomData) return;
            document.getElementById('phantomAlert').style.display = 'none';
            const fd = new FormData();
            const fecha  = document.getElementById('rFecha')?.value || '';
            const chofer = document.getElementById('rChofer')?.value || '';
            const maq    = document.getElementById('rMaquinaria')?.value || '';
            const obra   = document.getElementById('rObra')?.value || '';
            const placa  = document.getElementById('rPlaca')?.value || '';
            const hmin   = document.getElementById('rHMin')?.value || '3';
            const precio = document.getElementById('rPrecio')?.value || '0';
            if (!chofer || !maq) { showToast('Completa Chofer y Maquinaria antes de aceptar la fila fantasma.', false); return; }
            fd.append('fecha', fecha); fd.append('id_chofer', chofer); fd.append('id_maquinaria', maq);
            fd.append('obra_maquina', obra); fd.append('placa', placa);
            fd.append('hora_inicio', phantomData.hora_inicio); fd.append('hora_fin', phantomData.hora_fin);
            fd.append('hora_minima', hmin); fd.append('precio_hora', precio); fd.append('n_parte_diario', '');
            await sendRowForm(fd);
            phantomData = null;
        }

        async function addRow(event) {
            event.preventDefault();
            const btn = document.getElementById('btnAddRow');
            btn.disabled = true; btn.textContent = 'Guardando…';
            const fd = new FormData(document.getElementById('addRowForm'));
            await sendRowForm(fd);
            btn.disabled = false; btn.textContent = '+ Agregar Fila';
            document.getElementById('phantomAlert').style.display = 'none';
            phantomData = null;
        }

        async function sendRowForm(fd) {
            fd.append('_token', CSRF);
            try {
                const res = await fetch(BASE_URL, { method: 'POST', body: fd });
                const ct  = (res.headers.get('content-type') || '').toLowerCase();
                if (!ct.includes('application/json')) {
                    const raw = await res.text();
                    const msg = raw.includes('SQLSTATE') ? raw.match(/SQLSTATE[^<\n]+/)?.[0] : `HTTP ${res.status}`;
                    throw new Error(msg || `HTTP ${res.status}`);
                }
                const data = await res.json();
                if (!data.success) { showToast(data.message || 'Error al guardar.', false); return; }
                showToast('Fila agregada correctamente.');
                actualizarTotales(data.totales);
                document.getElementById('emptyRow')?.remove();
                appendRowToTable(data.row);
                resetAddForm();
            } catch(e) { showToast('Error de red: ' + e.message, false); }
        }

        function appendRowToTable(row) {
            const tbody = document.getElementById('rowTbody');
            const idx   = document.querySelectorAll('#rowTbody tr[data-id]').length + 1;
            const sumRow = document.getElementById('sumRow');
            if (sumRow) sumRow.remove();
            tbody.insertAdjacentHTML('beforeend', buildRowHtml(row, idx));
            updateSumRow();
            document.getElementById('filaCountDesc').textContent = `${idx} fila(s) registradas`;
            document.getElementById('totFilas').textContent = idx;
        }

        function buildRowHtml(row, idx) {
            if (ES_MAQUINARIA) {
                const maqNombre = MAQUINARIAS.find(m => m.id == row.id_maquinaria)?.nombre || row.maquinaria_nombre || '—';
                const choNombre = CHOFERES.find(c => c.id == row.id_chofer)?.nombre || row.chofer_nombre || '—';
                const imgBtn    = row.ruta_parte_diario ? `<a href="/storage/${row.ruta_parte_diario}" target="_blank" class="file-pill-sm">📷</a>` : '';
                return `<tr data-id="${row.id_cotizacion_maqu || row._row_id || ''}" data-idx="${idx}" class="new-row">
                    <td style="text-align:center;color:var(--text-muted);font-size:10px;">${idx}</td>
                    <td class="mono">${fmtFecha(row.fecha)}</td>
                    <td>${choNombre}</td>
                    <td>${maqNombre}</td>
                    <td class="mono">${row.placa||'—'}</td>
                    <td>${row.obra_maquina||'—'}</td>
                    <td>${row.n_parte_diario||''}${imgBtn}</td>
                    <td class="r">${Number(row.hora_inicio).toFixed(1)}</td>
                    <td class="r">${Number(row.hora_fin).toFixed(1)}</td>
                    <td class="r" style="color:#059669;">${Number(row.horas_trabajadas).toFixed(2)}</td>
                    <td class="r">${Number(row.hora_minima).toFixed(1)}</td>
                    <td class="r">${Number(row.precio_hora).toFixed(2)}</td>
                    <td class="r total-fila-cell">${Number(row.total_fila).toFixed(2)}</td>
                    <td><div style="display:flex;gap:4px;justify-content:flex-end;">
                        <button class="tbl-btn" onclick="abrirEditarFila(${row.id_cotizacion_maqu||row._row_id})"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                        <button class="tbl-btn del" onclick="confirmarEliminarFila(${row.id_cotizacion_maqu||row._row_id})"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div></td>
                </tr>`;
            } else {
                const agrNombre = AGREGADOS.find(a => a.id == row.id_agregado)?.nombre || row.agregado_nombre || '—';
                const choNombre = CHOFERES.find(c => c.id == row.id_chofer)?.nombre || row.chofer_nombre || '—';
                const imgBtn    = row.ruta_parte_diario ? `<a href="/storage/${row.ruta_parte_diario}" target="_blank" class="file-pill-sm">📷</a>` : '';
                const pdfBtn    = row.ruta_grr ? `<a href="/storage/${row.ruta_grr}" target="_blank" class="file-pill-sm pdf">📄 PDF</a>` : '';
                return `<tr data-id="${row.id_cotizacion_agr || row._row_id || ''}" data-idx="${idx}" class="new-row">
                    <td style="text-align:center;color:var(--text-muted);font-size:10px;">${idx}</td>
                    <td class="mono">${fmtFecha(row.fecha)}</td>
                    <td>${choNombre}</td>
                    <td>${agrNombre}</td>
                    <td class="mono">${row.placa||'—'}</td>
                    <td>${row.obra_agregado||'—'}</td>
                    <td>${row.n_parte_diario||''}${imgBtn}</td>
                    <td class="r">${Number(row.m3).toFixed(2)}</td>
                    <td class="r">${Number(row.precio_m3).toFixed(2)}</td>
                    <td class="r total-fila-cell">${Number(row.total_fila).toFixed(2)}</td>
                    <td>${row.grr||''} ${pdfBtn}</td>
                    <td><div style="display:flex;gap:4px;justify-content:flex-end;">
                        <button class="tbl-btn" onclick="abrirEditarFila(${row.id_cotizacion_agr||row._row_id})"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                        <button class="tbl-btn del" onclick="confirmarEliminarFila(${row.id_cotizacion_agr||row._row_id})"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div></td>
                </tr>`;
            }
        }

        function updateSumRow() {
            const tbody  = document.getElementById('rowTbody');
            const totals = [...document.querySelectorAll('#rowTbody tr[data-id] .total-fila-cell')]
                .reduce((s, td) => s + (parseFloat(td.textContent) || 0), 0);
            const colSpan = ES_MAQUINARIA ? 12 : 10;
            document.getElementById('sumRow')?.remove();
            tbody.insertAdjacentHTML('beforeend', `
                <tr class="sum-row" id="sumRow">
                    <td colspan="${colSpan}" style="text-align:right;font-size:12px;letter-spacing:.4px;text-transform:uppercase;">TOTAL</td>
                    <td class="r" style="font-size:14px;color:var(--gold-d);" id="sumTotalFila">${totals.toFixed(2)}</td>
                    <td></td>
                </tr>`);
        }

        function actualizarTotales(t) {
            document.getElementById('totBase').textContent  = 'S/ ' + Number(t.base_sin_igv).toFixed(2);
            document.getElementById('totIgv').textContent   = 'S/ ' + Number(t.total_igv).toFixed(2);
            document.getElementById('totTotal').textContent = 'S/ ' + Number(t.total).toFixed(2);
            updateSumRow();
        }

        function resetAddForm() {
            if (ES_MAQUINARIA) {
                ['rHI','rHT','rHTrab','rTotal'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                document.getElementById('rNParte').value = '';
                const img = document.getElementById('inputImgParte');
                if (img) img.value = '';
                const btn = document.getElementById('btnImgParte');
                if (btn) { btn.textContent = '📷 Adjuntar imagen'; btn.classList.remove('uploaded'); }
            } else {
                ['rM3','rTotal','rNParte','rGRR'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                ['inputImgParte','inputPdfGrr'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                [['btnImgParte','📷 Adjuntar'],['btnPdfGrr','📄 Adjuntar PDF']].forEach(([id, txt]) => {
                    const btn = document.getElementById(id);
                    if (btn) { btn.textContent = txt; btn.classList.remove('uploaded'); }
                });
            }
        }

        // ── Delete row ──────────────────────────────────────────────────────────────
        let deleteRowId = null;
        function confirmarEliminarFila(rowId) {
            deleteRowId = rowId;
            document.getElementById('modalDelFila').classList.add('open');
        }
        document.getElementById('btnConfDelFila').addEventListener('click', async () => {
            if (!deleteRowId) return;
            const res  = await fetch(`${BASE_URL}/${deleteRowId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            document.getElementById('modalDelFila').classList.remove('open');
            if (data.success) {
                document.querySelector(`#rowTbody tr[data-id="${deleteRowId}"]`)?.remove();
                actualizarTotales(data.totales);
                document.querySelectorAll('#rowTbody tr[data-id]').forEach((r, i) => {
                    const cell = r.querySelectorAll('td')[0];
                    if (cell) cell.textContent = i + 1;
                });
                const cnt = document.querySelectorAll('#rowTbody tr[data-id]').length;
                document.getElementById('filaCountDesc').textContent = `${cnt} fila(s) registradas`;
                document.getElementById('totFilas').textContent = cnt;
                showToast('Fila eliminada.');
            } else showToast('Error al eliminar.', false);
            deleteRowId = null;
        });

        // ── Edit row ────────────────────────────────────────────────────────────────
        let editRowId = null;
        function abrirEditarFila(rowId) {
            editRowId = rowId;
            const row = ROWS_DATA.find(r => r.id_cotizacion_maqu == rowId || r.id_cotizacion_agr == rowId);
            if (!row) return;
            document.getElementById('editFilaBody').innerHTML = buildEditForm(row);
            document.getElementById('modalEditFila').classList.add('open');
            setTimeout(calcEditTotal, 100);
        }

        function buildEditForm(r) {
            const choferOpts = CHOFERES.map(c => `<option value="${c.id}" ${c.id==r.id_chofer?'selected':''}>${c.nombre}</option>`).join('');
            const maqOpts    = MAQUINARIAS.map(m => `<option value="${m.id}" ${m.id==r.id_maquinaria?'selected':''}>${m.nombre}</option>`).join('');
            const agrOpts    = AGREGADOS.map(a => `<option value="${a.id}" ${a.id==r.id_agregado?'selected':''}>${a.nombre}</option>`).join('');

            if (ES_MAQUINARIA) {
                return `
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-input" value="${r.fecha||''}" required></div>
                    <div class="form-group"><label class="form-label">Chofer</label><select name="id_chofer" class="form-input" required><option value="">—</option>${choferOpts}</select></div>
                    <div class="form-group"><label class="form-label">Maquinaria</label><select name="id_maquinaria" class="form-input" required><option value="">—</option>${maqOpts}</select></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">Obra</label><input type="text" name="obra_maquina" class="form-input" value="${r.obra_maquina||''}"></div>
                    <div class="form-group"><label class="form-label">Placa</label><input type="text" name="placa" class="form-input" value="${r.placa||''}"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">HI *</label><input type="number" name="hora_inicio" class="form-input" step="0.01" value="${r.hora_inicio||''}" oninput="calcEditTotal()" required></div>
                    <div class="form-group"><label class="form-label">HT *</label><input type="number" name="hora_fin" class="form-input" step="0.01" value="${r.hora_fin||''}" oninput="calcEditTotal()" required></div>
                    <div class="form-group"><label class="form-label">H.Trab.</label><input type="number" class="form-input" style="background:#f8fafc;" id="eHTrab" step="0.01" readonly value="${r.horas_trabajadas||''}"></div>
                    <div class="form-group"><label class="form-label">H.Mín. *</label><input type="number" name="hora_minima" class="form-input" step="0.01" value="${r.hora_minima||3}" oninput="calcEditTotal()" required></div>
                    <div class="form-group"><label class="form-label">Precio/H *</label><input type="number" name="precio_hora" class="form-input" step="0.01" value="${r.precio_hora||''}" oninput="calcEditTotal()" required></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group"><label class="form-label">N° Parte Diario</label><input type="text" name="n_parte_diario" class="form-input" value="${r.n_parte_diario||''}"></div>
                    <div class="form-group"><label class="form-label">Imagen Parte Diario</label><input type="file" name="imagen_parte_diario" class="form-input" accept="image/*" style="height:auto;padding:6px 10px;"></div>
                </div>`;
            } else {
                return `
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-input" value="${r.fecha||''}" required></div>
                    <div class="form-group"><label class="form-label">Chofer</label><select name="id_chofer" class="form-input" required><option value="">—</option>${choferOpts}</select></div>
                    <div class="form-group"><label class="form-label">Agregado</label><select name="id_agregado" class="form-input" required><option value="">—</option>${agrOpts}</select></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">Obra</label><input type="text" name="obra_agregado" class="form-input" value="${r.obra_agregado||''}"></div>
                    <div class="form-group"><label class="form-label">Placa</label><input type="text" name="placa" class="form-input" value="${r.placa||''}"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group"><label class="form-label">M³ *</label><input type="number" name="m3" class="form-input" step="0.01" value="${r.m3||''}" oninput="calcEditTotal()" required></div>
                    <div class="form-group"><label class="form-label">Precio/M³ *</label><input type="number" name="precio_m3" class="form-input" step="0.01" value="${r.precio_m3||''}" oninput="calcEditTotal()" required></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                    <div class="form-group"><label class="form-label">N° Parte Diario</label><input type="text" name="n_parte_diario" class="form-input" value="${r.n_parte_diario||''}"></div>
                    <div class="form-group"><label class="form-label">N° GRR</label><input type="text" name="grr" class="form-input" value="${r.grr||''}"></div>
                    <div class="form-group"><label class="form-label">Imagen Parte</label><input type="file" name="imagen_parte_diario" class="form-input" accept="image/*" style="height:auto;padding:6px 10px;"></div>
                </div>
                <div style="margin-top:10px;" class="form-group"><label class="form-label">PDF GRR</label>
                    <input type="file" name="archivo_grr" class="form-input" accept="application/pdf" style="height:auto;padding:6px 10px;"></div>`;
            }
        }

        function calcEditTotal() {
            const form = document.getElementById('editFilaBody');
            if (ES_MAQUINARIA) {
                const hi   = parseFloat(form.querySelector('[name="hora_inicio"]')?.value) || 0;
                const ht   = parseFloat(form.querySelector('[name="hora_fin"]')?.value)    || 0;
                const trab = Math.max(0, ht - hi);
                const htEl = document.getElementById('eHTrab');
                if (htEl) htEl.value = trab.toFixed(2);
            }
        }

        async function guardarEditFila(event) {
            event.preventDefault();
            const fd = new FormData(document.getElementById('formEditFila'));
            fd.append('_method', 'PUT');
            fd.append('_token', CSRF);
            try {
                const res  = await fetch(`${BASE_URL}/${editRowId}`, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast('Fila actualizada.');
                    document.getElementById('modalEditFila').classList.remove('open');
                    actualizarTotales(data.totales);
                    setTimeout(() => location.reload(), 800);
                } else showToast(data.message || 'Error al guardar.', false);
            } catch(e) { showToast('Error de red.', false); }
        }

        // ── Edit header ─────────────────────────────────────────────────────────────
        function abrirEditarHeader() { document.getElementById('modalEditHeader').classList.add('open'); }

        async function guardarHeader(event) {
            event.preventDefault();
            const fd   = new FormData(event.target);
            const body = new URLSearchParams(fd);
            body.append('_method', 'PUT');
            const res  = await fetch(`/cotizaciones/{{ $cotizacion->id_cotizacion }}`, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body:    body.toString()
            });
            const data = await res.json();
            if (data.success) {
                showToast('Encabezado actualizado.');
                document.getElementById('modalEditHeader').classList.remove('open');
                setTimeout(() => location.reload(), 800);
            } else showToast('Error al guardar.', false);
        }

        // ── Helpers ─────────────────────────────────────────────────────────────────
        function fmtFecha(f) {
            if (!f) return '—';
            const d = new Date(f + 'T00:00:00');
            return d.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
        }

        ['modalDelFila','modalEditFila','modalEditHeader'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', e => {
                if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
            });
        });
    </script>
@endpush
