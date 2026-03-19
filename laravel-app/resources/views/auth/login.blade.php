<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — C.R.C. S.A.C.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:        #f5c842;
            --gold-hover:  #e8b820;
            --gold-light:  #fffbeb;
            --gold-border: #ead96a;
            --gold-mid:    #d4a017;
            --gold-deep:   #9a6e10;
            --text-dark:   #1c1600;
            --text-mid:    #7a6838;
            --text-muted:  #9a8840;
            --bg:          #fdf8ec;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            position: relative;
            overflow: hidden;
        }

        /* ── ORBES DECORATIVOS ── */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .orb1 { width: 420px; height: 420px; background: #f5c84218; top: -100px; right: -100px; animation: float1 8s ease-in-out infinite; }
        .orb2 { width: 260px; height: 260px; background: #e8b81412; bottom: -60px; left: -60px; animation: float2 10s ease-in-out infinite; }
        .orb3 { width: 150px; height: 150px; background: #f5c8420e; top: 45%; left: 8%; animation: float3 7s ease-in-out infinite; }

        @keyframes float1 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-20px,20px) scale(1.05)} }
        @keyframes float2 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(15px,-15px) scale(1.08)} }
        @keyframes float3 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(10px,20px)} }

        /* ── WRAPPER ── */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: slideUp .6s cubic-bezier(.16,1,.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── BRAND ── */
        .brand {
            text-align: center;
            margin-bottom: 24px;
            animation: slideUp .6s .1s cubic-bezier(.16,1,.3,1) both;
        }

        .brand-logo {
            width: 58px; height: 58px;
            background: var(--gold);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            position: relative;
            animation: logoPop .7s .3s cubic-bezier(.34,1.56,.64,1) both;
        }

        @keyframes logoPop {
            from { opacity: 0; transform: scale(.5); }
            to   { opacity: 1; transform: scale(1); }
        }

        .brand-logo::after {
            content: '';
            position: absolute; inset: -4px;
            border-radius: 20px;
            border: 2px solid #f5c84240;
            animation: pulse 2.5s 1s ease-in-out infinite;
        }

        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0; transform: scale(1.18); }
        }

        .brand h1 {
            font-size: 21px; font-weight: 700;
            color: var(--text-dark); letter-spacing: -.3px;
        }

        .brand p {
            font-size: 11px; color: var(--text-muted);
            margin-top: 3px; letter-spacing: .6px;
            text-transform: uppercase; font-weight: 500;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid var(--gold-border);
            overflow: hidden;
            animation: slideUp .6s .15s cubic-bezier(.16,1,.3,1) both;
        }

        .card-header {
            background: var(--gold-light);
            padding: 28px 32px 22px;
            border-bottom: 1px solid #f5e080;
            position: relative;
        }

        .card-header-accent {
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: var(--gold);
            border-radius: 24px 24px 0 0;
        }

        /* ── STEP DOTS ── */
        .step-dots { display: flex; gap: 6px; margin-bottom: 16px; }

        .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #f0d55a;
            transition: all .4s cubic-bezier(.16,1,.3,1);
        }
        .dot.active { background: var(--gold-mid); width: 22px; border-radius: 4px; }
        .dot.done   { background: #8b6914; }

        .card-title { font-size: 18px; font-weight: 700; color: var(--text-dark); }
        .card-desc  { font-size: 13px; color: var(--text-mid); margin-top: 4px; }

        /* ── BODY ── */
        .card-body { padding: 24px 32px 28px; }

        .form-label {
            display: flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .7px;
            color: var(--text-muted); margin-bottom: 7px;
        }

        .form-group {
            margin-bottom: 18px; position: relative;
            animation: fieldIn .4s cubic-bezier(.16,1,.3,1) both;
        }

        .f1 { animation-delay: .25s; }
        .f2 { animation-delay: .35s; }
        .f3 { animation-delay: .45s; }
        .f4 { animation-delay: .55s; }

        @keyframes fieldIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ── INPUT ── */
        .input-wrap {
            display: flex; align-items: center;
            border: 1.5px solid var(--gold-border);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            transition: border-color .2s, box-shadow .2s, transform .15s;
        }

        .input-wrap:focus-within {
            border-color: var(--gold-mid);
            box-shadow: 0 0 0 3px #f5c84228;
            transform: translateY(-1px);
        }

        .icon-cell {
            width: 46px;
            display: flex; align-items: center; justify-content: center;
            align-self: stretch;
            background: var(--gold-light);
            border-right: 1.5px solid var(--gold-border);
            flex-shrink: 0;
        }

        .icon-cell svg { color: #c8a832; width: 16px; height: 16px; }

        .form-input {
            flex: 1; height: 48px;
            padding: 0 16px;
            border: none; outline: none;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background: transparent;
        }

        .form-input::placeholder { color: #c4af70; }

        /* ── SHOW/HIDE PASSWORD ── */
        .eye-btn {
            background: none; border: none; cursor: pointer;
            padding: 0 14px; color: #c8a832;
            display: flex; align-items: center;
            transition: color .2s;
        }
        .eye-btn:hover { color: var(--gold-deep); }
        .eye-btn svg { width: 16px; height: 16px; }

        /* ── HINT ── */
        .form-hint {
            font-size: 11px; color: #b09840;
            margin-top: 5px; padding-left: 4px;
            display: flex; align-items: center; gap: 4px;
            opacity: 0; transition: opacity .3s;
        }
        .form-group:focus-within .form-hint { opacity: 1; }
        .form-hint svg { width: 10px; height: 10px; flex-shrink: 0; }

        /* ── ERROR MESSAGE ── */
        .error-msg {
            font-size: 12px; color: #c0392b;
            margin-top: 5px; display: flex; align-items: center; gap: 5px;
        }
        .error-msg svg { width: 12px; height: 12px; flex-shrink: 0; }

        /* ── OPTIONS ROW ── */
        .options-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
            animation: fieldIn .4s .45s cubic-bezier(.16,1,.3,1) both;
        }

        .check-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-mid);
            cursor: pointer; user-select: none;
        }

        .custom-check {
            width: 18px; height: 18px;
            border: 2px solid #d4b840; border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            background: #fff;
            transition: all .2s; flex-shrink: 0;
        }
        .custom-check.checked { background: var(--gold); border-color: var(--gold-mid); }
        .custom-check svg { opacity: 0; transition: opacity .2s; color: var(--text-dark); width: 10px; height: 10px; }
        .custom-check.checked svg { opacity: 1; }

        /* ── BUTTON ── */
        .btn-login {
            width: 100%; height: 50px;
            background: var(--gold);
            color: var(--text-dark);
            border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background .2s, transform .15s, box-shadow .2s;
            position: relative; overflow: hidden;
            animation: fieldIn .4s .55s cubic-bezier(.16,1,.3,1) both;
        }

        .btn-login::after {
            content: '';
            position: absolute; inset: 0;
            background: rgba(255,255,255,.2);
            transform: translateX(-100%) skewX(-15deg);
            transition: transform .4s;
        }

        .btn-login:hover { background: var(--gold-hover); transform: translateY(-2px); box-shadow: 0 8px 24px #f5c84240; }
        .btn-login:hover::after { transform: translateX(120%) skewX(-15deg); }
        .btn-login:active { transform: translateY(0); box-shadow: none; }

        .btn-login.loading { pointer-events: none; }

        .spinner {
            width: 18px; height: 18px;
            border: 2.5px solid rgba(28,22,0,.2);
            border-top-color: var(--text-dark);
            border-radius: 50%;
            display: none;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .spinner  { display: block; }

        /* ── ALERT ── */
        .alert-error {
            background: #fef9e0;
            border: 1px solid #f0d96a;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: #7a6838;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── SECURITY ROW ── */
        .security-row {
            display: flex; justify-content: center; gap: 16px;
            margin-top: 20px;
        }

        .sec-item {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--text-muted); font-weight: 500;
        }
        .sec-item svg { color: var(--gold-mid); width: 12px; height: 12px; }

        /* ── FOOTER ── */
        .card-foot {
            background: var(--gold-light);
            border-top: 1px solid #f5e080;
            padding: 14px 32px;
            text-align: center;
            font-size: 11px; color: #a89030; letter-spacing: .3px;
        }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            top: 20px; left: 50%; transform: translateX(-50%) translateY(-80px);
            background: #fff;
            border: 1.5px solid var(--gold-border);
            border-radius: 12px;
            padding: 11px 20px;
            font-size: 13px; color: var(--text-mid);
            white-space: nowrap;
            display: flex; align-items: center; gap: 8px;
            transition: transform .4s cubic-bezier(.16,1,.3,1);
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        .toast svg { color: #c8a832; width: 14px; height: 14px; }
        .toast.success { border-color: #86d068; }
        .toast.success svg { color: #4caf38; }
    </style>
</head>
<body>

    <!-- Orbes decorativos de fondo -->
    <div class="bg-orb orb1"></div>
    <div class="bg-orb orb2"></div>
    <div class="bg-orb orb3"></div>

    <!-- Toast de notificación -->
    <div class="toast" id="toast">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span id="toast-msg">Completa todos los campos</span>
    </div>

    <div class="login-wrapper">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-logo">
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#1c1600" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h1>C.R.C. S.A.C.</h1>
            <p>Sistema de Gestión Financiera</p>
        </div>

        <!-- Card -->
        <div class="card">

            <div class="card-header">
                <div class="card-header-accent"></div>
                <div class="step-dots">
                    <div class="dot active" id="d1"></div>
                    <div class="dot" id="d2"></div>
                    <div class="dot" id="d3"></div>
                </div>
                <div class="card-title">Bienvenido de nuevo</div>
                <div class="card-desc">Ingresa tus credenciales para acceder al sistema.</div>
            </div>

            <div class="card-body">

                @if($errors->any() && !$errors->has('nombre_usuario') && !$errors->has('clave_usuario'))
                    <div class="alert-error">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="login-form">
                    @csrf

                    <!-- Usuario -->
                    <div class="form-group f1">
                        <label class="form-label" for="nombre_usuario">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Usuario
                        </label>
                        <div class="input-wrap">
                            <div class="icon-cell">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="nombre_usuario"
                                name="nombre_usuario"
                                class="form-input"
                                value="{{ old('nombre_usuario') }}"
                                placeholder="Ej: cperez"
                                autocomplete="username"
                                autofocus>
                        </div>
                        <div class="form-hint">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Usa el usuario que te asignó el administrador
                        </div>
                        @error('nombre_usuario')
                        <div class="error-msg">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group f2">
                        <label class="form-label" for="clave_usuario">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Contraseña
                        </label>
                        <div class="input-wrap">
                            <div class="icon-cell">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="clave_usuario"
                                name="clave_usuario"
                                class="form-input"
                                placeholder="Tu contraseña"
                                autocomplete="current-password">
                            <button class="eye-btn" id="eye-btn" type="button" title="Mostrar/ocultar contraseña">
                                <svg id="eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="form-hint">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Distingue mayúsculas y minúsculas
                        </div>
                        @error('clave_usuario')
                        <div class="error-msg">
                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <!-- Opciones -->
                    <div class="options-row">
                        <label class="check-label" id="check-label">
                            <div class="custom-check" id="custom-check">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            Mantener sesión
                        </label>
                        <input type="hidden" name="recordar" id="recordar-input" value="0">
                    </div>

                    <!-- Botón -->
                    <button type="submit" class="btn-login f4" id="btn-login">
                        <span class="btn-text" style="display:flex;align-items:center;gap:10px;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Iniciar Sesión
                        </span>
                        <div class="spinner"></div>
                    </button>

                </form>

                <!-- Seguridad -->
                <div class="security-row">
                    <div class="sec-item">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Cifrado SSL
                    </div>
                    <div class="sec-item">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Acceso seguro
                    </div>
                    <div class="sec-item">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Sesión temporal
                    </div>
                </div>

            </div>

            <div class="card-foot">
                Consorcio Rodriguez Caballero S.A.C. &nbsp;·&nbsp; Sistema Interno
            </div>

        </div>
    </div>

    <script>
        /* ── Mostrar / ocultar contraseña ── */
        const eyeBtn  = document.getElementById('eye-btn');
        const passInp = document.getElementById('clave_usuario');
        const eyeIcon = document.getElementById('eye-icon');
        let passVisible = false;

        eyeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            passVisible = !passVisible;
            passInp.type = passVisible ? 'text' : 'password';
            eyeIcon.innerHTML = passVisible
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
        });

        /* ── Checkbox personalizado ── */
        const checkEl     = document.getElementById('custom-check');
        const recordarInp = document.getElementById('recordar-input');

        document.getElementById('check-label').addEventListener('click', () => {
            checkEl.classList.toggle('checked');
            recordarInp.value = checkEl.classList.contains('checked') ? '1' : '0';
        });

        /* ── Toast ── */
        const toast    = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-msg');
        let toastTimer;

        function showToast(msg, success = false) {
            clearTimeout(toastTimer);
            toastMsg.textContent = msg;
            toast.classList.toggle('success', success);
            toast.classList.add('show');
            toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
        }

        /* ── Step dots ── */
        const dots = ['d1','d2','d3'].map(id => document.getElementById(id));

        function activateDot(index) {
            dots.forEach((d, i) => {
                d.classList.remove('active', 'done');
                if (i < index)  d.classList.add('done');
                if (i === index) d.classList.add('active');
            });
        }

        /* ── Submit con animación ── */
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const user = document.getElementById('nombre_usuario').value.trim();
            const pass = document.getElementById('clave_usuario').value.trim();

            if (!user && !pass) { e.preventDefault(); showToast('Completa todos los campos'); return; }
            if (!user)          { e.preventDefault(); showToast('Ingresa tu usuario'); return; }
            if (!pass)          { e.preventDefault(); showToast('Ingresa tu contraseña'); return; }

            /* Animación de carga */
            const btn = document.getElementById('btn-login');
            btn.classList.add('loading');

            activateDot(1);
            setTimeout(() => activateDot(2), 600);
        });
    </script>

</body>
</html>
