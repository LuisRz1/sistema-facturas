@extends('layouts.app')
@section('title', 'Configuración')
@section('breadcrumb', 'Configuración')

@push('styles')
    <style>
        :root{--gold:#f5c842;--gold-b:#ead96a;--gold-m:#d4a017;--gold-l:#fffbeb;--gold-d:#9a6e10;}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
        @keyframes spin{to{transform:rotate(360deg)}}
        @keyframes qrAppear{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}

        /* ── CARD ── */
        .wa-card{background:#fff;border:1.5px solid var(--gold-b);border-radius:16px;overflow:hidden;max-width:560px;}
        .wa-card-header{padding:20px 24px;border-bottom:1px solid var(--gold-b);display:flex;align-items:center;gap:14px;}
        .wa-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
        .wa-icon.green{background:#d1fae5;}
        .wa-icon.amber{background:#fef3c7;}
        .wa-icon.red{background:#fee2e2;}
        .wa-card-body{padding:24px;}

        /* ── STATUS ── */
        .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px;vertical-align:middle;}
        .dot-green{background:#22c55e;}
        .dot-amber{background:#f59e0b;animation:pulse 1.5s infinite;}
        .dot-red{background:#ef4444;}

        /* ── QR ── */
        .qr-container{
            display:flex;flex-direction:column;align-items:center;
            padding:20px 0 8px;
        }
        .qr-frame{
            position:relative;
            width:240px;height:240px;
            border:3px solid var(--gold-b);
            border-radius:16px;
            overflow:hidden;
            background:#f8fafc;
            display:flex;align-items:center;justify-content:center;
        }
        .qr-frame img{
            width:100%;height:100%;
            object-fit:contain;
            animation:qrAppear .3s ease-out;
        }
        .qr-corners::before,.qr-corners::after{
            content:'';position:absolute;
            width:24px;height:24px;
            border-color:var(--gold-m);
            border-style:solid;
        }
        .qr-corners::before{top:-1px;left:-1px;border-width:3px 0 0 3px;border-radius:4px 0 0 0;}
        .qr-corners::after {bottom:-1px;right:-1px;border-width:0 3px 3px 0;border-radius:0 0 4px 0;}

        .qr-placeholder{
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:10px;color:var(--text-muted);font-size:12px;text-align:center;padding:20px;
        }

        /* ── TIMER ── */
        .qr-timer{
            display:flex;align-items:center;gap:8px;
            margin-top:12px;font-size:12px;color:var(--text-muted);
        }
        .timer-bar-bg{
            width:160px;height:4px;background:#f1f5f9;border-radius:4px;overflow:hidden;
        }
        .timer-bar-fill{
            height:100%;background:var(--gold-m);border-radius:4px;
            transition:width .1s linear;
        }
        .timer-bar-fill.urgent{background:#ef4444;}

        /* ── SPINNER ── */
        .spinner-sm{
            width:18px;height:18px;
            border:2.5px solid #e2e8f0;border-top-color:var(--gold-m);
            border-radius:50%;animation:spin .7s linear infinite;display:inline-block;
        }

        /* ── BUTTONS ── */
        .btn-wa-action{height:38px;padding:0 18px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:7px;transition:all .15s;}
        .btn-refresh{background:var(--gold-l);color:var(--gold-d);border:1.5px solid var(--gold-b);}
        .btn-refresh:hover:not(:disabled){background:var(--gold);color:#000;border-color:var(--gold-m);}
        .btn-refresh:disabled{opacity:.6;cursor:not-allowed;}
        .btn-logout{background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;}
        .btn-logout:hover:not(:disabled){background:#fca5a5;}
        .btn-logout:disabled{opacity:.6;cursor:not-allowed;}

        /* ── INFO / WARN BOXES ── */
        .info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;font-size:12px;color:#0369a1;margin-top:14px;line-height:1.6;}
        .warn-box{background:#fffbeb;border:1px solid var(--gold-b);border-radius:8px;padding:12px 14px;font-size:12px;color:#92400e;margin-top:14px;line-height:1.6;}

        /* ── STATES ── */
        #stateConectado,#stateQR,#stateError,#stateLoading{display:none;}
    </style>
@endpush

@section('content')

    <div class="page-header" style="animation:fadeDown .4s ease-out;">
        <div>
            <h1 class="page-title">Configuración</h1>
            <p class="page-desc">Administra la conexión de WhatsApp y ajustes del sistema.</p>
        </div>
    </div>

    <div style="max-width:560px;">
        <div class="wa-card">

            {{-- HEADER --}}
            <div class="wa-card-header">
                <div class="wa-icon" id="waIconWrap">
                    <div class="spinner-sm" style="width:24px;height:24px;border-width:3px;"></div>
                </div>
                <div style="flex:1;">
                    <div style="font-size:15px;font-weight:800;color:var(--text-primary);">Conexión WhatsApp</div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="waStatusText">Verificando estado…</div>
                </div>
                <span id="statusBadge" style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;background:#f1f5f9;color:#64748b;">
                    <span class="spinner-sm" style="width:14px;height:14px;border-width:2px;"></span>
                </span>
            </div>

            {{-- BODY --}}
            <div class="wa-card-body">

                {{-- Estado: Cargando --}}
                <div id="stateLoading" style="text-align:center;padding:30px 0;">
                    <div class="spinner-sm" style="width:36px;height:36px;border-width:4px;margin:0 auto 12px;"></div>
                    <div style="font-size:13px;color:var(--text-muted);">Verificando estado del worker…</div>
                </div>

                {{-- Estado: Conectado --}}
                <div id="stateConectado">
                    <div style="text-align:center;padding:20px 0;">
                        <div style="font-size:52px;margin-bottom:12px;">📱</div>
                        <div style="font-size:16px;font-weight:800;color:#065f46;margin-bottom:6px;">WhatsApp Listo para Envío</div>
                        <div style="font-size:13px;color:var(--text-muted);">La sesión está activa. Los mensajes y reportes se enviarán correctamente.</div>
                    </div>
                    <div class="info-box">
                        ℹ Para cambiar el número de WhatsApp, cierra la sesión actual y escanea el QR con el nuevo celular.
                    </div>
                    <div style="display:flex;justify-content:center;margin-top:16px;">
                        <button class="btn-wa-action btn-logout" onclick="logoutWA()" id="btnLogout">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Cerrar sesión WhatsApp
                        </button>
                    </div>
                </div>

                {{-- Estado: QR --}}
                <div id="stateQR">
                    <div class="warn-box">
                        📱 <strong>Escanea el QR con WhatsApp</strong> para conectar la sesión de envío de mensajes.<br>
                        Abre WhatsApp → Dispositivos vinculados → Vincular un dispositivo.
                    </div>

                    <div class="qr-container">
                        <div class="qr-frame" id="qrFrame">
                            <div class="qr-placeholder" id="qrPlaceholder">
                                <div class="spinner-sm" style="width:32px;height:32px;border-width:3.5px;"></div>
                                <span>Cargando código QR…</span>
                            </div>
                            <img id="qrImg" src="" alt="QR WhatsApp" style="display:none;">
                        </div>

                        {{-- Timer de expiración --}}
                        <div class="qr-timer" id="qrTimerWrap" style="display:none;">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                            <span id="qrTimerText">Expira en 60s</span>
                            <div class="timer-bar-bg">
                                <div class="timer-bar-fill" id="qrTimerBar" style="width:100%;"></div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:center;gap:10px;margin-top:16px;">
                        <button class="btn-wa-action btn-refresh" onclick="cargarQR()" id="btnRefreshQR">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Actualizar QR
                        </button>
                        <button class="btn-wa-action btn-logout" onclick="logoutWA()" id="btnLogoutQR" style="font-size:12px;height:34px;padding:0 14px;">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Cambiar número
                        </button>
                    </div>
                </div>

                {{-- Estado: Error --}}
                <div id="stateError">
                    <div style="text-align:center;padding:20px 0;">
                        <div style="font-size:48px;margin-bottom:12px;">⚠️</div>
                        <div style="font-size:15px;font-weight:700;color:#dc2626;margin-bottom:6px;">Worker no disponible</div>
                        <div style="font-size:12px;color:var(--text-muted);max-width:360px;margin:0 auto;" id="errorDetailText">No se pudo conectar con el worker de WhatsApp.</div>
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

            </div>

            {{-- FOOTER --}}
            <div style="padding:12px 24px;border-top:1px solid var(--gold-b);background:var(--gold-l);display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <span style="font-size:11px;color:var(--text-muted);">Auto-verifica cada <strong>30 segundos</strong></span>
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

        // ── Toast ─────────────────────────────────────────────────────────────────
        function showToast(msg, ok = true) {
            const t = document.getElementById('toast');
            document.getElementById('toastTxt').textContent = msg;
            t.style.background = ok ? '#d1fae5' : '#fee2e2';
            t.style.color      = ok ? '#065f46' : '#7f1d1d';
            t.style.border     = ok ? '1px solid #6ee7b7' : '1px solid #fca5a5';
            t.style.transform  = 'translateY(0)'; t.style.opacity = '1';
            setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3500);
        }

        // ── Mostrar estado ────────────────────────────────────────────────────────
        function showState(estado) {
            ['stateConectado','stateQR','stateError','stateLoading'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
            document.getElementById('state' + estado).style.display = 'block';
        }

        // ── Verificar estado del worker ──────────────────────────────────────────
        async function verificarEstado() {
            showState('Loading');
            try {
                const res  = await fetch('/configuracion/whatsapp/status');
                const data = await res.json();
                const badge      = document.getElementById('statusBadge');
                const iconWrap   = document.getElementById('waIconWrap');
                const statusText = document.getElementById('waStatusText');

                if (!data.ok) {
                    badge.innerHTML           = '<span class="status-dot dot-red"></span> Sin conexión';
                    badge.style.background    = '#fee2e2';
                    badge.style.color         = '#dc2626';
                    iconWrap.innerHTML        = '⚠️';
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
                    iconWrap.innerHTML        = '✅';
                    iconWrap.className        = 'wa-icon green';
                    statusText.textContent    = 'WhatsApp listo para envío';
                    stopQrTimer();
                    showState('Conectado');
                } else if (data.esperandoQr || !data.listo) {
                    badge.innerHTML           = '<span class="status-dot dot-amber"></span> Esperando QR';
                    badge.style.background    = '#fef3c7';
                    badge.style.color         = '#92400e';
                    iconWrap.innerHTML        = '📷';
                    iconWrap.className        = 'wa-icon amber';
                    statusText.textContent    = 'Escanea el QR para conectar';
                    showState('QR');
                    cargarQR();
                }
            } catch(e) {
                document.getElementById('errorDetailText').textContent = 'No se pudo conectar: ' + e.message;
                showState('Error');
            }
        }

        // ── Timer del QR ──────────────────────────────────────────────────────────
        let qrTimerInterval = null;
        let qrSecondsLeft   = 60;

        function startQrTimer() {
            stopQrTimer();
            qrSecondsLeft = 60;
            document.getElementById('qrTimerWrap').style.display = 'flex';
            updateTimerDisplay();
            qrTimerInterval = setInterval(() => {
                qrSecondsLeft--;
                updateTimerDisplay();
                if (qrSecondsLeft <= 0) {
                    stopQrTimer();
                    // QR expirado — mostrar placeholder con aviso
                    document.getElementById('qrImg').style.display = 'none';
                    document.getElementById('qrPlaceholder').innerHTML = `
                        <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="color:#92400e;font-weight:600;">QR expirado</span>
                        <span style="font-size:11px;">Haz clic en "Actualizar QR"</span>
                    `;
                    document.getElementById('qrPlaceholder').style.display = 'flex';
                }
            }, 1000);
        }

        function stopQrTimer() {
            if (qrTimerInterval) { clearInterval(qrTimerInterval); qrTimerInterval = null; }
            document.getElementById('qrTimerWrap').style.display = 'none';
        }

        function updateTimerDisplay() {
            const pct  = (qrSecondsLeft / 60) * 100;
            const bar  = document.getElementById('qrTimerBar');
            const txt  = document.getElementById('qrTimerText');
            bar.style.width = pct + '%';
            bar.className   = 'timer-bar-fill' + (qrSecondsLeft <= 15 ? ' urgent' : '');
            txt.textContent = `Expira en ${qrSecondsLeft}s`;
        }

        // ── Cargar QR ─────────────────────────────────────────────────────────────
        async function cargarQR() {
            const placeholder = document.getElementById('qrPlaceholder');
            const img         = document.getElementById('qrImg');
            const btn         = document.getElementById('btnRefreshQR');

            // Mostrar spinner mientras carga
            placeholder.innerHTML = `
                <div class="spinner-sm" style="width:32px;height:32px;border-width:3.5px;"></div>
                <span>Cargando QR…</span>
            `;
            placeholder.style.display = 'flex';
            img.style.display = 'none';
            if (btn) btn.disabled = true;
            stopQrTimer();

            try {
                const res  = await fetch('/configuracion/whatsapp/qr');
                const data = await res.json();

                if (data.listo) {
                    // Ya está conectado — verificar estado completo
                    await verificarEstado();
                    return;
                }

                // Intentar obtener el QR de diferentes campos posibles
                const qrSource = data.qr_data_url || data.qr || data.qrCode || data.data_url || null;

                if (qrSource) {
                    placeholder.style.display = 'none';
                    img.src = qrSource;
                    img.style.display = 'block';
                    startQrTimer();
                    showToast('✓ QR actualizado — escanea con WhatsApp');
                } else {
                    placeholder.innerHTML = `
                        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="color:#92400e;font-weight:600;font-size:12px;">QR no disponible aún</span>
                        <span style="font-size:11px;color:#9a8840;">Espera unos segundos y actualiza</span>
                    `;
                    placeholder.style.display = 'flex';
                }
            } catch(e) {
                placeholder.innerHTML = `
                    <span style="color:#dc2626;font-size:12px;text-align:center;">❌ Error al cargar QR:<br>${e.message}</span>
                `;
                placeholder.style.display = 'flex';
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        // ── Logout WhatsApp ───────────────────────────────────────────────────────
        async function logoutWA() {
            if (!confirm('¿Cerrar la sesión de WhatsApp?\n\nNecesitarás escanear el QR nuevamente para volver a conectar.')) return;

            const btns = document.querySelectorAll('#btnLogout, #btnLogoutQR');
            btns.forEach(b => { b.disabled = true; b.textContent = 'Cerrando sesión…'; });

            try {
                const res  = await fetch('/configuracion/whatsapp/logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.ok) {
                    showToast('✓ Sesión cerrada. Cargando QR para nuevo número…');
                    // Esperar un poco y volver a verificar
                    setTimeout(() => verificarEstado(), 2000);
                } else {
                    showToast(data.error || 'Error al cerrar sesión', false);
                    btns.forEach(b => { b.disabled = false; });
                }
            } catch(e) {
                showToast('Error: ' + e.message, false);
                btns.forEach(b => { b.disabled = false; });
            }
        }

        // ── Auto-refresh cada 30 segundos ──────────────────────────────────────
        verificarEstado();
        setInterval(verificarEstado, 30000);
    </script>
@endpush
