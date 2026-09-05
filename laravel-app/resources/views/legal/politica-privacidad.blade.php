<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de privacidad | Sistema de Facturación</title>
    <style>
        :root { color-scheme: light; --navy:#24384d; --blue:#2563eb; --muted:#64748b; --line:#e2e8f0; }
        * { box-sizing:border-box; }
        body { margin:0; background:#f8fafc; color:#172033; font-family:Arial, sans-serif; line-height:1.65; }
        header { background:var(--navy); color:#fff; padding:22px 24px; }
        header div, main, footer div { max-width:900px; margin:auto; }
        header a { color:#fff; text-decoration:none; font-size:20px; font-weight:bold; }
        main { padding:48px 24px; }
        article { background:#fff; border:1px solid var(--line); border-radius:16px; padding:40px; box-shadow:0 12px 35px rgba(15,23,42,.07); }
        h1, h2 { color:var(--navy); } h1 { margin-top:0; font-size:34px; } h2 { margin-top:32px; font-size:21px; }
        p, li { color:#334155; } a { color:var(--blue); } .updated { color:var(--muted); }
        footer { border-top:1px solid var(--line); padding:24px; color:var(--muted); background:#fff; }
        @media (max-width:600px) { main { padding:28px 16px; } article { padding:24px; } h1 { font-size:28px; } }
    </style>
</head>
<body>
    <header><div><a href="{{ route('informacion') }}">Sistema de Facturación</a></div></header>
    <main>
        <article>
            <h1>Política de privacidad</h1>
            <p class="updated">Actualizada el 5 de septiembre de 2026.</p>
            <p>Esta política explica cómo el Sistema de Facturación accede, utiliza y protege la información necesaria para prestar sus funciones administrativas y de notificación.</p>

            <h2>Información utilizada</h2>
            <p>El sistema procesa los datos de clientes, facturas y pagos que sus usuarios autorizados registran o importan. Para el envío de correos mediante Google OAuth, utiliza exclusivamente el permiso <code>gmail.send</code>.</p>

            <h2>Uso de datos de Google</h2>
            <p>Los datos de Google se utilizan únicamente para enviar correos transaccionales de facturación, confirmaciones de pago y recordatorios de cobranza desde la cuenta autorizada. La aplicación no solicita acceso para leer, listar, modificar o eliminar correos, contactos ni archivos de Google.</p>

            <h2>Almacenamiento y seguridad</h2>
            <p>Las credenciales OAuth se almacenan como variables cifradas o protegidas del entorno de despliegue y no forman parte del código fuente. Se aplican controles de acceso para limitar el uso del sistema a personal autorizado.</p>

            <h2>Transferencia y divulgación</h2>
            <p>No vendemos datos de Google ni los utilizamos para publicidad. No compartimos estos datos con terceros, excepto con Google para ejecutar el envío solicitado o cuando exista una obligación legal aplicable.</p>

            <h2>Conservación y revocación</h2>
            <p>La autorización se conserva mientras sea necesaria para el envío de notificaciones. El titular puede revocar el acceso desde la configuración de seguridad de su cuenta de Google. Tras la revocación, el sistema ya no podrá enviar mensajes mediante esa cuenta.</p>

            <h2>Uso limitado</h2>
            <p>El uso de información recibida de las APIs de Google cumple la <a href="https://developers.google.com/terms/api-services-user-data-policy" rel="noopener noreferrer">Política de Datos de Usuario de los Servicios API de Google</a>, incluidos sus requisitos de Uso Limitado.</p>

            <h2>Contacto</h2>
            <p>Para consultas sobre privacidad o para solicitar la eliminación de datos, escribe a <a href="mailto:luisanguloruiz23@gmail.com">luisanguloruiz23@gmail.com</a>.</p>
        </article>
    </main>
    <footer><div><a href="{{ route('informacion') }}">Volver a la información de la aplicación</a></div></footer>
</body>
</html>
