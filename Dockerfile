# Dockerfile

# Etapa 1: Builder - Construye la aplicación
FROM node:20-slim AS builder

# Establecer el directorio de trabajo
WORKDIR /app

# Instalar dependencias necesarias para la build
# Esto incluye devDependencies como 'typescript' que son necesarias para 'next build'
COPY package.json package-lock.json* ./
RUN npm install

# Copiar el resto del código fuente de la aplicación
COPY . .

# Ejecutar el comando de build de Next.js
# Esto generará la salida 'standalone' en .next/standalone
RUN npm run build

# Etapa 2: Runner - Ejecuta la aplicación optimizada
FROM node:20-slim AS runner
WORKDIR /app

ENV NODE_ENV=production
# Deshabilitar telemetría de Next.js
ENV NEXT_TELEMETRY_DISABLED 1

# Crear un usuario no-root por seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar la salida 'standalone' desde la etapa de builder
# Esta carpeta contiene solo lo necesario para ejecutar la app en producción, incluido un server.js mínimo
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./

# Copiar las carpetas 'public' y '.next/static'
COPY --from=builder --chown=nextjs:nodejs /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Cambiar la propiedad de los archivos al usuario no-root
USER nextjs

# Exponer el puerto que Next.js usará
EXPOSE 3000

ENV PORT 3000

# Comando para iniciar el servidor de producción de Next.js
CMD ["node", "server.js"]
