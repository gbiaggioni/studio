#!/bin/sh

# Este script te guiará para crear un archivo .env.local perfecto.
# Ejecútalo con: ./configure-env.sh

echo "--- Asistente de Configuración de QREasy ---"
echo "Por favor, introduce los valores para tu entorno. Presiona Enter para usar el valor por defecto si se muestra."
echo ""

# DB_HOST
printf "Introduce el Host de la Base de Datos (¡MUY IMPORTANTE! Para Docker, casi siempre es 172.17.0.1) [Default: 172.17.0.1]: "
read DB_HOST
DB_HOST=${DB_HOST:-172.17.0.1}

# DB_USER
printf "Introduce el Usuario de la Base de Datos: "
read DB_USER

# DB_PASSWORD
printf "Introduce la Contraseña de la Base de Datos: "
read DB_PASSWORD

# DB_NAME
printf "Introduce el Nombre de la Base de Datos: "
read DB_NAME

# NEXT_PUBLIC_BASE_URL
printf "Introduce la URL pública de tu sitio (ej. https://qr.esquel.ar): "
read NEXT_PUBLIC_BASE_URL

# Crear el archivo .env.local
# Usamos cat con un Here Document (EOF) para evitar cualquier problema de formato.
cat <<EOF > .env.local
DB_HOST=${DB_HOST}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_NAME=${DB_NAME}
NEXT_PUBLIC_BASE_URL=${NEXT_PUBLIC_BASE_URL}
EOF

echo ""
echo "¡ÉXITO! El archivo .env.local ha sido creado correctamente:"
echo "----------------------------------------------------"
cat .env.local
echo "----------------------------------------------------"
echo ""
echo "Próximos pasos recomendados:"
echo "1. Reconstruye tu imagen de Docker: sudo docker build -t qreasy-app ."
echo "2. Inicia tu nuevo contenedor con el comando SIMPLIFICADO (¡copia y pega!):"
echo "   sudo docker run -d --restart unless-stopped --name qreasy-container -p 3001:3000 qreasy-app"

exit 0
