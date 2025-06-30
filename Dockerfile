# Stage 1: Builder - Construye la aplicación Next.js
FROM node:20-alpine AS builder

# Establece el directorio de trabajo
WORKDIR /app

# Copia los archivos de definición de paquetes
COPY package*.json ./

# Instala las dependencias de forma limpia y eficiente
RUN npm ci

# Copia el resto del código de la aplicación
# Usa .dockerignore para excluir node_modules y otros archivos innecesarios
COPY . .

# Construye la aplicación Next.js para producción
RUN npm run build

# Stage 2: Runner - Crea la imagen final y optimizada
FROM node:20-alpine

# Establece el directorio de trabajo
WORKDIR /app

# Copia la aplicación construida desde el "builder" stage
# El output "standalone" de Next.js ya incluye los node_modules necesarios
COPY --from=builder /app/.next/standalone ./

# Copia los archivos públicos (imágenes, etc.)
COPY --from=builder /app/public ./public

# Expone el puerto en el que correrá la aplicación dentro del contenedor
EXPOSE 3001

# El comando para iniciar la aplicación
# Utiliza el server.js generado por el build "standalone"
# El puerto se gestionará a través de variables de entorno (PORT=3001)
CMD ["node", "server.js"]
