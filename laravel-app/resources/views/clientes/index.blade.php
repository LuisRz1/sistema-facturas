@extends('layouts.app')

@section('title', 'Directorio de Clientes')
@section('breadcrumb', 'Directorio de Clientes')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ══════════════════════════════════════
       PALETA DORADA — C.R.C. S.A.C.
    ══════════════════════════════════════ */
    :root {
        --gold:       #f5c842;
        --gold-h:     #e8b820;
        --gold-l:     #fffbeb;
        --gold-b:     #ead96a;
        --gold-m:     #d4a017;
        --gold-d:     #9a6e10;
        --gold-xd:    #633806;
        --bg:         #fdf8ec;
        --text:       #1c1600;
        --text-m:     #7a6838;
        --text-s:     #9a8840;
        --white:      #fff;
        --red:        #dc2626;
        --green-bg:   #d1fae5;
        --green-t:    #065f46;
        --yellow-bg:  #fef3c7;
        --yellow-t:   #92400e;
        --gray-bg:    #f1f5f9;
        --gray-t:     #64748b;
    }

    /* ══ BASE ══ */
    body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: var(--bg) !important; }

    /* ══ KEYFRAMES ══ */
    @keyframes fadeDown  { from { opacity:0; transform:translateY(-14px) } to { opacity:1; transform:translateY(0) } }
    @keyframes slideUp   { from { opacity:0; transform:translateY(22px)  } to { opacity:1; transform:translateY(0) } }
    @keyframes rowIn     { from { opacity:0; transform:translateX(-8px)  } to { opacity:1; transform:translateX(0) } }
    @keyframes chipPop   { from { opacity:0; transform:scale(.9)         } to { opacity:1; transform:scale(1)     } }
    @keyframes spinPulse { 0%,100% { transform:rotate(0) scale(1) } 50% { transform:rotate(180deg) scale(1.05) } }

    /* ══ PAGE HEADER ══ */
    .page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 28px 32px 0;
        animation: fadeDown .5s cubic-bezier(.16,1,.3,1) both;
    }

    .accent-bar {
        display: inline-block;
        width: 28px; height: 3px;
        background: var(--gold);
        border-radius: 2px;
        margin-right: 8px;
        vertical-align: middle;
        margin-bottom: 3px;
    }

    .page-title {
        font-size: 22px !important;
        font-weight: 800 !important;
        color: var(--text) !important;
        letter-spacing: -.4px;
    }

    .page-desc {
        font-size: 13px;
        color: var(--text-s);
        margin-top: 4px;
    }

    /* ══ STAT CHIPS ══ */
    .stat-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 20px 32px 0;
        animation: fadeDown .5s .1s cubic-bezier(.16,1,.3,1) both;
    }

    .chip {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--white);
        border: 1px solid var(--gold-b);
        border-radius: 14px;
        padding: 11px 18px;
        cursor: default;
        transition: transform .15s, box-shadow .15s;
        animation: chipPop .4s cubic-bezier(.34,1.56,.64,1) both;
    }

    .chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 22px #f5c84222;
    }

    .chip:nth-child(1) { animation-delay: .1s }
    .chip:nth-child(2) { animation-delay: .17s }
    .chip:nth-child(3) { animation-delay: .24s }
    .chip:nth-child(4) { animation-delay: .31s }

    .chip-icon {
        width: 34px; height: 34px;
        border-radius: 10px;
        background: var(--gold-l);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .chip-icon svg { color: var(--gold-m); width: 16px; height: 16px; }
    .chip-val  { font-size: 18px; font-weight: 800; color: var(--text); line-height: 1; }
    .chip-lbl  { font-size: 11px; color: var(--text-s); margin-top: 2px; }

    /* ══ CARD ══ */
    .card {
        background: var(--white) !important;
        border: 1px solid var(--gold-b) !important;
        border-radius: 20px !important;
        margin: 20px 32px !important;
        overflow: hidden;
        animation: slideUp .55s .15s cubic-bezier(.16,1,.3,1) both;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px !important;
        border-bottom: 1px solid #fef3c7 !important;
        background: transparent !important;
    }

    .card-title { font-size: 15px !important; font-weight: 700 !important; color: var(--text) !important; }
    .card-desc  { font-size: 12px; color: var(--text-s); margin-top: 2px; }

    /* ══ BTN ADD ══ */
    .btn-add, .btn.btn-primary {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        background: var(--gold) !important;
        color: var(--text) !important;
        border: none !important;
        border-radius: 10px !important;
        padding: 9px 18px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        cursor: pointer;
        transition: background .15s, transform .12s, box-shadow .15s;
        position: relative; overflow: hidden;
        text-decoration: none;
    }

    .btn-add::after, .btn.btn-primary::after {
        content: '';
        position: absolute; inset: 0;
        background: rgba(255,255,255,.2);
        transform: translateX(-100%) skewX(-15deg);
        transition: transform .35s;
    }

    .btn-add:hover, .btn.btn-primary:hover {
        background: var(--gold-h) !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px #f5c84230;
        color: var(--text) !important;
    }

    .btn-add:hover::after, .btn.btn-primary:hover::after {
        transform: translateX(110%) skewX(-15deg);
    }

    /* ══ SEARCH BAR ══ */
    .search-bar, .toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 24px;
        border-bottom: 1px solid #fef9e0;
        flex-wrap: wrap;
    }

    .search-input-wrap {
        position: relative;
        max-width: 300px;
        flex: 1;
    }

    .search-input-wrap svg {
        position: absolute;
        left: 11px; top: 50%;
        transform: translateY(-50%);
        color: #c8a832;
        pointer-events: none;
    }

    .search-input-wrap .form-input,
    input[type="text"].form-input {
        padding-left: 34px !important;
    }

    .form-input, .form-select {
        height: 38px !important;
        border: 1.5px solid var(--gold-b) !important;
        border-radius: 10px !important;
        font-size: 13px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: var(--text) !important;
        background: var(--white) !important;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }

    .form-input:focus, .form-select:focus {
        border-color: var(--gold-m) !important;
        box-shadow: 0 0 0 3px #f5c84220 !important;
    }

    .form-input::placeholder { color: #c4af70; }

    .form-select {
        padding: 0 12px !important;
        cursor: pointer;
        width: auto !important;
        min-width: 170px !important;
    }

    /* ══ TABLE ══ */
    table { width: 100%; border-collapse: collapse; }

    thead tr { background: var(--gold-l) !important; }

    th {
        padding: 10px 16px !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
        letter-spacing: .7px !important;
        text-transform: uppercase !important;
        color: var(--text-s) !important;
        white-space: nowrap;
        border: none !important;
    }

    tbody tr {
        border-bottom: 1px solid #fef9e0 !important;
        transition: background .15s;
        animation: rowIn .4s cubic-bezier(.16,1,.3,1) both;
    }

    tbody tr:nth-child(1)  { animation-delay: .18s }
    tbody tr:nth-child(2)  { animation-delay: .23s }
    tbody tr:nth-child(3)  { animation-delay: .28s }
    tbody tr:nth-child(4)  { animation-delay: .33s }
    tbody tr:nth-child(5)  { animation-delay: .38s }
    tbody tr:nth-child(6)  { animation-delay: .43s }
    tbody tr:nth-child(7)  { animation-delay: .48s }
    tbody tr:nth-child(8)  { animation-delay: .53s }
    tbody tr:nth-child(9)  { animation-delay: .58s }
    tbody tr:nth-child(10) { animation-delay: .63s }

    tbody tr:last-child { border-bottom: none !important; }
    tbody tr:hover { background: #fffdf5 !important; }

    td { padding: 13px 16px !important; vertical-align: middle !important; }

    /* ══ AVATAR ══ */
    .avatar-circle {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: var(--gold-l) !important;
        border: 2px solid var(--gold-b);
        color: var(--gold-d) !important;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800 !important;
        font-size: 14px !important;
        flex-shrink: 0;
        transition: transform .2s, box-shadow .2s;
    }

    tbody tr:hover .avatar-circle {
        transform: scale(1.1);
        box-shadow: 0 4px 12px #f5c84230;
    }

    .client-row-main {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .client-name { font-weight: 700; font-size: 13.5px; color: var(--text); }
    .client-since { font-size: 11px; color: var(--text-s); margin-top: 1px; }

    /* ══ RUC TAG ══ */
    .ruc-tag {
        display: inline-block;
        background: var(--gold-l);
        border: 1px solid var(--gold-b);
        border-radius: 6px;
        padding: 3px 9px;
        font-size: 12px;
        font-weight: 700;
        color: var(--gold-xd);
        letter-spacing: .3px;
        font-family: 'Courier New', monospace;
    }

    /* ══ CONTACT ══ */
    .contact-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: var(--text-m) !important;
        margin-bottom: 3px;
    }

    .contact-item svg { color: var(--gold-m) !important; flex-shrink: 0; }
    .contact-item:last-child { margin-bottom: 0; }

    /* ══ BADGES ══ */
    .estado-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .estado-badge::before {
        content: '';
        width: 5px; height: 5px;
        border-radius: 50%;
        background: currentColor;
        opacity: .7;
        flex-shrink: 0;
    }

    .estado-completo   { background: var(--green-bg);  color: var(--green-t); }
    .estado-incompleto { background: var(--yellow-bg); color: var(--yellow-t); }
    .estado-sin_datos  { background: var(--gray-bg);   color: var(--gray-t); }

    /* ══ ACTIONS ══ */
    .actions-cell {
        display: flex;
        align-items: center;
        gap: 4px;
        justify-content: flex-end;
    }

    .action-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        border: 1px solid var(--gold-b) !important;
        background: var(--white) !important;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all .15s;
        color: var(--text-m) !important;
    }

    .action-btn:hover {
        background: var(--gold-l) !important;
        border-color: var(--gold-m) !important;
        color: var(--gold-d) !important;
        transform: scale(1.1);
    }

    .action-btn[style*="red"]:hover,
    .action-btn[style*="var(--red)"]:hover {
        background: #fef2f2 !important;
        border-color: #fca5a5 !important;
        color: var(--red) !important;
    }

    .action-btn svg { width: 13px; height: 13px; }

    /* ══ EMPTY STATE ══ */
    .empty-state {
        text-align: center;
        padding: 56px 24px;
        color: var(--text-s);
    }

    .empty-state svg { color: var(--gold-b); margin: 0 auto 16px; display: block; }

    /* ══ MODAL ══ */
    .modal-overlay {
        position: fixed; inset: 0;
        background: rgba(28,22,0,.35);
        display: flex; align-items: center; justify-content: center;
        z-index: 50;
        opacity: 0; pointer-events: none;
        transition: opacity .25s;
        padding: 24px;
    }

    .modal-overlay.open { opacity: 1; pointer-events: all; }

    .modal {
        background: var(--white);
        border-radius: 22px;
        border: 1px solid var(--gold-b);
        width: 100%; max-width: 520px;
        transform: translateY(24px) scale(.97);
        transition: transform .3s cubic-bezier(.16,1,.3,1);
        overflow: hidden;
    }

    .modal-overlay.open .modal { transform: translateY(0) scale(1); }

    .modal-header {
        background: var(--gold-l);
        padding: 22px 28px 18px;
        border-bottom: 1px solid var(--gold-b);
        position: relative;
    }

    .modal-header::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: var(--gold);
        border-radius: 22px 22px 0 0;
    }

    .modal-header h2 {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: var(--text) !important;
    }

    .modal-header p {
        font-size: 13px;
        color: var(--text-m);
        margin-top: 3px;
    }

    .modal .modal-body { padding: 22px 28px; }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .form-full { grid-column: 1 / -1; }

    .form-group { margin-bottom: 0; }

    .form-label {
        display: block;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: .6px !important;
        text-transform: uppercase !important;
        color: var(--text-s) !important;
        margin-bottom: 6px !important;
    }

    .modal .form-input {
        height: 42px !important;
        font-size: 13.5px !important;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 28px;
        border-top: 1px solid #fef9e0;
    }

    .btn-outline, .btn.btn-outline {
        height: 40px; padding: 0 18px;
        border: 1.5px solid var(--gold-b) !important;
        border-radius: 10px !important;
        background: var(--white) !important;
        color: var(--text-m) !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        cursor: pointer;
        transition: all .15s;
    }

    .btn-outline:hover, .btn.btn-outline:hover {
        background: var(--gold-l) !important;
        border-color: var(--gold-m) !important;
    }

    .btn.btn-primary#btnSubmit {
        height: 40px;
    }

    /* ══ FONT MONO ══ */
    .font-mono { font-family: 'Courier New', monospace; }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 768px) {
        .page-header, .stat-chips, .card { padding: 16px !important; margin: 12px !important; }
        .stat-chips { gap: 8px; }
        .chip { padding: 9px 12px; }
        .form-grid { grid-template-columns: 1fr; }
        .form-full { grid-column: 1; }
    }
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <span class="accent-bar"></span>Directorio de Clientes
        </h1>
        <p class="page-desc">Administra los datos de contacto de tus clientes y socios comerciales.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary btn-add" onclick="abrirModal()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar Cliente
        </button>
    </div>
</div>

{{-- STAT CHIPS --}}
<div class="stat-chips">
    {{-- Total --}}
    <div class="chip">
        <div class="chip-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div class="chip-val">{{ $clientes->count() }}</div>
            <div class="chip-lbl">Total clientes</div>
        </div>
    </div>

    {{-- Completos --}}
    <div class="chip">
        <div class="chip-icon" style="background:#d1fae5;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#065f46" stroke-width="2" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="chip-val" style="color:#065f46;">{{ $clientes->where('estado_contacto','COMPLETO')->count() }}</div>
            <div class="chip-lbl">Completos</div>
        </div>
    </div>

    {{-- Incompletos --}}
    <div class="chip">
        <div class="chip-icon" style="background:#fef3c7;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#92400e" stroke-width="2" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <div class="chip-val" style="color:#92400e;">{{ $clientes->where('estado_contacto','INCOMPLETO')->count() }}</div>
            <div class="chip-lbl">Incompletos</div>
        </div>
    </div>

    {{-- Sin datos --}}
    <div class="chip">
        <div class="chip-icon" style="background:#f1f5f9;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#64748b" stroke-width="2" style="width:16px;height:16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="chip-val" style="color:#64748b;">{{ $clientes->where('estado_contacto','SIN_DATOS')->count() }}</div>
            <div class="chip-lbl">Sin datos</div>
        </div>
    </div>
</div>

{{-- CARD TABLA --}}
<div class="card">

    {{-- Header --}}
    <div class="card-header">
        <div>
            <div class="card-title">Clientes Registrados</div>
            <div class="card-desc">{{ $clientes->count() }} clientes en el sistema</div>
        </div>
    </div>

    {{-- Toolbar búsqueda --}}
    <div class="search-bar">
        <div class="search-input-wrap" style="max-width:300px;">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input
                type="text"
                class="form-input"
                id="searchInput"
                placeholder="Buscar por nombre o RUC..."
                onkeyup="filtrarClientes()">
        </div>
        <select
            class="form-select"
            id="filterEstado"
            onchange="filtrarClientes()"
            style="width:auto;min-width:180px;">
            <option value="">Todos los estados</option>
            <option value="COMPLETO">Completo</option>
            <option value="INCOMPLETO">Incompleto</option>
            <option value="SIN_DATOS">Sin datos</option>
        </select>
    </div>

    {{-- Tabla --}}
    <div style="overflow-x:auto;">
        <table id="clientesTable">
            <thead>
                <tr>
                    <th>Empresa / Cliente</th>
                    <th>RUC</th>
                    <th>Contacto</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="clientesBody">
                @forelse($clientes as $cliente)
                    @php
                        $inicial     = strtoupper(substr($cliente->razon_social, 0, 1));
                        $estadoClass = 'estado-' . strtolower($cliente->estado_contacto);
                    @endphp
                    <tr
                        data-search="{{ strtolower($cliente->razon_social . ' ' . $cliente->ruc) }}"
                        data-estado="{{ $cliente->estado_contacto }}">

                        {{-- Empresa --}}
                        <td>
                            <div class="client-row-main">
                                <div class="avatar-circle">{{ $inicial }}</div>
                                <div>
                                    <div class="client-name">{{ $cliente->razon_social }}</div>
                                    <div class="client-since">
                                        Desde {{ \Carbon\Carbon::parse($cliente->fecha_creacion)->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- RUC --}}
                        <td>
                            <span class="ruc-tag">{{ $cliente->ruc }}</span>
                        </td>

                        {{-- Contacto --}}
                        <td>
                            @if($cliente->correo)
                                <div class="contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $cliente->correo }}
                                </div>
                            @endif
                            @if($cliente->celular)
                                <div class="contact-item">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $cliente->celular }}
                                </div>
                            @endif
                            @if(!$cliente->correo && !$cliente->celular)
                                <span style="font-size:12px;color:var(--text-s);">Sin datos de contacto</span>
                            @endif
                        </td>

                        {{-- Dirección --}}
                        <td style="max-width:200px;">
                            <span style="font-size:12px;color:var(--text-s);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:180px;">
                                {{ $cliente->direccion_fiscal ? Str::limit($cliente->direccion_fiscal, 40) : '—' }}
                            </span>
                        </td>

                        {{-- Estado --}}
                        <td>
                            <span class="estado-badge {{ $estadoClass }}">
                                {{ str_replace('_', ' ', $cliente->estado_contacto) }}
                            </span>
                        </td>

                        {{-- Acciones --}}
                        <td>
                            <div class="actions-cell">
                                <button
                                    class="action-btn"
                                    title="Editar"
                                    onclick="editarCliente(
                                        {{ $cliente->id_cliente }},
                                        '{{ addslashes($cliente->razon_social) }}',
                                        '{{ $cliente->ruc }}',
                                        '{{ $cliente->celular }}',
                                        '{{ addslashes($cliente->direccion_fiscal ?? '') }}',
                                        '{{ $cliente->correo }}'
                                    )">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('clientes.destroy', $cliente->id_cliente) }}"
                                    style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar este cliente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="action-btn"
                                        title="Eliminar"
                                        style="color:var(--red);">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p style="font-weight:700;font-size:15px;color:var(--text);">Sin clientes registrados</p>
                                <p style="font-size:13px;margin-top:4px;">Agrega tu primer cliente usando el botón superior.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══════════════════════ MODAL ═══════════════════════ --}}
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Cliente</h2>
            <p id="modalDesc">Ingresa los datos del nuevo cliente o proveedor.</p>
        </div>

        <form id="clienteForm" method="POST" action="{{ route('clientes.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label class="form-label">Razón Social *</label>
                        <input type="text" name="razon_social" id="f_razon" class="form-input" placeholder="Ej. Constructora ABC S.A.C." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC *</label>
                        <input type="text" name="ruc" id="f_ruc" class="form-input" placeholder="20123456789" maxlength="11" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Celular / WhatsApp</label>
                        <input type="text" name="celular" id="f_celular" class="form-input" placeholder="+51 987 654 321">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" id="f_correo" class="form-input" placeholder="contacto@empresa.com">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Dirección Fiscal</label>
                        <input type="text" name="direccion_fiscal" id="f_direccion" class="form-input" placeholder="Av. Los Tulipanes 123, Lima">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    /* ── MODAL ── */
    function abrirModal() {
        document.getElementById('modalTitle').textContent = 'Nuevo Cliente';
        document.getElementById('modalDesc').textContent  = 'Ingresa los datos del nuevo cliente.';
        document.getElementById('clienteForm').action     = '{{ route('clientes.store') }}';
        document.getElementById('formMethod').value       = 'POST';
        document.getElementById('btnSubmit').textContent  = 'Guardar Cliente';
        ['f_razon','f_ruc','f_celular','f_correo','f_direccion']
            .forEach(id => document.getElementById(id).value = '');
        document.getElementById('modalOverlay').classList.add('open');
    }

    function editarCliente(id, razon, ruc, celular, direccion, correo) {
        document.getElementById('modalTitle').textContent   = 'Editar Cliente';
        document.getElementById('modalDesc').textContent    = 'Actualiza los datos del cliente.';
        document.getElementById('clienteForm').action       = '/clientes/' + id;
        document.getElementById('formMethod').value         = 'PUT';
        document.getElementById('f_razon').value            = razon;
        document.getElementById('f_ruc').value              = ruc;
        document.getElementById('f_celular').value          = celular   || '';
        document.getElementById('f_correo').value           = correo    || '';
        document.getElementById('f_direccion').value        = direccion || '';
        document.getElementById('btnSubmit').textContent    = 'Actualizar Cliente';
        document.getElementById('modalOverlay').classList.add('open');
    }

    function cerrarModal() {
        document.getElementById('modalOverlay').classList.remove('open');
    }

    document.getElementById('modalOverlay').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    /* ── FILTRO ── */
    function filtrarClientes() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const estado = document.getElementById('filterEstado').value;

        document.querySelectorAll('#clientesBody tr[data-search]').forEach(row => {
            const matchSearch = !search || row.dataset.search.includes(search);
            const matchEstado = !estado || row.dataset.estado === estado;
            row.style.display = (matchSearch && matchEstado) ? '' : 'none';
        });
    }
</script>
@endpush

@endsection
