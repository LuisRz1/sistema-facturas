<style>
    /* ── UI compartida (modales/toasts) para páginas standalone ── */
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
        box-shadow: 0 8px 32px rgba(0, 0, 0, .18);
        transform: translateY(20px);
        transition: transform .25s ease;
        color: #0f172a;
        font-family: Arial, Helvetica, sans-serif;
        text-align: left;
    }
    .modal-overlay.open .modal { transform: translateY(0); }
    .modal-header { position: relative; background: #171204; color: #fff; padding: 22px 28px; }
    .modal-header h2 { font-size: 20px; font-weight: 700; margin: 0; }
    .modal-header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
    .modal-header .modal-titles { padding-right: 32px; }
    .modal-header--gold { background: linear-gradient(135deg, #f5c842, #e0a800); color: #171204; }
    .modal-header--gold p { color: #4a3c12; }
    .modal-header--danger { background: linear-gradient(135deg, #b91c1c, #dc2626); color: #fff; }
    .modal-header--danger p { color: #fecaca; }
    .modal-header--plain { background: #fff; color: #0f172a; border-bottom: 1px solid #e2e8f0; }
    .modal-header--plain p { color: #64748b; }
    .modal-close {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 26px;
        line-height: 1;
        opacity: .7;
    }
    .modal-close:hover { opacity: 1; }
    .modal-body { padding: 24px 28px; overflow-y: auto; flex: 1; }
    .modal-footer {
        padding: 18px 28px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #fafbfd;
    }
    .modal .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
    }
    .modal .btn-primary { background: #171204; color: #fff; }
    .modal .btn-ghost { background: #fff; color: #475569; border-color: #e2e8f0; }

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
        box-shadow: 0 8px 24px rgba(0, 0, 0, .2);
        font-size: 13px;
        font-weight: 600;
        max-width: 380px;
        pointer-events: auto;
    }
    .crc-toast.ok { background: #065f46; }
    .crc-toast.error { background: #991b1b; }
    .crc-toast.info { background: #1e3a8a; }
</style>
