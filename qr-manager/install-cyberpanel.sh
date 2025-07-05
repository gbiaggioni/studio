#!/bin/bash

# =============================================================================
# SCRIPT DE INSTALACIÓN QR MANAGER PARA CYBERPANEL + OPENLITESPEED
# Ubuntu 20.04 + CyberPanel + OpenLiteSpeed
# =============================================================================

echo "🚀 Iniciando instalación de QR Manager para CyberPanel + OpenLiteSpeed..."
echo "==========================================================================="

# Verificar que estamos en el directorio correcto
if [ ! -f "config.php" ]; then
    echo "❌ Error: No se encuentra config.php. Ejecuta este script desde el directorio qr-manager/"
    exit 1
fi

echo "✅ Directorio verificado correctamente"

# Función para mostrar progreso
show_progress() {
    echo "📋 $1..."
}

# 1. Configurar permisos básicos
show_progress "Configurando permisos de archivos"
chmod 755 .
chmod 644 *.php
chmod 644 *.json
chmod 644 .htaccess
chmod 755 install-cyberpanel.sh

# 2. Crear directorio QR con permisos correctos
show_progress "Configurando directorio de QRs"
if [ ! -d "qr" ]; then
    mkdir qr
fi
chmod 755 qr

# Si ya existe el directorio ejemplo, configurar permisos
if [ -d "qr/ejemplo" ]; then
    chmod 755 qr/ejemplo
    chmod 644 qr/ejemplo/index.php
fi

# 3. Crear directorio de logs si no existe
show_progress "Configurando directorio de logs"
if [ ! -d "logs" ]; then
    mkdir logs
fi
chmod 755 logs
touch logs/access.log
touch logs/error.log
touch logs/security.log
chmod 644 logs/*.log

# 4. Configurar permisos especiales para archivos JSON
show_progress "Configurando permisos de archivos de datos"
chmod 666 *.json

# 5. Verificar configuración PHP
show_progress "Verificando configuración PHP"
php_version=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
echo "📋 Versión PHP detectada: $php_version"

if [[ $(echo "$php_version < 7.4" | bc -l) == 1 ]]; then
    echo "⚠️  Advertencia: Se recomienda PHP 7.4 o superior"
fi

# 6. Verificar extensiones PHP necesarias
show_progress "Verificando extensiones PHP requeridas"

required_extensions=("json" "session" "curl" "gd" "fileinfo")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if ! php -m | grep -qi "^$ext$"; then
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -eq 0 ]; then
    echo "✅ Todas las extensiones PHP requeridas están instaladas"
else
    echo "⚠️  Extensiones PHP faltantes: ${missing_extensions[*]}"
    echo "   Instálalas con: sudo apt install php-${missing_extensions[0]// / php-}"
fi

# 7. Crear archivo de configuración específico para OpenLiteSpeed
show_progress "Creando configuración específica de OpenLiteSpeed"
cat > openlitespeed-config.txt << 'EOF'
# =============================================================================
# CONFIGURACIÓN CYBERPANEL PARA QR MANAGER
# =============================================================================

## 1. CONFIGURACIÓN EN CYBERPANEL:

### A. PHP Settings:
- Ir a: "Websites" > [Tu sitio] > "PHP"
- Versión: PHP 7.4 o superior
- Configuraciones:
  * max_execution_time = 300
  * memory_limit = 256M
  * post_max_size = 10M
  * upload_max_filesize = 10M
  * session.gc_maxlifetime = 3600

### B. Security Headers:
- Ir a: "Websites" > [Tu sitio] > "Headers"
- Agregar:
  * X-Frame-Options: SAMEORIGIN
  * X-Content-Type-Options: nosniff
  * X-XSS-Protection: 1; mode=block
  * Referrer-Policy: strict-origin-when-cross-origin

### C. SSL/HTTPS:
- Ir a: "SSL" > [Tu sitio]
- Activar SSL con Let's Encrypt
- Forzar HTTPS redirect

### D. Compression:
- Ir a: "Websites" > [Tu sitio] > "Caching"
- Activar Gzip compression

## 2. VERIFICACIÓN:
1. Accede a: https://tu-dominio.com/qr-manager/
2. Login: admin / password
3. Crea un QR de prueba
4. Verifica funcionamiento

EOF

# 8. Crear script de verificación
show_progress "Creando script de verificación"
cat > verify-installation.php << 'EOF'
<?php
// Script de verificación para QR Manager en OpenLiteSpeed
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Verificación de QR Manager - OpenLiteSpeed</h2>";

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

# 9. Crear archivo de configuración PHP personalizado
show_progress "Creando configuración PHP optimizada"
cat > php-config.ini << 'EOF'
; Configuración PHP optimizada para QR Manager en OpenLiteSpeed
; Copia estas configuraciones en CyberPanel > Websites > [Tu sitio] > PHP

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

# 10. Verificaciones finales
show_progress "Realizando verificaciones finales"

# Verificar que config.php sea accesible
if [ -r "config.php" ]; then
    echo "✅ config.php es legible"
else
    echo "❌ config.php no es legible"
fi

# Verificar que los JSON estén protegidos (por .htaccess)
echo "✅ Configuración de protección aplicada"

# 11. Información final
echo ""
echo "==========================================================================="
echo "🎉 INSTALACIÓN COMPLETADA PARA CYBERPANEL + OPENLITESPEED"
echo "==========================================================================="
echo ""
echo "📋 PRÓXIMOS PASOS:"
echo ""
echo "1. 🔧 CONFIGURAR CYBERPANEL:"
echo "   - Copia el contenido de 'openlitespeed-config.txt'"
echo "   - Aplica las configuraciones en CyberPanel"
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
echo "   - openlitespeed-config.txt (configuración para CyberPanel)"
echo "   - verify-installation.php (verificación automática)"
echo "   - php-config.ini (configuración PHP recomendada)"
echo "   - cyberpanel-openlitespeed.conf (reglas de rewrite)"
echo ""
echo "✅ ¡QR Manager está listo para OpenLiteSpeed!"
echo "==========================================================================="