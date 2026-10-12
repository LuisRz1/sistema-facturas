{{--
    Fuente única de estilos de modales y toasts para TODO el sistema.
    Se incluye desde layouts/app.blade.php y desde las páginas standalone.
    No duplicar estas reglas en vistas ni en el layout.
    Requisito del sistema: un modal NO se cierra al pulsar el fondo ni con Escape.
--}}
<style>
    /* ── MODALES ─────────────────────────────────────────────────────────── */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .6);
        backdrop-filter: blur(4px);
        z-index: 200;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .28s cubic-bezier(.16, 1, .3, 1), backdrop-filter .28s ease;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    /* Bloquea el scroll de la página mientras haya un modal abierto, sin importar
       quién lo abra. El toggle de `body.crc-modal-open` en JS es el respaldo. */
    body:has(.modal-overlay.open) { overflow: hidden; }
    body.crc-modal-open { overflow: hidden; }

    .modal {
        background: #fff;
        border-radius: 16px;
        width: 100%;
        max-width: var(--modal-w, 680px);
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        color: var(--text-primary, #0f172a);
        box-shadow: 0 24px 64px rgba(15, 23, 42, .28), 0 2px 8px rgba(15, 23, 42, .12);
        transform: translateY(16px) scale(.97);
        opacity: 0;
        transition: transform .28s cubic-bezier(.16, 1, .3, 1), opacity .28s ease;
    }
    .modal-overlay.open .modal { transform: translateY(0) scale(1); opacity: 1; }
    @media (prefers-reduced-motion: reduce) {
        .modal-overlay, .modal-overlay .modal { transition: none !important; }
    }

    /* Tamaños canónicos. Usar el prop `size` del componente modal. */
    .modal--sm { --modal-w: 460px; }
    .modal--md { --modal-w: 680px; }
    .modal--lg { --modal-w: 880px; }
    .modal--xl { --modal-w: 1080px; }

    .modal-header {
        position: relative;
        flex: none;
        background: var(--sidebar-bg, #171204);
        color: #fff;
        padding: 22px 28px;
    }
    .modal-header h2 { font-size: 20px; font-weight: 700; line-height: 1.3; }
    .modal-header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
    .modal-header .modal-titles { padding-right: 40px; min-width: 0; }

    .modal-close {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 10px;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
        opacity: .7;
        transition: opacity .15s, background .15s;
    }
    .modal-close:hover { opacity: 1; background: rgba(255, 255, 255, .14); }
    .modal-close:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }

    .modal-header--gold { background: linear-gradient(135deg, #f5c842, #e0a800); color: #171204; }
    .modal-header--gold p { color: #4a3c12; }
    .modal-header--gold .modal-close:hover { background: rgba(0, 0, 0, .12); }
    .modal-header--danger { background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; }
    .modal-header--danger p { color: #fecaca; }
    .modal-header--plain { background: #fff; color: var(--text-primary, #0f172a); border-bottom: 1px solid var(--border, #e2e8f0); }
    .modal-header--plain p { color: var(--text-muted, #64748b); }
    .modal-header--plain .modal-close:hover { background: #f1f5f9; }

    .modal-body { flex: 1 1 auto; min-height: 0; padding: 26px 28px; overflow-y: auto; }
    .modal-body > :first-child { margin-top: 0; }
    .modal-body > :last-child { margin-bottom: 0; }

    .modal-footer {
        flex: none;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 18px 28px;
        border-top: 1px solid var(--border, #e2e8f0);
        background: #fafbfd;
    }

    /* Botones canónicos dentro de un modal. */
    .modal .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 18px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
        transition: filter .15s, background .15s, border-color .15s;
    }
    .modal .btn:disabled { opacity: .55; cursor: not-allowed; }
    .modal .btn-primary { background: var(--sidebar-bg, #171204); color: #fff; }
    .modal .btn-primary:hover:not(:disabled) { filter: brightness(1.3); }
    .modal .btn-ghost { background: #fff; color: #475569; border-color: var(--border, #e2e8f0); }
    .modal .btn-ghost:hover:not(:disabled) { background: #f8fafc; }
    .modal .btn-danger { background: #dc2626; color: #fff; }
    .modal .btn-danger:hover:not(:disabled) { filter: brightness(1.12); }
    .modal .btn-gold { background: linear-gradient(135deg, #f5c842, #e0a800); color: #171204; }
    .modal .btn-gold:hover:not(:disabled) { filter: brightness(1.06); }

    @media (max-width: 640px) {
        .modal-overlay { padding: 0; align-items: flex-end; }
        .modal { max-height: 92vh; border-radius: 16px 16px 0 0; }
        .modal-header { padding: 18px 20px; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 16px 20px; }
        .modal-footer .btn { flex: 1 1 auto; }
    }

    /* ── BOTÓN FLOTANTE (esquina inferior derecha) ───────────────────────── */
    .crc-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 150; /* por debajo de modales (200) y toasts (400) */
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 14px 22px;
        border: none;
        border-radius: 999px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .28), 0 2px 6px rgba(15, 23, 42, .14);
        transition: transform .15s ease, filter .15s ease;
    }
    .crc-fab:hover { transform: translateY(-2px); filter: brightness(1.06); }
    .crc-fab:focus-visible { outline: 3px solid rgba(245, 200, 66, .4); outline-offset: 2px; }
    .crc-fab--pendiente { background: linear-gradient(135deg, #f5c842, #e0a800); color: #171204; }
    .crc-fab--completo { background: var(--sidebar-bg, #171204); color: #fff; }
    /* Solo se muestra el icono que corresponde al estado actual. */
    .crc-fab--completo .crc-fab-ico--ok,
    .crc-fab--pendiente .crc-fab-ico--back { display: none; }
    /* Sube los toasts para que el FAB no los tape. */
    body:has(.crc-fab) .toast-stack { bottom: 96px; }

    @media (max-width: 640px) {
        .crc-fab { right: 16px; bottom: 16px; padding: 13px 18px; }
        body:has(.crc-fab) .toast-stack { bottom: 88px; }
    }

    /* ── TOASTS ──────────────────────────────────────────────────────────── */
    .toast-stack {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 400;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }
    .crc-toast {
        background: #0f172a;
        color: #fff;
        padding: 12px 16px;
        border-radius: 10px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .28);
        font-size: 13px;
        font-weight: 600;
        max-width: 380px;
        pointer-events: auto;
        animation: crcToastIn .2s ease;
    }
    .crc-toast.ok { background: #065f46; }
    .crc-toast.error { background: #991b1b; }
    .crc-toast.info { background: #1e3a8a; }
    @keyframes crcToastIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
</style>
