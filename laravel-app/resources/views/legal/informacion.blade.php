<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Facturación</title>
    <style>
        :root { color-scheme: light; --navy:#24384d; --blue:#2563eb; --muted:#64748b; --line:#e2e8f0; }
        * { box-sizing: border-box; }
        body { margin:0; background:#f8fafc; color:#172033; font-family:Arial, sans-serif; line-height:1.6; }
        header { background:var(--navy); color:#fff; padding:22px 24px; }
        header div, main, footer div { max-width:900px; margin:auto; }
        header strong { font-size:20px; }
        main { padding:64px 24px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:40px; box-shadow:0 12px 35px rgba(15,23,42,.07); }
        h1 { margin:0 0 14px; color:var(--navy); font-size:34px; }
        h2 { margin-top:32px; color:var(--navy); }
        p, li { color:#334155; }
        .lead { font-size:18px; }
        a { color:var(--blue); }
        footer { border-top:1px solid var(--line); padding:24px; color:var(--muted); background:#fff; }
        @media (max-width:600px) { main { padding:28px 16px; } .card { padding:24px; } h1 { font-size:28px; } }
    </style>
</head>
<body>
    <header><div><strong>Sistema de Facturación</strong></div></header>
    <main>
        <section class="card">
            <h1>Gestión de facturas y cobranzas</h1>
            <p class="lead">Aplicación privada para organizar clientes, facturas, pagos, conciliaciones y notificaciones de cobranza.</p>

            <h2>Funcionalidad</h2>
            <ul>
                <li>Registro, consulta y seguimiento de facturas.</li>
                <li>Control de pagos, saldos pendientes y conciliaciones.</li>
                <li>Envío de confirmaciones y recordatorios de pago.</li>
                <li>Reportes administrativos para usuarios autorizados.</li>
            </ul>

            <h2>Uso de Gmail</h2>
            <p>La aplicación solicita exclusivamente permiso para enviar, por indicación de sus usuarios autorizados, correos transaccionales relacionados con facturas y pagos. No lee, modifica ni elimina mensajes del buzón de Gmail.</p>

            <p>Consulta nuestra <a href="{{ route('politica-privacidad') }}">Política de privacidad</a> o escribe a <a href="mailto:luisanguloruiz23@gmail.com">luisanguloruiz23@gmail.com</a>.</p>
        </section>
    </main>
    <footer><div>Sistema de Facturación &copy; {{ date('Y') }}</div></footer>
</body>
</html>
