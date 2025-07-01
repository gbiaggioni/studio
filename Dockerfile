# Fase 1: Instalación de dependencias y construcción del proyecto
FROM node:20-slim AS builder

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar los archivos de manifiesto de paquetes
COPY package.json package-lock.json ./

# Instalar las dependencias de producción
RUN npm install --omit=dev

# Copiar el resto de los archivos del proyecto
COPY . .

# Construir la aplicación
RUN npm run build

# Fase 2: Ejecución de la aplicación
FROM node:20-slim AS runner

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar el build desde la fase anterior
COPY --from=builder /app/.next ./.next
COPY --from=builder /app/public ./public
COPY --from=builder /app/package.json ./package.json
COPY --from=builder /app/node_modules ./node_modules

# Exponer el puerto en el que correrá la aplicación
EXPOSE 3000

# Comando para iniciar la aplicación
CMD ["npm", "start"]
