FROM node:22-alpine

WORKDIR /app

RUN apk add --no-cache git python3 make g++

COPY package*.json ./
RUN npm install --omit=dev --no-audit --no-fund

COPY . .

ENV NODE_ENV=production

CMD ["npm", "start"]