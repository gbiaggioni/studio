#!/bin/bash

# --- Script de Diagnóstico (Health Check) para QREasy ---
# Este script verifica el estado de todos los componentes críticos de la aplicación.
# Ejecútalo para identificar rápidamente la causa de un problema.
# Uso: bash ./health-check.sh

# --- Colores para la salida ---
C_RED='\033[0;31m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[0;33m'
C_BLUE='\033[0;34m'
C_NC='\033[0m' # No Color

# --- Variables ---
APP_NAME="qreasy"
APP_PORT="3001"
APP_URL="https://esquel.org.ar/studio/"
LOCAL_URL="http://127.0.0.1:$APP_PORT/studio/"
APP_USER="esque9858"

# --- Funciones de Ayuda ---
print_header() {
    echo -e "\n${C_BLUE}--- $1 ---${C_NC}"
}

print_success() {
    echo -e "[ ${C_GREEN}OK${C_NC} ] $1"
}

print_error() {
    echo -e "[ ${C_RED}ERROR${C_NC} ] $1"
}

print_warning() {
    echo -e "[ ${C_YELLOW}WARN${C_NC} ] $1"
}

print_info() {
    echo -e "   -> $1"
}


# --- INICIO DEL SCRIPT ---
echo -e "${C_BLUE}===============================================${C_NC}"
echo -e "${C_BLUE}🩺 Ejecutando Health Check para QREasy...${C_NC}"
echo -e "${C_BLUE}===============================================${C_NC}"


# --- 1. Verificación de Dependencias del Entorno ---
print_header "1. Verificando Dependencias del Entorno"
# Node.js
if command -v node &> /dev/null; then
    print_success "Node.js está instalado: $(node -v)"
else
    print_error "Node.js no está instalado o no se encuentra en el PATH."
    print_info "Asegúrate de que Node.js v20+ esté instalado y accesible para el usuario root."
    exit 1
fi

# npm
if command -v npm &> /dev/null; then
    print_success "npm está instalado: $(npm -v)"
else
    print_error "npm no está instalado o no se encuentra en el PATH."
    exit 1
fi

# Git
if command -v git &> /dev/null; then
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

# server.js
if [ -f "server.js" ]; then
    print_success "El script 'server.js' existe."
    # Verificar permisos de server.js
    OWNER=$(stat -c '%U:%G' server.js)
    if [ "$OWNER" == "$APP_USER:$APP_USER" ]; then
        print_success "Permisos de 'server.js' son correctos ($OWNER)."
    else
        print_error "Permisos de 'server.js' son incorrectos. Propietario: $OWNER."
        print_info "Debe ser '$APP_USER:$APP_USER'. Ejecuta: chown $APP_USER:$APP_USER server.js"
    fi
else
    print_error "El script 'server.js' no existe."
    print_info "Este archivo es crucial. Asegúrate de tener la última versión desde GitHub ejecutando 'bash ./update.sh'."
fi


# --- 3. Verificación del Proceso PM2 ---
print_header "3. Verificando el Proceso PM2"
PM2_STATUS=$(pm2 describe "$APP_NAME" 2>/dev/null)

if [ -z "$PM2_STATUS" ]; then
    print_error "El proceso PM2 '$APP_NAME' no existe."
    print_info "Ejecuta el script 'update.sh' o los comandos de despliegue manual para iniciarlo."
else
    print_success "El proceso PM2 '$APP_NAME' existe."

    # Verificar estado
    STATUS=$(echo "$PM2_STATUS" | grep 'status' | awk -F'│' '{print $3}' | xargs)
    if [ "$STATUS" == "online" ]; then
        print_success "Estado: $STATUS"
    else
        print_error "Estado: $STATUS. Debería ser 'online'."
    fi

    # Verificar PID
    PID=$(echo "$PM2_STATUS" | grep 'pid' | awk -F'│' '{print $3}' | xargs)
    if [ "$PID" != "N/A" ] && [ "$PID" -gt 0 ]; then
        print_success "PID: $PID (proceso en ejecución)."
    else
        print_error "PID: $PID. La aplicación no se está ejecutando, está en un bucle de reinicio."
        print_info "Revisa los logs con: pm2 logs $APP_NAME"
    fi

    # Verificar usuario
    USER=$(echo "$PM2_STATUS" | grep 'username' | awk -F'│' '{print $3}' | xargs)
    if [ "$USER" == "$APP_USER" ]; then
        print_success "Ejecutándose como usuario: $USER"
    else
        print_error "Ejecutándose como usuario incorrecto: $USER. Debería ser '$APP_USER'."
    fi
fi


# --- 4. Verificación de Conectividad de Red ---
print_header "4. Verificando Conectividad de Red"

# Verificar si el proceso está escuchando en el puerto local
if ss -tlnp | grep ":$APP_PORT" &> /dev/null; then
    print_success "La aplicación está escuchando en el puerto local $APP_PORT."
    
    # Verificar respuesta de localhost
    CURL_LOCAL=$(curl -s -o /dev/null -w "%{http_code}" "$LOCAL_URL")
    if [ "$CURL_LOCAL" == "200" ] || [ "$CURL_LOCAL" == "404" ]; then
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
if [ "$CURL_PUBLIC" == "200" ]; then
    print_success "Respuesta del dominio público ($APP_URL) es exitosa (Código: $CURL_PUBLIC)."
elif [ "$CURL_PUBLIC" == "403" ]; then
    print_error "Respuesta del dominio público ($APP_URL) es 'Access Denied' (Código: 403)."
    print_info "Esto suele ser un problema de configuración de LiteSpeed (vHost). Revisa la configuración del proxy."
elif [ "$CURL_PUBLIC" == "500" ] || [ "$CURL_PUBLIC" == "502" ] || [ "$CURL_PUBLIC" == "503" ]; then
    print_error "Respuesta del dominio público ($APP_URL) es un error de servidor (Código: $CURL_PUBLIC)."
    print_info "Esto puede ser un problema de LiteSpeed o que la aplicación se está reiniciando. Revisa los logs de LiteSpeed y PM2."
else
    print_error "Respuesta del dominio público ($APP_URL) es inesperada (Código: $CURL_PUBLIC)."
fi

echo -e "\n${C_BLUE}===============================================${C_NC}"
echo -e "${C_BLUE}✅ Diagnóstico completado.${C_NC}"
echo -e "${C_BLUE}===============================================${C_NC}"
