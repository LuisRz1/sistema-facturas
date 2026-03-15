const express = require('express');
const qrcode = require('qrcode-terminal');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

const app = express();
app.use(express.json());

let listo = false;

const client = new Client({
    authStrategy: new LocalAuth({ clientId: 'facturacion-local' }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

client.on('qr', (qr) => {
    console.log('\nEscanea este QR con WhatsApp:\n');
    qrcode.generate(qr, { small: true });
});

client.on('authenticated', () => {
    console.log('Sesión autenticada');
});

client.on('ready', () => {
    listo = true;
    console.log('WhatsApp listo');
});

client.on('auth_failure', (msg) => {
    listo = false;
    console.error('Fallo de autenticación:', msg);
});

client.on('disconnected', (reason) => {
    listo = false;
    console.log('WhatsApp desconectado:', reason);
});

client.initialize();

app.get('/status', (req, res) => {
    res.json({ ok: true, listo });
});

// ─── Enviar mensaje de texto, imagen o documento PDF ─────────────────────────
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, imageUrl, documentUrl, fileName } = req.body;

        if (!listo) {
            return res.status(503).json({ ok: false, error: 'WhatsApp no está listo' });
        }

        if (!phone) {
            return res.status(400).json({ ok: false, error: 'phone es obligatorio' });
        }

        const chatId = `${phone}@c.us`;

        // ── CASO 1: Documento PDF (reporte financiero) ────────────────────────
        if (documentUrl) {
            try {
                console.log(`[DOC] Enviando PDF a ${chatId}: ${documentUrl}`);
                const media = await MessageMedia.fromUrl(documentUrl, { unsafeMime: true });

                // Asignar nombre de archivo legible al receptor
                if (fileName) {
                    media.filename = fileName;
                }

                const sentMessage = await client.sendMessage(chatId, media, {
                    sendMediaAsDocument: true,   // <-- clave: envía como archivo, no como imagen
                    caption: message || '',
                });

                return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'documento' });
            } catch (docError) {
                console.error('Error enviando documento:', docError.message);
                return res.status(500).json({ ok: false, error: docError.message, tipo: 'documento' });
            }
        }

        // ── CASO 2: Imagen (comprobante de pago) ──────────────────────────────
        if (imageUrl) {
            try {
                const media = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                const sentMessage = await client.sendMessage(chatId, media, { caption: message });
                return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'imagen' });
            } catch (imgError) {
                console.error('Error enviando imagen, enviando solo texto:', imgError.message);
                const textoConUrl = `${message}\n\n📎 Ver comprobante:\n${imageUrl}`;
                const sentMessage = await client.sendMessage(chatId, textoConUrl);
                return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'texto_fallback', warning: imgError.message });
            }
        }

        // ── CASO 3: Texto simple ──────────────────────────────────────────────
        if (!message) {
            return res.status(400).json({ ok: false, error: 'Se requiere message, imageUrl o documentUrl' });
        }

        const sentMessage = await client.sendMessage(chatId, message);
        return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'texto' });

    } catch (error) {
        return res.status(500).json({ ok: false, error: error.message });
    }
});

app.listen(3001, () => {
    console.log('Worker WhatsApp escuchando en http://localhost:3001');
});