# Dockerfile Definitivo para QREasy

# ------------------ BUILDER ------------------
FROM node:20-slim AS builder
WORKDIR /app

# Copia los archivos de manifiesto para cachear la instalación de dependencias
COPY package.json package-lock.json* ./

# Instala todas las dependencias (incluidas las de desarrollo para la build)
RUN npm ci

# Copia el resto del código de la aplicación.
# .dockerignore se encargará de excluir node_modules, .git, etc.
COPY . .

# *** IMPORTANTE ***
# La build de Next.js necesita que las variables públicas (NEXT_PUBLIC_*) estén
# definidas. El script `configure-env.sh` crea el archivo .env.local,
# que `next build` lee automáticamente cuando está presente en el contexto de build.
RUN npm run build

# ------------------ RUNNER ------------------
FROM node:20-slim AS runner
WORKDIR /app

# Crear un usuario y grupo no-root para seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar solo los artefactos necesarios de la build para una imagen ligera
# gracias a la configuración `output: 'standalone'` en next.config.js
COPY --from=builder --chown=nextjs:nodejs /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# Cambiar al usuario no-root
USER nextjs

EXPOSE 3000

# Comando para iniciar la aplicación.
# Las variables de entorno del servidor (DB_*) se deben pasar en el comando `docker run`.
CMD ["node", "server.js"]
