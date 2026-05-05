FROM node:22-alpine

WORKDIR /app

RUN apk add --no-cache git python3 make g++

COPY package*.json ./
RUN npm ci --omit=dev

COPY . .

ENV NODE_ENV=production

CMD ["npm", "start"]