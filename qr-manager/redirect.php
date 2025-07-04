<?php
require_once 'config.php';

// Obtener el ID del QR desde la URL
$qrId = $_GET['id'] ?? '';

if (empty($qrId)) {
    // Si no hay ID, redirigir al panel principal
    header('Location: index.php');
    exit;
}

// Buscar la redirección en la base de datos
$redirects = loadJsonFile(REDIRECTS_FILE);
$targetRedirect = null;

foreach ($redirects as $redirect) {
    if ($redirect['id'] === $qrId) {
        $targetRedirect = $redirect;
        break;
    }
}

if (!$targetRedirect) {
    // Si no se encuentra el QR, mostrar error 404
    http_response_code(404);
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR No Encontrado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h1 class="card-title">QR No Encontrado</h1>
                        <p class="card-text">El código QR que intentas acceder no existe o ha sido eliminado.</p>
                        <a href="/" class="btn btn-primary">Ir al Inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit;
}

// Registrar el acceso en analytics
logQrAccess($qrId, $targetRedirect['destination_url']);

// Redirigir al destino final
header('Location: ' . $targetRedirect['destination_url']);
exit;
?>