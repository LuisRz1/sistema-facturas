const express = require('express');
const qrcode = require('qrcode-terminal');
const { Client, LocalAuth } = require('whatsapp-web.js');

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

app.post('/send-message', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!listo) {
            return res.status(503).json({ ok: false, error: 'WhatsApp no está listo' });
        }

        if (!phone || !message) {
            return res.status(400).json({ ok: false, error: 'phone y message son obligatorios' });
        }

        const chatId = `${phone}@c.us`;
        const sentMessage = await client.sendMessage(chatId, message);

        return res.json({
            ok: true,
            id: sentMessage.id._serialized
        });
    } catch (error) {
        return res.status(500).json({
            ok: false,
            error: error.message
        });
    }
});

app.listen(3001, () => {
    console.log('Worker WhatsApp escuchando en http://localhost:3001');
});