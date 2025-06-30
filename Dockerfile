# Stage 1: Builder - Construye la aplicación Next.js
FROM node:20-alpine AS builder

# Establece el directorio de trabajo
WORKDIR /app

# Copia los archivos de manifiesto del paquete
COPY package*.json ./

# Instala las dependencias de forma limpia
# Usamos `npm ci` para una instalación más rápida y predecible en CI/CD
RUN npm ci

# Copia el resto de los archivos de la aplicación
COPY . .

# Construye la aplicación
RUN npm run build


# Stage 2: Runner - Crea la imagen final de producción
FROM node:20-alpine AS runner

WORKDIR /app

# Establece las variables de entorno para producción
ENV NODE_ENV=production
# Deshabilita la telemetría de Next.js
ENV NEXT_TELEMETRY_DISABLED 1

# Copia los archivos de la aplicación independiente desde la etapa de builder
# El modo 'standalone' ya incluye la carpeta 'public' si existe,
# por lo que no es necesario copiarla por separado.
COPY --from=builder /app/.next/standalone ./

# Expone el puerto en el que correrá la aplicación dentro del contenedor
# El valor del puerto se definirá en el comando `docker run`
EXPOSE 3001

# Comando para iniciar la aplicación
# server.js es el servidor de Next.js en modo standalone
CMD ["node", "server.js"]
