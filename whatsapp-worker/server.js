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

// ─── Enviar mensaje de texto o imagen ────────────────────────────────────────
app.post('/send-message', async (req, res) => {
    try {
        const { phone, message, imageUrl } = req.body;

        if (!listo) {
            return res.status(503).json({ ok: false, error: 'WhatsApp no está listo' });
        }

        if (!phone || !message) {
            return res.status(400).json({ ok: false, error: 'phone y message son obligatorios' });
        }

        const chatId = `${phone}@c.us`;

        // Si viene imageUrl, enviar como imagen con caption
        if (imageUrl) {
            try {
                const media = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                const sentMessage = await client.sendMessage(chatId, media, { caption: message });
                return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'imagen' });
            } catch (imgError) {
                console.error('Error enviando imagen, enviando solo texto:', imgError.message);
                // Si falla la imagen, manda el texto con la URL al final
                const textoConUrl = `${message}\n\n📎 Ver comprobante:\n${imageUrl}`;
                const sentMessage = await client.sendMessage(chatId, textoConUrl);
                return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'texto_fallback', warning: imgError.message });
            }
        }

        // Envío de texto simple
        const sentMessage = await client.sendMessage(chatId, message);
        return res.json({ ok: true, id: sentMessage.id._serialized, tipo: 'texto' });

    } catch (error) {
        return res.status(500).json({ ok: false, error: error.message });
    }
});

app.listen(3001, () => {
    console.log('Worker WhatsApp escuchando en http://localhost:3001');
});