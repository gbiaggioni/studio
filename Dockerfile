# ---- Fase 1: Builder ----
# Esta fase instala dependencias (incluidas las de desarrollo) y construye la aplicación.
FROM node:20-slim AS builder
WORKDIR /app

# Instalar solo las dependencias primero para aprovechar el caché de Docker
COPY package.json package-lock.json* ./
RUN npm install

# Copiar el resto del código fuente de la aplicación
COPY . .

# Construir la aplicación. Esto necesita las devDependencies.
RUN npm run build

# ---- Fase 2: Runner ----
# Esta fase crea la imagen final, ligera y optimizada para producción.
FROM node:20-slim AS runner
WORKDIR /app

# Crear un usuario y grupo no-root para mayor seguridad
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar los artefactos de la build desde la fase 'builder'
# La configuración 'output: standalone' en next.config.js empaqueta todo lo necesario aquí.
COPY --from=builder /app/.next/standalone ./
# Copiar las carpetas 'public' y '.next/static' (si existen)
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Establecer el propietario de los archivos de la aplicación
USER nextjs

# Exponer el puerto en el que correrá la aplicación
EXPOSE 3000

# Variables de entorno para el puerto
ENV PORT=3000

# Comando para iniciar el servidor de Next.js
CMD ["node", "server.js"]
