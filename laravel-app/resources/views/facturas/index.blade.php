@extends('layouts.app')
@section('title', 'Gestión de Facturas')
@section('breadcrumb', 'Gestión de Facturas')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════
   PALETA DORADA — C.R.C. S.A.C.  ·  Facturas
══════════════════════════════════════════════ */
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
    --blue-bg:    #dbeafe;
    --blue-t:     #1d4ed8;
}

/* ══ BASE ══ */
body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: var(--bg) !important; }

/* ══ KEYFRAMES ══ */
@keyframes fadeDown  { from { opacity:0; transform:translateY(-14px) } to { opacity:1; transform:translateY(0) } }
@keyframes slideUp   { from { opacity:0; transform:translateY(22px)  } to { opacity:1; transform:translateY(0) } }
@keyframes rowIn     { from { opacity:0; transform:translateX(-8px)  } to { opacity:1; transform:translateX(0) } }
@keyframes chipPop   { from { opacity:0; transform:scale(.88)        } to { opacity:1; transform:scale(1)     } }

/* ══ PAGE HEADER ══ */
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 26px 32px 0;
    flex-wrap: wrap;
    gap: 14px;
    animation: fadeDown .5s cubic-bezier(.16,1,.3,1) both;
}

.accent-bar {
    display: inline-block;
    width: 26px; height: 3px;
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
    letter-spacing: -.3px;
}

.page-desc { font-size: 12px; color: var(--text-s); margin-top: 3px; }

.page-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* ══ BUTTONS ══ */
.btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 8px 14px !important;
    border-radius: 10px !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
}

.btn::after {
    content: '';
    position: absolute; inset: 0;
    background: rgba(255,255,255,.2);
    transform: translateX(-100%) skewX(-15deg);
    transition: transform .35s;
}
.btn:hover::after { transform: translateX(110%) skewX(-15deg); }

.btn-primary, .btn.btn-primary {
    background: var(--gold) !important;
    color: var(--text) !important;
    border: none !important;
}
.btn-primary:hover, .btn.btn-primary:hover {
    background: var(--gold-h) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px #f5c84230;
}

.btn-outline, .btn.btn-outline {
    background: var(--white) !important;
    color: var(--text-m) !important;
    border: 1.5px solid var(--gold-b) !important;
}
.btn-outline:hover, .btn.btn-outline:hover {
    background: var(--gold-l) !important;
    border-color: var(--gold-m) !important;
    color: var(--gold-d) !important;
    transform: translateY(-1px);
}

.btn-ghost, .btn.btn-ghost {
    background: transparent !important;
    color: var(--text-m) !important;
    border: 1.5px solid var(--gold-b) !important;
}
.btn-ghost:hover { background: var(--gold-l) !important; }

.btn-sm { padding: 5px 11px !important; font-size: 11px !important; border-radius: 8px !important; }

.btn svg { width: 13px; height: 13px; flex-shrink: 0; }

/* ══ STATS GRID ══ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding: 18px 32px 0;
    animation: fadeDown .5s .08s cubic-bezier(.16,1,.3,1) both;
}

.stat-card {
    background: var(--white);
    border: 1px solid var(--gold-b);
    border-radius: 16px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: transform .15s, box-shadow .15s;
    animation: chipPop .45s cubic-bezier(.34,1.56,.64,1) both;
}

.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px #f5c84222; }
.stat-card:nth-child(1) { animation-delay: .10s }
.stat-card:nth-child(2) { animation-delay: .16s }
.stat-card:nth-child(3) { animation-delay: .22s }
.stat-card:nth-child(4) { animation-delay: .28s }

.stat-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.stat-card.blue  .stat-icon { background: #eff6ff; }
.stat-card.blue  .stat-icon svg { color: #1d4ed8; }
.stat-card.blue  .stat-value { color: #1d4ed8; }

.stat-card.amber .stat-icon { background: var(--gold-l); }
.stat-card.amber .stat-icon svg { color: var(--gold-m); }
.stat-card.amber .stat-value { color: var(--gold-d); }

.stat-card.green .stat-icon { background: #d1fae5; }
.stat-card.green .stat-icon svg { color: #059669; }
.stat-card.green .stat-value { color: #059669; }

.stat-card.red   .stat-icon { background: #fef2f2; }
.stat-card.red   .stat-icon svg { color: #dc2626; }
.stat-card.red   .stat-value { color: #dc2626; }

.stat-label { font-size: 11px; color: var(--text-s); font-weight: 500; margin-bottom: 3px; }
.stat-value { font-size: 17px; font-weight: 800; color: var(--text); letter-spacing: -.3px; }

/* ══ DATE RANGE ══ */
.date-range-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--white);
    border: 1px solid var(--gold-b);
    border-radius: 14px;
    padding: 12px 20px;
    margin: 16px 32px 0;
    flex-wrap: wrap;
    animation: slideUp .5s .12s cubic-bezier(.16,1,.3,1) both;
}

.date-range-wrap label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-s);
    white-space: nowrap;
}

.date-range-wrap input[type="date"] {
    height: 36px;
    padding: 0 11px;
    border: 1.5px solid var(--gold-b);
    border-radius: 9px;
    font-size: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--white);
    color: var(--text);
    outline: none;
    transition: border-color .15s;
    cursor: pointer;
}

.date-range-wrap input[type="date"]:focus { border-color: var(--gold-m); box-shadow: 0 0 0 3px #f5c84218; }
.date-range-wrap .sep { color: var(--text-s); font-weight: 600; }

/* ══ CARD ══ */
.card {
    background: var(--white) !important;
    border: 1px solid var(--gold-b) !important;
    border-radius: 20px !important;
    margin: 14px 32px 32px !important;
    overflow: hidden;
    animation: slideUp .55s .18s cubic-bezier(.16,1,.3,1) both;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px !important;
    border-bottom: 1px solid #fef3c7 !important;
    background: transparent !important;
    animation: fadeDown .4s .2s cubic-bezier(.16,1,.3,1) both;
}

.card-title { font-size: 14px !important; font-weight: 700 !important; color: var(--text) !important; }
.card-desc  { font-size: 11px; color: var(--text-s); margin-top: 2px; }

/* ══ SEARCH BAR / FILTER ROW ══ */
.search-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border-bottom: 1px solid #fef9e0;
    flex-wrap: wrap;
    animation: slideUp .45s .22s cubic-bezier(.16,1,.3,1) both;
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    width: 100%;
}

/* Animación escalonada de cada control del toolbar */
.filter-row > * { animation: filterIn .4s cubic-bezier(.16,1,.3,1) both; }
.filter-row > *:nth-child(1) { animation-delay: .26s }
.filter-row > *:nth-child(2) { animation-delay: .32s }
.filter-row > *:nth-child(3) { animation-delay: .38s }
.filter-row > *:nth-child(4) { animation-delay: .44s }
@keyframes filterIn { from { opacity:0; transform:translateY(8px) } to { opacity:1; transform:translateY(0) } }

.search-input-wrap {
    position: relative;
    width: 240px;
    flex-shrink: 0;
}

/* Ícono dentro del search — correctamente posicionado */
.search-input-wrap .search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #c8a832;
    pointer-events: none;
    display: flex;
    align-items: center;
    z-index: 1;
}

.search-input-wrap .search-icon svg { width: 13px; height: 13px; display: block; }

.form-input, .form-select {
    height: 36px !important;
    border: 1.5px solid var(--gold-b) !important;
    border-radius: 9px !important;
    font-size: 12px !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
    color: var(--text) !important;
    background: var(--white) !important;
    outline: none;
    transition: border-color .15s, box-shadow .15s, transform .12s;
}

.form-input:focus, .form-select:focus {
    border-color: var(--gold-m) !important;
    box-shadow: 0 0 0 3px #f5c84218 !important;
    transform: translateY(-1px);
}

.form-input::placeholder { color: #c4af70; }

/* El input de búsqueda tiene padding izq para dejar espacio al ícono */
.search-input-wrap .form-input {
    width: 100%;
    padding-left: 34px !important;
}

.form-select {
    padding: 0 10px !important;
    cursor: pointer;
    width: auto !important;
    min-width: 150px;
    appearance: auto;
}

/* ══ TABLE ══ */
table { width: 100%; border-collapse: collapse; }

thead tr { background: var(--gold-l) !important; }

th {
    padding: 9px 13px !important;
    text-align: left;
    font-size: 9.5px !important;
    font-weight: 700 !important;
    letter-spacing: .65px !important;
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

tbody tr:nth-child(1)  { animation-delay: .20s }
tbody tr:nth-child(2)  { animation-delay: .25s }
tbody tr:nth-child(3)  { animation-delay: .30s }
tbody tr:nth-child(4)  { animation-delay: .35s }
tbody tr:nth-child(5)  { animation-delay: .40s }
tbody tr:nth-child(6)  { animation-delay: .45s }
tbody tr:nth-child(7)  { animation-delay: .50s }
tbody tr:nth-child(8)  { animation-delay: .55s }
tbody tr:nth-child(9)  { animation-delay: .60s }
tbody tr:nth-child(10) { animation-delay: .65s }

tbody tr:last-child { border-bottom: none !important; }
tbody tr:hover { background: #fffdf5 !important; }

td { padding: 11px 13px !important; vertical-align: middle !important; }

/* ══ SERIE ══ */
.serie-num {
    font-family: 'Courier New', monospace;
    font-weight: 800;
    font-size: 12.5px;
    color: var(--text);
}
.serie-sub { font-size: 10px; color: var(--text-s); margin-top: 2px; }

/* ══ CLIENT CELL ══ */
.client-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    cursor: pointer;
    border-radius: 6px;
    padding: 4px 6px;
    transition: background .15s;
    margin: -4px -6px;
}
.client-cell:hover { background: var(--gold-l); }
.client-name { font-weight: 700; font-size: 12.5px; color: var(--text); }
.client-ruc  { font-family: 'Courier New', monospace; font-size: 10px; color: var(--text-s); }

/* ══ AMOUNTS ══ */
.amount-main { font-family: 'Courier New', monospace; font-weight: 800; font-size: 12.5px; color: var(--text); }
.amount-sub  { font-family: 'Courier New', monospace; font-size: 10px; color: var(--text-s); margin-top: 2px; }

/* ══ PERCENTAGE BAR ══ */
.perc-wrap { display: flex; align-items: center; gap: 6px; }
.perc-bar  { flex: 1; height: 4px; background: #f0e8c8; border-radius: 2px; min-width: 40px; overflow: hidden; }
.perc-fill { height: 100%; border-radius: 2px; background: var(--gold-m); transition: width .6s cubic-bezier(.16,1,.3,1); }
.perc-val  { font-size: 10px; font-weight: 700; color: var(--gold-d); white-space: nowrap; min-width: 30px; text-align: right; }

/* ══ RECAUDACION ══ */
.amount-recaud { font-family: 'Courier New', monospace; font-weight: 700; font-size: 12px; color: var(--gold-d); }

/* ══ TIPO RECAUD ══ */
.tipo-tag {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    background: #ede9fe;
    color: #5b21b6;
}

/* ══ ESTADO BADGES ══ */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 50px;
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
}
.badge::before {
    content: '';
    width: 5px; height: 5px;
    border-radius: 50%;
    background: currentColor;
    opacity: .7;
    flex-shrink: 0;
}

.badge-pendiente  { background: var(--yellow-bg); color: var(--yellow-t); }
.badge-por_vencer { background: #fef9c3; color: #713f12; }
.badge-vencida    { background: #fee2e2; color: #991b1b; }
.badge-pagada     { background: var(--green-bg); color: var(--green-t); }
.badge-anulada    { background: var(--gray-bg); color: var(--gray-t); }
.badge-observada  { background: var(--yellow-bg); color: var(--yellow-t); }
.badge-enviado    { background: var(--green-bg); color: var(--green-t); }
.badge-error      { background: #fee2e2; color: #991b1b; }

/* Badges sin dot para notificaciones pequeñas */
.badge-sm::before { display: none; }
.badge-sm { padding: 2px 6px; font-size: 9px; }

/* ══ NOTIFY CELL ══ */
.notify-cell { display: flex; flex-direction: column; gap: 4px; }
.notify-row  { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
.notify-meta { font-size: 10px; color: var(--text-s); }

.tag { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: 700; letter-spacing: .3px; flex-shrink: 0; }
.tag-wa   { background: #d1fae5; color: #059669; }
.tag-mail { background: var(--blue-bg); color: var(--blue-t); }

/* ══ COMPROBANTE THUMB ══ */
.img-preview-thumb {
    width: 32px; height: 32px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--gold-b);
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    display: block;
}
.img-preview-thumb:hover { transform: scale(1.12); box-shadow: 0 4px 12px rgba(0,0,0,.12); }

/* ══ ACTIONS ══ */
.actions-cell {
    display: flex;
    align-items: center;
    gap: 3px;
    flex-wrap: wrap;
}

.action-btn {
    width: 30px; height: 30px;
    border-radius: 7px;
    border: 1px solid var(--gold-b) !important;
    background: var(--white) !important;
    display: inline-flex; align-items: center; justify-content: center;
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

.btn-icon-text {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all .15s;
    font-family: 'Plus Jakarta Sans', sans-serif;
    white-space: nowrap;
}

.btn-wa   { background: #d1fae5; color: #059669; }
.btn-wa:hover { background: #a7f3d0; transform: scale(1.05); }

.btn-mail { background: var(--blue-bg); color: var(--blue-t); }
.btn-mail:hover { background: #bfdbfe; transform: scale(1.05); }

.btn-icon-text svg { width: 10px; height: 10px; }

/* ══ CREATOR ══ */
.creator-name { font-size: 11px; font-weight: 700; color: var(--text); }

/* ══ EMPTY STATE ══ */
.empty-state {
    text-align: center;
    padding: 56px 24px;
    color: var(--text-s);
}
.empty-state svg { color: var(--gold-b); margin: 0 auto 16px; display: block; }
.empty-state p:first-of-type { font-weight: 700; font-size: 15px; color: var(--text); }
.empty-state p:last-of-type { font-size: 13px; margin-top: 4px; }

/* ══ MODAL ══ */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(28,22,0,.38);
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
    width: 100%; max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(24px) scale(.97);
    transition: transform .3s cubic-bezier(.16,1,.3,1);
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
.modal-header h2 { font-size: 16px !important; font-weight: 800 !important; color: var(--text) !important; padding-right: 32px; }
.modal-header p  { font-size: 13px; color: var(--text-m); margin-top: 3px; }

.modal-close {
    position: absolute; right: 18px; top: 18px;
    background: none; border: none; cursor: pointer;
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-m); font-size: 18px; line-height: 1;
    transition: background .15s, color .15s;
}
.modal-close:hover { background: rgba(0,0,0,.08); color: var(--text); }

.modal-body { padding: 22px 28px; }

.form-grid-modal {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.form-full { grid-column: 1 / -1; }

.form-group { display: flex; flex-direction: column; gap: 6px; }

.form-label {
    font-size: 10.5px !important;
    font-weight: 700 !important;
    letter-spacing: .6px !important;
    text-transform: uppercase !important;
    color: var(--text-s) !important;
}

.modal .form-input,
.modal select.form-input,
.modal textarea.form-input {
    height: 42px !important;
    font-size: 13px !important;
    padding: 0 13px !important;
    width: 100%;
}

.modal textarea.form-input {
    height: auto !important;
    padding: 10px 13px !important;
    resize: vertical;
    min-height: 60px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 28px;
    border-top: 1px solid #fef9e0;
}

/* ══ DROP ZONE ══ */
.drop-zone {
    border: 2px dashed var(--gold-b);
    border-radius: 12px;
    padding: 36px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
}
.drop-zone:hover {
    border-color: var(--gold-m);
    background: var(--gold-l);
}
.drop-zone svg { color: var(--gold-b); margin: 0 auto 14px; display: block; transition: color .2s; }
.drop-zone:hover svg { color: var(--gold-m); }
.drop-zone-title { font-weight: 700; font-size: 13px; color: var(--text); margin-bottom: 5px; }
.drop-zone-sub   { font-size: 11px; color: var(--text-s); }

/* ══ PROGRESS BAR ══ */
.upload-progress { display: none; margin-top: 16px; }
.progress-track  { background: #f0e8c8; border-radius: 50px; height: 6px; overflow: hidden; }
.progress-fill   { background: var(--gold-m); height: 100%; width: 0%; transition: width .3s; border-radius: 50px; }
.progress-label  { font-size: 11px; color: var(--text-s); margin-top: 8px; text-align: center; }

/* ══ RESPONSIVE ══ */
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr; }
    .page-header, .date-range-wrap, .card { margin-left: 12px !important; margin-right: 12px !important; padding-left: 16px !important; padding-right: 16px !important; }
    .form-grid-modal { grid-template-columns: 1fr; }
    .form-full { grid-column: 1; }
}
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title"><span class="accent-bar"></span>Gestión de Facturas</h1>
        <p class="page-desc">Control de facturas y notificaciones a clientes.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('facturas.importar') }}" class="btn btn-outline">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Importar Excel
        </a>
        <button type="button" class="btn btn-outline" onclick="generarReportePDF()">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar PDF
        </button>
        <button type="button" class="btn" onclick="generarReporteDeuda()"
                style="background:var(--white);color:#dc2626;border:1.5px solid #fca5a5 !important;">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Reporte Deuda
        </button>
    </div>
</div>

{{-- STATS --}}
@php
    $total     = $facturas->sum('importe_total');
    $pendiente = $facturas->whereIn('estado',['PENDIENTE','POR_VENCER'])->sum('importe_total');
    $pagada    = $facturas->where('estado','PAGADA')->sum('importe_total');
    $vencida   = $facturas->where('estado','VENCIDA')->sum('importe_total');
@endphp

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
            <div class="stat-label">Total Facturado</div>
            <div class="stat-value">S/ {{ number_format($total, 2) }}</div>
        </div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="stat-label">Cuentas por Cobrar</div>
            <div class="stat-value">S/ {{ number_format($pendiente, 2) }}</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
            <div class="stat-label">Cobrado</div>
            <div class="stat-value">S/ {{ number_format($pagada, 2) }}</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <div class="stat-label">Deuda Vencida</div>
            <div class="stat-value">S/ {{ number_format($vencida, 2) }}</div>
        </div>
    </div>
</div>

{{-- FILTRO DE RANGO DE FECHAS --}}
<form method="GET" action="{{ route('facturas.index') }}" id="frmFiltros">
    <div class="date-range-wrap">
        <label>Período:</label>
        <input type="date" name="fecha_desde" id="inputDesde" value="{{ $fechaDesde }}"
               onchange="document.getElementById('frmFiltros').submit()">
        <span class="sep">→</span>
        <input type="date" name="fecha_hasta" id="inputHasta" value="{{ $fechaHasta }}"
               onchange="document.getElementById('frmFiltros').submit()">

        <span style="font-size:11px;color:var(--text-s);margin-left:4px;">
            Mostrando del <strong style="color:var(--text);">{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}</strong>
            al <strong style="color:var(--text);">{{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</strong>
            &nbsp;·&nbsp; <strong style="color:var(--text);">{{ $facturas->count() }}</strong> facturas
        </span>

        <div style="display:flex;gap:6px;margin-left:auto;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('mes')">Este mes</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('trimestre')">Trimestre</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="setRango('anio')">Este año</button>
        </div>
    </div>
</form>

{{-- TABLA --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Listado de Facturas</div>
            <div class="card-desc">{{ $facturas->count() }} facturas en el período seleccionado</div>
        </div>
    </div>

    <div class="search-bar">
        <div class="filter-row">

            {{-- Búsqueda con ícono correcto --}}
            <div class="search-input-wrap">
                <span class="search-icon">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                </span>
                <input type="text" class="form-input" id="searchInput"
                       placeholder="Buscar factura, cliente..." onkeyup="filtrarTabla()">
            </div>

            {{-- Estado --}}
            <select class="form-select" id="filterEstado" onchange="filtrarTabla()">
                <option value="">Todos los estados</option>
                <option value="PENDIENTE">Pendiente</option>
                <option value="POR_VENCER">Por Vencer</option>
                <option value="VENCIDA">Vencida</option>
                <option value="PAGADA">Pagada</option>
                <option value="ANULADA">Anulada</option>
            </select>

            {{-- Moneda --}}
            <select class="form-select" id="filterMoneda" onchange="filtrarTabla()">
                <option value="">Todas las monedas</option>
                <option value="PEN">Soles (PEN)</option>
                <option value="USD">Dólares (USD)</option>
            </select>

            {{-- Empresa --}}
            <select class="form-select" id="filterEmpresa" onchange="filtrarTabla()" style="min-width:200px;">
                <option value="">Todas las empresas</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id_cliente }}">{{ $c->razon_social }}</option>
                @endforeach
            </select>

        </div>
    </div>

    <div style="overflow-x:auto;">
        <table id="facturasTable">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Emisión / Vcto.</th>
                    <th>Montos</th>
                    <th>% Recaud.</th>
                    <th>Recaudación</th>
                    <th>Tipo</th>
                    <th>F. Abono</th>
                    <th>Comprobante</th>
                    <th>Estado</th>
                    <th>Notificaciones</th>
                    <th>Creado por</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="facturasBody">
            @forelse($facturas as $factura)
                @php
                    $ultimaNotifWa     = $factura->ultima_notif_wa     ?? null;
                    $ultimaNotifCorreo = $factura->ultima_notif_correo ?? null;
                    $badgeMap = [
                        'PENDIENTE'  => 'badge-pendiente',
                        'POR_VENCER' => 'badge-por_vencer',
                        'VENCIDA'    => 'badge-vencida',
                        'PAGADA'     => 'badge-pagada',
                        'ANULADA'    => 'badge-anulada',
                        'OBSERVADA'  => 'badge-observada',
                    ];
                    $badgeClass          = $badgeMap[$factura->estado] ?? 'badge-pendiente';
                    $puedeNotificarDeuda = in_array($factura->estado, ['PENDIENTE','POR_VENCER','VENCIDA']);
                    $montoRecaudacion    = $factura->monto_recaudacion ?? 0;
                    $porcentaje          = $factura->porcentaje_recaudacion ?? 0;
                    $tipoRecaudacion     = $factura->tipo_recaudacion_actual;
                    $tieneComprobante    = !empty($factura->ruta_comprobante_pago);
                    $percColor           = $porcentaje >= 100 ? '#059669' : ($porcentaje >= 50 ? 'var(--gold-m)' : '#dc2626');
                @endphp
                <tr
                    data-cliente="{{ $factura->id_cliente }}"
                    data-estado="{{ $factura->estado }}"
                    data-moneda="{{ $factura->moneda }}"
                    data-search="{{ strtolower($factura->serie.'-'.$factura->numero.' '.($factura->razon_social ?? '')) }}">

                    {{-- FACTURA --}}
                    <td>
                        <div class="serie-num">{{ $factura->serie }}-{{ str_pad($factura->numero, 8, '0', STR_PAD_LEFT) }}</div>
                        <div class="serie-sub">{{ $factura->moneda }}</div>
                    </td>

                    {{-- CLIENTE --}}
                    <td>
                        <div class="client-cell"
                             onclick="abrirModalEditarCliente('{{ $factura->id_factura }}')"
                             title="Clic para editar cliente">
                            <div class="client-name">{{ $factura->razon_social ?? 'Sin cliente' }}</div>
                            <div class="client-ruc">{{ $factura->ruc ?? '—' }}</div>
                        </div>
                    </td>

                    {{-- FECHAS --}}
                    <td>
                        <div style="font-size:12px;color:var(--text);">{{ $factura->fecha_emision }}</div>
                        <div style="font-size:10px;color:var(--text-s);margin-top:3px;">
                            Vcto: <strong style="{{ $factura->estado === 'VENCIDA' ? 'color:#dc2626;' : '' }}">
                                {{ $factura->fecha_vencimiento ?? '—' }}
                            </strong>
                            @if($factura->estado === 'VENCIDA') <span style="color:#dc2626;">⚠</span> @endif
                        </div>
                    </td>

                    {{-- MONTOS --}}
                    <td>
                        <div class="amount-main">{{ $factura->moneda }} {{ number_format($factura->importe_total, 2) }}</div>
                        <div class="amount-sub">IGV: {{ number_format($factura->monto_igv ?? 0, 2) }}</div>
                    </td>

                    {{-- % RECAUDACION --}}
                    <td>
                        @if($porcentaje > 0)
                            <div class="perc-wrap">
                                <div class="perc-bar">
                                    <div class="perc-fill" style="width:{{ min($porcentaje,100) }}%;background:{{ $percColor }};"></div>
                                </div>
                                <span class="perc-val" style="color:{{ $percColor }};">{{ $porcentaje }}%</span>
                            </div>
                        @else
                            <span style="font-size:11px;color:var(--text-s);">—</span>
                        @endif
                    </td>

                    {{-- RECAUDACION --}}
                    <td>
                        @if($montoRecaudacion > 0)
                            <span class="amount-recaud" style="color:{{ $percColor }};">
                                {{ $factura->moneda }} {{ number_format($montoRecaudacion, 2) }}
                            </span>
                        @else
                            <span style="font-size:11px;color:var(--text-s);">—</span>
                        @endif
                    </td>

                    {{-- TIPO --}}
                    <td style="text-align:center;">
                        @if($tipoRecaudacion)
                            <span class="tipo-tag">{{ $tipoRecaudacion }}</span>
                        @else
                            <span style="font-size:11px;color:var(--text-s);">—</span>
                        @endif
                    </td>

                    {{-- FECHA ABONO --}}
                    <td style="text-align:center;font-family:'Courier New',monospace;font-size:11px;color:var(--text-m);">
                        {{ $factura->fecha_abono ? \Carbon\Carbon::parse($factura->fecha_abono)->format('d/m/Y') : '—' }}
                    </td>

                    {{-- COMPROBANTE --}}
                    <td style="text-align:center;">
                        @if($tieneComprobante)
                            <a href="{{ $factura->ruta_comprobante_pago }}" target="_blank">
                                <img src="{{ $factura->ruta_comprobante_pago }}" class="img-preview-thumb" alt="Comprobante">
                            </a>
                        @else
                            <span style="font-size:10px;color:var(--text-s);">Sin imagen</span>
                        @endif
                    </td>

                    {{-- ESTADO --}}
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$factura->estado) }}</span>
                    </td>

                    {{-- NOTIFICACIONES --}}
                    <td>
                        <div class="notify-cell">
                            <div class="notify-row">
                                <span class="tag tag-wa">WA</span>
                                @if($ultimaNotifWa)
                                    <span class="badge badge-sm {{ $ultimaNotifWa->estado_envio === 'ENVIADO' ? 'badge-enviado' : 'badge-error' }}">
                                        {{ $ultimaNotifWa->estado_envio }}
                                    </span>
                                    <span class="notify-meta">{{ \Carbon\Carbon::parse($ultimaNotifWa->fecha_creacion)->format('d/m H:i') }}</span>
                                @else
                                    <span class="notify-meta">Sin envíos</span>
                                @endif
                            </div>
                            <div class="notify-row">
                                <span class="tag tag-mail">✉</span>
                                @if($ultimaNotifCorreo)
                                    <span class="badge badge-sm {{ $ultimaNotifCorreo->estado_envio === 'ENVIADO' ? 'badge-enviado' : 'badge-error' }}">
                                        {{ $ultimaNotifCorreo->estado_envio }}
                                    </span>
                                    <span class="notify-meta">{{ \Carbon\Carbon::parse($ultimaNotifCorreo->fecha_creacion)->format('d/m H:i') }}</span>
                                @else
                                    <span class="notify-meta">Sin envíos</span>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- CREADO POR --}}
                    <td>
                        @if($factura->usuario_nombre)
                            <div class="creator-name">{{ $factura->usuario_nombre }} {{ $factura->usuario_apellido }}</div>
                        @else
                            <span style="font-size:11px;color:var(--text-s);">—</span>
                        @endif
                    </td>

                    {{-- ACCIONES --}}
                    <td>
                        <div class="actions-cell" style="justify-content:flex-end;">

                            {{-- Editar factura --}}
                            <button type="button"
                                    class="action-btn"
                                    onclick="abrirModalEditar('{{ $factura->id_factura }}')"
                                    title="Editar factura"
                                    style="color:#7c3aed;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>

                            {{-- Subir comprobante --}}
                            <button type="button"
                                    class="action-btn"
                                    onclick="abrirModalComprobante('{{ $factura->id_factura }}')"
                                    title="Subir comprobante"
                                    style="color:{{ $tieneComprobante ? '#059669' : '#d97706' }};">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>

                            {{-- Notificaciones de deuda --}}
                            @if($puedeNotificarDeuda)
                                <form method="POST" action="{{ route('facturas.enviar-whatsapp-manual', $factura->id_factura) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon-text btn-wa" title="Enviar WA deuda">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        WA
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('facturas.enviar-correo-manual', $factura->id_factura) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon-text btn-mail" title="Enviar correo deuda">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        ✉
                                    </button>
                                </form>
                            @endif

                            {{-- Confirmación de pagada --}}
                            @if($factura->estado === 'PAGADA')
                                <form method="POST" action="{{ route('facturas.enviar-factura-pagada-whatsapp', $factura->id_factura) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon-text btn-wa"
                                            style="background:{{ $tieneComprobante ? '#a7f3d0' : '#d1fae5' }};"
                                            title="Confirmar pago WA">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        {{ $tieneComprobante ? '📎' : '' }} OK
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('facturas.enviar-factura-pagada-correo', $factura->id_factura) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-icon-text btn-mail" style="background:#bfdbfe;" title="Confirmar pago correo">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        OK
                                    </button>
                                </form>
                            @endif

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">
                        <div class="empty-state">
                            <svg width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>Sin facturas en el período seleccionado</p>
                            <p>Cambia el rango de fechas o importa facturas desde Excel.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════  MODAL EDITAR FACTURA  ══════════════ --}}
<div class="modal-overlay" id="modalEditarOverlay">
    <div class="modal">
        <div class="modal-header">
            <h2>Editar Factura</h2>
            <p>Actualiza los datos de la factura seleccionada.</p>
            <button class="modal-close" onclick="cerrarModalEditar()">×</button>
        </div>
        <form id="formEditarFactura" onsubmit="guardarFactura(event)">
            @csrf
            <div class="modal-body">
                <div class="form-grid-modal">
                    <div class="form-group">
                        <label class="form-label">Fecha Emisión</label>
                        <input type="date" name="fecha_emision" id="editFechaEmision" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" id="editFechaVencimiento" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha Abono</label>
                        <input type="date" name="fecha_abono" id="editFechaAbono" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select name="estado" id="editEstado" class="form-input">
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="POR_VENCER">Por Vencer</option>
                            <option value="VENCIDA">Vencida</option>
                            <option value="PAGADA">Pagada</option>
                            <option value="ANULADA">Anulada</option>
                            <option value="OBSERVADA">Observada</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Glosa</label>
                        <textarea name="glosa" id="editGlosa" class="form-input"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pago</label>
                        <input type="text" name="forma_pago" id="editFormaPago" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Importe Total</label>
                        <input type="number" name="importe_total" id="editImporteTotal" step="0.01" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IGV</label>
                        <input type="number" name="monto_igv" id="editMontoIgv" step="0.01" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subtotal Gravado</label>
                        <input type="number" name="subtotal_gravado" id="editSubtotalGravado" step="0.01" class="form-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalEditar()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════  MODAL COMPROBANTE  ══════════════ --}}
<div class="modal-overlay" id="modalComprobanteOverlay">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h2>Imagen / Comprobante de Pago</h2>
            <p>Sube la foto de la factura o comprobante de pago.</p>
            <button class="modal-close" onclick="cerrarModalComprobante()">×</button>
        </div>
        <form id="formComprobante" onsubmit="enviarComprobante(event)" enctype="multipart/form-data">
            @csrf
            <div class="modal-body" style="text-align:center;">
                <div class="drop-zone" id="dropZone">
                    <svg width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div class="drop-zone-title">Arrastra o haz clic para seleccionar</div>
                    <div class="drop-zone-sub">JPG, PNG, GIF o PDF — máximo 5 MB</div>
                    <input type="file" name="comprobante" id="fileComprobante"
                           accept="image/*,application/pdf" style="display:none;"
                           onchange="mostrarPreview(event)">
                </div>

                <div id="preview" style="display:none;margin-top:16px;">
                    <img id="previewImg" src="" style="max-width:100%;max-height:260px;border-radius:10px;border:1px solid var(--gold-b);">
                    <p id="previewPdf" style="display:none;padding:12px;background:var(--gold-l);border-radius:8px;font-size:13px;color:var(--text-m);border:1px solid var(--gold-b);">
                        📄 Archivo PDF seleccionado
                    </p>
                    <button type="button" onclick="limpiarPreview()"
                            style="display:block;margin:10px auto 0;padding:7px 16px;border:none;background:#fee2e2;color:#dc2626;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;">
                        Cambiar archivo
                    </button>
                </div>

                <div class="upload-progress" id="uploadProgress">
                    <div class="progress-track">
                        <div class="progress-fill" id="progressBar"></div>
                    </div>
                    <div class="progress-label">Subiendo a Cloudinary…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalComprobante()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnEnviarComprobante">Subir imagen</button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════  MODAL EDITAR CLIENTE  ══════════════ --}}
<div class="modal-overlay" id="modalEditarClienteOverlay">
    <div class="modal" style="max-width:500px;">
        <div class="modal-header">
            <h2>Editar Cliente</h2>
            <p>Actualiza los datos del cliente asociado a la factura.</p>
            <button class="modal-close" onclick="cerrarModalEditarCliente()">×</button>
        </div>
        <form id="formEditarCliente" onsubmit="guardarCliente(event)">
            @csrf
            <div class="modal-body">
                <div class="form-grid-modal">
                    <div class="form-group form-full">
                        <label class="form-label">Razón Social *</label>
                        <input type="text" name="razon_social" id="editRazonSocial" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">RUC *</label>
                        <input type="text" name="ruc" id="editRuc" class="form-input" maxlength="11" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Celular / WhatsApp</label>
                        <input type="text" name="celular" id="editCelular" class="form-input" maxlength="15">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" id="editCorreo" class="form-input">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Dirección Fiscal</label>
                        <input type="text" name="direccion_fiscal" id="editDireccionFiscal" class="form-input">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalEditarCliente()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let facturaActualId = null;

/* ══ RANGOS DE FECHA ══ */
function setRango(tipo) {
    const hoy  = new Date();
    const fmt  = d => d.toISOString().split('T')[0];
    let desde, hasta = fmt(hoy);
    if (tipo === 'mes')       { desde = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1)); }
    else if (tipo === 'trimestre') { const m = Math.floor(hoy.getMonth()/3)*3; desde = fmt(new Date(hoy.getFullYear(), m, 1)); }
    else if (tipo === 'anio') { desde = fmt(new Date(hoy.getFullYear(), 0, 1)); }
    document.getElementById('inputDesde').value = desde;
    document.getElementById('inputHasta').value = hasta;
    document.getElementById('frmFiltros').submit();
}

/* ══ FILTRO ══ */
function filtrarTabla() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const estado  = document.getElementById('filterEstado').value;
    const moneda  = document.getElementById('filterMoneda').value;
    const empresa = document.getElementById('filterEmpresa').value;
    document.querySelectorAll('#facturasBody tr[data-estado]').forEach(row => {
        const ok = (!search  || row.dataset.search.includes(search))
                && (!estado  || row.dataset.estado   === estado)
                && (!moneda  || row.dataset.moneda   === moneda)
                && (!empresa || row.dataset.cliente  === empresa);
        row.style.display = ok ? '' : 'none';
    });
}

/* ══ MODAL EDITAR FACTURA ══ */
function abrirModalEditar(id) {
    facturaActualId = id;
    document.getElementById('modalEditarOverlay').classList.add('open');
    fetch(`/facturas/${id}/edit`)
        .then(r => r.json())
        .then(f => {
            document.getElementById('editFechaEmision').value    = f.fecha_emision    || '';
            document.getElementById('editFechaVencimiento').value= f.fecha_vencimiento|| '';
            document.getElementById('editFechaAbono').value      = f.fecha_abono      || '';
            document.getElementById('editEstado').value          = f.estado           || '';
            document.getElementById('editGlosa').value           = f.glosa            || '';
            document.getElementById('editFormaPago').value       = f.forma_pago       || '';
            document.getElementById('editImporteTotal').value    = f.importe_total    || '';
            document.getElementById('editMontoIgv').value        = f.monto_igv        || '';
            document.getElementById('editSubtotalGravado').value = f.subtotal_gravado || '';
        });
}

function cerrarModalEditar() { document.getElementById('modalEditarOverlay').classList.remove('open'); }

function guardarFactura(event) {
    event.preventDefault();
    const datos = {
        fecha_emision:     document.getElementById('editFechaEmision').value,
        fecha_vencimiento: document.getElementById('editFechaVencimiento').value,
        fecha_abono:       document.getElementById('editFechaAbono').value,
        estado:            document.getElementById('editEstado').value,
        glosa:             document.getElementById('editGlosa').value,
        forma_pago:        document.getElementById('editFormaPago').value,
        importe_total:     document.getElementById('editImporteTotal').value,
        monto_igv:         document.getElementById('editMontoIgv').value,
        subtotal_gravado:  document.getElementById('editSubtotalGravado').value,
    };
    fetch(`/facturas/${facturaActualId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(datos),
    })
    .then(r => { if (!r.ok) throw new Error(`Error ${r.status}`); return r.json(); })
    .then(data => {
        if (data.success) { cerrarModalEditar(); location.reload(); }
        else alert('Error: ' + (data.message || 'No se pudo guardar'));
    })
    .catch(err => alert('Error al guardar: ' + err.message));
}

/* ══ MODAL COMPROBANTE ══ */
function abrirModalComprobante(id) {
    facturaActualId = id;
    limpiarPreview();
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('modalComprobanteOverlay').classList.add('open');
}

function cerrarModalComprobante() { document.getElementById('modalComprobanteOverlay').classList.remove('open'); }

document.getElementById('dropZone').addEventListener('click', () => document.getElementById('fileComprobante').click());
document.getElementById('dropZone').addEventListener('dragover', e => e.preventDefault());
document.getElementById('dropZone').addEventListener('drop', e => {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
        document.getElementById('fileComprobante').files = e.dataTransfer.files;
        mostrarPreview({ target: { files: e.dataTransfer.files } });
    }
});

function mostrarPreview(event) {
    const file = event.target.files[0];
    if (!file) return;
    document.getElementById('preview').style.display  = 'block';
    document.getElementById('dropZone').style.display = 'none';
    if (file.type === 'application/pdf') {
        document.getElementById('previewImg').style.display = 'none';
        document.getElementById('previewPdf').style.display = 'block';
    } else {
        const r = new FileReader();
        r.onload = e => {
            document.getElementById('previewImg').src             = e.target.result;
            document.getElementById('previewImg').style.display   = 'block';
            document.getElementById('previewPdf').style.display   = 'none';
        };
        r.readAsDataURL(file);
    }
}

function limpiarPreview() {
    document.getElementById('fileComprobante').value            = '';
    document.getElementById('preview').style.display            = 'none';
    document.getElementById('dropZone').style.display           = 'block';
    document.getElementById('previewImg').src                   = '';
    document.getElementById('btnEnviarComprobante').disabled    = false;
    document.getElementById('btnEnviarComprobante').textContent = 'Subir imagen';
}

function enviarComprobante(event) {
    event.preventDefault();
    const file = document.getElementById('fileComprobante').files[0];
    if (!file) { alert('Por favor selecciona un archivo'); return; }

    const btn = document.getElementById('btnEnviarComprobante');
    btn.disabled    = true;
    btn.textContent = 'Subiendo…';
    document.getElementById('uploadProgress').style.display = 'block';

    let p = 0;
    const iv = setInterval(() => { p = Math.min(p + 10, 85); document.getElementById('progressBar').style.width = p + '%'; }, 200);

    fetch(`/facturas/${facturaActualId}/upload-comprobante`, {
        method: 'POST',
        body: new FormData(document.getElementById('formComprobante')),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => r.json())
    .then(data => {
        clearInterval(iv);
        document.getElementById('progressBar').style.width = '100%';
        setTimeout(() => {
            if (data.success) { cerrarModalComprobante(); location.reload(); }
            else {
                alert(data.error || 'Error al subir');
                btn.disabled    = false;
                btn.textContent = 'Subir imagen';
                document.getElementById('uploadProgress').style.display = 'none';
            }
        }, 400);
    })
    .catch(err => {
        clearInterval(iv);
        alert('Error: ' + err.message);
        btn.disabled    = false;
        btn.textContent = 'Subir imagen';
        document.getElementById('uploadProgress').style.display = 'none';
    });
}

/* ══ MODAL EDITAR CLIENTE ══ */
function abrirModalEditarCliente(id) {
    facturaActualId = id;
    document.getElementById('modalEditarClienteOverlay').classList.add('open');
    fetch(`/facturas/${id}/cliente`)
        .then(r => r.json())
        .then(c => {
            document.getElementById('editRazonSocial').value      = c.razon_social      || '';
            document.getElementById('editRuc').value              = c.ruc               || '';
            document.getElementById('editCelular').value          = c.celular           || '';
            document.getElementById('editCorreo').value           = c.correo            || '';
            document.getElementById('editDireccionFiscal').value  = c.direccion_fiscal  || '';
        })
        .catch(err => alert('Error al cargar cliente: ' + err.message));
}

function cerrarModalEditarCliente() { document.getElementById('modalEditarClienteOverlay').classList.remove('open'); }

function guardarCliente(event) {
    event.preventDefault();
    const datos = {
        razon_social:     document.getElementById('editRazonSocial').value,
        ruc:              document.getElementById('editRuc').value,
        celular:          document.getElementById('editCelular').value,
        correo:           document.getElementById('editCorreo').value,
        direccion_fiscal: document.getElementById('editDireccionFiscal').value,
    };
    fetch(`/facturas/${facturaActualId}/cliente`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(datos),
    })
    .then(r => { if (!r.ok) throw new Error(`Error ${r.status}`); return r.json(); })
    .then(data => {
        if (data.success) { cerrarModalEditarCliente(); location.reload(); }
        else alert('Error: ' + (data.message || 'No se pudo guardar'));
    })
    .catch(err => alert('Error al guardar: ' + err.message));
}

/* ══ REPORTES ══ */
function generarReportePDF() {
    const params = new URLSearchParams();
    const empresa = document.getElementById('filterEmpresa').value;
    const estado  = document.getElementById('filterEstado').value;
    const desde   = document.getElementById('inputDesde').value;
    const hasta   = document.getElementById('inputHasta').value;
    if (empresa) params.append('id_cliente',  empresa);
    if (estado)  params.append('estado',       estado);
    if (desde)   params.append('fecha_desde',  desde);
    if (hasta)   params.append('fecha_hasta',  hasta);
    window.open('{{ route("reportes.pdf") }}?' + params.toString(), '_blank');
}

function generarReporteDeuda() {
    const params = new URLSearchParams();
    const desde  = document.getElementById('inputDesde').value;
    const hasta  = document.getElementById('inputHasta').value;
    if (desde) params.append('fecha_desde', desde);
    if (hasta)  params.append('fecha_hasta', hasta);
    window.open('{{ route("reportes.deuda-general") }}?' + params.toString(), '_blank');
}

/* ══ CERRAR MODALES AL CLICK FUERA ══ */
['modalEditarOverlay','modalComprobanteOverlay','modalEditarClienteOverlay'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', e => {
        if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
    });
});
</script>
@endpush

@endsection