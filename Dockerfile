
# Dockerfile Definitivo para QREasy con Next.js Standalone
# Etapa 1: Builder - Construye la aplicación
FROM node:20-slim AS builder

# Establece el directorio de trabajo en /app
WORKDIR /app

# Instala las dependencias necesarias para construir
COPY package.json package-lock.json* ./
RUN npm install

# Copia el resto del código fuente de la aplicación
COPY . .

# Construye la aplicación. Esto generará la salida en modo 'standalone'
# dentro de la carpeta /app/.next/standalone
RUN npm run build

# Etapa 2: Runner - Ejecuta la aplicación optimizada
FROM node:20-slim AS runner

WORKDIR /app

# Crea un usuario y grupo no-root para mayor seguridad
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copia la salida 'standalone' de la etapa de construcción
COPY --from=builder /app/.next/standalone ./

# El modo 'standalone' no copia la carpeta 'public' ni los assets estáticos,
# por lo que debemos copiarlos manualmente si existen.
# En este proyecto no hay carpeta 'public', así que solo copiamos los assets estáticos.
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Cambia al usuario no-root
USER nextjs

# Expone el puerto 3000
EXPOSE 3000

# Define el puerto que la aplicación usará
ENV PORT=3000

# Comando para iniciar el servidor de Next.js
CMD ["node", "server.js"]
