# ── Etapa 1: instalar dependencias (con herramientas de compilación) ─────
FROM node:22-alpine AS builder

WORKDIR /app

# git + build tools necesarios para compilar módulos nativos de baileys
RUN apk add --no-cache git python3 make g++

COPY package*.json ./
# --omit=optional omite 'sharp' (~150MB RAM, no necesario para texto/PDF)
RUN npm install --omit=dev --omit=optional --no-audit --no-fund

# ── Etapa 2: imagen final sin build tools ────────────────────────────────
FROM node:22-alpine

WORKDIR /app

COPY --from=builder /app/node_modules ./node_modules
COPY package*.json ./
COPY server.js ./

ENV NODE_ENV=production

CMD ["npm", "start"]