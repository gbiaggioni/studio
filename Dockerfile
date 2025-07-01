# Dockerfile para QREasy - Entorno de Producción Optimizado

# ------------------ BUILDER ------------------
# Esta etapa instala dependencias y construye la aplicación.
FROM node:20-slim AS builder

WORKDIR /app

# Instalar dependencias
COPY package.json package-lock.json* ./
RUN npm ci

# Copiar el resto de los archivos del proyecto
COPY . .

# Validar que .env.local existe ANTES de construir para evitar errores en el despliegue.
# Aunque no se usa en la build 'standalone', es un paso crítico para el usuario.
RUN if [ ! -f .env.local ]; then \
      echo ""; \
      echo "--> ERROR: El archivo .env.local no existe."; \
      echo "--> Por favor, créalo ejecutando el script ./configure-env.sh ANTES de construir la imagen."; \
      echo ""; \
      exit 1; \
    fi

# Construir la aplicación para producción
RUN npm run build

# ------------------ RUNNER ------------------
# Esta etapa toma solo los artefactos de la build para una imagen final ligera.
# Las variables de entorno se deben proporcionar en el comando `docker run`.
FROM node:20-slim AS runner

WORKDIR /app

# Copiar los archivos de la aplicación construida desde la etapa 'builder'
COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static

# El puerto 3000 es el que expone la aplicación Next.js
EXPOSE 3000

# El comando para iniciar la aplicación en modo producción.
# El host 0.0.0.0 es necesario para que Docker exponga el puerto correctamente.
CMD ["node", "server.js"]
