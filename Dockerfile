# Dockerfile definitivo para QREasy - Sigue las mejores prácticas de Next.js

# ------------------ BUILDER ------------------
# Utiliza una imagen ligera de Node.js para la etapa de construcción
FROM node:20-slim AS builder
WORKDIR /app

# Instala openssl, que a veces es necesario para la compilación de dependencias nativas.
RUN apt-get update && apt-get install -y --no-install-recommends openssl

# Copia los archivos de manifiesto del paquete y el lockfile
COPY package.json package-lock.json* ./

# Instala las dependencias. Usamos `npm ci` para instalaciones limpias y reproducibles.
RUN npm ci

# Copia el resto del código fuente. Se hace después de `npm ci` para aprovechar el caché de capas de Docker.
COPY . .

# *** ¡IMPORTANTE! ***
# La construcción debe ser agnóstica al entorno.
# NO dependemos de variables de entorno en tiempo de build.
# Las credenciales de la BD se pasarán al contenedor en tiempo de ejecución.
RUN npm run build

# ------------------ RUNNER ------------------
# Utiliza una imagen aún más ligera para la etapa de ejecución
FROM node:20-slim AS runner
WORKDIR /app

# Crea un usuario no privilegiado para mayor seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copia los artefactos de la construcción desde la etapa 'builder'
# .next/standalone contiene solo los archivos necesarios para producción
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Cambia al usuario no privilegiado
USER nextjs

# Expone el puerto en el que la aplicación se ejecutará dentro del contenedor
EXPOSE 3000

# Define la variable de entorno para el puerto
ENV PORT 3000

# El comando para iniciar la aplicación.
# Las variables de entorno (DB_HOST, etc.) se deben pasar
# usando la opción `--env-file` en el comando `docker run`.
CMD ["node", "server.js"]
