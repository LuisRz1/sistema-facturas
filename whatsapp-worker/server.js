const express = require('express');
const qrcode = require('qrcode-terminal');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3001;
let listo = false;

const client = new Client({
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

                if (fileName) {
                    media.filename = fileName;
                }

                const sentMessage = await client.sendMessage(chatId, media, {
                    sendMediaAsDocument: true,
                    caption: message || '',
                });

                return res.json({
                    ok: true,
                    id: sentMessage.id._serialized,
                    tipo: 'documento'
                });
            } catch (docError) {
                console.error('Error enviando documento:', docError.message);
                return res.status(500).json({
                    ok: false,
                    error: docError.message,
                    tipo: 'documento'
                });
            }
        }

        if (imageUrl) {
            try {
                const media = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                const sentMessage = await client.sendMessage(chatId, media, {
                    caption: message || ''
                });

                return res.json({
                    ok: true,
                    id: sentMessage.id._serialized,
                    tipo: 'imagen'
                });
            } catch (imgError) {
                console.error('Error enviando imagen, enviando solo texto:', imgError.message);
                const textoConUrl = `${message || ''}\n\nVer comprobante:\n${imageUrl}`;
                const sentMessage = await client.sendMessage(chatId, textoConUrl.trim());

                return res.json({
                    ok: true,
                    id: sentMessage.id._serialized,
                    tipo: 'texto_fallback',
                    warning: imgError.message
                });
            }
        }

        if (!message) {
            return res.status(400).json({
                ok: false,
                error: 'Se requiere message, imageUrl o documentUrl'
            });
        }

        const sentMessage = await client.sendMessage(chatId, message);

        return res.json({
            ok: true,
            id: sentMessage.id._serialized,
            tipo: 'texto'
        });
    } catch (error) {
        console.error('Error general:', error);
        return res.status(500).json({ ok: false, error: error.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Worker WhatsApp escuchando en puerto ${PORT}`);
});