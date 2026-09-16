# AGENTS.md — Sistema de Facturación

## Alcance y fuente de verdad

- Repositorio canónico: `https://github.com/LuisRz1/sistema-facturas.git`.
- La rama activa y desplegada en Railway es `pruebas`. Antes de trabajar, ejecutar `git fetch --all --prune` y comprobar que `HEAD`, `origin/pruebas` y el despliegue apuntan al mismo commit.
- `main` no es la rama de producción actual. Al 2026-09-15 está 90 commits detrás de `pruebas`.
- No copiar secretos, tokens, contraseñas, credenciales OAuth ni contenido de `.env` a código, documentación, commits, logs o respuestas.
- Memoria técnica complementaria: `D:\Obsidian\00-Indice\Indice Maestro.md`.

## Estructura

- `laravel-app/`: Laravel 12, PHP 8.3, Blade, Vite/Tailwind y MySQL. Es la aplicación principal.
- `whatsapp-worker/`: Node.js ESM, Express y Baileys. Envía mensajes y une PDFs.
- Railway tiene tres servicios en producción: `sistema-facturas`, `Whastapp` y `MySQL`.
- El despliegue de Laravel usa `/laravel-app`, Railpack y `php artisan serve --host=0.0.0.0 --port=$PORT`.
- El worker usa `/whatsapp-worker` y su `Dockerfile`.

## Reglas de seguridad y operación

- Nunca ejecutar `migrate:fresh`, `migrate:reset`, `db:wipe`, `DROP`, `TRUNCATE` ni borrados masivos contra la base conectada. El esquema histórico no se reconstruye solo con las migraciones del repositorio.
- No editar ni versionar `.env`, `vendor/`, `node_modules/`, sesiones de WhatsApp, cachés ni artefactos de compilación.
- No cambiar variables, dominios, despliegues o datos de Railway salvo solicitud explícita. Las inspecciones deben ser de solo lectura y no deben imprimir valores secretos.
- Las nuevas rutas funcionales deben quedar detrás de `auth`, salvo que exista una razón documentada para hacerlas públicas. Mantener protección CSRF en operaciones de escritura.
- Validar y autorizar archivos subidos, especialmente Excel y PDF. Nunca confiar en nombre, MIME o contenido enviados por el navegador.
- Toda modificación de pagos, conciliación o saldos debe usar transacciones y bloqueo adecuado; evitar escrituras parciales.

## Invariantes funcionales

- `factura.monto_pendiente` es la fuente de verdad del saldo. No recalcularlo desde el total si ya existen abonos, recaudaciones, detracciones, extornos o diferencias.
- Los estados pendientes usados por facturación son `PENDIENTE`, `VENCIDO` y `DIFERENCIA PENDIENTE`; revisar cuidadosamente cualquier ampliación porque dashboard y reportes replican esta lógica.
- La lista de facturas pagina 10 por defecto y solo admite 10, 20 o 50 filas. Los filtros deben conservarse al cambiar de página.
- En Railway Hobby el correo de producción usa Gmail API por HTTPS: `MAIL_MAILER=gmail-api`. No volver a SMTP como solución de producción sin comprobar antes las restricciones de red del plan.
- El remitente debe coincidir con la cuenta autorizada por OAuth. Las credenciales esperadas se leen desde `config/services.php` y variables de entorno; nunca se incrustan en el código.
- Los comprobantes persistentes usan almacenamiento S3 compatible. No depender del disco efímero del contenedor.
- El worker de WhatsApp no tiene volumen persistente en Railway; un reinicio puede requerir volver a vincular la sesión. Sus endpoints tampoco tienen autenticación propia en el código actual, por lo que no se deben ampliar ni exponer sin protección.

## Base de datos

- Motor desplegado: MySQL 9.4 con volumen persistente de 5 GB.
- Conviven tablas heredadas en singular y en español (`factura`, `cliente`, `usuario`, etc.) con tablas estándar de Laravel (`users`, `jobs`, `cache`, `sessions`). No renombrarlas por convención sin una migración y plan de compatibilidad.
- Solo hay 10 migraciones versionadas, mientras que la base observada contiene 34 tablas. Antes de alterar el esquema, comparar `Schema`/`INFORMATION_SCHEMA`, modelos y SQL existente.
- Existen dos identidades: `App\Models\Usuario` sobre `usuario`, usada por la autenticación del sistema, y `App\Models\User` sobre `users`. No intercambiarlas accidentalmente.
- La cola usa la base de datos. En producción no hay un proceso worker dedicado documentado; cualquier cambio que despache jobs debe incluir estrategia de ejecución y supervisión.

## Flujo de trabajo

Desde `laravel-app/`:

```powershell
composer install
npm ci
composer test
npm run build
php artisan route:list
php artisan migrate:status
composer audit
npm audit --omit=dev
```

Desde `whatsapp-worker/`:

```powershell
npm ci
npm audit --omit=dev
npm start
```

- No hay suite automatizada del worker; probar al menos `/status` y, cuando corresponda, envío y combinación de PDF en un entorno seguro.
- Para cambios de correo, comprobar el camino alternativo y el camino `gmail-api`, los errores de renovación OAuth y una entrega real a una cuenta de prueba autorizada.
- Para cambios de facturas, probar filtros, totales, tamaños 10/20/50, navegación entre páginas y preservación de query string.
- Para pagos/conciliación, probar pago total, parcial, excedente, extorno, moneda y ejecución repetida/idempotencia.

## Criterio de terminado

1. El diff contiene solo cambios del objetivo y no incluye secretos ni artefactos generados.
2. Las pruebas y el build relevantes pasan, o se documenta exactamente el bloqueo.
3. Se revisan migraciones y rutas cuando el cambio toca datos o endpoints.
4. Se comprueba que el despliegue objetivo sigue la rama/commit esperado.
5. Si cambia arquitectura, operación, esquema o una decisión estable, actualizar también el cerebro de Obsidian.

## Riesgos conocidos que no deben ignorarse

- La auditoría del 2026-09-15 detectó 46 avisos en 15 paquetes PHP; destacan Laravel, PhpSpreadsheet, Dompdf, Guzzle y varios componentes Symfony. Actualizar por lotes pequeños con pruebas de importación, PDF, correo y autenticación.
- El worker de WhatsApp tiene avisos transitivos de npm y carece de autenticación de aplicación y volumen persistente.
- Cuatro rutas de documentos de cotización están fuera del grupo `auth`; revisar autorización y exposición antes de mantenerlas públicas.
- La cobertura actual es muy baja: 5 pruebas y 11 aserciones para un dominio financiero amplio.
- La base observada tenía un job pendiente y cero fallidos; sin worker permanente ese trabajo puede no procesarse.
