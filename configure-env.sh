#!/bin/bash

# Este script te guiará para crear un archivo .env.local perfecto.
# Ejecútalo con: ./configure-env.sh

echo "--- Asistente de Configuración de QREasy ---"
echo "Por favor, introduce los valores para tu entorno. Presiona Enter para usar el valor por defecto si se muestra."
echo ""

# DB_HOST
read -p "Introduce el Host de la Base de Datos [Default: 172.17.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-172.17.0.1}

# DB_USER
read -p "Introduce el Usuario de la Base de Datos: " DB_USER

# DB_PASSWORD
read -p "Introduce la Contraseña de la Base de Datos: " -s DB_PASSWORD
echo ""

# DB_NAME
read -p "Introduce el Nombre de la Base de Datos: " DB_NAME

# NEXT_PUBLIC_BASE_URL
read -p "Introduce la URL pública de tu sitio (ej. https://qr.esquel.ar): " NEXT_PUBLIC_BASE_URL

# Crear el archivo .env.local
# Usamos cat con un Here Document (EOF) para evitar cualquier problema de formato.
cat <<EOF > .env.local
# Archivo generado automáticamente por configure-env.sh
DB_HOST=${DB_HOST}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_NAME=${DB_NAME}
NEXT_PUBLIC_BASE_URL=${NEXT_PUBLIC_BASE_URL}
EOF

# Eliminar la línea de comentario para dejar el archivo 100% limpio
sed -i '/^#.*$/d' .env.local

echo ""
echo "¡ÉXITO! El archivo .env.local ha sido creado correctamente:"
echo "----------------------------------------------------"
cat .env.local
echo "----------------------------------------------------"
echo ""
echo "Próximos pasos recomendados:"
echo "1. Reconstruye tu imagen de Docker: sudo docker build -t qreasy-app ."
echo "2. Reinicia tu contenedor: sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 --env-file ./.env.local qreasy-app"

exit 0
