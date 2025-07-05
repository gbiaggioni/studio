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
