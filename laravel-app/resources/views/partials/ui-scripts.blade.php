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

        // ── Loader global (círculo de carga) ──────────────────────────────
        let cargandoPendientes = 0;
        let cargandoTimer = null;
        const loader = document.createElement('div');
        loader.id = 'crcLoader';
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = '<div class="crc-spinner" role="status" aria-label="Cargando"></div>';
        (function () {
            const estilo = document.createElement('style');
            estilo.textContent = '#crcLoader{position:fixed;inset:0;background:rgba(15,23,42,.35);display:none;align-items:center;justify-content:center;z-index:100000;}#crcLoader.show{display:flex;}#crcLoader .crc-spinner{width:46px;height:46px;border:4px solid rgba(255,255,255,.45);border-top-color:#f5c842;border-radius:50%;animation:crcSpin .8s linear infinite;}@keyframes crcSpin{to{transform:rotate(360deg);}}';
            document.head.appendChild(estilo);
        })();
        function montarLoader() {
            if (document.body && !document.getElementById('crcLoader')) document.body.appendChild(loader);
        }
        if (document.body) montarLoader(); else document.addEventListener('DOMContentLoaded', montarLoader);

        const loading = {
            start(espera = 200) {
                cargandoPendientes++;
                if (cargandoTimer) return;
                cargandoTimer = setTimeout(() => { cargandoTimer = null; if (cargandoPendientes > 0) loader.classList.add('show'); }, espera);
            },
            stop() {
                cargandoPendientes = Math.max(0, cargandoPendientes - 1);
                if (cargandoPendientes === 0) {
                    if (cargandoTimer) { clearTimeout(cargandoTimer); cargandoTimer = null; }
                    loader.classList.remove('show');
                }
            },
            show() {
                if (cargandoTimer) { clearTimeout(cargandoTimer); cargandoTimer = null; }
                loader.classList.add('show');
            },
            hide() {
                cargandoPendientes = 0;
                if (cargandoTimer) { clearTimeout(cargandoTimer); cargandoTimer = null; }
                loader.classList.remove('show');
            },
        };

        // Envuelve fetch para mostrar el loader cuando una petición tarda.
        if (window.fetch) {
            const fetchOriginal = window.fetch.bind(window);
            window.fetch = function (...args) {
                loading.start();
                return fetchOriginal(...args).finally(() => loading.stop());
            };
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

        // Un modal solo se cierra con una acción explícita. El capturador se
        // registra antes de los scripts de cada vista para neutralizar también
        // manejadores heredados que cerraban al pulsar el fondo.
        document.addEventListener('click', function (e) {
            if (e.target.classList && e.target.classList.contains('modal-overlay')) {
                e.stopPropagation();
            }
        }, true);

        // Apertura/cierre declarativo: [data-modal-open="id"] y [data-modal-close].
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

        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !topOpenModal()) return;
            e.preventDefault();
            e.stopPropagation();
        }, true);

        // Evita doble envío: al enviar un formulario, deshabilita su botón.
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            const boton = form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
            if (!boton) return;
            setTimeout(() => {
                boton.disabled = true;
                boton.dataset.submitting = '1';
            }, 0);
        }, true);

        return { open: openModal, close: closeModal, toast: toast, confirm: confirmDialog, feedback: feedback, loading: loading };
    })();
</script>
