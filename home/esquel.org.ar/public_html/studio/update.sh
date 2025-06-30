
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
# El script asume que se ejecuta desde la raíz del proyecto,
# pero nos aseguramos de estar en la ruta correcta para robustez.
PROJECT_DIR="/home/esquel.org.ar/public_html/studio"
cd "$PROJECT_DIR" || { echo "Error: No se pudo encontrar el directorio del proyecto en $PROJECT_DIR"; exit 1; }
echo "-> En el directorio del proyecto: $(pwd)"

# 2. Descargar los últimos cambios desde GitHub
echo "-> Descargando últimos cambios desde la rama 'master' de GitHub..."
# Se usa fetch y reset para forzar la actualización y evitar conflictos. Es más robusto que 'git pull'.
git fetch origin
git reset --hard origin/master
if [ $? -ne 0 ]; then
    echo "Error: 'git reset' falló. Por favor, revisa si tienes conflictos o problemas de conexión."
    exit 1
fi

# 3. Instalar/actualizar dependencias de Node.js
echo "-> Instalando/actualizando dependencias con npm (puede tardar un momento)..."
npm install
if [ $? -ne 0 ]; then
    echo "Error: 'npm install' falló. Revisa el log para más detalles."
    exit 1
fi

# 4. Reconstruir la aplicación para producción
echo "-> Reconstruyendo la aplicación para producción..."
npm run build
if [ $? -ne 0 ]; then
    echo "Error: 'npm run build' falló. Revisa el log para más detalles."
    exit 1
fi

# 5. ¡Paso Crucial! Asegurar que todos los archivos tengan los permisos correctos
echo "-> Asignando propiedad de todos los archivos a 'esque9858'..."
chown -R esque9858:esque9858 "$PROJECT_DIR"
if [ $? -ne 0 ]; then
    echo "Error: 'chown' falló."
    exit 1
fi

# 6. Reiniciar la aplicación con PM2
echo "-> Reiniciando la aplicación 'qreasy' con PM2..."
# Usamos 'restart' que es más seguro. Si el proceso no existe, pm2 lo indica pero no falla.
pm2 restart qreasy
if [ $? -ne 0 ]; then
    echo "Advertencia: 'pm2 restart qreasy' falló. Esto puede ser normal si el proceso no existía."
    echo "Intentando iniciar el proceso desde cero..."
    # Usamos el comando de inicio completo y correcto
    pm2 start server.js --name "qreasy" --uid esque9858 --gid esque9858
    if [ $? -ne 0 ]; then
        echo "Error: 'pm2 start' también falló. Por favor, revisa los logs de PM2 con 'pm2 logs qreasy'."
        exit 1
    fi
fi

# 7. Guardar la configuración de PM2 para que sobreviva a reinicios del servidor
pm2 save --force

echo ""
echo "--------------------------------------------------------"
echo "✅ ¡Actualización completada exitosamente!"
echo "-> Puedes verificar el estado con 'pm2 list'."
echo "--------------------------------------------------------"
