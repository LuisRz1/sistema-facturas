import express from 'express';
import qrcode from 'qrcode-terminal';
import QRCode from 'qrcode';
import { PDFDocument } from 'pdf-lib';
import {
    makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import pino from 'pino';
import fs from 'fs';

const app = express();
app.use(express.json({ limit: '80mb' }));

const PORT     = process.env.PORT || 3001;
const AUTH_DIR = process.env.AUTH_DIR || './auth_state';
const logger   = pino({ level: 'silent' });

let listo      = false;
let qrActual   = null;
let sock       = null;
let restarting = false;

// ── Destruir socket anterior (libera recursos de red) ────────────────────
async function destruirSocket() {
    if (!sock) return;
    const old = sock;
    sock     = null;
    listo    = false;
    qrActual = null;
    try { old.ev.removeAllListeners(); } catch (e) {}
    try { await old.end(new Error('Recreando cliente')); } catch (e) {}
}

// ── Crear / Recrear conexión WhatsApp ────────────────────────────────────
async function crearCliente(eliminarAuth = false) {
    if (restarting) return;
    restarting = true;

    try {
        await destruirSocket();

        if (eliminarAuth && fs.existsSync(AUTH_DIR)) {
            fs.rmSync(AUTH_DIR, { recursive: true, force: true });
            console.log('Auth state eliminado para nuevo QR');
        }

        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version }          = await fetchLatestBaileysVersion();

        sock = makeWASocket({
            version,
            auth:                  state,
            logger,
            printQRInTerminal:     false,
            browser:               ['sistema-facturas', 'Chrome', '126.0.0'],
            connectTimeoutMs:      30_000,
            defaultQueryTimeoutMs: 60_000,
            keepAliveIntervalMs:   30_000,
            markOnlineOnConnect:   false,
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrActual = qr;
                console.log('\nEscanea este QR con WhatsApp:\n');
                qrcode.generate(qr, { small: true });
            }

            if (connection === 'open') {
                listo    = true;
                qrActual = null;
                console.log('WhatsApp listo');
            }

            if (connection === 'close') {
                listo    = false;
                qrActual = null;
                const code = new Boom(lastDisconnect?.error)?.output?.statusCode;
                console.log('WhatsApp desconectado, código:', code);

                if (code === DisconnectReason.loggedOut) {
                    console.log('Sesión cerrada (loggedOut). Esperando reconexión manual.');
                } else {
                    setTimeout(() => crearCliente(), 5000);
                }
            }
        });

    } finally {
        restarting = false;
    }
}

// Iniciar al arrancar
crearCliente();

// ── GET /status ───────────────────────────────────────────────────────────
app.get('/status', (req, res) => {
    res.json({
        ok          : true,
        listo,
        esperandoQr : qrActual !== null,
    });
});

// ── GET /qr ───────────────────────────────────────────────────────────────
app.get('/qr', async (req, res) => {
    if (listo) {
        return res.json({ ok: true, listo: true, message: 'Ya conectado, no se necesita QR.' });
    }
    if (!qrActual) {
        return res.json({ ok: false, listo: false, message: 'QR no disponible todavía. Espera unos segundos.' });
    }
    try {
        const dataUrl = await QRCode.toDataURL(qrActual, { width: 256, margin: 2 });
        res.json({ ok: true, listo: false, qr_data_url: dataUrl });
    } catch (err) {
        res.status(500).json({ ok: false, error: err.message });
    }
});

// ── POST /logout ──────────────────────────────────────────────────────────
app.post('/logout', async (req, res) => {
    try {
        listo    = false;
        qrActual = null;

        if (sock) {
            try { await sock.logout(); } catch (e) {
                console.log('Logout ignorado:', e.message);
            }
        }

        console.log('Reinicializando para nuevo QR...');
        setTimeout(() => crearCliente(true), 2000);

        res.json({ ok: true, message: 'Sesión cerrada. Generando nuevo QR en 2 segundos...' });
    } catch (err) {
        console.error('Error en logout:', err.message);
        setTimeout(() => crearCliente(true), 3000);
        res.status(500).json({ ok: false, error: err.message });
    }
});

// ── POST /send-message ────────────────────────────────────────────────────
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, imageUrl, documentUrl, fileName } = req.body;

        if (!listo || !sock) {
            return res.status(503).json({ ok: false, error: 'WhatsApp no está listo' });
        }
        if (!phone) {
            return res.status(400).json({ ok: false, error: 'phone es obligatorio' });
        }

        const phoneClean = String(phone).replace(/\D/g, '');
        const jid        = `${phoneClean}@s.whatsapp.net`;

        if (documentUrl) {
            try {
                console.log(`[DOC] Descargando PDF: ${documentUrl}`);
                const response = await fetch(documentUrl);
                if (!response.ok) throw new Error(`HTTP ${response.status} al descargar PDF`);
                const buffer = Buffer.from(await response.arrayBuffer());
                const sent   = await sock.sendMessage(jid, {
                    document : buffer,
                    fileName : fileName || 'documento.pdf',
                    mimetype : 'application/pdf',
                    caption  : message || '',
                });
                return res.json({ ok: true, id: sent.key.id, tipo: 'documento' });
            } catch (docError) {
                console.error('Error enviando documento:', docError.message);
                return res.status(500).json({ ok: false, error: docError.message, tipo: 'documento' });
            }
        }

        if (imageUrl) {
            try {
                const response = await fetch(imageUrl);
                if (!response.ok) throw new Error(`HTTP ${response.status} al descargar imagen`);
                const buffer = Buffer.from(await response.arrayBuffer());
                const sent   = await sock.sendMessage(jid, {
                    image   : buffer,
                    caption : message || '',
                });
                return res.json({ ok: true, id: sent.key.id, tipo: 'imagen' });
            } catch (imgError) {
                console.error('Error enviando imagen, fallback a texto:', imgError.message);
                const textoConUrl = `${message || ''}\n\nVer comprobante:\n${imageUrl}`;
                const sent = await sock.sendMessage(jid, { text: textoConUrl.trim() });
                return res.json({ ok: true, id: sent.key.id, tipo: 'texto_fallback', warning: imgError.message });
            }
        }

        if (!message) {
            return res.status(400).json({ ok: false, error: 'Se requiere message, imageUrl o documentUrl' });
        }

        const sent = await sock.sendMessage(jid, { text: message });
        return res.json({ ok: true, id: sent.key.id, tipo: 'texto' });

    } catch (error) {
        console.error('Error general:', error);
        return res.status(500).json({ ok: false, error: error.message });
    }
});

// ── POST /merge-pdfs ─────────────────────────────────────────────────────
app.post('/merge-pdfs', async (req, res) => {
    try {
        const { files } = req.body || {};

        if (!Array.isArray(files) || files.length < 2) {
            return res.status(400).json({ ok: false, error: 'Se requieren al menos 2 archivos PDF en files[].' });
        }

        const outDoc = await PDFDocument.create();

        for (const file of files) {
            const b64 = (file && typeof file.base64 === 'string') ? file.base64 : '';
            if (!b64) continue;

            let bytes;
            try { bytes = Buffer.from(b64, 'base64'); } catch (e) { continue; }
            if (!bytes || bytes.length === 0) continue;

            const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
            const pages  = await outDoc.copyPages(srcDoc, srcDoc.getPageIndices());
            pages.forEach((p) => outDoc.addPage(p));
        }

        if (outDoc.getPageCount() === 0) {
            return res.status(422).json({ ok: false, error: 'No se pudieron procesar páginas PDF válidas.' });
        }

        const outBytes = await outDoc.save();
        return res.json({
            ok: true,
            pageCount: outDoc.getPageCount(),
            pdfBase64: Buffer.from(outBytes).toString('base64'),
        });
    } catch (error) {
        console.error('Error en /merge-pdfs:', error);
        return res.status(500).json({ ok: false, error: error.message || String(error) });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Worker WhatsApp (Baileys) escuchando en puerto ${PORT}`);
});