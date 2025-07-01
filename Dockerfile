# Dockerfile

# Stage 1: Builder
# ----------------
FROM node:20-slim AS builder
WORKDIR /app

# Copia los archivos de manifiesto del paquete e instala las dependencias
COPY package.json package-lock.json* ./
RUN npm install

# Copia el resto del código fuente
COPY . .

# Construye la aplicación
RUN npm run build

# Stage 2: Runner
# --------------
FROM node:20-slim AS runner
WORKDIR /app

# Crea un usuario y grupo no-root para mayor seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copia solo los artefactos de build necesarios desde la etapa 'builder'
# Esto crea una imagen final mucho más pequeña y segura.
COPY --from=builder /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Establece el usuario no-root
USER nextjs

# Expone el puerto en el que se ejecutará la aplicación
ENV PORT=3000
EXPOSE 3000

# El comando para iniciar la aplicación.
# Se ha añadido un paso de depuración para imprimir todas las variables de entorno
# que el contenedor está viendo. Esto es para diagnosticar el problema de las credenciales.
CMD ["sh", "-c", "echo '--- [QREASY_DOCKER_DEBUG] Imprimiendo variables de entorno ---' && printenv && echo '--- [QREASY_DOCKER_DEBUG] Iniciando servidor Next.js ---' && node server.js"]
