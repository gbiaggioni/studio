<?php
/**
 * SISTEMA DE REDIRECCIÓN SIN MOD_REWRITE
 * 
 * Este archivo maneja las redirecciones cuando no se usa mod_rewrite
 * URLs como: /qr/abc123/index.php en lugar de /qr/abc123
 */

require_once dirname(__DIR__) . '/config.php';

// Función para obtener el ID del QR sin mod_rewrite
function getQRIdWithoutRewrite() {
    // Obtener la ruta actual
    $currentPath = $_SERVER['REQUEST_URI'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Extraer el ID desde la estructura /qr/ID/index.php
    $pathParts = explode('/', trim($currentPath, '/'));
    
    // Buscar la posición de 'qr' en la ruta
    $qrIndex = array_search('qr', $pathParts);
    
    if ($qrIndex !== false && isset($pathParts[$qrIndex + 1])) {
        return $pathParts[$qrIndex + 1];
    }
    
    // Método alternativo: desde el directorio actual
    $currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
    if ($currentDir !== 'qr-manager' && $currentDir !== 'qr') {
        return $currentDir;
    }
    
    return null;
}

// Obtener el ID del QR
$qrId = getQRIdWithoutRewrite();

if (!$qrId) {
    // Mostrar página de error amigable
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR No Encontrado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-danger">❌ QR No Encontrado</h2>
                            <p>El código QR solicitado no existe o la URL no es válida.</p>
                            <p class="text-muted">Verifica que la URL sea correcta.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Cargar datos de redirección
$redirects = loadJsonFile(REDIRECTS_FILE);
$redirect = null;

foreach ($redirects as $r) {
    if ($r['id'] === $qrId) {
        $redirect = $r;
        break;
    }
}

if (!$redirect) {
    // QR no encontrado en la base de datos
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR No Encontrado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-warning">⚠️ QR Desactivado</h2>
                            <p>El código QR <strong><?php echo htmlspecialchars($qrId); ?></strong> ha sido desactivado o eliminado.</p>
                            <p class="text-muted">Contacta al administrador si crees que esto es un error.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Verificar si hay configuraciones de seguridad
$securitySettings = array();
if (file_exists(SECURITY_SETTINGS_FILE)) {
    $allSecuritySettings = loadJsonFile(SECURITY_SETTINGS_FILE);
    if (isset($allSecuritySettings[$qrId])) {
        $securitySettings = $allSecuritySettings[$qrId];
    }
}

// Procesar seguridad si está habilitada
if (!empty($securitySettings) && $securitySettings['security_enabled']) {
    
    // Verificar fecha de expiración
    if (isset($securitySettings['expiry_date']) && $securitySettings['expiry_date']) {
        $expiryDate = strtotime($securitySettings['expiry_date']);
        if ($expiryDate && time() > $expiryDate) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>QR Expirado</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h2 class="text-danger">⏰ QR Expirado</h2>
                                    <p>Este código QR expiró el <?php echo date('d/m/Y H:i', $expiryDate); ?></p>
                                    <p class="text-muted">Ya no es posible acceder a este contenido.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    // Verificar límite de usos
    if (isset($securitySettings['max_uses']) && $securitySettings['max_uses'] > 0) {
        $currentUses = $securitySettings['current_uses'] ?? 0;
        if ($currentUses >= $securitySettings['max_uses']) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>QR Sin Usos Disponibles</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light">
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h2 class="text-warning">🚫 Límite Alcanzado</h2>
                                    <p>Este código QR ha alcanzado su límite máximo de usos (<?php echo $securitySettings['max_uses']; ?>).</p>
                                    <p class="text-muted">Ya no es posible acceder a este contenido.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    // Verificar contraseña
    if ($securitySettings['security_type'] === 'password' && !empty($securitySettings['password'])) {
        session_start();
        $sessionKey = 'qr_access_' . $qrId;
        
        if (!isset($_SESSION[$sessionKey])) {
            if ($_POST && isset($_POST['qr_password'])) {
                if (password_verify($_POST['qr_password'], $securitySettings['password'])) {
                    $_SESSION[$sessionKey] = true;
                } else {
                    $passwordError = "Contraseña incorrecta";
                }
            }
            
            if (!isset($_SESSION[$sessionKey])) {
                ?>
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Acceso Protegido</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                </head>
                <body class="bg-light">
                    <div class="container mt-5">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="text-center mb-4">🔒 Acceso Protegido</h3>
                                        <?php if (isset($passwordError)): ?>
                                            <div class="alert alert-danger"><?php echo $passwordError; ?></div>
                                        <?php endif; ?>
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label for="qr_password" class="form-label">Contraseña:</label>
                                                <input type="password" class="form-control" id="qr_password" name="qr_password" required>
                                                <?php if (!empty($securitySettings['password_hint'])): ?>
                                                    <div class="form-text"><?php echo htmlspecialchars($securitySettings['password_hint']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Acceder</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
        }
    }
}

// Registrar el acceso en analytics
logQrAccess($qrId, $redirect['destination_url']);

// Incrementar contador de usos si hay límite
if (!empty($securitySettings) && isset($securitySettings['max_uses']) && $securitySettings['max_uses'] > 0) {
    incrementQrUsage($qrId);
}

// Realizar la redirección
$destinationUrl = $redirect['destination_url'];

// Validar URL de destino
if (!filter_var($destinationUrl, FILTER_VALIDATE_URL)) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>URL Inválida</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h2 class="text-danger">❌ URL Inválida</h2>
                            <p>La URL de destino no es válida.</p>
                            <p class="text-muted">Contacta al administrador para corregir este problema.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Página de redirección con contador opcional
$redirectDelay = $securitySettings['custom_redirect_delay'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($redirectDelay <= 0): ?>
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($destinationUrl); ?>">
    <?php endif; ?>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <h3>Redirigiendo...</h3>
                        <p class="text-muted">Serás redirigido automáticamente.</p>
                        <?php if ($redirectDelay > 0): ?>
                        <p>Redirigiendo en <span id="countdown"><?php echo $redirectDelay; ?></span> segundos...</p>
                        <?php endif; ?>
                        <p><a href="<?php echo htmlspecialchars($destinationUrl); ?>" class="btn btn-primary">Ir ahora</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($redirectDelay > 0): ?>
    <script>
        let countdown = <?php echo $redirectDelay; ?>;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '<?php echo addslashes($destinationUrl); ?>';
            }
        }, 1000);
    </script>
    <?php else: ?>
    <script>
        // Redirección inmediata como fallback
        setTimeout(() => {
            window.location.href = '<?php echo addslashes($destinationUrl); ?>';
        }, 100);
    </script>
    <?php endif; ?>
</body>
</html>