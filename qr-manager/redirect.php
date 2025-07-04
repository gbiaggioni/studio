<?php
require_once 'config.php';
session_start();

// Limpiar tokens expirados periódicamente
if (rand(1, 100) <= 5) { // 5% de probabilidad
    cleanExpiredTokens();
}

// Obtener el ID del QR desde la URL
$qrId = $_GET['id'] ?? '';
$action = $_GET['action'] ?? 'access';
$token = $_GET['token'] ?? '';

if (empty($qrId)) {
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
    showErrorPage('QR No Encontrado', 'El código QR que intentas acceder no existe o ha sido eliminado.');
}

// Obtener configuración de seguridad
$security = getSecuritySettings($qrId);
$userIP = getUserIP();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Log del intento de acceso
if ($security && $security['access_log']) {
    logSecureAccess($qrId, $userIP, $userAgent, 'access_attempt');
}

// Si no hay configuración de seguridad, permitir acceso directo
if (!$security || !$security['security_enabled']) {
    logQrAccess($qrId, $targetRedirect['destination_url']);
    header('Location: ' . $targetRedirect['destination_url']);
    exit;
}

// Manejar diferentes acciones
switch ($action) {
    case 'verify_email':
        handleEmailVerification();
        break;
    case 'capture_form':
        handleCaptureForm();
        break;
    case 'password_check':
        handlePasswordCheck();
        break;
    case 'validate_token':
        handleTokenValidation();
        break;
    default:
        handleInitialAccess();
        break;
}

function handleInitialAccess() {
    global $qrId, $security, $targetRedirect, $userIP, $userAgent;
    
    // Validación inicial básica
    $validation = validateQrAccess($qrId);
    
    if (!$validation['allowed']) {
        logSecureAccess($qrId, $userIP, $userAgent, 'access_denied', ['reason' => $validation['error_type']]);
        showErrorPage('Acceso Denegado', $validation['message']);
    }
    
    // Determinar el siguiente paso según el tipo de seguridad
    if ($security['security_type'] === 'password') {
        showPasswordForm();
    } elseif ($security['capture_form']['enabled']) {
        showCaptureForm();
    } elseif ($security['email_verification']['enabled']) {
        showEmailVerificationForm();
    } else {
        // Acceso directo permitido
        proceedToDestination();
    }
}

function handlePasswordCheck() {
    global $qrId, $security, $targetRedirect, $userIP, $userAgent;
    
    $password = $_POST['password'] ?? '';
    $validation = validateQrAccess($qrId, $password);
    
    if (!$validation['allowed']) {
        logSecureAccess($qrId, $userIP, $userAgent, 'password_failed');
        showPasswordForm('Contraseña incorrecta. Inténtalo de nuevo.');
        return;
    }
    
    // Contraseña correcta, verificar si hay más pasos
    if ($security['capture_form']['enabled']) {
        showCaptureForm();
    } elseif ($security['email_verification']['enabled']) {
        showEmailVerificationForm();
    } else {
        proceedToDestination();
    }
}

function handleCaptureForm() {
    global $qrId, $security, $targetRedirect, $userIP, $userAgent;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $captureData = [];
        foreach ($security['capture_form']['fields'] as $field) {
            $value = $_POST[$field['name']] ?? '';
            if ($field['required'] && empty($value)) {
                showCaptureForm('Por favor, completa todos los campos requeridos.');
                return;
            }
            $captureData[$field['name']] = $value;
        }
        
        // Validar email si es necesario
        if ($security['email_verification']['enabled']) {
            $email = $captureData['email'] ?? '';
            if (!$email || !isEmailDomainAllowed($email, $security['email_verification']['allowed_domains'])) {
                showCaptureForm('El dominio de email no está autorizado.');
                return;
            }
            
            if ($security['employee_only'] && !isAuthorizedEmployee($email)) {
                showCaptureForm('Solo empleados autorizados pueden acceder.');
                return;
            }
        }
        
        // Guardar datos de captura en sesión
        $_SESSION['capture_data'] = $captureData;
        
        logSecureAccess($qrId, $userIP, $userAgent, 'capture_completed', $captureData);
        
        if ($security['email_verification']['enabled']) {
            sendVerificationEmailAndShowForm($captureData['email'] ?? '');
        } else {
            proceedToDestination();
        }
    } else {
        showCaptureForm();
    }
}

function handleEmailVerification() {
    global $qrId, $security, $userIP, $userAgent;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = $_POST['verification_code'] ?? '';
        $email = $_SESSION['verification_email'] ?? '';
        
        if (empty($code) || empty($email)) {
            showEmailVerificationForm('Código de verificación requerido.');
            return;
        }
        
        // Verificar código (en producción, usar base de datos)
        $expectedCode = $_SESSION['verification_code'] ?? '';
        $codeTimestamp = $_SESSION['code_timestamp'] ?? 0;
        
        if (time() - $codeTimestamp > 600) { // 10 minutos
            showEmailVerificationForm('El código ha expirado. Solicita uno nuevo.');
            return;
        }
        
        if ($code !== $expectedCode) {
            logSecureAccess($qrId, $userIP, $userAgent, 'verification_failed', ['email' => $email]);
            showEmailVerificationForm('Código incorrecto. Inténtalo de nuevo.');
            return;
        }
        
        // Código válido
        logSecureAccess($qrId, $userIP, $userAgent, 'verification_success', ['email' => $email]);
        unset($_SESSION['verification_code'], $_SESSION['code_timestamp'], $_SESSION['verification_email']);
        
        proceedToDestination();
    } else {
        $email = $_GET['email'] ?? '';
        if ($email) {
            sendVerificationEmailAndShowForm($email);
        } else {
            showEmailVerificationForm();
        }
    }
}

function handleTokenValidation() {
    global $qrId, $token, $targetRedirect, $userIP, $userAgent;
    
    $tokenData = validateAccessToken($token);
    
    if (!$tokenData || $tokenData['qr_id'] !== $qrId) {
        logSecureAccess($qrId, $userIP, $userAgent, 'invalid_token');
        showErrorPage('Token Inválido', 'El token de acceso no es válido o ha expirado.');
        return;
    }
    
    markTokenAsUsed($token);
    logSecureAccess($qrId, $userIP, $userAgent, 'token_access_granted');
    
    proceedToDestination();
}

function proceedToDestination() {
    global $qrId, $targetRedirect, $security, $userIP, $userAgent;
    
    // Incrementar contador de usos
    incrementQrUsage($qrId);
    
    // Log del acceso exitoso
    logQrAccess($qrId, $targetRedirect['destination_url']);
    logSecureAccess($qrId, $userIP, $userAgent, 'access_granted');
    
    // Aplicar delay si está configurado
    if ($security['custom_redirect_delay'] > 0) {
        showRedirectPage($targetRedirect['destination_url'], $security['custom_redirect_delay']);
    } else {
        header('Location: ' . $targetRedirect['destination_url']);
        exit;
    }
}

function sendVerificationEmailAndShowForm($email) {
    global $qrId;
    
    $verificationCode = generateVerificationCode();
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['code_timestamp'] = time();
    $_SESSION['verification_email'] = $email;
    
    sendVerificationEmail($email, $qrId, $verificationCode);
    showEmailVerificationForm('Se ha enviado un código de verificación a tu email.');
}

// ============ FUNCIONES DE VISUALIZACIÓN ============

function showPasswordForm($error = '') {
    global $qrId, $security, $targetRedirect;
    
    $hint = $security['password_hint'] ?? '';
    
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Protegido - QR Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .security-card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; }
        .security-icon { font-size: 4rem; color: #007bff; margin-bottom: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card security-card">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-lock security-icon"></i>
                        <h2 class="card-title mb-3">Contenido Protegido</h2>
                        <p class="text-muted mb-4">Este QR requiere contraseña para acceder</p>';
    
    if ($error) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
    }
    
    if ($hint) {
        echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> ' . htmlspecialchars($hint) . '</div>';
    }
    
    echo '
                        <form method="POST" action="?id=' . urlencode($qrId) . '&action=password_check">
                            <div class="mb-3">
                                <input type="password" class="form-control form-control-lg" name="password" 
                                       placeholder="Ingresa la contraseña" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-unlock"></i> Acceder
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Conexión segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit;
}

function showCaptureForm($error = '') {
    global $qrId, $security;
    
    $title = $security['capture_form']['title'] ?? 'Información Requerida';
    $message = $security['capture_form']['message'] ?? 'Por favor, proporciona tu información para continuar.';
    $fields = $security['capture_form']['fields'] ?? [];
    
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información Requerida - QR Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .security-card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; }
        .security-icon { font-size: 3rem; color: #28a745; margin-bottom: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card security-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-check security-icon"></i>
                            <h2 class="card-title">' . htmlspecialchars($title) . '</h2>
                            <p class="text-muted">' . htmlspecialchars($message) . '</p>
                        </div>';
    
    if ($error) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
    }
    
    echo '
                        <form method="POST" action="?id=' . urlencode($qrId) . '&action=capture_form">';
    
    foreach ($fields as $field) {
        $required = $field['required'] ? 'required' : '';
        $asterisk = $field['required'] ? '<span class="text-danger">*</span>' : '';
        
        echo '<div class="mb-3">
                <label class="form-label">' . htmlspecialchars($field['label']) . ' ' . $asterisk . '</label>';
        
        switch ($field['type']) {
            case 'email':
                echo '<input type="email" class="form-control" name="' . htmlspecialchars($field['name']) . '" ' . $required . '>';
                break;
            case 'tel':
                echo '<input type="tel" class="form-control" name="' . htmlspecialchars($field['name']) . '" ' . $required . '>';
                break;
            case 'textarea':
                echo '<textarea class="form-control" name="' . htmlspecialchars($field['name']) . '" rows="3" ' . $required . '></textarea>';
                break;
            default:
                echo '<input type="text" class="form-control" name="' . htmlspecialchars($field['name']) . '" ' . $required . '>';
        }
        
        echo '</div>';
    }
    
    echo '
                            <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                                <i class="fas fa-arrow-right"></i> Continuar
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Tu información está protegida
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit;
}

function showEmailVerificationForm($message = '') {
    global $qrId;
    
    $email = $_SESSION['verification_email'] ?? '';
    
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación por Email - QR Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .security-card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; }
        .security-icon { font-size: 4rem; color: #17a2b8; margin-bottom: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card security-card">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-envelope-open security-icon"></i>
                        <h2 class="card-title mb-3">Verificación por Email</h2>';
    
    if ($email) {
        echo '<p class="text-muted mb-4">Hemos enviado un código a:<br><strong>' . htmlspecialchars($email) . '</strong></p>';
    } else {
        echo '<p class="text-muted mb-4">Ingresa tu email para recibir el código de verificación</p>';
    }
    
    if ($message) {
        $alertClass = strpos($message, 'enviado') !== false ? 'alert-success' : 'alert-warning';
        echo '<div class="alert ' . $alertClass . '">' . htmlspecialchars($message) . '</div>';
    }
    
    if ($email) {
        echo '
                        <form method="POST" action="?id=' . urlencode($qrId) . '&action=verify_email">
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-lg text-center" 
                                       name="verification_code" placeholder="000000" 
                                       pattern="[0-9]{6}" maxlength="6" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-info btn-lg w-100 mb-3">
                                <i class="fas fa-check"></i> Verificar Código
                            </button>
                        </form>';
    } else {
        echo '
                        <form method="GET" action="?id=' . urlencode($qrId) . '&action=verify_email">
                            <input type="hidden" name="id" value="' . htmlspecialchars($qrId) . '">
                            <input type="hidden" name="action" value="verify_email">
                            <div class="mb-3">
                                <input type="email" class="form-control form-control-lg" 
                                       name="email" placeholder="tu@email.com" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-info btn-lg w-100 mb-3">
                                <i class="fas fa-paper-plane"></i> Enviar Código
                            </button>
                        </form>';
    }
    
    echo '
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> El código expira en 10 minutos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit;
}

function showRedirectPage($url, $delay) {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo... - QR Manager</title>
    <meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars($url) . '">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); min-height: 100vh; }
        .redirect-card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; }
        .success-icon { font-size: 4rem; color: #28a745; margin-bottom: 1rem; }
        .countdown { font-size: 2rem; font-weight: bold; color: #28a745; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card redirect-card">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-check-circle success-icon"></i>
                        <h2 class="card-title mb-3">Acceso Autorizado</h2>
                        <p class="text-muted mb-4">Redirigiendo en <span class="countdown" id="countdown">' . $delay . '</span> segundos...</p>
                        
                        <div class="progress mb-4">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="progressBar"></div>
                        </div>
                        
                        <a href="' . htmlspecialchars($url) . '" class="btn btn-success btn-lg">
                            <i class="fas fa-external-link-alt"></i> Ir Ahora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let timeLeft = ' . $delay . ';
        const countdown = document.getElementById("countdown");
        const progressBar = document.getElementById("progressBar");
        
        const timer = setInterval(() => {
            timeLeft--;
            countdown.textContent = timeLeft;
            
            const progress = (((' . $delay . ' - timeLeft) / ' . $delay . ') * 100);
            progressBar.style.width = progress + "%";
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = "' . htmlspecialchars($url) . '";
            }
        }, 1000);
    </script>
</body>
</html>';
    exit;
}

function showErrorPage($title, $message) {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - QR Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); min-height: 100vh; }
        .error-card { box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; }
        .error-icon { font-size: 4rem; color: #dc3545; margin-bottom: 1rem; }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card error-card">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-exclamation-triangle error-icon"></i>
                        <h1 class="card-title">' . htmlspecialchars($title) . '</h1>
                        <p class="card-text">' . htmlspecialchars($message) . '</p>
                        <a href="/" class="btn btn-primary">
                            <i class="fas fa-home"></i> Ir al Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    exit;
}
?>