
#!/bin/bash

# --- Script de Actualización Automática para QREasy ---
# Este script automatiza el proceso de actualizar la aplicación desde GitHub.
# DEBE EJECUTARSE COMO ROOT.
# Ejemplo de uso: sudo bash ./update.sh

echo "### Iniciando proceso de actualización de QREasy... ###"

# Asegurarse de que el script se ejecuta como root
if [ "$(id -u)" != "0" ]; then
   echo "Este script debe ser ejecutado como root" 1>&2
   exit 1
fi

# 1. Navegar al directorio del proyecto.
PROJECT_DIR="/home/esquel.org.ar/public_html/studio"
cd "$PROJECT_DIR" || { echo "Error: No se pudo encontrar el directorio del proyecto en $PROJECT_DIR"; exit 1; }
echo "-> En el directorio del proyecto: $(pwd)"

# 2. ¡Paso Crucial! Detener, eliminar y borrar la configuración antigua de PM2 para evitar estados corruptos.
echo "-> Limpiando configuración de PM2 anterior..."
pm2 stop qreasy || echo "Advertencia: El proceso qreasy no estaba corriendo."
pm2 delete qreasy || echo "Advertencia: El proceso qreasy no existía."
pm2 save --force

# 3. Descargar los últimos cambios desde GitHub
echo "-> Descargando últimos cambios desde la rama 'master' de GitHub..."
git fetch origin
git reset --hard origin/master
if [ $? -ne 0 ]; then
    echo "Error: 'git reset' falló. Por favor, revisa si tienes conflictos o problemas de conexión."
    exit 1
fi

# 4. Instalar/actualizar dependencias de Node.js
echo "-> Instalando/actualizando dependencias con npm..."
npm install
if [ $? -ne 0 ]; then
    echo "Error: 'npm install' falló. Revisa el log para más detalles."
    exit 1
fi

# 5. Reconstruir la aplicación para producción
echo "-> Reconstruyendo la aplicación para producción..."
npm run build
if [ $? -ne 0 ]; then
    echo "Error: 'npm run build' falló. Revisa el log para más detalles."
    exit 1
fi

# 6. ¡Paso Crucial! Asegurar que todos los archivos tengan los permisos correctos
echo "-> Asignando propiedad de todos los archivos a 'esque9858'..."
chown -R esque9858:esque9858 "$PROJECT_DIR"
if [ $? -ne 0 ]; then
    echo "Error: 'chown' falló."
    exit 1
fi

# 7. Iniciar la aplicación desde cero con el comando limpio y correcto
echo "-> Iniciando la aplicación con PM2..."
pm2 start server.js --name "qreasy" --uid esque9858 --gid esque9858
if [ $? -ne 0 ]; then
    echo "Error: 'pm2 start' falló. Por favor, revisa los logs de PM2 con 'pm2 logs qreasy'."
    exit 1
fi

# 8. Guardar la nueva y correcta configuración de PM2
pm2 save

echo ""
echo "--------------------------------------------------------"
echo "✅ ¡Actualización completada exitosamente!"
echo "-> Puedes verificar el estado con 'pm2 list'."
echo "--------------------------------------------------------"
