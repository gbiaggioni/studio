#!/bin/bash

# --- Script de Actualización y Reinicio Limpio para QREasy ---
# Este script es la solución definitiva para actualizar la aplicación o para
# repararla si deja de funcionar. Realiza un "reinicio limpio" completo.
# DEBE EJECUTARSE COMO ROOT.
# Uso: sudo bash ./update.sh

echo "### Iniciando proceso de actualización y reinicio limpio de QREasy... ###"

# Asegurarse de que el script se ejecuta como root
if [ "$(id -u)" != "0" ]; then
   echo "Error: Este script debe ser ejecutado como root" 1>&2
   exit 1
fi

# 1. Navegar al directorio del proyecto.
PROJECT_DIR="/home/esquel.org.ar/public_html/studio"
cd "$PROJECT_DIR" || { echo "Error: No se pudo encontrar el directorio del proyecto en $PROJECT_DIR"; exit 1; }
echo "-> En el directorio del proyecto: $(pwd)"

# 2. ¡Paso Crucial! Detener, eliminar y borrar la configuración antigua de PM2.
# Esto erradica cualquier "configuración fantasma" o estado corrupto.
echo "-> Limpiando configuración de PM2 anterior..."
pm2 stop qreasy || echo "Info: El proceso 'qreasy' no estaba corriendo (esto es normal)."
pm2 delete qreasy || echo "Info: El proceso 'qreasy' no existía (esto es normal)."
pm2 save --force
echo "-> Configuración de PM2 limpiada."

# 3. Descargar los últimos cambios desde GitHub.
# Se usa fetch y reset --hard para forzar la actualización y evitar conflictos.
echo "-> Descargando últimos cambios desde la rama 'master' de GitHub..."
git fetch origin
git reset --hard origin/master
if [ $? -ne 0 ]; then
    echo "Error: Falló la descarga desde GitHub. Revisa conflictos o conexión."
    exit 1
fi

# 4. Instalar/actualizar dependencias de Node.js.
echo "-> Instalando/actualizando dependencias con npm (puede tardar un momento)..."
npm install
if [ $? -ne 0 ]; then
    echo "Error: 'npm install' falló. Revisa el log para más detalles."
    exit 1
fi

# 5. Reconstruir la aplicación para producción.
echo "-> Reconstruyendo la aplicación para producción..."
npm run build
if [ $? -ne 0 ]; then
    echo "Error: 'npm run build' falló. Revisa el log para más detalles."
    exit 1
fi

# 6. ¡Paso Crucial! Asegurar que todos los archivos tengan los permisos correctos.
echo "-> Asignando propiedad de todos los archivos a 'esque9858'..."
chown -R esque9858:esque9858 "$PROJECT_DIR"
if [ $? -ne 0 ]; then
    echo "Error: 'chown' falló."
    exit 1
fi

# 7. Iniciar la aplicación desde cero con el comando limpio y correcto.
echo "-> Iniciando la aplicación con PM2 como usuario 'esque9858'..."
pm2 start server.js --name "qreasy" --uid esque9858 --gid esque9858
if [ $? -ne 0 ]; then
    echo "Error: 'pm2 start' falló. Revisa los logs con 'pm2 logs qreasy'."
    exit 1
fi

# 8. Guardar la nueva y correcta configuración de PM2.
pm2 save

echo ""
echo "--------------------------------------------------------"
echo "✅ ¡Reinicio limpio completado exitosamente!"
echo "-> La aplicación ha sido actualizada y reiniciada."
echo "-> Puedes verificar el estado con 'pm2 list'."
echo "--------------------------------------------------------"
