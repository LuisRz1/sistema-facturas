const express = require('express');
const qrcode = require('qrcode-terminal');
const QRCode = require('qrcode');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const { PDFDocument } = require('pdf-lib');

const app = express();
app.use(express.json({ limit: '80mb' }));

const PORT = process.env.PORT || 3001;
let listo    = false;
let qrActual = null;
let client   = null;

// ── Crear / Recrear el cliente WhatsApp ──────────────────────────────────
function crearCliente() {
    if (client) {
        try { client.removeAllListeners(); } catch(e) {}
    }

    listo    = false;
    qrActual = null;

    client = new Client({
        authStrategy: new LocalAuth({ clientId: 'facturacion-local' }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-first-run',
                '--no-zygote',
                '--single-process'
            ]
        }
    });

    client.on('qr', (qr) => {
        qrActual = qr;
        console.log('\nEscanea este QR con WhatsApp:\n');
        qrcode.generate(qr, { small: true });
    });

    client.on('authenticated', () => {
        qrActual = null;
        console.log('Sesión autenticada');
    });

    client.on('ready', () => {
        listo    = true;
        qrActual = null;
        console.log('WhatsApp listo');
    });

    client.on('auth_failure', (msg) => {
        listo    = false;
        qrActual = null;
        console.error('Fallo de autenticación:', msg);
        // Reintentar en 5 segundos
        setTimeout(() => crearCliente(), 5000);
    });

    client.on('disconnected', (reason) => {
        listo    = false;
        qrActual = null;
        console.log('WhatsApp desconectado:', reason);
        // Si fue desconectado (no por logout manual), reinicializar para mostrar QR
        if (reason !== 'LOGOUT') {
            setTimeout(() => crearCliente(), 3000);
        }
    });

    client.initialize().catch(err => {
        console.error('Error al inicializar:', err.message);
        setTimeout(() => crearCliente(), 5000);
    });
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
// Cierra la sesión actual Y reinicializa el cliente para generar nuevo QR
app.post('/logout', async (req, res) => {
    try {
        listo    = false;
        qrActual = null;

        if (client) {
            try {
                await client.logout();
                console.log('Sesión cerrada, destruyendo cliente...');
            } catch(e) {
                console.log('Error en logout (ignorado):', e.message);
            }

            try {
                await client.destroy();
                console.log('Cliente destruido.');
            } catch(e) {
                console.log('Error en destroy (ignorado):', e.message);
            }
        }

        // Esperar un momento y reinicializar para generar nuevo QR
        console.log('Reinicializando cliente para nuevo QR...');
        setTimeout(() => crearCliente(), 2000);

        res.json({ ok: true, message: 'Sesión cerrada. Generando nuevo QR en 2 segundos...' });

    } catch (err) {
        console.error('Error en logout:', err.message);
        // Intentar reinicializar de todas formas
        setTimeout(() => crearCliente(), 3000);
        res.status(500).json({ ok: false, error: err.message });
    }
});

// ── POST /send-message ────────────────────────────────────────────────────
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, imageUrl, documentUrl, fileName } = req.body;

        if (!listo) {
            return res.status(503).json({ ok: false, error: 'WhatsApp no está listo' });
        }
        if (!phone) {
            return res.status(400).json({ ok: false, error: 'phone es obligatorio' });
        }

        const phoneClean = String(phone).replace(/\D/g, '');
        const chatId = `${phoneClean}@c.us`;

        if (documentUrl) {
            try {
                console.log(`[DOC] Enviando PDF a ${chatId}: ${documentUrl}`);
                const media = await MessageMedia.fromUrl(documentUrl, { unsafeMime: true });
                if (fileName) media.filename = fileName;
                const sent = await client.sendMessage(chatId, media, {
                    sendMediaAsDocument: true,
                    caption: message || '',
                });
                return res.json({ ok: true, id: sent.id._serialized, tipo: 'documento' });
            } catch (docError) {
                console.error('Error enviando documento:', docError.message);
                return res.status(500).json({ ok: false, error: docError.message, tipo: 'documento' });
            }
        }

        if (imageUrl) {
            try {
                const media = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                const sent  = await client.sendMessage(chatId, media, { caption: message || '' });
                return res.json({ ok: true, id: sent.id._serialized, tipo: 'imagen' });
            } catch (imgError) {
                console.error('Error enviando imagen, fallback a texto:', imgError.message);
                const textoConUrl = `${message || ''}\n\nVer comprobante:\n${imageUrl}`;
                const sent = await client.sendMessage(chatId, textoConUrl.trim());
                return res.json({ ok: true, id: sent.id._serialized, tipo: 'texto_fallback', warning: imgError.message });
            }
        }

        if (!message) {
            return res.status(400).json({ ok: false, error: 'Se requiere message, imageUrl o documentUrl' });
        }

        const sent = await client.sendMessage(chatId, message);
        return res.json({ ok: true, id: sent.id._serialized, tipo: 'texto' });

    } catch (error) {
        console.error('Error general:', error);
        return res.status(500).json({ ok: false, error: error.message });
    }
});

// ── POST /merge-pdfs ─────────────────────────────────────────────────────
// Endpoint utilitario para fusionar PDFs (sin afectar lógica de WhatsApp).
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
            try {
                bytes = Buffer.from(b64, 'base64');
            } catch (e) {
                continue;
            }

            if (!bytes || bytes.length === 0) continue;

            const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
            const pages = await outDoc.copyPages(srcDoc, srcDoc.getPageIndices());
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
    console.log(`Worker WhatsApp escuchando en puerto ${PORT}`);
});