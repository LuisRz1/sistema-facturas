<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — C.R.C. S.A.C.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent: #1d4ed8;
            --accent-light: #dbeafe;
            --text-primary: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --red: #dc2626;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        /* ── BRAND ── */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 56px; height: 56px;
            background: var(--accent);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 24px rgba(29, 78, 216, .4);
        }

        .brand-logo svg { color: #fff; }

        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.3px;
        }

        .brand p {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 36px 32px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .card-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        /* ── FORM ── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--text-muted);
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 40px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text-primary);
            background: #fff;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .form-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(29,78,216,.1);
        }

        .form-input.is-error { border-color: var(--red); }

        .error-msg {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--red);
            margin-top: 6px;
            font-weight: 500;
        }

        /* ── REMEMBER ── */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .remember-row label {
            font-size: 13px;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* ── BUTTON ── */
        .btn-login {
            width: 100%;
            height: 46px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all .18s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: .2px;
        }

        .btn-login:hover {
            background: #1e40af;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(29,78,216,.35);
        }

        .btn-login:active { transform: translateY(0); }

        /* ── FOOTER ── */
        .card-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }

        /* ── ALERT ── */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: #b91c1c;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <div class="brand">
        <div class="brand-logo">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h1>C.R.C. S.A.C.</h1>
        <p>Sistema de Gestión Financiera</p>
    </div>

    <div class="card">
        <div class="card-title">Bienvenido de nuevo</div>
        <div class="card-desc">Ingresa tus credenciales para continuar.</div>

        @if($errors->any() && !$errors->has('nombre_usuario') && !$errors->has('clave_usuario'))
            <div class="alert-error">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="nombre_usuario">Usuario</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <input
                        type="text"
                        id="nombre_usuario"
                        name="nombre_usuario"
                        class="form-input {{ $errors->has('nombre_usuario') ? 'is-error' : '' }}"
                        value="{{ old('nombre_usuario') }}"
                        placeholder="Ej: cperez"
                        autocomplete="username"
                        autofocus>
                </div>
                @error('nombre_usuario')
                <div class="error-msg">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="clave_usuario">Contraseña</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input
                        type="password"
                        id="clave_usuario"
                        name="clave_usuario"
                        class="form-input {{ $errors->has('clave_usuario') ? 'is-error' : '' }}"
                        placeholder="Tu contraseña"
                        autocomplete="current-password">
                </div>
                @error('clave_usuario')
                <div class="error-msg">
                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="remember-row">
                <input type="checkbox" id="recordar" name="recordar" value="1">
                <label for="recordar">Mantener sesión iniciada</label>
            </div>

            <button type="submit" class="btn-login">
                Iniciar Sesión
            </button>
        </form>

        <div class="card-footer">
            Consorcio Rodriguez Caballero S.A.C. &nbsp;·&nbsp; Sistema Interno
        </div>
    </div>

</div>

</body>
</html>
