FROM node:22-bookworm-slim

WORKDIR /app

# Instalar Chromium del sistema (evita que Puppeteer descargue su propio ~280MB Chromium)
RUN apt-get update && apt-get install -y \
    chromium \
    fonts-liberation \
    && rm -rf /var/lib/apt/lists/*

# Saltar descarga de Chromium bundled de Puppeteer
ENV PUPPETEER_SKIP_DOWNLOAD=true
ENV CHROMIUM_PATH=/usr/bin/chromium
ENV NODE_ENV=production

COPY package*.json ./
RUN npm ci --omit=dev

COPY . .

CMD ["npm", "start"]