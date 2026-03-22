<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Facturación') — Consorcio Rodriguez Caballero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sw:        260px;
            --gold:      #f5c842;
            --gold-h:    #e8b820;
            --gold-l:    #fffbeb;
            --gold-b:    #ead96a;
            --gold-m:    #d4a017;
            --gold-d:    #9a6e10;
            --gold-xd:   #633806;
            --bg:        #fdf8ec;
            --white:     #fff;
            --text:      #1c1600;
            --text-m:    #7a6838;
            --text-s:    #9a8840;
            --border:    #ead96a;
            --border-l:  #fef3c7;
            --green:     #059669;
            --green-l:   #d1fae5;
            --red:       #dc2626;
            --red-l:     #fee2e2;
            --amber:     #d97706;
            --amber-l:   #fef3c7;
            --accent:    #1d4ed8;
            --accent-l:  #dbeafe;
            --radius:    14px;
            --radius-sm: 9px;
        }

        @keyframes fadeDown { from{opacity:0;transform:translateY(-12px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideIn  { from{opacity:0;transform:translateX(-16px)} to{opacity:1;transform:translateX(0)} }
        @keyframes slideUp  { from{opacity:0;transform:translateY(16px)}  to{opacity:1;transform:translateY(0)} }
        @keyframes navIn    { from{opacity:0;transform:translateX(-8px)}  to{opacity:1;transform:translateX(0)} }
        @keyframes pulse    { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0;transform:scale(1.16)} }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ══════════════════════════
           SIDEBAR — blanco puro
        ══════════════════════════ */
        .sidebar {
            width: var(--sw);
            background: var(--white);
            border-right: 1.5px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            animation: slideIn .5s cubic-bezier(.16,1,.3,1) both;
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .sidebar-brand {
            padding: 20px 18px 16px;
            border-bottom: 1.5px solid var(--border-l);
            display: flex; align-items: center; gap: 11px;
            position: relative;
        }

        .sidebar-logo {
            width: 40px; height: 40px;
            background: var(--gold); border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; position: relative;
        }
        .sidebar-logo::after {
            content: '';
            position: absolute; inset: -4px; border-radius: 16px;
            border: 2px solid #f5c84235;
            animation: pulse 2.8s 1s ease-in-out infinite;
        }
        .sidebar-logo svg { color: var(--text); width: 20px; height: 20px; }

        .sidebar-brand-text strong {
            display: block; font-size: 13.5px; font-weight: 800;
            color: var(--text); letter-spacing: -.2px;
        }
        .sidebar-brand-text span {
            font-size: 9.5px; color: var(--text-s);
            text-transform: uppercase; letter-spacing: .5px;
        }

        .sidebar-close {
            position: absolute; right: 14px; top: 18px;
            width: 28px; height: 28px; border: none;
            background: transparent; color: var(--text-s);
            cursor: pointer; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            transition: all .18s;
        }
        .sidebar-close:hover { background: var(--gold-l); color: var(--gold-d); }
        .sidebar-close svg { width: 16px; height: 16px; }

        .sidebar-nav { flex: 1; padding: 14px 10px; overflow-y: auto; }

        .nav-label {
            font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.1px;
            color: var(--text-s); padding: 0 9px; margin: 16px 0 5px;
        }
        .nav-label:first-child { margin-top: 0; }

        .nav-item {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 12px; border-radius: 10px;
            color: var(--text-m); text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: all .18s; margin-bottom: 2px;
            position: relative;
            animation: navIn .4s cubic-bezier(.16,1,.3,1) both;
        }
        .nav-item:nth-child(1){animation-delay:.08s} .nav-item:nth-child(2){animation-delay:.12s}
        .nav-item:nth-child(3){animation-delay:.16s} .nav-item:nth-child(4){animation-delay:.20s}
        .nav-item:nth-child(5){animation-delay:.24s} .nav-item:nth-child(6){animation-delay:.28s}
        .nav-item:nth-child(7){animation-delay:.32s} .nav-item:nth-child(8){animation-delay:.36s}

        .nav-item svg { width: 15px; height: 15px; flex-shrink: 0; color: var(--text-s); }
        .nav-item:hover { background: var(--gold-l); color: var(--gold-d); }
        .nav-item:hover svg { color: var(--gold-m); }

        .nav-item.active { background: var(--gold); color: var(--text); font-weight: 700; }
        .nav-item.active svg { color: var(--text); }
        .nav-item.active::before {
            content: ''; position: absolute; left: -10px; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 55%;
            background: var(--gold-m); border-radius: 0 2px 2px 0;
        }

        .sidebar-footer { padding: 12px 10px; border-top: 1.5px solid var(--border-l); }

        .sidebar-user {
            display: flex; align-items: center; gap: 9px;
            background: var(--gold-l); border: 1px solid var(--border);
            border-radius: 10px; padding: 9px 12px;
        }
        .sidebar-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--gold); color: var(--text);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800; flex-shrink: 0;
        }
        .sidebar-user-name  { font-size: 12px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-login { font-size: 9.5px; color: var(--text-s); }
        .sidebar-logout {
            margin-left: auto; background: none; border: none;
            cursor: pointer; color: var(--text-s); padding: 5px;
            border-radius: 6px; display: flex; align-items: center;
            transition: color .15s; flex-shrink: 0;
        }
        .sidebar-logout:hover { color: var(--red); }
        .sidebar-logout svg { width: 14px; height: 14px; }

        /* ══ BOTÓN FLOTANTE ══ */
        .sidebar-toggle-floating {
            display: none;
            position: fixed; top: 18px; left: 18px;
            width: 44px; height: 44px; border-radius: 12px;
            background: var(--white); border: 1.5px solid var(--border);
            color: var(--gold-m); cursor: pointer; z-index: 99;
            align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(212,160,23,.18);
            transition: all .3s cubic-bezier(.4,0,.2,1);
        }
        .sidebar-toggle-floating:hover { background: var(--gold-l); transform: scale(1.06); }
        .sidebar-toggle-floating svg { width: 20px; height: 20px; }
        .main-wrapper.sidebar-collapsed .sidebar-toggle-floating { display: flex; }

        /* ══ MAIN WRAPPER ══ */
        .main-wrapper {
            margin-left: var(--sw); flex: 1;
            display: flex; flex-direction: column; min-height: 100vh;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }
        .main-wrapper.sidebar-collapsed { margin-left: 0; }

        /* ══ TOPBAR ══ */
        .topbar {
            background: var(--white); border-bottom: 1.5px solid var(--border);
            padding: 0 28px; height: 62px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            animation: fadeDown .45s cubic-bezier(.16,1,.3,1) both;
        }
        .topbar-left { display: flex; align-items: center; gap: 12px; }

        .sidebar-toggle {
            display: none; width: 36px; height: 36px;
            border-radius: 9px; border: 1.5px solid var(--border);
            background: var(--white); color: var(--text-m);
            cursor: pointer; align-items: center; justify-content: center;
            transition: all .18s;
        }
        .sidebar-toggle:hover { background: var(--gold-l); border-color: var(--gold-m); color: var(--gold-d); }
        .sidebar-toggle svg { width: 16px; height: 16px; }
        .main-wrapper.sidebar-collapsed .sidebar-toggle { display: flex; }

        .breadcrumb { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--text-s); }
        .breadcrumb svg { width: 12px; height: 12px; }
        .breadcrumb-current { color: var(--text); font-weight: 700; }

        .topbar-right {
            display: flex; align-items: center; gap: 10px;
            animation: slideUp .4s .1s cubic-bezier(.16,1,.3,1) both;
        }
        .topbar-user {
            display: flex; align-items: center; gap: 8px;
            background: var(--gold-l); border: 1px solid var(--border);
            border-radius: 50px; padding: 5px 14px 5px 6px;
        }
        .user-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--gold); color: var(--text);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 800;
        }
        .user-info strong { font-size: 12.5px; font-weight: 700; display: block; color: var(--text); }
        .user-info span   { font-size: 10px; color: var(--text-s); }

        .btn-logout {
            display: inline-flex; align-items: center; gap: 6px;
            height: 34px; padding: 0 13px;
            border: 1.5px solid var(--border); border-radius: 9px;
            background: var(--white); color: var(--text-m);
            font-size: 12px; font-weight: 600;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; text-decoration: none; transition: all .15s;
        }
        .btn-logout:hover { background: var(--gold-l); border-color: var(--gold-m); color: var(--gold-d); }
        .btn-logout svg { width: 13px; height: 13px; }

        /* ══ CONTENT ══ */
        .main-content { padding: 28px 32px; flex: 1; }

        /* ══ ALERTS ══ */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 11px;
            font-size: 13px; font-weight: 600; margin-bottom: 20px;
            animation: slideUp .4s cubic-bezier(.16,1,.3,1) both;
        }
        .alert svg { width: 15px; height: 15px; flex-shrink: 0; }
        .alert-success { background: var(--green-l); color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error   { background: var(--red-l);   color: #7f1d1d; border: 1px solid #fca5a5; }

        /* ══ CARDS ══ */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-l);
            display: flex; align-items: center;
            justify-content: space-between; gap: 14px;
            background: var(--gold-l); position: relative;
        }
        .card-header::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold);
        }
        .card-title { font-size: 14.5px; font-weight: 700; color: var(--text); }
        .card-desc  { font-size: 11.5px; color: var(--text-s); margin-top: 2px; }

        /* ══ STATS GRID ══ */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4,1fr);
            gap: 14px; margin-bottom: 22px;
        }
        .stat-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; padding: 16px 18px;
            display: flex; align-items: center; gap: 14px;
            transition: transform .15s, box-shadow .15s;
            animation: slideUp .45s cubic-bezier(.34,1.56,.64,1) both;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px #f5c84222; }
        .stat-card:nth-child(1){animation-delay:.10s} .stat-card:nth-child(2){animation-delay:.16s}
        .stat-card:nth-child(3){animation-delay:.22s} .stat-card:nth-child(4){animation-delay:.28s}

        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon svg { width: 20px; height: 20px; }

        .stat-card.blue  .stat-icon { background: #eff6ff; }   .stat-card.blue  .stat-icon svg { color: #1d4ed8; }  .stat-card.blue  .stat-value { color: #1d4ed8; }
        .stat-card.amber .stat-icon { background: var(--gold-l); } .stat-card.amber .stat-icon svg { color: var(--gold-m); } .stat-card.amber .stat-value { color: var(--gold-d); }
        .stat-card.green .stat-icon { background: var(--green-l); } .stat-card.green .stat-icon svg { color: var(--green); } .stat-card.green .stat-value { color: var(--green); }
        .stat-card.red   .stat-icon { background: var(--red-l); }   .stat-card.red   .stat-icon svg { color: var(--red); }   .stat-card.red   .stat-value { color: var(--red); }

        .stat-label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--text-s); }
        .stat-value { font-size: 20px; font-weight: 800; color: var(--text); line-height: 1.1; margin-top: 3px; }

        /* ══ TABLA ══ */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--gold-l); }
        th { padding: 10px 16px; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--text-s); white-space: nowrap; border-bottom: 1px solid var(--border-l); }
        td { padding: 13px 16px; font-size: 13px; border-bottom: 1px solid #fef9e0; vertical-align: middle; }
        tbody tr { transition: background .15s; }
        tbody tr:hover { background: #fffdf5; }
        tbody tr:last-child td { border-bottom: none; }

        /* ══ BADGES ══ */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; opacity: .7; flex-shrink: 0; }
        .badge-pendiente  { background: var(--amber-l); color: #92400e; }
        .badge-vencida    { background: var(--red-l);   color: #7f1d1d; }
        .badge-pagada     { background: var(--green-l); color: #065f46; }
        .badge-anulada    { background: #f1f5f9;        color: #475569; }
        .badge-por_vencer { background: #fed7aa;        color: #7c2d12; }
        .badge-enviado    { background: var(--accent-l);color: #1e40af; }
        .badge-error      { background: var(--red-l);   color: #7f1d1d; }
        .badge-observada  { background: var(--amber-l); color: #92400e; }

        /* ══ BOTONES ══ */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 700;
            cursor: pointer; border: none; text-decoration: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all .15s; white-space: nowrap;
            position: relative; overflow: hidden;
        }
        .btn::after { content: ''; position: absolute; inset: 0; background: rgba(255,255,255,.2); transform: translateX(-100%) skewX(-15deg); transition: transform .35s; }
        .btn:hover::after { transform: translateX(115%) skewX(-15deg); }

        .btn-primary { background: var(--gold); color: var(--text); }
        .btn-primary:hover { background: var(--gold-h); transform: translateY(-1px); box-shadow: 0 4px 16px #f5c84235; }
        .btn-outline { background: var(--white); color: var(--text-m); border: 1.5px solid var(--border) !important; }
        .btn-outline:hover { background: var(--gold-l); border-color: var(--gold-m) !important; color: var(--gold-d); }
        .btn-ghost { background: transparent; color: var(--text-m); border: 1.5px solid var(--border) !important; }
        .btn-ghost:hover { background: var(--gold-l); }
        .btn-sm { padding: 6px 12px !important; font-size: 11.5px !important; border-radius: 8px !important; }
        .btn svg { width: 13px; height: 13px; flex-shrink: 0; }

        /* ══ INPUTS ══ */
        .form-input, .form-select {
            height: 40px; padding: 0 13px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--white); color: var(--text);
            outline: none; width: 100%;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-input:focus, .form-select:focus { border-color: var(--gold-m); box-shadow: 0 0 0 3px #f5c84218; }
        .form-input::placeholder { color: #c4af70; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--text-s); }

        /* ══ SEARCH BAR ══ */
        .search-bar { display: flex; align-items: center; gap: 10px; padding: 13px 22px; border-bottom: 1px solid #fef9e0; flex-wrap: wrap; }
        .search-input-wrap { position: relative; flex: 1; min-width: 200px; max-width: 300px; }
        .search-input-wrap .search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #c8a832; pointer-events: none; display: flex; align-items: center; z-index: 1; }
        .search-input-wrap .search-icon svg { width: 13px; height: 13px; display: block; }
        .search-input-wrap .form-input { padding-left: 34px !important; }
        .form-select { padding: 0 10px; cursor: pointer; width: auto; }

        /* ══ PAGE HEADER ══ */
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px; gap: 14px; animation: fadeDown .5s cubic-bezier(.16,1,.3,1) both; }
        .accent-bar { display: inline-block; width: 24px; height: 3px; background: var(--gold); border-radius: 2px; margin-right: 8px; vertical-align: middle; margin-bottom: 3px; }
        .page-title { font-size: 24px; font-weight: 800; letter-spacing: -.4px; color: var(--text); }
        .page-desc  { font-size: 13px; color: var(--text-s); margin-top: 4px; }
        .page-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* ══ MODAL ══ */
        .modal-overlay { position: fixed; inset: 0; background: rgba(28,22,0,.35); display: flex; align-items: center; justify-content: center; z-index: 200; padding: 24px; opacity: 0; pointer-events: none; transition: opacity .22s; }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal { background: var(--white); border-radius: 22px; border: 1px solid var(--border); width: 100%; max-width: 680px; max-height: 92vh; overflow: hidden; display: flex; flex-direction: column; transform: translateY(22px) scale(.97); transition: transform .28s cubic-bezier(.16,1,.3,1); }
        .modal-overlay.open .modal { transform: translateY(0) scale(1); }
        .modal-header { background: var(--gold-l); padding: 22px 28px 18px; border-bottom: 1.5px solid var(--border); position: relative; }
        .modal-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--gold); border-radius: 22px 22px 0 0; }
        .modal-header h2 { font-size: 17px; font-weight: 800; color: var(--text); padding-right: 32px; }
        .modal-header p  { font-size: 12.5px; color: var(--text-m); margin-top: 3px; }
        .modal-close { position: absolute; right: 20px; top: 20px; background: none; border: none; cursor: pointer; color: var(--text-m); font-size: 20px; line-height: 1; width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; transition: all .15s; }
        .modal-close:hover { background: var(--border-l); color: var(--text); }
        .modal-body   { padding: 26px 28px; overflow-y: auto; flex: 1; }
        .modal-footer { padding: 16px 28px; border-top: 1px solid var(--border-l); display: flex; justify-content: flex-end; gap: 10px; background: var(--gold-l); }
        .form-grid        { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-full        { grid-column: 1 / -1; }

        /* ══ MISC ══ */
        .text-muted { color: var(--text-s); font-size: 12px; }
        .font-mono  { font-family: 'Courier New', monospace; }
        .empty-state { text-align: center; padding: 52px 24px; color: var(--text-s); }
        .empty-state svg { margin: 0 auto 16px; display: block; color: var(--gold-b); }
        .empty-state p:first-of-type { font-weight: 700; font-size: 15px; color: var(--text); }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .main-content { padding: 16px; }
            .topbar { padding: 0 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <strong>C.R.C. S.A.C.</strong>
            <span>Gestión Financiera</span>
        </div>
        <button class="sidebar-close" onclick="toggleSidebar()" title="Cerrar menú">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Panel Principal
        </a>

        <a href="{{ route('facturas.index') }}" class="nav-item {{ request()->routeIs('facturas.*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Gestión de Facturas
        </a>

        <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Directorio Clientes
        </a>

        <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reportes Financieros
        </a>

        <div class="nav-label">Configuración</div>

        @if(Auth::user()->id_rol == 1)
        <a href="{{ route('usuarios.index') }}" class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v12a3 3 0 003 3h12a3 3 0 003-3V9h-6m0 0V5a3 3 0 00-3-3H9a3 3 0 00-3 3v4z"/></svg>
            Gestión de Usuarios
        </a>
        @endif

        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Reglas de Notificación
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">{{ strtoupper(substr(Auth::user()->nombre ?? 'A', 0, 1)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="sidebar-user-name">{{ Auth::user()->nombre ?? 'Admin' }} {{ Auth::user()->apellido ?? '' }}</div>
                <div class="sidebar-user-login">{{ Auth::user()->nombre_usuario ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                @csrf
                <button type="submit" class="sidebar-logout" title="Cerrar sesión">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<button type="button" class="sidebar-toggle-floating" onclick="toggleSidebar()" title="Abrir menú">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="sidebar-toggle" onclick="toggleSidebar()">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="breadcrumb">
                <span>CRC S.A.C.</span>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="breadcrumb-current">@yield('breadcrumb', 'Inicio')</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->nombre ?? 'A', 0, 1)) }}</div>
                <div class="user-info">
                    <strong>{{ Auth::user()->nombre ?? 'Admin' }}</strong>
                    <span>{{ Auth::user()->nombre_usuario ?? '' }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Salir
                </button>
            </form>
        </div>
    </header>

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
<script>
    function toggleSidebar() {
        const sidebar     = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        sidebar.classList.toggle('collapsed');
        mainWrapper.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? 'true' : 'false');
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('mainWrapper').classList.add('sidebar-collapsed');
        }
    });
</script>
</body>
</html>