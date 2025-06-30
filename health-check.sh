
#!/bin/sh

# --- AVISO DE OBSOLESCENCIA ---
# Este script está obsoleto y solo es relevante para el antiguo método de despliegue sin Docker.
# Para el método de despliegue recomendado con Docker, por favor, sigue las instrucciones en el archivo README.md.
# Para verificar el estado del contenedor Docker, usa los comandos 'sudo docker ps' y 'sudo docker logs qreasy-container'.
# --- FIN DEL AVISO ---

# --- Script de Diagnóstico (Health Check) para QREasy ---
# Este script verifica el estado de todos los componentes críticos de la aplicación.
# Ejecútalo para identificar rápidamente la causa de un problema.
# Uso: sh ./health-check.sh

# --- Colores para la salida ---
C_RED='\033[0;31m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[0;33m'
C_BLUE='\033[0;34m'
C_NC='\033[0m' # No Color

# --- Variables ---
APP_NAME="qreasy"
APP_PORT="3001"
APP_URL="https://qr.esquel.org.ar/"
LOCAL_URL="http://127.0.0.1:$APP_PORT/"
APP_USER="esque9858"

# --- Funciones de Ayuda ---
print_header() {
    echo "\n${C_BLUE}--- $1 ---${C_NC}"
}

print_success() {
    echo "[ ${C_GREEN}OK${C_NC} ] $1"
}

print_error() {
    echo "[ ${C_RED}ERROR${C_NC} ] $1"
}

print_warning() {
    echo "[ ${C_YELLOW}WARN${C_NC} ] $1"
}

print_info() {
    echo "   -> $1"
}


# --- INICIO DEL SCRIPT ---
echo "${C_BLUE}===============================================${C_NC}"
echo "🩺 Ejecutando Health Check para QREasy..."
echo "${C_BLUE}===============================================${C_NC}"


# --- 1. Verificación de Dependencias del Entorno ---
print_header "1. Verificando Dependencias del Entorno"
# Node.js
if command -v node > /dev/null; then
    print_success "Node.js está instalado: $(node -v)"
else
    print_error "Node.js no está instalado o no se encuentra en el PATH."
    print_info "Asegúrate de que Node.js v20+ esté instalado y accesible para el usuario root."
    exit 1
fi

# npm
if command -v npm > /dev/null; then
    print_success "npm está instalado: $(npm -v)"
else
    print_error "npm no está instalado o no se encuentra en el PATH."
    exit 1
fi

# Git
if command -v git > /dev/null; then
    print_success "Git está instalado."
else
    print_warning "Git no está instalado. No podrás actualizar desde GitHub."
fi


# --- 2. Verificación de Archivos del Proyecto ---
print_header "2. Verificando Archivos y Carpetas del Proyecto"
# node_modules
if [ -d "node_modules" ]; then
    print_success "La carpeta 'node_modules' existe."
else
    print_error "La carpeta 'node_modules' no existe."
    print_info "Ejecuta 'npm install' para instalar las dependencias."
fi

# Carpeta de build de Next.js
if [ -d ".next" ]; then
    print_success "La carpeta de build '.next' existe."
else
    print_error "La carpeta de build '.next' no existe."
    print_info "Ejecuta 'npm run build' para construir la aplicación."
fi

# package.json
if [ -f "package.json" ]; then
    print_success "El archivo 'package.json' existe."
else
    print_error "El archivo 'package.json' no existe."
fi


# --- 3. Verificación del Proceso PM2 ---
print_header "3. Verificando el Proceso PM2"
PM2_STATUS=$(pm2 describe "$APP_NAME" 2>/dev/null)

if [ -z "$PM2_STATUS" ]; then
    print_error "El proceso PM2 '$APP_NAME' no existe."
    print_info "Ejecuta el script 'update.sh' o los comandos de despliegue manual para iniciarlo."
else
    print_success "El proceso PM2 '$APP_NAME' existe."

    # Usamos grep -w para buscar la palabra exacta y evitar coincidencias parciales.
    STATUS=$(echo "$PM2_STATUS" | grep -w 'status' | awk -F'│' '{print $3}' | xargs)
    PID=$(echo "$PM2_STATUS" | grep -w 'pid' | awk -F'│' '{print $3}' | xargs)
    USER=$(echo "$PM2_STATUS" | grep -w 'username' | awk -F'│' '{print $3}' | xargs)

    # Verificar estado
    if [ "$STATUS" = "online" ]; then
        print_success "Estado: $STATUS"
    else
        print_error "Estado: $STATUS. Debería ser 'online'."
    fi

    # Verificar PID (si es un número mayor que 0)
    if [ "$PID" -gt 0 ] 2>/dev/null; then
        print_success "PID: $PID (proceso en ejecución)."
    else
        print_error "PID: $PID. La aplicación no se está ejecutando correctamente (puede estar en un bucle de reinicio)."
        print_info "Revisa los logs con: pm2 logs $APP_NAME"
    fi

    # Verificar usuario
    if [ "$USER" = "$APP_USER" ]; then
        print_success "Ejecutándose como usuario: $USER"
    else
        print_error "Ejecutándose como usuario incorrecto: '$USER'. Debería ser '$APP_USER'."
    fi
fi


# --- 4. Verificación de Conectividad de Red ---
print_header "4. Verificando Conectividad de Red"

# Verificar si el proceso está escuchando en el puerto local
if ss -tlnp | grep ":$APP_PORT" > /dev/null; then
    print_success "La aplicación está escuchando en el puerto local $APP_PORT."

    # Verificar respuesta de localhost
    CURL_LOCAL=$(curl -s -o /dev/null -w "%{http_code}" "$LOCAL_URL")
    if [ "$CURL_LOCAL" = "200" ] || [ "$CURL_LOCAL" = "404" ]; then
        print_success "Respuesta de localhost (127.0.0.1:$APP_PORT) es exitosa (Código: $CURL_LOCAL)."
    else
        print_error "Respuesta de localhost (127.0.0.1:$APP_PORT) falló (Código: $CURL_LOCAL)."
        print_info "Esto indica un problema dentro de la aplicación Next.js."
    fi
else
    print_error "Ningún proceso está escuchando en el puerto $APP_PORT."
    print_info "Esto confirma que la aplicación no arrancó. Revisa los logs de PM2 con 'pm2 logs $APP_NAME'."
fi


# Verificar respuesta del dominio público
CURL_PUBLIC=$(curl -s -o /dev/null -L -w "%{http_code}" "$APP_URL")
if [ "$CURL_PUBLIC" = "200" ]; then
    print_success "Respuesta del dominio público ($APP_URL) es exitosa (Código: $CURL_PUBLIC)."
elif [ "$CURL_PUBLIC" = "403" ]; then
    print_error "Respuesta del dominio público ($APP_URL) es 'Access Denied' (Código: 403)."
    print_info "Esto suele ser un problema de configuración de LiteSpeed (vHost) o que la aplicación no está respondiendo."
elif [ "$CURL_PUBLIC" = "500" ] || [ "$CURL_PUBLIC" = "502" ] || [ "$CURL_PUBLIC" = "503" ]; then
    print_error "Respuesta del dominio público ($APP_URL) es un error de servidor (Código: $CURL_PUBLIC)."
    print_info "Esto puede ser un problema de LiteSpeed o que la aplicación se está reiniciando. Revisa los logs de LiteSpeed y PM2."
else
    print_error "Respuesta del dominio público ($APP_URL) es inesperada (Código: $CURL_PUBLIC)."
fi

echo "\n${C_BLUE}===============================================${C_NC}"
echo "✅ Diagnóstico completado."
echo "${C_BLUE}===============================================${C_NC}"
