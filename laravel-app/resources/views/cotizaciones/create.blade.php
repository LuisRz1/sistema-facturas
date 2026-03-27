@extends('layouts.app')
@section('title', 'Nueva Cotización')
@section('breadcrumb', 'Nueva Cotización')

@push('styles')
    <style>
        :root { --gold:#f5c842; --gold-b:#ead96a; --gold-m:#d4a017; --gold-l:#fffbeb; }
        .tipo-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:10px; }
        .tipo-card {
            position:relative; border:2px solid var(--border); border-radius:14px;
            padding:24px 20px 20px; cursor:pointer; transition:all .18s;
            background:#f8fafc; user-select:none;
        }
        .tipo-card:hover { border-color:#94a3b8; background:#f1f5f9; }
        .tipo-card.active-maq { border-color:#d97706; background:#fef3c7; }
        .tipo-card.active-agr { border-color:#059669; background:#d1fae5; }
        .tipo-icon { font-size:36px; display:block; margin-bottom:12px; }
        .tipo-label { font-size:14px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .tipo-desc  { font-size:12px; color:var(--text-muted); margin-top:6px; line-height:1.5; }
        .tipo-check {
            position:absolute; top:12px; right:14px;
            width:22px; height:22px; border-radius:50%; border:2px solid var(--border);
            display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:900;
        }
        .form-wrap { max-width:680px; margin:0 auto; }
        .section-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-muted); margin-bottom:10px; display:flex; align-items:center; gap:8px; }
        .section-lbl::after { content:''; flex:1; height:1px; background:var(--border,#e2e8f0); }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ route('cotizaciones.index') }}">Cotizaciones</a>
                <span>›</span><span>Nueva</span>
            </div>
            <h1 class="page-title">Nueva Cotización</h1>
            <p class="page-desc">Define el tipo, empresa y período antes de cargar las filas.</p>
        </div>
        <a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost">← Volver</a>
    </div>

    <div class="card form-wrap">
        <form id="frmCotizacion" method="POST" action="{{ route('cotizaciones.store') }}">
            @csrf
            <input type="hidden" name="tipo_cotizacion" id="inputTipo" value="">

            <div style="padding:24px;">

                {{-- Tipo --}}
                <div class="section-lbl">① Tipo de cotización</div>
                <div class="tipo-grid">
                    <div class="tipo-card" id="cardMaq" onclick="selTipo('MAQUINARIA')">
                        <span class="tipo-check" id="chkMaq"></span>
                        <span class="tipo-icon">⚙️</span>
                        <span class="tipo-label" style="color:#92400e;">Maquinaria</span>
                        <p class="tipo-desc">Alquiler de volquetes, cisternas, motoniveladoras, etc. Se cobra por horas trabajadas.</p>
                    </div>
                    <div class="tipo-card" id="cardAgr" onclick="selTipo('AGREGADO')">
                        <span class="tipo-check" id="chkAgr"></span>
                        <span class="tipo-icon">🪨</span>
                        <span class="tipo-label" style="color:#065f46;">Agregados</span>
                        <p class="tipo-desc">Arena gruesa, eliminación de desmonte, suministro de agua, etc. Se cobra por m³.</p>
                    </div>
                </div>
                @error('tipo_cotizacion')
                <p style="color:#dc2626;font-size:12px;margin-top:8px;">⚠ {{ $message }}</p>
                @enderror

                <div style="height:24px;"></div>

                {{-- Empresa + N° Valorización --}}
                <div class="section-lbl">② Datos generales</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="form-group">
                        <label class="form-label">Empresa / Cliente *</label>
                        <select name="id_cliente" class="form-input" required>
                            <option value="">— Seleccionar empresa —</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id_cliente }}" {{ old('id_cliente') == $c->id_cliente ? 'selected' : '' }}>
                                    {{ $c->razon_social }} ({{ $c->ruc }})
                                </option>
                            @endforeach
                        </select>
                        @error('id_cliente')<p style="color:#dc2626;font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">N° de Valorización *</label>
                        <input type="text" name="numero_valorizacion" class="form-input"
                               value="{{ old('numero_valorizacion') }}" placeholder="Ej: 01" maxlength="20" required>
                        @error('numero_valorizacion')<p style="color:#dc2626;font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Obra --}}
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Nombre de la Obra *</label>
                    <input type="text" name="obra" class="form-input"
                           value="{{ old('obra') }}" placeholder="Ej: ENG-MOCHE, ELIMINACION DE DESMONTE…" required maxlength="250">
                    @error('obra')<p style="color:#dc2626;font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>

                {{-- Período --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Período Inicio *</label>
                        <input type="date" name="periodo_inicio" class="form-input"
                               value="{{ old('periodo_inicio', now()->startOfMonth()->format('Y-m-d')) }}" required>
                        @error('periodo_inicio')<p style="color:#dc2626;font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Período Fin *</label>
                        <input type="date" name="periodo_fin" class="form-input"
                               value="{{ old('periodo_fin', now()->endOfMonth()->format('Y-m-d')) }}" required>
                        @error('periodo_fin')<p style="color:#dc2626;font-size:11px;margin-top:4px;">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div style="border-top:1px solid var(--border);padding:16px 24px;display:flex;justify-content:flex-end;gap:10px;background:#fafbfd;">
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary" id="btnCrear" disabled>
                    Crear Cotización → Agregar Filas
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        window.selTipo = function(tipo) {
            const cardMaq = document.getElementById('cardMaq');
            const cardAgr = document.getElementById('cardAgr');
            const chkMaq  = document.getElementById('chkMaq');
            const chkAgr  = document.getElementById('chkAgr');

            cardMaq.className = 'tipo-card';
            cardAgr.className = 'tipo-card';
            chkMaq.textContent = chkMaq.style.cssText = '';
            chkAgr.textContent = chkAgr.style.cssText = '';

            if (tipo === 'MAQUINARIA') {
                cardMaq.className += ' active-maq';
                chkMaq.textContent = '✓';
                chkMaq.style.cssText = 'background:#d97706;border-color:#d97706;color:#fff;';
            } else {
                cardAgr.className += ' active-agr';
                chkAgr.textContent = '✓';
                chkAgr.style.cssText = 'background:#059669;border-color:#059669;color:#fff;';
            }

            document.getElementById('inputTipo').value = tipo;
            document.getElementById('btnCrear').disabled = false;
        };

        // Restore if old()
        var oldTipo = '{{ old("tipo_cotizacion") }}';
        if (oldTipo) selTipo(oldTipo);
    </script>
@endpush
