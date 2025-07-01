# ------------------ BUILDER ------------------
FROM node:20-slim AS builder

WORKDIR /app

COPY package.json package-lock.json* ./

RUN npm ci

COPY . .

RUN if [ ! -f .env.local ]; then \
      echo ""; \
      echo "--> ERROR: El archivo .env.local no existe."; \
      echo "--> Por favor, créalo ejecutando el script './configure-env.sh' antes de construir la imagen."; \
      echo ""; \
      exit 1; \
    fi

RUN npm run build

# ------------------ RUNNER ------------------
FROM node:20-slim

WORKDIR /app

COPY --from=builder /app/public ./public
# Copia el archivo .env.local al runner para que las variables de entorno estén disponibles en tiempo de ejecución.
COPY --from=builder /app/.env.local ./.env.local
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

EXPOSE 3000

CMD ["node", "server.js"]
