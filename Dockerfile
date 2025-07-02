# ------------------ BUILDER ------------------
# Usa una imagen oficial de Node.js como base.
FROM node:20-slim AS builder

# Establece el directorio de trabajo en la app.
WORKDIR /app

# Copia los archivos de definición de dependencias.
COPY package*.json ./

# Instala las dependencias. `npm ci` es más rápido y seguro para builds.
RUN npm ci

# Copia el resto de los archivos de la aplicación.
# (El .dockerignore se encarga de no copiar node_modules, etc.)
COPY . .

# ANTES de construir, asegúrate de que el .env.local exista.
# El script de despliegue DEBE haberlo creado.
RUN if [ ! -f .env.local ]; then \
      echo ""; \
      echo "--> ERROR: El archivo .env.local no existe."; \
      echo "--> Por favor, créalo ejecutando './configure-env.sh' ANTES de construir la imagen."; \
      echo ""; \
      exit 1; \
    fi

# Construye la aplicación para producción.
RUN npm run build

# ------------------ RUNNER ------------------
# Usa una imagen de Node.js más pequeña para producción.
FROM node:20-slim AS runner

# Establece el directorio de trabajo.
WORKDIR /app

# Establece el entorno a producción.
ENV NODE_ENV=production

# Copia el archivo .env.local desde el contexto de build original.
# Next.js lo leerá automáticamente.
COPY .env.local ./.env.local

# Copia los artefactos de build desde la etapa 'builder'.
# Next.js genera una versión "standalone" para despliegues ligeros.
COPY --from=builder /app/public ./public
COPY --from=builder /app/next.config.js ./next.config.js
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

# Expone el puerto 3000, que es el que usa Next.js por defecto.
EXPOSE 3000

# El comando para iniciar la aplicación.
CMD ["node", "server.js"]
