<?php
session_start();

// Configuración del dominio
define('BASE_URL', 'http://localhost/qr-manager'); // Cambiar por tu dominio
define('QR_DIR', __DIR__ . '/qr/');
define('QR_URL', BASE_URL . '/qr/');

// Archivos de datos
define('USERS_FILE', __DIR__ . '/users.json');
define('REDIRECTS_FILE', __DIR__ . '/redirects.json');

// Funciones auxiliares
function loadJsonFile($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveJsonFile($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function generateRandomId($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

function sanitizeId($id) {
    return preg_replace('/[^a-zA-Z0-9-_]/', '', $id);
}
?>