<?php
/**
 * Script de verificación de instalación
 * Ejecuta este archivo para verificar que todo esté configurado correctamente
 */

echo "<h1>🔧 QR Manager - Verificación de Instalación</h1>";
echo "<hr>";

// Verificar PHP
echo "<h3>✓ Verificación de PHP</h3>";
echo "Versión de PHP: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "✅ Versión de PHP compatible<br>";
} else {
    echo "❌ Se requiere PHP 7.4 o superior<br>";
}

// Verificar archivos
echo "<h3>✓ Verificación de Archivos</h3>";
$required_files = [
    'config.php',
    'index.php', 
    'admin.php',
    'logout.php',
    'users.json',
    'redirects.json',
    '.htaccess'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file no encontrado<br>";
    }
}

// Verificar permisos
echo "<h3>✓ Verificación de Permisos</h3>";

// Directorio QR
if (is_dir('qr')) {
    if (is_writable('qr')) {
        echo "✅ Directorio qr/ tiene permisos de escritura<br>";
    } else {
        echo "❌ Directorio qr/ no tiene permisos de escritura<br>";
        echo "Ejecuta: chmod 777 qr/<br>";
    }
} else {
    echo "❌ Directorio qr/ no existe<br>";
}

// Archivos JSON
$json_files = ['users.json', 'redirects.json'];
foreach ($json_files as $file) {
    if (file_exists($file)) {
        if (is_writable($file)) {
            echo "✅ $file tiene permisos de escritura<br>";
        } else {
            echo "❌ $file no tiene permisos de escritura<br>";
            echo "Ejecuta: chmod 666 $file<br>";
        }
    }
}

// Verificar JSON válido
echo "<h3>✓ Verificación de Archivos JSON</h3>";

foreach ($json_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $json = json_decode($content, true);
        
        if ($json !== null) {
            echo "✅ $file tiene formato JSON válido<br>";
        } else {
            echo "❌ $file tiene formato JSON inválido<br>";
        }
    }
}

// Verificar configuración
echo "<h3>✓ Verificación de Configuración</h3>";

if (file_exists('config.php')) {
    require_once 'config.php';
    
    echo "Base URL configurada: " . BASE_URL . "<br>";
    echo "Directorio QR: " . QR_DIR . "<br>";
    echo "URL QR: " . QR_URL . "<br>";
    
    if (BASE_URL === 'http://localhost/qr-manager') {
        echo "⚠️ Recuerda cambiar BASE_URL por tu dominio real<br>";
    } else {
        echo "✅ Base URL personalizada configurada<br>";
    }
}

// Verificar funciones de hash
echo "<h3>✓ Verificación de Funciones PHP</h3>";

if (function_exists('password_hash')) {
    echo "✅ password_hash() disponible<br>";
} else {
    echo "❌ password_hash() no disponible<br>";
}

if (function_exists('json_encode')) {
    echo "✅ json_encode() disponible<br>";
} else {
    echo "❌ json_encode() no disponible<br>";
}

if (function_exists('file_get_contents')) {
    echo "✅ file_get_contents() disponible<br>";
} else {
    echo "❌ file_get_contents() no disponible<br>";
}

// Probar creación de carpeta
echo "<h3>✓ Prueba de Creación de Carpetas</h3>";

$test_dir = 'qr/test-' . time();
if (mkdir($test_dir, 0755, true)) {
    echo "✅ Puede crear carpetas en qr/<br>";
    
    // Probar creación de archivo
    $test_file = $test_dir . '/index.php';
    $test_content = "<?php\nheader('Location: https://ejemplo.com');\nexit;\n?>";
    
    if (file_put_contents($test_file, $test_content)) {
        echo "✅ Puede crear archivos en carpetas QR<br>";
        
        // Limpiar
        unlink($test_file);
        rmdir($test_dir);
        echo "✅ Limpieza de prueba completada<br>";
    } else {
        echo "❌ No puede crear archivos en carpetas QR<br>";
    }
} else {
    echo "❌ No puede crear carpetas en qr/<br>";
}

// Resumen
echo "<hr>";
echo "<h3>📋 Resumen</h3>";
echo "<p><strong>Acceso por defecto:</strong></p>";
echo "<ul>";
echo "<li>Usuario: admin</li>";
echo "<li>Contraseña: password</li>";
echo "</ul>";

echo "<p><strong>URLs importantes:</strong></p>";
echo "<ul>";
echo "<li>Login: <a href='index.php'>index.php</a></li>";
echo "<li>Panel Admin: <a href='admin.php'>admin.php</a></li>";
echo "</ul>";

echo "<p><strong>Próximos pasos:</strong></p>";
echo "<ol>";
echo "<li>Corregir cualquier error mostrado arriba</li>";
echo "<li>Configurar tu dominio en config.php</li>";
echo "<li>Cambiar la contraseña por defecto</li>";
echo "<li>Probar creando tu primera redirección QR</li>";
echo "</ol>";

echo "<hr>";
echo "<p><em>Elimina este archivo (test-setup.php) después de verificar la instalación.</em></p>";
?>