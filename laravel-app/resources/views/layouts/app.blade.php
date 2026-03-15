<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Facturación') — Consorcio Rodriguez Caballero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #1d4ed8;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --main-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --accent: #1d4ed8;
            --accent-light: #dbeafe;
            --green: #059669;
            --green-light: #d1fae5;
            --amber: #d97706;
            --amber-light: #fef3c7;
            --red: #dc2626;
            --red-light: #fee2e2;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 12px rgba(0,0,0,.06);
            --shadow-lg: 0 8px 32px rgba(0,0,0,.12);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--main-bg);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform .3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(0);
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .sidebar-logo {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-logo svg { color: #fff; }

        .sidebar-brand-text {
            line-height: 1.2;
        }

        .sidebar-brand-text strong {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.2px;
        }

        .sidebar-brand-text span {
            font-size: 10px;
            color: var(--sidebar-text);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 9px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 8px;
            margin: 16px 0 6px;
        }

        .nav-label:first-child { margin-top: 0; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all .18s ease;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-item.active {
            background: var(--accent);
            color: #fff;
        }

        .nav-item svg { flex-shrink: 0; opacity: .85; }
        .nav-item.active svg { opacity: 1; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid #1e293b;
        }

        /* ── MAIN ── */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-wrapper.sidebar-collapsed {
            margin-left: 0;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-muted);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
        }

        .sidebar-toggle:hover {
            background: var(--main-bg);
            color: var(--text-primary);
        }

        /* Botón flotante cuando sidebar está colapsado */
        .sidebar-toggle-floating {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--sidebar-bg);
            border: none;
            color: #fff;
            cursor: pointer;
            z-index: 99;
            align-items: center;
            justify-content: center;
            transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-toggle-floating:hover {
            background: var(--sidebar-hover);
            transform: scale(1.08);
            box-shadow: 0 6px 28px rgba(0, 0, 0, 0.2);
        }

        .main-wrapper.sidebar-collapsed .sidebar-toggle {
            display: flex;
        }

        .main-wrapper.sidebar-collapsed .sidebar-toggle-floating {
            display: flex;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .breadcrumb-current {
            color: var(--text-primary);
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--main-bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 14px 6px 8px;
        }

        .user-avatar {
            width: 30px; height: 30px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .user-info strong { font-size: 13px; display: block; }
        .user-info span { font-size: 10px; color: var(--text-muted); }

        .main-content {
            padding: 32px;
            flex: 1;
        }

        /* ── ALERTS ── */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: var(--green-light); color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: var(--red-light); color: #7f1d1d; border: 1px solid #fca5a5; }

        /* ── CARDS ── */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .card-title { font-size: 16px; font-weight: 700; }
        .card-desc { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 4px solid transparent;
        }

        .stat-card.blue { border-left-color: var(--accent); }
        .stat-card.green { border-left-color: var(--green); }
        .stat-card.amber { border-left-color: var(--amber); }
        .stat-card.red { border-left-color: var(--red); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-card.blue .stat-icon { background: var(--accent-light); color: var(--accent); }
        .stat-card.green .stat-icon { background: var(--green-light); color: var(--green); }
        .stat-card.amber .stat-icon { background: var(--amber-light); color: var(--amber); }
        .stat-card.red .stat-icon { background: var(--red-light); color: var(--red); }

        .stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--text-muted); }
        .stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-top: 2px; }

        /* ── TABLE ── */
        table { width: 100%; border-collapse: collapse; }

        thead tr { background: #f8fafc; }
        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--text-muted);
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 16px;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-vencida { background: #fee2e2; color: #7f1d1d; }
        .badge-pagada { background: #d1fae5; color: #065f46; }
        .badge-anulada { background: #f1f5f9; color: #475569; }
        .badge-por_vencer { background: #fed7aa; color: #7c2d12; }
        .badge-enviado { background: #dbeafe; color: #1e40af; }
        .badge-error { background: #fee2e2; color: #7f1d1d; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .15s ease;
            white-space: nowrap;
        }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #1e40af; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(29,78,216,.3); }
        .btn-green { background: #059669; color: #fff; }
        .btn-green:hover { background: #047857; }
        .btn-outline { background: #fff; color: var(--text-primary); border: 1px solid var(--border); }
        .btn-outline:hover { background: var(--main-bg); }
        .btn-ghost { background: transparent; color: var(--text-muted); }
        .btn-ghost:hover { background: var(--main-bg); color: var(--text-primary); }
        .btn-danger { background: #fee2e2; color: var(--red); }
        .btn-danger:hover { background: #fca5a5; }
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 6px; }

        /* ── INPUTS ── */
        .form-input, .form-select {
            height: 40px;
            padding: 0 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-family: 'DM Sans', sans-serif;
            background: #fff;
            color: var(--text-primary);
            outline: none;
            transition: border-color .15s;
            width: 100%;
        }

        .form-input:focus, .form-select:focus { border-color: var(--accent); }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }

        /* ── SEARCH BAR ── */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #fafbfd;
            flex-wrap: wrap;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 320px;
        }

        .search-input-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-input-wrap input {
            padding-left: 38px;
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s;
        }

        .modal-overlay.open { opacity: 1; pointer-events: all; }

        .modal {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 680px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            transform: translateY(20px);
            transition: transform .25s ease;
        }

        .modal-overlay.open .modal { transform: translateY(0); }

        .modal-header {
            background: var(--sidebar-bg);
            color: #fff;
            padding: 24px 28px;
        }

        .modal-header h2 { font-size: 22px; font-weight: 700; }
        .modal-header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }

        .modal-body {
            padding: 28px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 20px 28px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #fafbfd;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-full { grid-column: 1 / -1; }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
        }

        .page-title { font-size: 28px; font-weight: 800; letter-spacing: -.5px; }
        .page-desc { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        .page-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* ── MISC ── */
        .text-muted { color: var(--text-muted); font-size: 12px; }
        .font-mono { font-family: 'DM Mono', monospace; }
        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-muted); }
        .empty-state svg { margin: 0 auto 16px; color: #cbd5e1; }

        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <strong>C.R.C. S.A.C.</strong>
            <span>Gestión Financiera</span>
        </div>
        <button type="button" onclick="toggleSidebar()" style="position:absolute;right:16px;top:20px;width:32px;height:32px;border:none;background:transparent;color:var(--sidebar-text);cursor:pointer;border-radius:6px;display:flex;align-items:center;justify-content:center;transition:all .2s ease;" onmouseover="this.style.background='var(--sidebar-hover)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='var(--sidebar-text)';" title="Cerrar menú">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Principal</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Panel Principal
        </a>

        <a href="{{ route('facturas.index') }}" class="nav-item {{ request()->routeIs('facturas.*') ? 'active' : '' }}">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Gestión de Facturas
        </a>

        <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Directorio Clientes
        </a>

        <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Reportes Financieros
        </a>

        <div class="nav-label">Configuración</div>

        <a href="#" class="nav-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Reglas de Notificación
        </a>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;background:#1e293b;">
            <div style="width:30px;height:30px;border-radius:50%;background:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr(Auth::user()->nombre ?? 'A', 0, 1)) }}
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ Auth::user()->nombre ?? 'Admin' }} {{ Auth::user()->apellido ?? '' }}
                </div>
                <div style="font-size:10px;color:#64748b;">{{ Auth::user()->nombre_usuario ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                @csrf
                <button type="submit"
                        title="Cerrar sesión"
                        style="background:none;border:none;cursor:pointer;color:#64748b;padding:4px;border-radius:4px;display:flex;align-items:center;"
                        onmouseover="this.style.color='#ef4444'"
                        onmouseout="this.style.color='#64748b'">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- BOTÓN FLOTANTE (aparece cuando sidebar está colapsado) -->
<button type="button" class="sidebar-toggle-floating" onclick="toggleSidebar()" title="Abrir menú de navegación">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<!-- MAIN -->
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" title="Abrir menú">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="breadcrumb">
                <span>CRC S.A.C.</span>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="breadcrumb-current">@yield('breadcrumb', 'Inicio')</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->nombre ?? 'A', 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <strong>{{ Auth::user()->nombre ?? 'Admin' }}</strong>
                        <span>{{ Auth::user()->nombre_usuario ?? '' }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" title="Cerrar sesión"
                            style="color:var(--text-muted);">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainWrapper = document.querySelector('.main-wrapper');

        sidebar.classList.toggle('collapsed');
        mainWrapper.classList.toggle('sidebar-collapsed');

        // Guardar estado en localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
    }

    // Restaurar estado del sidebar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            const sidebar = document.querySelector('.sidebar');
            const mainWrapper = document.querySelector('.main-wrapper');
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('sidebar-collapsed');
        }
    });
</script>
</body>
</html>
