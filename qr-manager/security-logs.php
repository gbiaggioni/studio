<?php
require_once 'config.php';
requireLogin();

$qrId = $_GET['qr_id'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

if (empty($qrId)) {
    die('ID de QR requerido');
}

// Cargar información del QR
$redirects = loadJsonFile(REDIRECTS_FILE);
$qrInfo = null;
foreach ($redirects as $redirect) {
    if ($redirect['id'] === $qrId) {
        $qrInfo = $redirect;
        break;
    }
}

if (!$qrInfo) {
    die('QR no encontrado');
}

// Cargar configuración de seguridad
$security = getSecuritySettings($qrId);

// Cargar logs de seguridad
$securityLogs = [];
$securityLogFile = __DIR__ . '/logs/security_access.log';

if (file_exists($securityLogFile)) {
    $lines = file($securityLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $logEntry = json_decode($line, true);
        if ($logEntry && $logEntry['qr_id'] === $qrId) {
            $securityLogs[] = $logEntry;
        }
    }
    
    // Ordenar por timestamp (más recientes primero)
    usort($securityLogs, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
}

// Paginación
$totalLogs = count($securityLogs);
$totalPages = ceil($totalLogs / $limit);
$pagedLogs = array_slice($securityLogs, $offset, $limit);

// Cargar logs de acceso normales también
$normalLogs = getQrAnalytics($qrId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Seguridad - QR <?php echo htmlspecialchars($qrId); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .header-card { border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .log-entry { border-left: 4px solid #ddd; padding: 10px; margin-bottom: 8px; }
        .log-entry.success { border-left-color: #28a745; background-color: #f8fff9; }
        .log-entry.danger { border-left-color: #dc3545; background-color: #fff8f8; }
        .log-entry.warning { border-left-color: #ffc107; background-color: #fffdf7; }
        .log-entry.info { border-left-color: #17a2b8; background-color: #f7feff; }
        .timestamp { font-family: monospace; font-size: 0.9rem; }
        .user-agent { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .stats-card { text-align: center; }
        .metric { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="card header-card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-0">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            Logs de Seguridad: <code><?php echo htmlspecialchars($qrId); ?></code>
                        </h4>
                        <p class="text-muted mb-0">
                            Destino: <a href="<?php echo htmlspecialchars($qrInfo['destination_url']); ?>" target="_blank">
                                <?php echo htmlspecialchars($qrInfo['destination_url']); ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-1"></i>Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas de seguridad -->
        <?php if ($security): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="metric text-primary"><?php echo $security['current_uses'] ?? 0; ?></div>
                        <small class="text-muted">Usos Actuales</small>
                        <?php if ($security['max_uses']): ?>
                            <br><small class="text-info">/ <?php echo $security['max_uses']; ?> máximo</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="metric text-success"><?php echo count(array_filter($securityLogs, function($l) { return $l['result'] === 'access_granted'; })); ?></div>
                        <small class="text-muted">Accesos Exitosos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="metric text-danger"><?php echo count(array_filter($securityLogs, function($l) { return strpos($l['result'], 'denied') !== false || strpos($l['result'], 'failed') !== false; })); ?></div>
                        <small class="text-muted">Accesos Denegados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="metric <?php echo $security['expiry_date'] && strtotime($security['expiry_date']) < time() ? 'text-danger' : 'text-success'; ?>">
                            <?php if ($security['expiry_date']): ?>
                                <?php 
                                $expiry = strtotime($security['expiry_date']);
                                $now = time();
                                if ($expiry < $now) {
                                    echo 'Expirado';
                                } else {
                                    $days = ceil(($expiry - $now) / 86400);
                                    echo $days . ' día' . ($days != 1 ? 's' : '');
                                }
                                ?>
                            <?php else: ?>
                                Sin expirar
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Estado</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuración actual -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Configuración de Seguridad Actual
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Tipo de Protección:</strong><br>
                        <?php 
                        $typeLabels = [
                            'password' => '<i class="fas fa-key text-warning"></i> Contraseña',
                            'capture' => '<i class="fas fa-form text-info"></i> Formulario',
                            'email' => '<i class="fas fa-envelope text-primary"></i> Email',
                            'employee' => '<i class="fas fa-user-tie text-success"></i> Empleados',
                            'combined' => '<i class="fas fa-shield text-danger"></i> Combinado'
                        ];
                        echo $typeLabels[$security['security_type']] ?? $security['security_type'];
                        ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Restricciones:</strong><br>
                        <?php if (!empty($security['allowed_ips'])): ?>
                            <small class="badge bg-info">IP Restringida (<?php echo count($security['allowed_ips']); ?>)</small><br>
                        <?php endif; ?>
                        <?php if ($security['employee_only']): ?>
                            <small class="badge bg-warning">Solo Empleados</small><br>
                        <?php endif; ?>
                        <?php if ($security['max_uses']): ?>
                            <small class="badge bg-secondary">Límite: <?php echo $security['max_uses']; ?> usos</small><br>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Creado:</strong><br>
                        <small><?php echo date('d/m/Y H:i', strtotime($security['created_at'])); ?></small><br>
                        <small class="text-muted">por <?php echo htmlspecialchars($security['created_by']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Navegación por pestañas -->
        <ul class="nav nav-tabs mb-3" id="logTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="security-logs-tab" data-bs-toggle="tab" data-bs-target="#security-logs" 
                        type="button" role="tab">
                    <i class="fas fa-shield-alt me-1"></i>
                    Logs de Seguridad (<?php echo count($securityLogs); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="access-logs-tab" data-bs-toggle="tab" data-bs-target="#access-logs" 
                        type="button" role="tab">
                    <i class="fas fa-history me-1"></i>
                    Accesos Normales (<?php echo count($normalLogs); ?>)
                </button>
            </li>
        </ul>
        
        <!-- Contenido de pestañas -->
        <div class="tab-content" id="logTabsContent">
            <!-- Logs de seguridad -->
            <div class="tab-pane fade show active" id="security-logs" role="tabpanel">
                <?php if (empty($pagedLogs)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay logs de seguridad disponibles</p>
                            <small class="text-muted">Los logs se generan cuando hay intentos de acceso a QRs protegidos</small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Logs de Seguridad</h6>
                            <small class="text-muted">
                                Mostrando <?php echo count($pagedLogs); ?> de <?php echo $totalLogs; ?> entradas
                            </small>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pagedLogs as $log): ?>
                                <?php 
                                $logClass = 'info';
                                $icon = 'fa-info-circle';
                                
                                if (strpos($log['result'], 'granted') !== false || strpos($log['result'], 'success') !== false) {
                                    $logClass = 'success';
                                    $icon = 'fa-check-circle';
                                } elseif (strpos($log['result'], 'denied') !== false || strpos($log['result'], 'failed') !== false) {
                                    $logClass = 'danger';
                                    $icon = 'fa-times-circle';
                                } elseif (strpos($log['result'], 'attempt') !== false) {
                                    $logClass = 'warning';
                                    $icon = 'fa-exclamation-triangle';
                                }
                                ?>
                                <div class="log-entry <?php echo $logClass; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <i class="fas <?php echo $icon; ?> me-2"></i>
                                            <span class="timestamp"><?php echo $log['timestamp']; ?></span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong><?php echo htmlspecialchars($log['ip']); ?></strong>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge bg-<?php echo $logClass === 'success' ? 'success' : ($logClass === 'danger' ? 'danger' : 'warning'); ?>">
                                                <?php 
                                                $resultLabels = [
                                                    'access_attempt' => 'Intento de Acceso',
                                                    'access_granted' => 'Acceso Autorizado',
                                                    'access_denied' => 'Acceso Denegado',
                                                    'password_failed' => 'Contraseña Incorrecta',
                                                    'verification_failed' => 'Verificación Fallida',
                                                    'verification_success' => 'Verificación Exitosa',
                                                    'capture_completed' => 'Formulario Completado',
                                                    'invalid_token' => 'Token Inválido',
                                                    'token_access_granted' => 'Acceso por Token'
                                                ];
                                                echo $resultLabels[$log['result']] ?? ucfirst(str_replace('_', ' ', $log['result']));
                                                ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4">
                                            <?php if (!empty($log['additional_data'])): ?>
                                                <button class="btn btn-sm btn-outline-info" type="button" 
                                                        data-bs-toggle="collapse" data-bs-target="#details-<?php echo md5($log['timestamp'] . $log['ip']); ?>">
                                                    <i class="fas fa-info-circle"></i> Detalles
                                                </button>
                                            <?php endif; ?>
                                            
                                            <div class="user-agent mt-1" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                                <small class="text-muted">
                                                    <?php 
                                                    $ua = $log['user_agent'];
                                                    if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false) {
                                                        echo '<i class="fas fa-mobile-alt"></i> Móvil';
                                                    } elseif (strpos($ua, 'iPad') !== false || strpos($ua, 'Tablet') !== false) {
                                                        echo '<i class="fas fa-tablet-alt"></i> Tablet';
                                                    } else {
                                                        echo '<i class="fas fa-desktop"></i> Desktop';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($log['additional_data'])): ?>
                                        <div class="collapse mt-2" id="details-<?php echo md5($log['timestamp'] . $log['ip']); ?>">
                                            <div class="card card-body bg-light">
                                                <h6>Información Adicional:</h6>
                                                <pre class="mb-0"><?php echo htmlspecialchars(json_encode($log['additional_data'], JSON_PRETTY_PRINT)); ?></pre>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Paginación -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Log pagination" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?qr_id=<?php echo urlencode($qrId); ?>&page=<?php echo $page - 1; ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?qr_id=<?php echo urlencode($qrId); ?>&page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?qr_id=<?php echo urlencode($qrId); ?>&page=<?php echo $page + 1; ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Logs de acceso normales -->
            <div class="tab-pane fade" id="access-logs" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Accesos Normales (Sin Protección)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($normalLogs)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay accesos normales registrados</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>IP</th>
                                            <th>User Agent</th>
                                            <th>Dispositivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($normalLogs, 0, 50) as $access): ?>
                                            <tr>
                                                <td>
                                                    <span class="timestamp"><?php echo $access['timestamp']; ?></span>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($access['ip']); ?></code>
                                                </td>
                                                <td>
                                                    <div class="user-agent" title="<?php echo htmlspecialchars($access['user_agent']); ?>">
                                                        <?php echo htmlspecialchars(substr($access['user_agent'], 0, 50) . '...'); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $ua = $access['user_agent'];
                                                    if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false) {
                                                        echo '<span class="badge bg-success"><i class="fas fa-mobile-alt"></i> Móvil</span>';
                                                    } elseif (strpos($ua, 'iPad') !== false || strpos($ua, 'Tablet') !== false) {
                                                        echo '<span class="badge bg-info"><i class="fas fa-tablet-alt"></i> Tablet</span>';
                                                    } else {
                                                        echo '<span class="badge bg-primary"><i class="fas fa-desktop"></i> Desktop</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (count($normalLogs) > 50): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Mostrando los últimos 50 accesos. Total: <?php echo count($normalLogs); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh cada 30 segundos si está en la pestaña activa
        let refreshInterval;
        
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                if (!document.hidden) {
                    location.reload();
                }
            }, 30000);
        }
        
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });
        
        // Iniciar auto-refresh
        startAutoRefresh();
        
        // Parar auto-refresh cuando se cierre la ventana
        window.addEventListener('beforeunload', stopAutoRefresh);
    </script>
</body>
</html>