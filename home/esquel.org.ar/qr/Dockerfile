# Stage 1: Builder - Construye la aplicación Next.js
FROM node:20-alpine AS builder

# Establece el directorio de trabajo
WORKDIR /app

# Copia los archivos de paquetes
COPY package*.json ./

# Instala las dependencias de forma limpia
# Usar 'npm ci' es más rápido y seguro para builds de CI/CD
RUN npm ci

# Copia el resto del código fuente de la aplicación
COPY . .

# Construye la aplicación para producción
RUN npm run build

# Stage 2: Runner - Crea la imagen final y optimizada
FROM node:20-alpine AS runner

WORKDIR /app

# Establece las variables de entorno para producción
ENV NODE_ENV=production
# La aplicación correrá en el puerto 3001 por defecto, pero esto puede ser sobreescrito.
ENV PORT=3001

# Copia solo los archivos necesarios desde la etapa de 'builder'
# Esto es gracias a la configuración 'output: standalone' en next.config.js
COPY --from=builder /app/.next/standalone ./

# La carpeta 'public' se copia automáticamente dentro de '.next/standalone/public'
# si existe, por lo que no se necesita un comando COPY adicional para ella.

# Expone el puerto en el que correrá la aplicación dentro del contenedor
EXPOSE 3001

# Comando para iniciar la aplicación
# 'server.js' es el servidor que crea Next.js en modo 'standalone'
CMD ["node", "server.js"]
