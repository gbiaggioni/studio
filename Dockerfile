# Stage 1: Builder - Construye la aplicación Next.js
FROM node:20-alpine AS builder

# Establece el directorio de trabajo
WORKDIR /app

# Copia los archivos de definición de paquetes
COPY package*.json ./

# Instala las dependencias de producción
RUN npm ci

# Copia el resto de los archivos de la aplicación
COPY . .

# Construye la aplicación
RUN npm run build

# Stage 2: Runner - Crea la imagen final de producción
FROM node:20-alpine AS runner

WORKDIR /app

# Copia los archivos de la aplicación independiente desde la etapa de construcción.
# La salida 'standalone' de Next.js incluye automáticamente la carpeta 'public' si existe,
# por lo que no es necesario un paso de copia por separado para ella.
COPY --from=builder /app/.next/standalone ./

# Expone el puerto en el que correrá la aplicación dentro del contenedor
# Asegúrate de que este puerto coincida con el que se define en .env.local y en el comando docker run
EXPOSE 3001

# Comando para iniciar la aplicación
# El modo 'standalone' crea un server.js optimizado.
CMD ["node", "server.js"]
