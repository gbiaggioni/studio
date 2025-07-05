#!/bin/bash

# =============================================================================
# SCRIPT DE INSTALACIÓN QR MANAGER PARA CYBERPANEL + OPENLITESPEED
# Ubuntu 20.04 + CyberPanel + OpenLiteSpeed
# Compatible con sh y bash - Versión con verificación completa de requisitos
# =============================================================================

echo "🚀 Iniciando instalación de QR Manager para CyberPanel + OpenLiteSpeed..."
echo "==========================================================================="

# Verificar que estamos en el directorio correcto
if [ ! -f "config.php" ]; then
    echo "❌ Error: No se encuentra config.php. Ejecuta este script desde el directorio qr-manager/"
    exit 1
fi

echo "✅ Directorio verificado correctamente"

# =============================================================================
# VERIFICACIÓN COMPLETA DE REQUISITOS DEL SISTEMA
# =============================================================================

echo ""
echo "🔍 VERIFICANDO REQUISITOS DEL SISTEMA..."
echo "========================================="

# Variables para el reporte final
ERRORS=0
WARNINGS=0
SERVER_TYPE=""
PHP_VERSION=""
PHP_CLI_AVAILABLE=false

# Función para mostrar errores
show_error() {
    echo "❌ ERROR: $1"
    ERRORS=$((ERRORS + 1))
}

# Función para mostrar advertencias
show_warning() {
    echo "⚠️  ADVERTENCIA: $1"
    WARNINGS=$((WARNINGS + 1))
}

# Función para mostrar éxito
show_success() {
    echo "✅ $1"
}

# Función para mostrar información
show_info() {
    echo "📋 $1"
}

# =============================================================================
# 1. DETECTAR SERVIDOR WEB
# =============================================================================

show_info "Detectando servidor web..."

# Verificar OpenLiteSpeed
if command -v litespeed >/dev/null 2>&1 || [ -f "/usr/local/lsws/bin/litespeed" ] || [ -d "/usr/local/lsws" ]; then
    SERVER_TYPE="OpenLiteSpeed"
    show_success "OpenLiteSpeed detectado"
    
    # Verificar CyberPanel
    if command -v cyberpanel >/dev/null 2>&1 || [ -f "/usr/local/CyberCP/cyberpanel/manage.py" ]; then
        show_success "CyberPanel detectado - Configuración óptima"
    else
        show_warning "OpenLiteSpeed detectado pero CyberPanel no encontrado"
        show_info "  - Se recomienda usar CyberPanel para facilitar la gestión"
    fi

# Verificar Apache
elif command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
    if command -v apache2 >/dev/null 2>&1; then
        SERVER_TYPE="Apache"
        show_success "Apache detectado"
    else
        SERVER_TYPE="Apache (httpd)"
        show_success "Apache (httpd) detectado"
    fi
    
    # Verificar mod_rewrite
    if apache2ctl -M 2>/dev/null | grep -q "rewrite_module" || httpd -M 2>/dev/null | grep -q "rewrite_module"; then
        show_success "mod_rewrite está habilitado"
    else
        show_error "mod_rewrite NO está habilitado (requerido para Apache)"
        show_info "  - Habilitar con: sudo a2enmod rewrite && sudo systemctl reload apache2"
    fi

# Verificar Nginx
elif command -v nginx >/dev/null 2>&1; then
    SERVER_TYPE="Nginx"
    show_success "Nginx detectado"
    show_warning "Nginx requiere configuración manual adicional"
    show_info "  - Necesitarás configurar las reglas de rewrite manualmente"

# No se detectó servidor web
else
    show_error "No se detectó servidor web (Apache, OpenLiteSpeed, Nginx)"
    show_info "  - Instala Apache: sudo apt install apache2"
    show_info "  - O instala OpenLiteSpeed con CyberPanel"
fi

# =============================================================================
# 2. VERIFICAR VERSIÓN Y CONFIGURACIÓN DE PHP
# =============================================================================

show_info "Verificando PHP..."

# Verificar PHP CLI
if command -v php >/dev/null 2>&1; then
    PHP_CLI_AVAILABLE=true
    PHP_VERSION=$(php -v 2>/dev/null | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
    show_success "PHP CLI disponible - Versión: $PHP_VERSION"
    
    # Verificar versión PHP
    case "$PHP_VERSION" in
        7.4*|7.5*|7.6*|7.7*|7.8*|7.9*|8.*|9.*)
            show_success "Versión PHP compatible ($PHP_VERSION)"
            ;;
        7.0*|7.1*|7.2*|7.3*)
            show_warning "Versión PHP antigua ($PHP_VERSION) - Se recomienda 7.4+"
            ;;
        *)
            show_error "Versión PHP no compatible o no detectada ($PHP_VERSION)"
            ;;
    esac
else
    show_warning "PHP CLI no disponible en PATH"
    show_info "  - En CyberPanel, PHP puede estar configurado pero no en CLI"
    show_info "  - Esto es normal en algunos servidores compartidos"
fi

# =============================================================================
# 3. VERIFICAR EXTENSIONES PHP REQUERIDAS
# =============================================================================

show_info "Verificando extensiones PHP requeridas..."

if [ "$PHP_CLI_AVAILABLE" = true ]; then
    required_extensions="json session curl gd fileinfo"
    missing_extensions=""
    
    for ext in $required_extensions; do
        if php -m 2>/dev/null | grep -qi "^$ext$"; then
            show_success "Extensión $ext: Disponible"
        else
            show_warning "Extensión $ext: No detectada en CLI"
            if [ -z "$missing_extensions" ]; then
                missing_extensions="$ext"
            else
                missing_extensions="$missing_extensions $ext"
            fi
        fi
    done
    
    if [ -n "$missing_extensions" ]; then
        show_info "  - Extensiones faltantes: $missing_extensions"
        show_info "  - En CyberPanel: Websites > [Tu sitio] > PHP > Verificar extensiones"
        show_info "  - En Ubuntu: sudo apt install php-json php-curl php-gd"
    fi
else
    show_info "  - No se pueden verificar extensiones sin PHP CLI"
    show_info "  - Verifica en CyberPanel que estén instaladas: json, session, curl, gd, fileinfo"
fi

# =============================================================================
# 4. VERIFICAR ESTRUCTURA DE DIRECTORIOS Y PERMISOS
# =============================================================================

show_info "Verificando estructura de directorios..."

# Verificar directorio actual
CURRENT_DIR=$(pwd)
if echo "$CURRENT_DIR" | grep -q "qr-manager"; then
    show_success "Directorio qr-manager detectado: $CURRENT_DIR"
else
    show_warning "No pareces estar en un directorio qr-manager"
    show_info "  - Directorio actual: $CURRENT_DIR"
fi

# Verificar si estamos en un directorio web típico
if echo "$CURRENT_DIR" | grep -qE "(public_html|www|htdocs|web)"; then
    show_success "Directorio web detectado en la ruta"
elif echo "$CURRENT_DIR" | grep -q "home"; then
    show_success "Directorio de usuario detectado"
else
    show_warning "No parece ser un directorio web típico"
    show_info "  - Asegúrate de estar en: /home/tu-dominio.com/public_html/qr-manager/"
fi

# Verificar permisos de escritura
if [ -w . ]; then
    show_success "Permisos de escritura en directorio actual"
else
    show_error "Sin permisos de escritura en directorio actual"
    show_info "  - Ejecutar: chmod 755 ."
fi

# =============================================================================
# 5. VERIFICAR CONECTIVIDAD EXTERNA
# =============================================================================

show_info "Verificando conectividad externa..."

# Verificar conectividad a APIs esenciales
test_urls="https://api.qrserver.com https://ipapi.co"
connectivity_ok=true

for url in $test_urls; do
    if curl -s --max-time 5 --head "$url" >/dev/null 2>&1; then
        show_success "Conectividad OK: $url"
    elif wget -q --timeout=5 --spider "$url" >/dev/null 2>&1; then
        show_success "Conectividad OK: $url (wget)"
    else
        show_warning "Sin conectividad: $url"
        connectivity_ok=false
    fi
done

if [ "$connectivity_ok" = false ]; then
    show_info "  - Algunas funciones (QR generation, geolocation) pueden fallar"
    show_info "  - Verifica firewall y configuración de red"
fi

# =============================================================================
# 6. VERIFICAR DEPENDENCIAS DEL SISTEMA
# =============================================================================

show_info "Verificando dependencias del sistema..."

# Verificar herramientas básicas
tools="curl wget chmod chown mkdir touch"
for tool in $tools; do
    if command -v "$tool" >/dev/null 2>&1; then
        show_success "Herramienta disponible: $tool"
    else
        show_warning "Herramienta faltante: $tool"
    fi
done

# =============================================================================
# 7. REPORTE FINAL DE REQUISITOS
# =============================================================================

echo ""
echo "📊 REPORTE FINAL DE REQUISITOS"
echo "==============================="

show_info "Servidor Web: $SERVER_TYPE"
show_info "PHP Versión: $PHP_VERSION"
show_info "Directorio: $CURRENT_DIR"
show_info "Errores encontrados: $ERRORS"
show_info "Advertencias: $WARNINGS"

echo ""

# Decidir si continuar según los errores
if [ $ERRORS -gt 0 ]; then
    echo "❌ INSTALACIÓN NO RECOMENDADA"
    echo "Se encontraron $ERRORS errores críticos que deben resolverse."
    echo ""
    echo "¿Deseas continuar de todos modos? (no recomendado)"
    echo "Escribe 'si' para continuar o 'no' para salir:"
    read -r response
    if [ "$response" != "si" ] && [ "$response" != "SI" ] && [ "$response" != "s" ]; then
        echo "❌ Instalación cancelada. Resuelve los errores y vuelve a intentar."
        exit 1
    fi
elif [ $WARNINGS -gt 0 ]; then
    echo "⚠️  INSTALACIÓN CON ADVERTENCIAS"
    echo "Se encontraron $WARNINGS advertencias. La instalación puede continuar."
    echo ""
    echo "¿Deseas continuar? (recomendado: si)"
    echo "Escribe 'si' para continuar o 'no' para salir:"
    read -r response
    if [ "$response" = "no" ] || [ "$response" = "NO" ] || [ "$response" = "n" ]; then
        echo "❌ Instalación cancelada por el usuario."
        exit 1
    fi
else
    echo "✅ TODOS LOS REQUISITOS CUMPLIDOS"
    echo "Sistema óptimo para QR Manager"
fi

echo ""
echo "🚀 CONTINUANDO CON LA INSTALACIÓN..."
echo "====================================="

# Función para mostrar progreso
show_progress() {
    echo "📋 $1..."
}

# 1. Configurar permisos básicos
show_progress "Configurando permisos de archivos"
chmod 755 .
chmod 644 *.php 2>/dev/null || true
chmod 644 *.json 2>/dev/null || true
chmod 644 .htaccess 2>/dev/null || true
chmod 755 install-cyberpanel.sh 2>/dev/null || true

# 2. Crear directorio QR con permisos correctos
show_progress "Configurando directorio de QRs"
if [ ! -d "qr" ]; then
    mkdir qr
fi
chmod 755 qr

# Si ya existe el directorio ejemplo, configurar permisos
if [ -d "qr/ejemplo" ]; then
    chmod 755 qr/ejemplo
    chmod 644 qr/ejemplo/index.php 2>/dev/null || true
fi

# 3. Crear directorio de logs si no existe
show_progress "Configurando directorio de logs"
if [ ! -d "logs" ]; then
    mkdir logs
fi
chmod 755 logs
touch logs/access.log 2>/dev/null || true
touch logs/error.log 2>/dev/null || true
touch logs/security.log 2>/dev/null || true
chmod 644 logs/*.log 2>/dev/null || true

# 4. Configurar permisos especiales para archivos JSON
show_progress "Configurando permisos de archivos de datos"
chmod 666 *.json 2>/dev/null || true

# 5. Crear configuración específica según servidor detectado
show_progress "Creando configuración específica para $SERVER_TYPE"

if [ "$SERVER_TYPE" = "OpenLiteSpeed" ]; then
    # Configuración específica para OpenLiteSpeed
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA OPENLITESPEED + CYBERPANEL
# =============================================================================

## CONFIGURACIÓN EN CYBERPANEL:

### A. PHP Settings (Websites > [Tu sitio] > PHP):
- Versión: PHP 7.4 o superior
- max_execution_time = 300
- memory_limit = 256M
- post_max_size = 10M
- upload_max_filesize = 10M
- session.gc_maxlifetime = 3600
- allow_url_fopen = On

### B. Security Headers (Websites > [Tu sitio] > Headers):
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

### C. SSL/HTTPS (SSL > [Tu sitio]):
- Activar SSL con Let's Encrypt
- Forzar HTTPS redirect

### D. Compression (Websites > [Tu sitio] > Caching):
- Activar Gzip compression
- Cache para archivos estáticos

## VENTAJAS DE OPENLITESPEED:
- ✅ 6x más rápido que Apache
- ✅ 50% menos memoria
- ✅ 10,000+ conexiones simultáneas
- ✅ Cache integrado nativo

EOF

elif [ "$SERVER_TYPE" = "Apache" ] || [ "$SERVER_TYPE" = "Apache (httpd)" ]; then
    # Configuración específica para Apache
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA APACHE
# =============================================================================

## CONFIGURACIÓN REQUERIDA:

### A. Módulos Apache necesarios:
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate
sudo systemctl reload apache2

### B. Configuración Virtual Host:
<Directory "/path/to/qr-manager">
    AllowOverride All
    Require all granted
</Directory>

### C. PHP Settings (en .htaccess o php.ini):
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
allow_url_fopen = On

### D. SSL/HTTPS (recomendado):
sudo certbot --apache -d tu-dominio.com

## NOTA: El .htaccess incluido maneja las reglas de rewrite

EOF

else
    # Configuración genérica
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA $SERVER_TYPE
# =============================================================================

## CONFIGURACIÓN REQUERIDA:

### A. PHP Settings:
- Versión: PHP 7.4 o superior
- max_execution_time = 300
- memory_limit = 256M
- post_max_size = 10M
- upload_max_filesize = 10M
- allow_url_fopen = On

### B. Reglas de Rewrite necesarias:
- Proteger archivos *.json
- Configurar DirectoryIndex index.php
- Habilitar headers de seguridad

### C. Extensiones PHP requeridas:
- json, session, curl, gd, fileinfo

## NOTA: Configuración manual requerida para $SERVER_TYPE

EOF

fi

# 6. Crear script de verificación
show_progress "Creando script de verificación"
cat > verify-installation.php << 'EOF'
<?php
// Script de verificación para QR Manager
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Verificación de QR Manager</h2>";

// 1. Verificar versión PHP
echo "<h3>1. Configuración PHP</h3>";
echo "Versión PHP: " . PHP_VERSION . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";

// 2. Verificar extensiones
echo "<h3>2. Extensiones PHP</h3>";
$required = ['json', 'session', 'curl', 'gd', 'fileinfo'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "$status $ext<br>";
}

// 3. Verificar permisos de archivos
echo "<h3>3. Permisos de Archivos</h3>";
$files = ['config.php', 'users.json', 'redirects.json', 'qr/'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "✅ $file ($perms)<br>";
    } else {
        echo "❌ $file (no existe)<br>";
    }
}

// 4. Verificar configuración de sesiones
echo "<h3>4. Configuración de Sesiones</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// 5. Verificar conectividad externa
echo "<h3>5. Conectividad Externa</h3>";
$test_urls = [
    'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=test',
    'https://ipapi.co/json/'
];

foreach ($test_urls as $url) {
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents($url, false, $context);
    $status = $result !== false ? '✅' : '❌';
    echo "$status $url<br>";
}

echo "<h3>6. Estado de Archivos JSON</h3>";
// Verificar que los JSON no sean accesibles directamente
$json_files = ['users.json', 'redirects.json', 'analytics.json'];
foreach ($json_files as $json) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $json;
    $headers = @get_headers($url, 1);
    $protected = (strpos($headers[0], '403') !== false || strpos($headers[0], '404') !== false);
    $status = $protected ? '✅ Protegido' : '⚠️ Accesible';
    echo "$status $json<br>";
}

echo "<hr>";
echo "<p><strong>✅ Si ves todo en verde, la instalación está correcta!</strong></p>";
echo "<p><a href='index.php'>← Ir a QR Manager</a></p>";
?>
EOF

# 7. Crear archivo de configuración PHP personalizado
show_progress "Creando configuración PHP optimizada"
cat > php-config.ini << 'EOF'
; Configuración PHP optimizada para QR Manager
; Aplicar según tu servidor web

max_execution_time = 300
max_input_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
max_file_uploads = 20

; Configuración de sesiones
session.gc_maxlifetime = 3600
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On

; Configuración de errores (producción)
display_errors = Off
log_errors = On
error_log = logs/error.log

; Configuración de seguridad
allow_url_fopen = On
allow_url_include = Off
expose_php = Off

; Optimizaciones
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 0
EOF

# 8. Verificaciones finales
show_progress "Realizando verificaciones finales"

# Verificar que config.php sea accesible
if [ -r "config.php" ]; then
    echo "✅ config.php es legible"
else
    echo "❌ config.php no es legible"
fi

# 9. Información final
echo ""
echo "==========================================================================="
echo "🎉 INSTALACIÓN COMPLETADA PARA $SERVER_TYPE"
echo "==========================================================================="
echo ""
echo "📋 PRÓXIMOS PASOS:"
echo ""
echo "1. 🔧 CONFIGURAR SERVIDOR:"
echo "   - Consulta el archivo 'server-config.txt' para configuraciones específicas"
echo ""
echo "2. 🌐 CONFIGURAR DOMINIO:"
echo "   - Edita config.php línea 4:"
echo "   - Cambia 'http://localhost/qr-manager' por tu dominio real"
echo ""
echo "3. 🔍 VERIFICAR INSTALACIÓN:"
echo "   - Accede a: https://tu-dominio.com/qr-manager/verify-installation.php"
echo "   - Verifica que todo esté en verde"
echo ""
echo "4. 🚀 USAR LA APLICACIÓN:"
echo "   - URL: https://tu-dominio.com/qr-manager/"
echo "   - Usuario: admin"
echo "   - Contraseña: password"
echo ""
echo "📚 ARCHIVOS CREADOS:"
echo "   - server-config.txt (configuración específica para $SERVER_TYPE)"
echo "   - verify-installation.php (verificación automática)"
echo "   - php-config.ini (configuración PHP recomendada)"
echo ""
echo "📊 RESUMEN DE VERIFICACIÓN:"
echo "   - Servidor: $SERVER_TYPE"
echo "   - PHP: $PHP_VERSION"
echo "   - Errores: $ERRORS"
echo "   - Advertencias: $WARNINGS"
echo ""
echo "✅ ¡QR Manager está listo para $SERVER_TYPE!"
echo "==========================================================================="