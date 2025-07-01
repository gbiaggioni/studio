# Dockerfile optimizado y seguro para QREasy

# ------------------ BUILDER ------------------
FROM node:20-slim AS builder

WORKDIR /app

# Copiar archivos de dependencias y reinstalar para cacheo eficiente
# Esto es más rápido porque Docker cachea esta capa si los archivos no cambian.
COPY package.json package-lock.json* ./
RUN npm install

# Copiar el resto de los archivos de la aplicación
# Esto incluye el .env.local que debe existir ANTES de construir la imagen
COPY . .

# --- ¡PUNTO CLAVE DE VALIDACIÓN! ---
# La build fallará si .env.local no existe, forzando la configuración correcta.
RUN if [ ! -f .env.local ]; then \
      echo ""; \
      echo "--> ERROR: El archivo .env.local no existe."; \
      echo "--> Por favor, créalo ejecutando el script './configure-env.sh' antes de construir la imagen."; \
      echo ""; \
      exit 1; \
    fi

# Construir la aplicación para producción
# Next.js carga automáticamente .env.local durante la build.
RUN npm run build

# ------------------ RUNNER ------------------
FROM node:20-slim AS runner
WORKDIR /app

# Crear usuario y grupo no-root para mejorar la seguridad
RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar los archivos de la build del builder (modo Standalone)
COPY --from=builder /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static

# --- ¡LA SOLUCIÓN DEFINITIVA! ---
# Copiar el archivo .env.local para que las variables de entorno estén disponibles en tiempo de ejecución.
# Esto elimina la necesidad de usar --env-file en el comando docker run.
COPY --from=builder --chown=nextjs:nodejs /app/.env.local ./.env.local

# Cambiar al usuario no-root
USER nextjs

# Exponer el puerto en el que correrá la app
EXPOSE 3000

# Variable de entorno para que Next.js sepa en qué puerto escuchar
ENV PORT=3000

# Comando para iniciar la aplicación
CMD ["node", "server.js"]
