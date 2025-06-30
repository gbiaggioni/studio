# Stage 1: Builder - Construye la aplicación Next.js
FROM node:20-alpine AS builder

# Establece el directorio de trabajo
WORKDIR /app

# Copia los archivos de definición de paquetes
COPY package*.json ./

# Instala las dependencias de producción de forma limpia
RUN npm ci

# Copia el resto del código fuente
COPY . .

# Construye la aplicación para producción
RUN npm run build

# Stage 2: Runner - Imagen de producción ligera
FROM node:20-alpine AS runner
WORKDIR /app

# Establece el entorno a producción
ENV NODE_ENV=production
# Descomenta la siguiente línea si quieres deshabilitar la telemetría en tiempo de ejecución.
# ENV NEXT_TELEMETRY_DISABLED 1

# Crea un usuario y grupo no-root para mayor seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copia los archivos del build standalone desde la etapa anterior
# Esto incluye solo lo necesario para ejecutar la aplicación
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./

# El modo standalone ya maneja la copia de la carpeta `public` si existe.

# Cambia al usuario no-root
USER nextjs

# Expone el puerto en el que correrá la aplicación dentro del contenedor (por defecto de Next.js)
EXPOSE 3000

# Inicia la aplicación
CMD ["node", "server.js"]
