@extends('layouts.app')
@section('title', 'Configuración')
@section('breadcrumb', 'Configuración')

@push('styles')
    <style>
        :root{--gold:#f5c842;--gold-b:#ead96a;--gold-m:#d4a017;--gold-l:#fffbeb;--gold-d:#9a6e10;}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}

        /* Status card */
        .wa-card{background:#fff;border:1.5px solid var(--gold-b);border-radius:16px;overflow:hidden;max-width:520px;}
        .wa-card-header{padding:20px 24px;border-bottom:1px solid var(--gold-b);display:flex;align-items:center;gap:14px;}
        .wa-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
        .wa-icon.green{background:#d1fae5;}
        .wa-icon.amber{background:#fef3c7;}
        .wa-icon.red{background:#fee2e2;}
        .wa-card-body{padding:24px;}

        /* Status indicator */
        .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px;vertical-align:middle;}
        .dot-green{background:#22c55e;}
        .dot-amber{background:#f59e0b;animation:pulse 1.5s infinite;}
        .dot-red{background:#ef4444;}

        /* QR container */
        .qr-wrap{text-align:center;padding:24px 0;}
        .qr-wrap img{width:220px;height:220px;border:3px solid var(--gold-b);border-radius:12px;margin:0 auto;display:block;}
        .qr-placeholder{width:220px;height:220px;border:2px dashed var(--gold-b);border-radius:12px;margin:0 auto;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:12px;flex-direction:column;gap:10px;}

        /* Spinner */
        .spinner-sm{width:18px;height:18px;border:2.5px solid #e2e8f0;border-top-color:var(--gold-m);border-radius:50%;animation:spin .7s linear infinite;display:inline-block;}
        @keyframes spin{to{transform:rotate(360deg)}}

        /* Action buttons */
        .btn-wa-action{height:38px;padding:0 18px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:7px;transition:all .15s;}
        .btn-refresh{background:var(--gold-l);color:var(--gold-d);border:1.5px solid var(--gold-b);}
        .btn-refresh:hover{background:var(--gold);color:#000;border-color:var(--gold-m);}
        .btn-logout{background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;}
        .btn-logout:hover{background:#fca5a5;}

        /* Info box */
        .info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;font-size:12px;color:#0369a1;margin-top:14px;line-height:1.6;}
        .warn-box{background:#fffbeb;border:1px solid var(--gold-b);border-radius:8px;padding:12px 14px;font-size:12px;color:#92400e;margin-top:14px;line-height:1.6;}
    </style>
@endpush

@section('content')

    <div class="page-header" style="animation:fadeDown .4s ease-out;">
        <div>
            <h1 class="page-title">Configuración</h1>
            <p class="page-desc">Administra la conexión de WhatsApp y ajustes del sistema.</p>
        </div>
    </div>

    {{-- ── WHATSAPP CARD ── --}}
    <div style="max-width:560px;">
        <div class="wa-card">
            <div class="wa-card-header">
                <div class="wa-icon" id="waIconWrap"></div>
                <div>
                    <div style="font-size:15px;font-weight:800;color:var(--text-primary);">Conexión WhatsApp</div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="waStatusText">
                        Verificando estado…
                    </div>
                </div>
                <div style="margin-left:auto;">
                <span id="statusBadge" style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;background:#f1f5f9;color:#64748b;">
                    <span class="spinner-sm"></span>
                </span>
                </div>
            </div>

            <div class="wa-card-body">

                {{-- QR / Conectado state --}}
                <div id="stateConectado" style="display:none;">
                    <div style="text-align:center;padding:20px 0;">
                        <div style="font-size:48px;margin-bottom:12px;"></div>
                        <div style="font-size:16px;font-weight:800;color:#065f46;margin-bottom:6px;">WhatsApp Listo para Envío</div>
                        <div style="font-size:13px;color:var(--text-muted);">La sesión está activa. Los mensajes y reportes se enviarán correctamente.</div>
                    </div>
                    <div class="info-box">
                        ℹ Para desconectar la sesión actual (por ejemplo, si quieres cambiar de número),
                        usa el botón "Cerrar sesión". Necesitarás escanear el QR nuevamente.
                    </div>
                    <div style="display:flex;justify-content:center;margin-top:16px;">
                        <button class="btn-wa-action btn-logout" onclick="logoutWA()" id="btnLogout">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Cerrar sesión WhatsApp
                        </button>
                    </div>
                </div>

                <div id="stateQR" style="display:none;">
                    <div class="warn-box">
                        ⚠ <strong>Escanea el QR con tu WhatsApp</strong> para conectar la sesión de envío de mensajes.
                        Abre WhatsApp en tu celular → Dispositivos vinculados → Vincular un dispositivo.
                    </div>
                    <div class="qr-wrap" id="qrWrap">
                        <div class="qr-placeholder" id="qrPlaceholder">
                            <div class="spinner-sm" style="width:28px;height:28px;border-width:3px;"></div>
                            <span>Cargando QR…</span>
                        </div>
                        <img id="qrImg" src="" alt="QR WhatsApp" style="display:none;">
                    </div>
                    <div style="text-align:center;font-size:12px;color:var(--text-muted);margin-bottom:16px;">
                        El QR expira en <strong>~60 segundos</strong>. Si expira, haz clic en "Actualizar QR".
                    </div>
                    <div style="display:flex;justify-content:center;gap:10px;">
                        <button class="btn-wa-action btn-refresh" onclick="cargarQR()" id="btnRefreshQR">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Actualizar QR
                        </button>
                    </div>
                </div>

                <div id="stateError" style="display:none;">
                    <div style="text-align:center;padding:20px 0;">
                        <div style="font-size:48px;margin-bottom:12px;"></div>
                        <div style="font-size:15px;font-weight:700;color:#dc2626;margin-bottom:6px;">Worker no disponible</div>
                        <div style="font-size:12px;color:var(--text-muted);max-width:360px;margin:0 auto;" id="errorDetailText">
                            No se pudo conectar con el worker de WhatsApp.
                        </div>
                    </div>
                    <div class="info-box">
                        💡 Asegúrate de que el worker de WhatsApp esté corriendo.<br>
                        Si usas Docker: <code>docker-compose up -d whatsapp-worker</code><br>
                        Si corres directo: <code>cd whatsapp-worker && npm start</code>
                    </div>
                    <div style="display:flex;justify-content:center;margin-top:16px;">
                        <button class="btn-wa-action btn-refresh" onclick="verificarEstado()">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reintentar conexión
                        </button>
                    </div>
                </div>

                <div id="stateLoading">
                    <div style="text-align:center;padding:30px 0;">
                        <div class="spinner-sm" style="width:36px;height:36px;border-width:4px;margin:0 auto 12px;"></div>
                        <div style="font-size:13px;color:var(--text-muted);">Verificando estado del worker…</div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div style="padding:12px 24px;border-top:1px solid var(--gold-b);background:var(--gold-l);display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <span style="font-size:11px;color:var(--text-muted);">
                Auto-verifica cada <strong>30 segundos</strong>
            </span>
                <button class="btn-wa-action btn-refresh" style="height:32px;font-size:12px;" onclick="verificarEstado()">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Verificar ahora
                </button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;padding:13px 20px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);transform:translateY(80px);opacity:0;transition:all .3s;max-width:380px;">
        <span id="toastTxt"></span>
    </div>

@endsection

@push('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        function showToast(msg, ok = true) {
            const t = document.getElementById('toast');
            document.getElementById('toastTxt').textContent = msg;
            t.style.background = ok ? '#d1fae5' : '#fee2e2';
            t.style.color      = ok ? '#065f46' : '#7f1d1d';
            t.style.border     = ok ? '1px solid #6ee7b7' : '1px solid #fca5a5';
            t.style.transform  = 'translateY(0)'; t.style.opacity = '1';
            setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3500);
        }

        function showState(estado) {
            ['stateConectado','stateQR','stateError','stateLoading'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
            document.getElementById('state' + estado).style.display = 'block';
        }

        async function verificarEstado() {
            showState('Loading');
            try {
                const res  = await fetch('/configuracion/whatsapp/status');
                const data = await res.json();

                const badge      = document.getElementById('statusBadge');
                const iconWrap   = document.getElementById('waIconWrap');
                const statusText = document.getElementById('waStatusText');

                if (!data.ok) {
                    badge.textContent         = 'Sin conexión';
                    badge.style.background    = '#fee2e2';
                    badge.style.color         = '#dc2626';
                    iconWrap.textContent      = '';
                    iconWrap.className        = 'wa-icon red';
                    statusText.textContent    = 'Worker no disponible';
                    document.getElementById('errorDetailText').textContent = data.error || 'Error desconocido';
                    showState('Error');
                    return;
                }

                if (data.listo) {
                    badge.innerHTML           = '<span class="status-dot dot-green"></span> Conectado';
                    badge.style.background    = '#d1fae5';
                    badge.style.color         = '#065f46';
                    iconWrap.textContent      = '';
                    iconWrap.className        = 'wa-icon green';
                    statusText.textContent    = 'WhatsApp listo para envío';
                    showState('Conectado');
                } else if (data.esperandoQr) {
                    badge.innerHTML           = '<span class="status-dot dot-amber"></span> Esperando QR';
                    badge.style.background    = '#fef3c7';
                    badge.style.color         = '#92400e';
                    iconWrap.className        = 'wa-icon amber';
                    statusText.textContent    = 'Escanea el QR para conectar';
                    showState('QR');
                    cargarQR();
                } else {
                    badge.innerHTML           = '<span class="status-dot dot-amber"></span> Iniciando…';
                    badge.style.background    = '#fef3c7';
                    badge.style.color         = '#92400e';
                    iconWrap.className        = 'wa-icon amber';
                    statusText.textContent    = 'Worker iniciando, espera unos segundos…';
                    showState('QR');
                    // Try to get QR, may not be ready yet
                    setTimeout(cargarQR, 3000);
                }

            } catch(e) {
                document.getElementById('errorDetailText').textContent = 'No se pudo conectar: ' + e.message;
                showState('Error');
            }
        }

        async function cargarQR() {
            const placeholder = document.getElementById('qrPlaceholder');
            const img         = document.getElementById('qrImg');
            const btn         = document.getElementById('btnRefreshQR');

            if (placeholder) placeholder.style.display = 'flex';
            if (img)         img.style.display = 'none';
            if (btn)         btn.disabled = true;

            try {
                const res  = await fetch('/configuracion/whatsapp/qr');
                const data = await res.json();

                if (data.listo) {
                    // Became ready while loading QR
                    await verificarEstado();
                    return;
                }

                if (data.qr_data_url) {
                    if (placeholder) placeholder.style.display = 'none';
                    img.src          = data.qr_data_url;
                    img.style.display = 'block';
                    showToast('QR actualizado. Escanéalo con WhatsApp.');
                } else {
                    if (placeholder) placeholder.innerHTML = '<span style="font-size:12px;text-align:center;padding:10px;">QR no disponible aún.<br>Espera unos segundos y actualiza.</span>';
                }
            } catch(e) {
                if (placeholder) placeholder.innerHTML = '<span style="color:#dc2626;font-size:12px;">Error al cargar QR: ' + e.message + '</span>';
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function logoutWA() {
            if (!confirm('¿Cerrar sesión de WhatsApp? Necesitarás escanear el QR para volver a conectar.')) return;
            const btn = document.getElementById('btnLogout');
            btn.disabled = true; btn.textContent = 'Cerrando…';

            try {
                const res  = await fetch('/configuracion/whatsapp/logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Sesión cerrada. Esperando nuevo QR…');
                    setTimeout(verificarEstado, 2000);
                } else {
                    showToast(data.error || 'Error al cerrar sesión', false);
                    btn.disabled = false;
                }
            } catch(e) {
                showToast('Error: ' + e.message, false);
                btn.disabled = false;
            }
        }

        // ── Auto-refresh every 30 seconds ──────────────────────────────────────────
        verificarEstado();
        setInterval(verificarEstado, 30000);
    </script>
@endpush
