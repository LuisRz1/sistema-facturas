<div class="toast-stack" id="crcToastStack" aria-live="polite" aria-atomic="true"></div>

<script>
    // ── UI compartida: modales, feedback y toasts ──────────────────────
    window.CRC = (function () {
        function openModal(id) {
            const el = typeof id === 'string' ? document.getElementById(id) : id;
            if (!el) return;
            el.classList.add('open');
            el.setAttribute('aria-hidden', 'false');
            const focusable = el.querySelector('input:not([type=hidden]), select, textarea, button');
            if (focusable) setTimeout(() => focusable.focus(), 50);
        }

        function closeModal(id) {
            const el = typeof id === 'string' ? document.getElementById(id) : id;
            if (!el) return;
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
        }

        function topOpenModal() {
            const abiertos = document.querySelectorAll('.modal-overlay.open');
            return abiertos.length ? abiertos[abiertos.length - 1] : null;
        }

        function toast(mensaje, tipo = 'info', ms = 4000) {
            const stack = document.getElementById('crcToastStack');
            if (!stack) return;
            const el = document.createElement('div');
            el.className = 'crc-toast ' + tipo;
            el.textContent = mensaje;
            stack.appendChild(el);
            setTimeout(() => {
                el.style.transition = 'opacity .25s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 250);
            }, ms);
        }

        function escapeHtml(texto) {
            const div = document.createElement('div');
            div.textContent = texto == null ? '' : String(texto);
            return div.innerHTML;
        }

        /**
         * Modal de validación / confirmación de registro.
         * opciones: { tipo: 'ok'|'error'|'info', titulo, mensaje, detalles:[], textoOk, onClose }
         */
        function feedback(opciones) {
            const o = typeof opciones === 'string' ? { mensaje: opciones } : (opciones || {});
            const tipo = o.tipo || 'info';
            const theme = tipo === 'ok' ? 'gold' : (tipo === 'error' ? 'danger' : 'plain');
            const icono = tipo === 'ok' ? '✓' : (tipo === 'error' ? '✗' : 'i');
            const id = 'crcFeedbackDialog';
            document.getElementById(id)?.remove();

            const detalles = Array.isArray(o.detalles) ? o.detalles.filter(Boolean) : [];
            const detallesHtml = detalles.length
                ? '<ul style="margin:14px 0 0;padding-left:18px;font-size:13px;line-height:1.7;color:#475569;">'
                    + detalles.map(d => `<li>${escapeHtml(d)}</li>`).join('') + '</ul>'
                : '';

            const wrap = document.createElement('div');
            wrap.id = id;
            wrap.className = 'modal-overlay open';
            wrap.setAttribute('data-modal', '');
            wrap.innerHTML = `
                <div class="modal" style="max-width:520px;">
                    <div class="modal-header modal-header--${theme}">
                        <div class="modal-titles">
                            <h2>${icono} ${escapeHtml(o.titulo || (tipo === 'ok' ? 'Operación realizada' : (tipo === 'error' ? 'No se pudo completar' : 'Información')))}</h2>
                        </div>
                        <button type="button" class="modal-close" data-crc-fb-close aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:14px;line-height:1.65;">${escapeHtml(o.mensaje || '')}</p>
                        ${detallesHtml}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-crc-fb-close>${escapeHtml(o.textoOk || 'Entendido')}</button>
                    </div>
                </div>`;
            document.body.appendChild(wrap);

            wrap.querySelectorAll('[data-crc-fb-close]').forEach(btn => btn.addEventListener('click', () => {
                wrap.remove();
                if (typeof o.onClose === 'function') o.onClose();
            }));
        }

        function confirmDialog(mensaje, onConfirm, opciones = {}) {
            const id = 'crcConfirmDialog';
            document.getElementById(id)?.remove();

            const wrap = document.createElement('div');
            wrap.id = id;
            wrap.className = 'modal-overlay open';
            wrap.setAttribute('data-modal', '');
            wrap.innerHTML = `
                <div class="modal" style="max-width:480px;">
                    <div class="modal-header modal-header--danger">
                        <div class="modal-titles"><h2>${escapeHtml(opciones.titulo || 'Confirmar acción')}</h2></div>
                    </div>
                    <div class="modal-body"><p style="font-size:14px;line-height:1.6;">${escapeHtml(mensaje)}</p></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost" data-crc-cancel>Cancelar</button>
                        <button type="button" class="btn btn-primary" style="background:#dc2626;border-color:#dc2626;" data-crc-ok>${escapeHtml(opciones.textoOk || 'Confirmar')}</button>
                    </div>
                </div>`;
            document.body.appendChild(wrap);

            wrap.querySelector('[data-crc-cancel]').addEventListener('click', () => wrap.remove());
            wrap.querySelector('[data-crc-ok]').addEventListener('click', () => {
                wrap.remove();
                if (typeof onConfirm === 'function') onConfirm();
            });
        }

        // Apertura/cierre declarativo: [data-modal-open="id"], [data-modal-close] y backdrop
        document.addEventListener('click', function (e) {
            const opener = e.target.closest('[data-modal-open]');
            if (opener) {
                e.preventDefault();
                openModal(opener.getAttribute('data-modal-open'));
                return;
            }

            const closer = e.target.closest('[data-modal-close]');
            if (closer) {
                e.preventDefault();
                const overlay = closer.closest('.modal-overlay');
                if (overlay) closeModal(overlay);
                return;
            }

            if (e.target.classList && e.target.classList.contains('modal-overlay')
                && e.target.hasAttribute('data-modal')
                && e.target.getAttribute('data-close-on-backdrop') !== '0') {
                closeModal(e.target);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            const el = topOpenModal();
            if (el && el.getAttribute('data-close-on-escape') !== '0') closeModal(el);
        });

        return { open: openModal, close: closeModal, toast: toast, confirm: confirmDialog, feedback: feedback };
    })();
</script>
