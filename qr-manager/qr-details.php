<?php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acceso denegado</div>';
    exit;
}

$qrId = $_GET['id'] ?? '';

if (empty($qrId)) {
    echo '<div class="alert alert-danger">ID de QR no especificado</div>';
    exit;
}

// Cargar datos del QR
$redirects = loadJsonFile(REDIRECTS_FILE);
$redirect = null;

foreach ($redirects as $r) {
    if ($r['id'] === $qrId) {
        $redirect = $r;
        break;
    }
}

if (!$redirect) {
    echo '<div class="alert alert-danger">QR no encontrado</div>';
    exit;
}

// Cargar estilo del QR
$qrStyle = loadQrStyle($qrId);

// Cargar categoría
$category = null;
if (isset($redirect['category_id'])) {
    $category = getCategoryById($redirect['category_id']);
}

// Cargar analytics
$analytics = getQrAnalytics($qrId);
$clickCount = count($analytics);

// Obtener diferentes tamaños de QR
$qrSizes = [
    'small' => ['size' => 200, 'label' => 'Pequeño (200x200)'],
    'medium' => ['size' => 300, 'label' => 'Mediano (300x300)'],
    'large' => ['size' => 400, 'label' => 'Grande (400x400)'],
    'xlarge' => ['size' => 500, 'label' => 'Extra Grande (500x500)'],
    'print' => ['size' => 1000, 'label' => 'Impresión (1000x1000)']
];

// Generar URLs de QR para diferentes tamaños
$qrUrls = [];
foreach ($qrSizes as $key => $sizeInfo) {
    $styleWithSize = array_merge($qrStyle, ['size' => $sizeInfo['size']]);
    $qrUrls[$key] = generateCustomQR($redirect['qr_url'], $styleWithSize);
}
?>

<div class="row">
    <div class="col-md-4">
        <!-- Vista previa del QR -->
        <div class="text-center mb-4">
            <img src="<?php echo $qrUrls['medium']; ?>" alt="QR Code" class="img-fluid border rounded" style="max-width: 250px;">
            <br>
            <h6 class="mt-2"><code><?php echo htmlspecialchars($qrId); ?></code></h6>
            <?php if (!empty($redirect['description'])): ?>
                <p class="text-muted"><?php echo htmlspecialchars($redirect['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="card bg-light">
            <div class="card-body text-center">
                <h4 class="text-primary mb-1"><?php echo $clickCount; ?></h4>
                <small class="text-muted">Clics totales</small>
                <?php if ($clickCount > 0): ?>
                    <?php 
                    $lastAccess = end($analytics);
                    $daysSinceLastAccess = floor((time() - strtotime($lastAccess['timestamp'])) / 86400);
                    ?>
                    <br><small class="text-info">
                        Último acceso: hace <?php echo $daysSinceLastAccess; ?> día<?php echo $daysSinceLastAccess != 1 ? 's' : ''; ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Información del QR -->
        <div class="mb-4">
            <h6><i class="fas fa-info-circle me-2"></i>Información General</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>ID:</strong></td>
                    <td><code><?php echo htmlspecialchars($qrId); ?></code></td>
                </tr>
                <tr>
                    <td><strong>URL Destino:</strong></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($redirect['destination_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($redirect['destination_url']); ?>
                            <i class="fas fa-external-link-alt ms-1 small"></i>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>URL QR:</strong></td>
                    <td>
                        <a href="<?php echo htmlspecialchars($redirect['qr_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($redirect['qr_url']); ?>
                            <i class="fas fa-external-link-alt ms-1 small"></i>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td><strong>Categoría:</strong></td>
                    <td>
                        <?php if ($category): ?>
                            <span class="badge" style="background-color: <?php echo $category['color']; ?>">
                                <i class="<?php echo $category['icon']; ?> me-1"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Sin categoría</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Creado:</strong></td>
                    <td>
                        <?php echo date('d/m/Y H:i', strtotime($redirect['created_at'])); ?>
                        por <?php echo htmlspecialchars($redirect['created_by']); ?>
                    </td>
                </tr>
                <?php if (isset($redirect['updated_at'])): ?>
                <tr>
                    <td><strong>Última modificación:</strong></td>
                    <td>
                        <?php echo date('d/m/Y H:i', strtotime($redirect['updated_at'])); ?>
                        por <?php echo htmlspecialchars($redirect['updated_by'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Configuración visual -->
        <div class="mb-4">
            <h6><i class="fas fa-palette me-2"></i>Configuración Visual</h6>
            <div class="row">
                <div class="col-md-6">
                    <small><strong>Tamaño:</strong> <?php echo $qrStyle['size']; ?>x<?php echo $qrStyle['size']; ?>px</small><br>
                    <small><strong>Corrección de error:</strong> <?php echo $qrStyle['error_correction']; ?></small><br>
                    <small><strong>Estilo marco:</strong> <?php echo ucfirst($qrStyle['frame_style']); ?></small>
                </div>
                <div class="col-md-6">
                    <small><strong>Color principal:</strong> 
                        <span class="badge" style="background-color: <?php echo $qrStyle['foreground_color']; ?>">
                            <?php echo $qrStyle['foreground_color']; ?>
                        </span>
                    </small><br>
                    <small><strong>Color fondo:</strong> 
                        <span class="badge" style="background-color: <?php echo $qrStyle['background_color']; ?>; color: <?php echo $qrStyle['foreground_color']; ?>">
                            <?php echo $qrStyle['background_color']; ?>
                        </span>
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Opciones de descarga -->
        <div class="mb-4">
            <h6><i class="fas fa-download me-2"></i>Descargar QR</h6>
            
            <!-- Tamaños disponibles -->
            <div class="row g-2 mb-3">
                <?php foreach ($qrSizes as $key => $sizeInfo): ?>
                <div class="col-md-6">
                    <div class="card border">
                        <div class="card-body p-2 text-center">
                            <img src="<?php echo $qrUrls[$key]; ?>&size=80x80" alt="Preview" class="mb-2" style="max-width: 60px;">
                            <br>
                            <small><strong><?php echo $sizeInfo['label']; ?></strong></small>
                            <br>
                            <div class="btn-group btn-group-sm mt-1" role="group">
                                <a href="<?php echo $qrUrls[$key]; ?>&format=png" 
                                   download="qr-<?php echo $qrId; ?>-<?php echo $sizeInfo['size']; ?>px.png"
                                   class="btn btn-outline-primary btn-sm">PNG</a>
                                <a href="<?php echo $qrUrls[$key]; ?>&format=svg" 
                                   download="qr-<?php echo $qrId; ?>-<?php echo $sizeInfo['size']; ?>px.svg"
                                   class="btn btn-outline-success btn-sm">SVG</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Descarga en lote -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Tip:</strong> Para uso en impresión, recomendamos el tamaño "Extra Grande" o "Impresión" en formato PNG o SVG.
                Los archivos SVG son vectoriales y se pueden escalar sin pérdida de calidad.
            </div>
        </div>
        
        <!-- Links útiles -->
        <div class="row">
            <div class="col-md-6">
                <a href="<?php echo htmlspecialchars($redirect['qr_url']); ?>" 
                   target="_blank" class="btn btn-primary btn-sm w-100 mb-2">
                    <i class="fas fa-external-link-alt me-1"></i>
                    Probar Redirección
                </a>
            </div>
            <div class="col-md-6">
                <button type="button" 
                        onclick="editRedirect('<?php echo htmlspecialchars($redirect['id']); ?>', '<?php echo htmlspecialchars($redirect['destination_url']); ?>')" 
                        class="btn btn-warning btn-sm w-100 mb-2"
                        data-bs-dismiss="modal">
                    <i class="fas fa-edit me-1"></i>
                    Editar Destino
                </button>
            </div>
        </div>
    </div>
</div>