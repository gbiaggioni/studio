#!/bin/bash

# =============================================================================
# SCRIPT DE INSTALACIÓN UNIVERSAL QR MANAGER
# Compatible con: Windows (XAMPP), Linux (Apache/OpenLiteSpeed/Nginx)
# Incluye soporte para Apache sin mod_rewrite
# =============================================================================

echo "🚀 Iniciando instalación UNIVERSAL de QR Manager..."
echo "Compatible con Windows (XAMPP) y Linux (Apache/OpenLiteSpeed/Nginx)"
echo "========================================================================"

# Verificar que estamos en el directorio correcto
if [ ! -f "config.php" ]; then
    echo "❌ Error: No se encuentra config.php. Ejecuta este script desde el directorio qr-manager/"
    exit 1
fi

echo "✅ Directorio verificado correctamente"

# =============================================================================
# DETECCIÓN DE SISTEMA OPERATIVO
# =============================================================================

echo ""
echo "🔍 DETECTANDO SISTEMA OPERATIVO Y ENTORNO..."
echo "=============================================="

# Variables globales
OS_TYPE=""
ENVIRONMENT=""
SERVER_TYPE=""
PHP_VERSION=""
XAMPP_PATH=""
APACHE_VERSION=""
ERRORS=0
WARNINGS=0
USE_MOD_REWRITE=true

# Funciones de reporte
show_error() {
    echo "❌ ERROR: $1"
    ERRORS=$((ERRORS + 1))
}

show_warning() {
    echo "⚠️  ADVERTENCIA: $1"
    WARNINGS=$((WARNINGS + 1))
}

show_success() {
    echo "✅ $1"
}

show_info() {
    echo "📋 $1"
}

# Detectar sistema operativo
if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]] || [[ "$OSTYPE" == "win32" ]] || [ -n "$WINDIR" ]; then
    OS_TYPE="Windows"
    show_success "Sistema operativo: Windows detectado"
    
    # Detectar XAMPP en Windows
    XAMPP_PATHS=(
        "/c/xampp"
        "/d/xampp" 
        "/e/xampp"
        "C:/xampp"
        "D:/xampp"
        "E:/xampp"
        "$PROGRAMFILES/xampp"
        "$HOME/xampp"
    )
    
    for path in "${XAMPP_PATHS[@]}"; do
        if [ -d "$path" ] && [ -f "$path/apache/bin/httpd.exe" ]; then
            XAMPP_PATH="$path"
            ENVIRONMENT="XAMPP"
            SERVER_TYPE="Apache (XAMPP)"
            show_success "XAMPP detectado en: $XAMPP_PATH"
            break
        fi
    done
    
    if [ -z "$XAMPP_PATH" ]; then
        show_error "XAMPP no encontrado en ubicaciones típicas"
        show_info "  - Instala XAMPP desde: https://www.apachefriends.org/"
        show_info "  - Ubicaciones verificadas: ${XAMPP_PATHS[*]}"
    fi

elif [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "linux"* ]]; then
    OS_TYPE="Linux"
    show_success "Sistema operativo: Linux detectado"
    
    # Detectar distribución Linux
    if [ -f /etc/lsb-release ]; then
        DISTRO=$(grep DISTRIB_ID /etc/lsb-release | cut -d= -f2)
        VERSION=$(grep DISTRIB_RELEASE /etc/lsb-release | cut -d= -f2)
        show_info "Distribución: $DISTRO $VERSION"
    elif [ -f /etc/os-release ]; then
        DISTRO=$(grep ^NAME /etc/os-release | cut -d= -f2 | tr -d '"')
        show_info "Distribución: $DISTRO"
    fi

elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS_TYPE="macOS"
    show_success "Sistema operativo: macOS detectado"
    show_info "Buscando servidores web en macOS..."

else
    OS_TYPE="Desconocido"
    show_warning "Sistema operativo no reconocido: $OSTYPE"
fi

# =============================================================================
# DETECCIÓN DE SERVIDOR WEB EN LINUX/MACOS
# =============================================================================

if [ "$OS_TYPE" != "Windows" ]; then
    show_info "Detectando servidor web en $OS_TYPE..."
    
    # Verificar OpenLiteSpeed
    if command -v litespeed >/dev/null 2>&1 || [ -f "/usr/local/lsws/bin/litespeed" ] || [ -d "/usr/local/lsws" ]; then
        SERVER_TYPE="OpenLiteSpeed"
        ENVIRONMENT="OpenLiteSpeed"
        show_success "OpenLiteSpeed detectado"
        
        # Verificar CyberPanel
        if command -v cyberpanel >/dev/null 2>&1 || [ -f "/usr/local/CyberCP/cyberpanel/manage.py" ]; then
            ENVIRONMENT="CyberPanel + OpenLiteSpeed"
            show_success "CyberPanel detectado - Configuración óptima"
        fi

    # Verificar Apache
    elif command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
        if command -v apache2 >/dev/null 2>&1; then
            SERVER_TYPE="Apache"
            APACHE_VERSION=$(apache2 -v 2>/dev/null | head -n1 | cut -d'/' -f2 | cut -d' ' -f1)
        else
            SERVER_TYPE="Apache (httpd)"
            APACHE_VERSION=$(httpd -v 2>/dev/null | head -n1 | cut -d'/' -f2 | cut -d' ' -f1)
        fi
        
        ENVIRONMENT="Apache"
        show_success "Apache detectado - Versión: $APACHE_VERSION"
        
        # Verificar mod_rewrite
        if apache2ctl -M 2>/dev/null | grep -q "rewrite_module" || httpd -M 2>/dev/null | grep -q "rewrite_module"; then
            show_success "mod_rewrite está habilitado"
            echo ""
            echo "❓ CONFIGURACIÓN DE APACHE:"
            echo "¿Deseas usar mod_rewrite para URLs amigables? (recomendado)"
            echo "1) Sí - Usar mod_rewrite (URLs: /qr/id)"
            echo "2) No - Sin mod_rewrite (URLs: /qr/id/index.php)"
            echo ""
            read -p "Selecciona opción (1-2): " rewrite_choice
            
            case $rewrite_choice in
                2)
                    USE_MOD_REWRITE=false
                    ENVIRONMENT="Apache (sin mod_rewrite)"
                    show_info "Configuración seleccionada: Apache sin mod_rewrite"
                    ;;
                *)
                    USE_MOD_REWRITE=true
                    ENVIRONMENT="Apache (con mod_rewrite)"
                    show_info "Configuración seleccionada: Apache con mod_rewrite"
                    ;;
            esac
        else
            show_warning "mod_rewrite NO detectado - Configurando para funcionar sin él"
            USE_MOD_REWRITE=false
            ENVIRONMENT="Apache (sin mod_rewrite)"
            show_info "  - Se configurará para funcionar sin mod_rewrite"
            show_info "  - URLs serán: /qr/id/index.php en lugar de /qr/id"
        fi

    # Verificar Nginx
    elif command -v nginx >/dev/null 2>&1; then
        SERVER_TYPE="Nginx"
        ENVIRONMENT="Nginx"
        NGINX_VERSION=$(nginx -v 2>&1 | cut -d'/' -f2)
        show_success "Nginx detectado - Versión: $NGINX_VERSION"
        show_warning "Nginx requiere configuración manual de reglas"

    # No se detectó servidor web
    else
        show_error "No se detectó servidor web"
        show_info "  - Para Ubuntu: sudo apt install apache2"
        show_info "  - Para CentOS: sudo yum install httpd"
        show_info "  - O instala OpenLiteSpeed con CyberPanel"
    fi
fi

# =============================================================================
# VERIFICACIÓN DE PHP
# =============================================================================

show_info "Verificando PHP..."

# Rutas de PHP según el entorno
PHP_PATHS=()
if [ "$ENVIRONMENT" = "XAMPP" ]; then
    PHP_PATHS=("$XAMPP_PATH/php/php.exe" "$XAMPP_PATH/php/php")
elif [ "$OS_TYPE" = "Windows" ]; then
    PHP_PATHS=("C:/php/php.exe" "D:/php/php.exe" "php.exe")
else
    PHP_PATHS=("php")
fi

PHP_FOUND=false
for php_path in "${PHP_PATHS[@]}"; do
    if command -v "$php_path" >/dev/null 2>&1; then
        PHP_FOUND=true
        PHP_VERSION=$("$php_path" -v 2>/dev/null | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
        show_success "PHP encontrado: $php_path - Versión: $PHP_VERSION"
        
        # Verificar versión PHP
        case "$PHP_VERSION" in
            7.4*|7.5*|7.6*|7.7*|7.8*|7.9*|8.*|9.*)
                show_success "Versión PHP compatible ($PHP_VERSION)"
                ;;
            7.0*|7.1*|7.2*|7.3*)
                show_warning "Versión PHP antigua ($PHP_VERSION) - Se recomienda 7.4+"
                ;;
            *)
                show_warning "Versión PHP no estándar ($PHP_VERSION)"
                ;;
        esac
        
        # Verificar extensiones PHP
        show_info "Verificando extensiones PHP..."
        required_extensions="json session curl gd fileinfo"
        missing_extensions=""
        
        for ext in $required_extensions; do
            if "$php_path" -m 2>/dev/null | grep -qi "^$ext$"; then
                show_success "Extensión $ext: Disponible"
            else
                show_warning "Extensión $ext: No detectada"
                if [ -z "$missing_extensions" ]; then
                    missing_extensions="$ext"
                else
                    missing_extensions="$missing_extensions $ext"
                fi
            fi
        done
        
        if [ -n "$missing_extensions" ]; then
            show_info "  - Extensiones faltantes: $missing_extensions"
            if [ "$ENVIRONMENT" = "XAMPP" ]; then
                show_info "  - En XAMPP: Editar $XAMPP_PATH/php/php.ini y descomentar extensions"
            else
                show_info "  - En Linux: sudo apt install php-json php-curl php-gd"
            fi
        fi
        
        break
    fi
done

if [ "$PHP_FOUND" = false ]; then
    show_error "PHP no encontrado"
    if [ "$ENVIRONMENT" = "XAMPP" ]; then
        show_info "  - Verifica que XAMPP esté instalado correctamente"
        show_info "  - Inicia XAMPP Control Panel y arranca Apache"
    else
        show_info "  - Instalar PHP: sudo apt install php"
    fi
fi

# =============================================================================
# VERIFICACIÓN DE PERMISOS Y DIRECTORIOS
# =============================================================================

show_info "Verificando estructura de directorios..."

CURRENT_DIR=$(pwd)
if echo "$CURRENT_DIR" | grep -q "qr-manager"; then
    show_success "Directorio qr-manager detectado: $CURRENT_DIR"
else
    show_warning "No pareces estar en un directorio qr-manager"
    show_info "  - Directorio actual: $CURRENT_DIR"
fi

# Verificar ubicación típica según el entorno
if [ "$ENVIRONMENT" = "XAMPP" ]; then
    if echo "$CURRENT_DIR" | grep -q "htdocs"; then
        show_success "Directorio dentro de htdocs detectado (XAMPP)"
    else
        show_warning "No parece estar en htdocs de XAMPP"
        show_info "  - Ubicación recomendada: $XAMPP_PATH/htdocs/qr-manager/"
    fi
elif echo "$CURRENT_DIR" | grep -qE "(public_html|www|htdocs|web|var/www)"; then
    show_success "Directorio web detectado en la ruta"
else
    show_warning "No parece ser un directorio web típico"
    show_info "  - Ubicación recomendada para Apache: /var/www/html/qr-manager/"
fi

# Verificar permisos de escritura
if [ -w . ]; then
    show_success "Permisos de escritura en directorio actual"
else
    show_error "Sin permisos de escritura en directorio actual"
    if [ "$OS_TYPE" = "Windows" ]; then
        show_info "  - Ejecutar como Administrador o cambiar permisos de carpeta"
    else
        show_info "  - Ejecutar: chmod 755 ."
    fi
fi

# =============================================================================
# VERIFICACIÓN DE CONECTIVIDAD
# =============================================================================

show_info "Verificando conectividad externa..."

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
    show_info "  - Algunas funciones pueden no funcionar correctamente"
    show_info "  - Verifica conexión a internet y firewall"
fi

# =============================================================================
# REPORTE FINAL Y DECISIÓN
# =============================================================================

echo ""
echo "📊 REPORTE FINAL DE VERIFICACIÓN"
echo "================================="

show_info "Sistema Operativo: $OS_TYPE"
show_info "Entorno: $ENVIRONMENT"
show_info "Servidor Web: $SERVER_TYPE"
show_info "PHP Versión: $PHP_VERSION"
show_info "Directorio: $CURRENT_DIR"
show_info "mod_rewrite: $([ "$USE_MOD_REWRITE" = true ] && echo "Habilitado" || echo "Deshabilitado")"
show_info "Errores encontrados: $ERRORS"
show_info "Advertencias: $WARNINGS"

echo ""

# Decidir si continuar
if [ $ERRORS -gt 0 ]; then
    echo "❌ INSTALACIÓN NO RECOMENDADA"
    echo "Se encontraron $ERRORS errores críticos."
    echo ""
    read -p "¿Continuar de todos modos? (no recomendado) [s/N]: " response
    if [[ ! "$response" =~ ^[SsYy]$ ]]; then
        echo "❌ Instalación cancelada."
        exit 1
    fi
elif [ $WARNINGS -gt 0 ]; then
    echo "⚠️  INSTALACIÓN CON ADVERTENCIAS"
    echo "Se encontraron $WARNINGS advertencias."
    echo ""
    read -p "¿Continuar con la instalación? [S/n]: " response
    if [[ "$response" =~ ^[Nn]$ ]]; then
        echo "❌ Instalación cancelada por el usuario."
        exit 1
    fi
else
    echo "✅ TODOS LOS REQUISITOS CUMPLIDOS"
    echo "Sistema óptimo para QR Manager"
fi

echo ""
echo "🚀 INICIANDO INSTALACIÓN PARA $ENVIRONMENT..."
echo "=============================================="

# =============================================================================
# CONFIGURACIÓN DE PERMISOS
# =============================================================================

show_info "Configurando permisos de archivos..."

if [ "$OS_TYPE" = "Windows" ]; then
    # En Windows, los permisos se manejan diferente
    show_info "Sistema Windows detectado - permisos automáticos"
else
    # Configuración estándar para Linux/macOS
    chmod 755 . 2>/dev/null || true
    chmod 644 *.php 2>/dev/null || true
    chmod 644 *.json 2>/dev/null || true
    chmod 644 .htaccess 2>/dev/null || true
    chmod 755 *.sh 2>/dev/null || true
fi

# Crear directorios necesarios
show_info "Creando estructura de directorios..."

mkdir -p qr 2>/dev/null || true
mkdir -p logs 2>/dev/null || true

if [ "$OS_TYPE" != "Windows" ]; then
    chmod 755 qr logs 2>/dev/null || true
    chmod 666 *.json 2>/dev/null || true
fi

# =============================================================================
# GENERAR CONFIGURACIÓN ESPECÍFICA
# =============================================================================

show_info "Generando configuración específica para $ENVIRONMENT..."

if [ "$ENVIRONMENT" = "XAMPP" ]; then
    # Configuración para XAMPP en Windows
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA XAMPP (WINDOWS)
# =============================================================================

## CONFIGURACIÓN XAMPP:

### A. Iniciar servicios XAMPP:
1. Abrir XAMPP Control Panel
2. Iniciar Apache
3. Iniciar MySQL (opcional, para futuras funciones)

### B. Configuración PHP ($XAMPP_PATH/php/php.ini):
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
allow_url_fopen = On

; Descomentar extensiones (quitar ;):
extension=curl
extension=gd
extension=fileinfo

### C. Configuración Apache ($XAMPP_PATH/apache/conf/httpd.conf):
; Verificar que esté habilitado:
LoadModule rewrite_module modules/mod_rewrite.so

### D. Ubicación recomendada:
$XAMPP_PATH/htdocs/qr-manager/

### E. URL de acceso:
http://localhost/qr-manager/

## VENTAJAS DE XAMPP:
- ✅ Instalación rápida y sencilla
- ✅ Apache + PHP + MySQL incluidos
- ✅ Panel de control gráfico
- ✅ Ideal para desarrollo local

EOF

elif [ "$ENVIRONMENT" = "Apache (sin mod_rewrite)" ]; then
    # Configuración para Apache sin mod_rewrite
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA APACHE SIN MOD_REWRITE
# =============================================================================

## CONFIGURACIÓN APACHE:

### A. Instalación (si no está instalado):
sudo apt install apache2 php libapache2-mod-php

### B. Configuración PHP (/etc/php/*/apache2/php.ini):
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
allow_url_fopen = On

### C. Extensiones PHP:
sudo apt install php-json php-curl php-gd php-fileinfo

### D. Virtual Host (/etc/apache2/sites-available/qr-manager.conf):
<VirtualHost *:80>
    DocumentRoot /var/www/html/qr-manager
    ServerName tu-dominio.com
    
    <Directory /var/www/html/qr-manager>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

### E. Activar sitio:
sudo a2ensite qr-manager
sudo systemctl reload apache2

## FUNCIONAMIENTO SIN MOD_REWRITE:
- ✅ URLs funcionan como: /qr/abc123/index.php
- ✅ No requiere configuración compleja
- ✅ Compatible con hosting básico
- ✅ Mismo funcionamiento que con mod_rewrite

EOF

elif [ "$ENVIRONMENT" = "Apache (con mod_rewrite)" ]; then
    # Configuración para Apache con mod_rewrite
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA APACHE CON MOD_REWRITE
# =============================================================================

## CONFIGURACIÓN APACHE:

### A. Módulos necesarios:
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo systemctl reload apache2

### B. Configuración PHP:
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
allow_url_fopen = On

### C. Virtual Host con mod_rewrite:
<VirtualHost *:80>
    DocumentRoot /var/www/html/qr-manager
    ServerName tu-dominio.com
    
    <Directory /var/www/html/qr-manager>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

### D. SSL (recomendado):
sudo certbot --apache -d tu-dominio.com

## VENTAJAS CON MOD_REWRITE:
- ✅ URLs amigables: /qr/abc123
- ✅ Mejor SEO
- ✅ .htaccess completo funcional
- ✅ Redirecciones automáticas

EOF

elif [ "$ENVIRONMENT" = "CyberPanel + OpenLiteSpeed" ]; then
    # Configuración para CyberPanel
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA CYBERPANEL + OPENLITESPEED
# =============================================================================

## CONFIGURACIÓN EN CYBERPANEL:

### A. PHP Settings (Websites > [Tu sitio] > PHP):
- Versión: PHP 7.4 o superior
- max_execution_time = 300
- memory_limit = 256M
- post_max_size = 10M
- upload_max_filesize = 10M
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

else
    # Configuración genérica
    cat > server-config.txt << EOF
# =============================================================================
# CONFIGURACIÓN PARA $ENVIRONMENT
# =============================================================================

## CONFIGURACIÓN REQUERIDA:

### A. PHP Settings:
max_execution_time = 300
memory_limit = 256M
post_max_size = 10M
upload_max_filesize = 10M
allow_url_fopen = On

### B. Extensiones PHP requeridas:
- json, session, curl, gd, fileinfo

### C. Configuración específica:
- Consultar documentación del servidor web
- Configurar permisos de directorio
- Habilitar procesamiento PHP

## NOTA: Configuración manual requerida para $ENVIRONMENT

EOF

fi

# =============================================================================
# CONFIGURAR APLICACIÓN SEGÚN ENTORNO
# =============================================================================

show_info "Configurando aplicación según entorno..."

# Configurar URL base en config.php
if [ "$ENVIRONMENT" = "XAMPP" ]; then
    # Para XAMPP, usar localhost
    BASE_URL_CONFIG="http://localhost/qr-manager"
else
    # Para Linux, usar configuración genérica
    BASE_URL_CONFIG="https://tu-dominio.com/qr-manager"
fi

# Actualizar config.php si es necesario
if grep -q "localhost/qr-manager" config.php; then
    if [ "$ENVIRONMENT" != "XAMPP" ]; then
        show_info "Recuerda actualizar BASE_URL en config.php con tu dominio real"
    fi
else
    show_info "Configuración BASE_URL actual: $(grep 'BASE_URL' config.php)"
fi

# Configurar .htaccess según mod_rewrite
if [ "$USE_MOD_REWRITE" = false ]; then
    show_info "Configurando .htaccess para funcionar sin mod_rewrite..."
    
    # Crear .htaccess simplificado
    cat > .htaccess << 'EOF'
# =============================================================================
# QR MANAGER - CONFIGURACIÓN SIN MOD_REWRITE
# =============================================================================

# Configuración básica de seguridad
Options -Indexes
ServerSignature Off

# Proteger archivos JSON
<Files "*.json">
    Require all denied
</Files>

# Proteger archivos de logs
<Files "*.log">
    Require all denied
</Files>

# Configurar tipos MIME
<IfModule mod_mime.c>
    AddType application/json .json
    AddType application/javascript .js
    AddType text/css .css
</IfModule>

# Headers de seguridad básicos
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
</IfModule>

# DirectoryIndex
DirectoryIndex index.php

# NO usar mod_rewrite - URLs serán /qr/id/index.php
EOF

    show_success ".htaccess configurado para funcionar sin mod_rewrite"
    show_info "Las URLs de QR serán: /qr/abc123/index.php"
fi

# =============================================================================
# CREAR ARCHIVOS DE VERIFICACIÓN
# =============================================================================

show_info "Creando archivos de verificación..."

# Script de verificación PHP
cat > verify-installation.php << EOF
<?php
// Script de verificación universal para QR Manager
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Verificación QR Manager - $ENVIRONMENT</h2>";

// 1. Información del sistema
echo "<h3>1. Información del Sistema</h3>";
echo "OS: " . PHP_OS . "<br>";
echo "Servidor: " . (\$_SERVER['SERVER_SOFTWARE'] ?? 'No detectado') . "<br>";
echo "PHP Versión: " . PHP_VERSION . "<br>";
echo "SAPI: " . php_sapi_name() . "<br>";

// 2. Verificar extensiones
echo "<h3>2. Extensiones PHP</h3>";
\$required = ['json', 'session', 'curl', 'gd', 'fileinfo'];
foreach (\$required as \$ext) {
    \$status = extension_loaded(\$ext) ? '✅' : '❌';
    echo "\$status \$ext<br>";
}

// 3. Verificar archivos
echo "<h3>3. Archivos del Sistema</h3>";
\$files = ['config.php', 'users.json', 'redirects.json', 'qr/', 'logs/'];
foreach (\$files as \$file) {
    if (file_exists(\$file)) {
        \$perms = is_dir(\$file) ? 'DIR' : substr(sprintf('%o', fileperms(\$file)), -4);
        echo "✅ \$file (\$perms)<br>";
    } else {
        echo "❌ \$file (no existe)<br>";
    }
}

// 4. Verificar configuración PHP
echo "<h3>4. Configuración PHP</h3>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ Sí' : '❌ No') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";

// 5. Verificar conectividad
echo "<h3>5. Conectividad Externa</h3>";
\$test_urls = [
    'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=test',
    'https://ipapi.co/json/'
];

foreach (\$test_urls as \$url) {
    \$context = stream_context_create(['http' => ['timeout' => 5]]);
    \$result = @file_get_contents(\$url, false, \$context);
    \$status = \$result !== false ? '✅' : '❌';
    echo "\$status \$url<br>";
}

// 6. Información específica del entorno
echo "<h3>6. Configuración del Entorno</h3>";
echo "Document Root: " . (\$_SERVER['DOCUMENT_ROOT'] ?? 'No detectado') . "<br>";
echo "Script Path: " . \$_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Mod Rewrite: $USE_MOD_REWRITE<br>";

echo "<hr>";
echo "<p><strong>✅ Si todo está en verde, la instalación es correcta</strong></p>";
echo "<p><a href='index.php'>← Ir a QR Manager</a></p>";
?>
EOF

# =============================================================================
# INFORMACIÓN FINAL
# =============================================================================

echo ""
echo "=========================================================================="
echo "🎉 INSTALACIÓN COMPLETADA PARA $ENVIRONMENT"
echo "=========================================================================="
echo ""
echo "📊 RESUMEN DE INSTALACIÓN:"
echo "   - Sistema: $OS_TYPE"
echo "   - Entorno: $ENVIRONMENT"
echo "   - Servidor: $SERVER_TYPE"
echo "   - PHP: $PHP_VERSION"
echo "   - mod_rewrite: $([ "$USE_MOD_REWRITE" = true ] && echo "Habilitado" || echo "Deshabilitado")"
echo "   - Errores: $ERRORS | Advertencias: $WARNINGS"
echo ""
echo "📋 PRÓXIMOS PASOS:"
echo ""
echo "1. 🔧 CONFIGURAR SERVIDOR:"
echo "   - Consulta: server-config.txt"
echo "   - Sigue las instrucciones específicas para $ENVIRONMENT"
echo ""

if [ "$ENVIRONMENT" = "XAMPP" ]; then
    echo "2. 🌐 ACCEDER A LA APLICACIÓN:"
    echo "   - URL: http://localhost/qr-manager/"
    echo "   - Usuario: admin | Contraseña: password"
else
    echo "2. 🌐 CONFIGURAR DOMINIO:"
    echo "   - Edita config.php línea 4 con tu dominio real"
    echo "   - Actualiza BASE_URL: '$BASE_URL_CONFIG'"
fi

echo ""
echo "3. 🔍 VERIFICAR INSTALACIÓN:"
if [ "$ENVIRONMENT" = "XAMPP" ]; then
    echo "   - http://localhost/qr-manager/verify-installation.php"
else
    echo "   - https://tu-dominio.com/qr-manager/verify-installation.php"
fi
echo ""
echo "4. 📚 ARCHIVOS CREADOS:"
echo "   - server-config.txt (configuración específica)"
echo "   - verify-installation.php (verificación web)"
if [ "$USE_MOD_REWRITE" = false ]; then
    echo "   - .htaccess (sin mod_rewrite)"
fi
echo ""

if [ "$USE_MOD_REWRITE" = false ]; then
    echo "🔗 URLS DE QR (SIN MOD_REWRITE):"
    echo "   - Formato: /qr/abc123/index.php"
    echo "   - Ejemplo: http://tu-dominio.com/qr-manager/qr/test/index.php"
    echo ""
fi

echo "✅ ¡QR Manager configurado para $ENVIRONMENT!"
echo "=========================================================================="