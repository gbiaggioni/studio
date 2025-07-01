# ------------------ BUILDER ------------------
# Usa una imagen base de Node.js delgada para la etapa de construcción
FROM node:20-slim AS builder

# Establece el directorio de trabajo dentro del contenedor
WORKDIR /app

# Instala las dependencias antes de copiar el código para aprovechar el caché de Docker
COPY package.json package-lock.json* ./
RUN npm ci

# Copia el resto del código fuente de la aplicación
COPY . .

# Asegúrate de que el archivo .env.local no se necesita para el build
# Las variables de entorno se proporcionarán en tiempo de ejecución
RUN npm run build

# ------------------ RUNNER ------------------
# Usa la misma imagen base delgada para la etapa de ejecución
FROM node:20-slim AS runner

# Establece el directorio de trabajo
WORKDIR /app

# Crea un usuario y grupo no-root para mejorar la seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copia los artefactos de construcción de la etapa 'builder'
# La configuración 'output: standalone' en next.config.js agrupa todo lo necesario
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Cambia al usuario no-root
USER nextjs

# Expone el puerto en el que la aplicación Next.js se ejecutará
EXPOSE 3000

# El comando para iniciar el servidor de Next.js en modo producción
# Las variables de entorno para la base de datos se deben pasar al comando `docker run`
# usando la bandera --env-file ./.env.local
CMD ["node", "server.js"]
