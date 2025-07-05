<?php
/**
 * FUNCIONES AUXILIARES PARA CREACIÓN DE QRs
 * Compatible con mod_rewrite y sin mod_rewrite
 */

require_once 'config.php';

/**
 * Detectar si el sistema debe usar mod_rewrite
 */
function shouldUseModRewrite() {
    // Verificar si existe archivo de configuración
    $configFile = __DIR__ . '/.mod_rewrite_config';
    if (file_exists($configFile)) {
        $config = trim(file_get_contents($configFile));
        return $config === 'enabled';
    }
    
    // Auto-detectar según el servidor web
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    
    // Si es Apache, intentar detectar mod_rewrite
    if (stripos($serverSoftware, 'apache') !== false) {
        // Verificar si mod_rewrite está disponible
        if (function_exists('apache_get_modules')) {
            return in_array('mod_rewrite', apache_get_modules());
        }
        
        // Verificar mediante .htaccess
        if (file_exists(__DIR__ . '/.htaccess')) {
            $htaccess = file_get_contents(__DIR__ . '/.htaccess');
            return strpos($htaccess, 'RewriteEngine') !== false;
        }
    }
    
    // Para OpenLiteSpeed, asumir que rewrite está disponible
    if (stripos($serverSoftware, 'litespeed') !== false) {
        return true;
    }
    
    // Por defecto, no usar mod_rewrite para máxima compatibilidad
    return false;
}

/**
 * Configurar el modo de rewrite
 */
function setModRewriteMode($enabled) {
    $configFile = __DIR__ . '/.mod_rewrite_config';
    file_put_contents($configFile, $enabled ? 'enabled' : 'disabled');
}

/**
 * Crear carpeta QR con el archivo index.php correcto
 */
function createQRFolder($qrId, $destinationUrl) {
    $qrPath = QR_DIR . $qrId;
    
    // Crear directorio si no existe
    if (!file_exists($qrPath)) {
        if (!mkdir($qrPath, 0755, true)) {
            return false;
        }
    }
    
    $indexPath = $qrPath . '/index.php';
    $useModRewrite = shouldUseModRewrite();
    
    if ($useModRewrite) {
        // Crear index.php para modo con mod_rewrite
        $indexContent = createIndexWithRewrite($qrId);
    } else {
        // Crear index.php para modo sin mod_rewrite
        $indexContent = createIndexWithoutRewrite($qrId);
    }
    
    return file_put_contents($indexPath, $indexContent) !== false;
}

/**
 * Crear contenido index.php para modo CON mod_rewrite
 */
function createIndexWithRewrite($qrId) {
    return <<<PHP
<?php
/**
 * Redirección QR con mod_rewrite
 * ID: {$qrId}
 * Generado: " . date('Y-m-d H:i:s') . "
 */

// Redirigir al sistema centralizado de redirección
header('Location: ../../redirect.php?id={$qrId}');
exit;
?>
PHP;
}

/**
 * Crear contenido index.php para modo SIN mod_rewrite
 */
function createIndexWithoutRewrite($qrId) {
    return <<<PHP
<?php
/**
 * Redirección QR sin mod_rewrite
 * ID: {$qrId}
 * Generado: " . date('Y-m-d H:i:s') . "
 */

require_once '../../redirect-sin-rewrite.php';
?>
PHP;
}

/**
 * Obtener URL completa del QR según el modo
 */
function getQRUrl($qrId) {
    $baseUrl = rtrim(BASE_URL, '/');
    $useModRewrite = shouldUseModRewrite();
    
    if ($useModRewrite) {
        // URL amigable: /qr/abc123
        return $baseUrl . '/qr/' . $qrId;
    } else {
        // URL sin mod_rewrite: /qr/abc123/index.php
        return $baseUrl . '/qr/' . $qrId . '/index.php';
    }
}

/**
 * Verificar si un QR existe y es válido
 */
function qrExists($qrId) {
    $qrPath = QR_DIR . $qrId;
    $indexPath = $qrPath . '/index.php';
    
    return file_exists($qrPath) && file_exists($indexPath);
}

/**
 * Eliminar QR completamente
 */
function deleteQRFolder($qrId) {
    $qrPath = QR_DIR . $qrId;
    
    if (!file_exists($qrPath)) {
        return true; // Ya no existe
    }
    
    // Eliminar archivos dentro del directorio
    $files = glob($qrPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Eliminar directorio
    return rmdir($qrPath);
}

/**
 * Recrear todos los QRs existentes con el modo actual
 */
function recreateAllQRs() {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $errors = [];
    $success = 0;
    
    foreach ($redirects as $redirect) {
        if (createQRFolder($redirect['id'], $redirect['destination_url'])) {
            $success++;
        } else {
            $errors[] = $redirect['id'];
        }
    }
    
    return [
        'success' => $success,
        'errors' => $errors,
        'total' => count($redirects)
    ];
}

/**
 * Obtener información del modo actual
 */
function getRewriteModeInfo() {
    $useModRewrite = shouldUseModRewrite();
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido';
    
    return [
        'use_mod_rewrite' => $useModRewrite,
        'mode_name' => $useModRewrite ? 'Con mod_rewrite' : 'Sin mod_rewrite',
        'url_format' => $useModRewrite ? '/qr/{id}' : '/qr/{id}/index.php',
        'server_software' => $serverSoftware,
        'rewrite_available' => function_exists('apache_get_modules') ? 
            in_array('mod_rewrite', apache_get_modules()) : 'No detectado'
    ];
}

/**
 * Cambiar modo de rewrite y recrear QRs
 */
function switchRewriteMode($enableRewrite) {
    // Configurar nuevo modo
    setModRewriteMode($enableRewrite);
    
    // Recrear todos los QRs
    $result = recreateAllQRs();
    
    // Actualizar .htaccess si es necesario
    if (!$enableRewrite) {
        updateHtaccessForNoRewrite();
    }
    
    return $result;
}

/**
 * Actualizar .htaccess para modo sin rewrite
 */
function updateHtaccessForNoRewrite() {
    $htaccessPath = __DIR__ . '/.htaccess';
    
    $htaccessContent = <<<HTACCESS
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
HTACCESS;

    return file_put_contents($htaccessPath, $htaccessContent) !== false;
}

/**
 * Crear configuración de ejemplo para testing
 */
function createTestQR() {
    $testId = 'test-' . substr(md5(time()), 0, 6);
    $testUrl = 'https://www.google.com/search?q=QR+Manager+Test';
    
    if (createQRFolder($testId, $testUrl)) {
        return [
            'success' => true,
            'id' => $testId,
            'url' => getQRUrl($testId),
            'destination' => $testUrl
        ];
    }
    
    return ['success' => false];
}

/**
 * Validar configuración actual
 */
function validateCurrentConfig() {
    $info = getRewriteModeInfo();
    $issues = [];
    $warnings = [];
    
    // Verificar que el directorio QR existe
    if (!file_exists(QR_DIR)) {
        $issues[] = 'Directorio QR no existe: ' . QR_DIR;
    } elseif (!is_writable(QR_DIR)) {
        $issues[] = 'Directorio QR no tiene permisos de escritura';
    }
    
    // Verificar configuración de mod_rewrite
    if ($info['use_mod_rewrite'] && $info['rewrite_available'] === false) {
        $warnings[] = 'mod_rewrite habilitado pero no disponible en el servidor';
    }
    
    // Verificar .htaccess
    $htaccessPath = __DIR__ . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $warnings[] = 'Archivo .htaccess no encontrado';
    }
    
    // Verificar QRs existentes
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $brokenQRs = [];
    
    foreach ($redirects as $redirect) {
        if (!qrExists($redirect['id'])) {
            $brokenQRs[] = $redirect['id'];
        }
    }
    
    if (!empty($brokenQRs)) {
        $warnings[] = 'QRs con archivos faltantes: ' . implode(', ', $brokenQRs);
    }
    
    return [
        'mode_info' => $info,
        'issues' => $issues,
        'warnings' => $warnings,
        'broken_qrs' => $brokenQRs,
        'total_qrs' => count($redirects)
    ];
}
?>