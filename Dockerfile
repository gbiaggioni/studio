# ------------------ BUILDER ------------------
# Etapa 1: Construir la aplicación
FROM node:20-slim AS builder

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar los archivos de definición de dependencias
COPY package.json package-lock.json* ./

# Instalar las dependencias
RUN npm install

# Copiar el resto de los archivos del proyecto
COPY . .

# Construir la aplicación para producción
RUN npm run build

# ------------------ RUNNER ------------------
# Etapa 2: Crear la imagen final de producción
FROM node:20-slim AS runner
WORKDIR /app

# Crear un usuario y grupo no-root para mayor seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar los archivos de la aplicación construida desde la etapa 'builder'
# La build 'standalone' de Next.js incluye solo lo necesario para correr
COPY --from=builder /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Establecer el usuario no-root
USER nextjs

# Exponer el puerto en el que corre la aplicación
EXPOSE 3000

# Variable de entorno para indicar el puerto
ENV PORT 3000

# Comando para iniciar la aplicación
# La salida 'standalone' crea un server.js optimizado
CMD ["node", "server.js"]
