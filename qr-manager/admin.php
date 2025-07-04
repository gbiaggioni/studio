<?php
require_once 'config.php';
requireLogin();

$message = '';
$messageType = '';

// Cargar redirecciones existentes
$redirects = loadJsonFile(REDIRECTS_FILE);

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $destinationUrl = trim($_POST['destination_url'] ?? '');
            $customId = trim($_POST['custom_id'] ?? '');
            
            if (empty($destinationUrl)) {
                $message = 'La URL de destino es obligatoria';
                $messageType = 'danger';
                break;
            }
            
            if (!filter_var($destinationUrl, FILTER_VALIDATE_URL)) {
                $message = 'La URL de destino no es válida';
                $messageType = 'danger';
                break;
            }
            
            // Generar ID único
            $qrId = $customId ? sanitizeId($customId) : generateRandomId();
            
            // Verificar que el ID no exista
            $idExists = false;
            foreach ($redirects as $redirect) {
                if ($redirect['id'] === $qrId) {
                    $idExists = true;
                    break;
                }
            }
            
            if ($idExists) {
                $message = 'El ID especificado ya existe. Use otro ID o deje vacío para generar uno automático.';
                $messageType = 'danger';
                break;
            }
            
            // Crear carpeta
            $qrPath = QR_DIR . $qrId;
            if (!is_dir($qrPath)) {
                mkdir($qrPath, 0755, true);
            }
            
            // Crear archivo index.php en la carpeta
            $indexContent = "<?php\nheader('Location: " . addslashes($destinationUrl) . "');\nexit;\n?>";
            file_put_contents($qrPath . '/index.php', $indexContent);
            
            // Guardar en redirects.json
            $newRedirect = [
                'id' => $qrId,
                'destination_url' => $destinationUrl,
                'qr_url' => QR_URL . $qrId,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['username']
            ];
            
            $redirects[] = $newRedirect;
            saveJsonFile(REDIRECTS_FILE, $redirects);
            
            $message = 'Redirección QR creada exitosamente. ID: ' . $qrId;
            $messageType = 'success';
            break;
            
        case 'edit':
            $editId = $_POST['edit_id'] ?? '';
            $newDestinationUrl = trim($_POST['new_destination_url'] ?? '');
            
            if (empty($newDestinationUrl)) {
                $message = 'La nueva URL de destino es obligatoria';
                $messageType = 'danger';
                break;
            }
            
            if (!filter_var($newDestinationUrl, FILTER_VALIDATE_URL)) {
                $message = 'La nueva URL de destino no es válida';
                $messageType = 'danger';
                break;
            }
            
            if ($editId) {
                // Buscar y actualizar la redirección
                $found = false;
                foreach ($redirects as &$redirect) {
                    if ($redirect['id'] === $editId) {
                        $oldUrl = $redirect['destination_url'];
                        $redirect['destination_url'] = $newDestinationUrl;
                        $redirect['updated_at'] = date('Y-m-d H:i:s');
                        $redirect['updated_by'] = $_SESSION['username'];
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    // Guardar cambios en JSON
                    saveJsonFile(REDIRECTS_FILE, $redirects);
                    
                    // Actualizar archivo index.php en la carpeta
                    $qrPath = QR_DIR . $editId;
                    if (is_dir($qrPath)) {
                        $indexContent = "<?php\nheader('Location: " . addslashes($newDestinationUrl) . "');\nexit;\n?>";
                        file_put_contents($qrPath . '/index.php', $indexContent);
                    }
                    
                    $message = 'Redirección actualizada exitosamente. Nueva URL: ' . $newDestinationUrl;
                    $messageType = 'success';
                } else {
                    $message = 'No se encontró la redirección especificada';
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'delete':
            $deleteId = $_POST['delete_id'] ?? '';
            
            if ($deleteId) {
                // Buscar y eliminar de la lista
                $redirects = array_filter($redirects, function($redirect) use ($deleteId) {
                    return $redirect['id'] !== $deleteId;
                });
                
                // Reindexar array
                $redirects = array_values($redirects);
                
                // Guardar cambios
                saveJsonFile(REDIRECTS_FILE, $redirects);
                
                // Eliminar carpeta
                $qrPath = QR_DIR . $deleteId;
                if (is_dir($qrPath)) {
                    if (file_exists($qrPath . '/index.php')) {
                        unlink($qrPath . '/index.php');
                    }
                    rmdir($qrPath);
                }
                
                $message = 'Redirección eliminada exitosamente';
                $messageType = 'success';
            }
            break;
    }
}

// Recargar redirecciones después de cambios
$redirects = loadJsonFile(REDIRECTS_FILE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Manager - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .qr-code {
            max-width: 150px;
            max-height: 150px;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-qrcode me-2"></i>
                QR Manager
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Mensajes -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulario de creación -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Crear Nueva Redirección QR
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="destination_url" class="form-label">URL de Destino *</label>
                                <input type="url" class="form-control" id="destination_url" name="destination_url" 
                                       placeholder="https://ejemplo.com" required>
                                <div class="form-text">URL completa a la que redirigirá el código QR</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="custom_id" class="form-label">ID Personalizado (Opcional)</label>
                                <input type="text" class="form-control" id="custom_id" name="custom_id" 
                                       placeholder="mi-qr-personalizado" pattern="[a-zA-Z0-9\-_]+">
                                <div class="form-text">Deje vacío para generar automáticamente</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-qrcode me-2"></i>
                                Crear Código QR
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de redirecciones -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Redirecciones Existentes (<?php echo count($redirects); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($redirects)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay redirecciones creadas aún</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>URL Destino</th>
                                            <th>QR URL</th>
                                            <th>Código QR</th>
                                            <th>Creado</th>
                                            <th>Última Actualización</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($redirects as $redirect): ?>
                                            <tr>
                                                <td>
                                                    <code><?php echo htmlspecialchars($redirect['id']); ?></code>
                                                </td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($redirect['destination_url']); ?>" 
                                                       target="_blank" class="text-decoration-none">
                                                        <?php echo htmlspecialchars(substr($redirect['destination_url'], 0, 30) . (strlen($redirect['destination_url']) > 30 ? '...' : '')); ?>
                                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($redirect['qr_url']); ?>" 
                                                       target="_blank" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($redirect['qr_url']); ?>
                                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($redirect['qr_url']); ?>" 
                                                         class="qr-code" alt="QR Code">
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo htmlspecialchars($redirect['created_at']); ?><br>
                                                        <span class="text-muted">por <?php echo htmlspecialchars($redirect['created_by']); ?></span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php if (isset($redirect['updated_at'])): ?>
                                                            <?php echo htmlspecialchars($redirect['updated_at']); ?><br>
                                                            <span class="text-muted">por <?php echo htmlspecialchars($redirect['updated_by'] ?? 'N/A'); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin modificaciones</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($redirect['qr_url']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-primary" title="Ver QR grande">
                                                            <i class="fas fa-search-plus"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                onclick="editRedirect('<?php echo htmlspecialchars($redirect['id']); ?>', '<?php echo htmlspecialchars($redirect['destination_url']); ?>')" 
                                                                title="Editar destino">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteRedirect('<?php echo htmlspecialchars($redirect['id']); ?>')" 
                                                                title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro de que desea eliminar esta redirección QR? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="delete_id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar redirección -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Editar Destino QR
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="edit_id" id="editId">
                        
                        <div class="mb-3">
                            <label for="editQrId" class="form-label">ID del QR</label>
                            <input type="text" class="form-control" id="editQrId" readonly>
                            <div class="form-text">Este ID no se puede modificar</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editCurrentUrl" class="form-label">URL Actual</label>
                            <input type="text" class="form-control" id="editCurrentUrl" readonly>
                            <div class="form-text">URL de destino actual</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newDestinationUrl" class="form-label">Nueva URL de Destino *</label>
                            <input type="url" class="form-control" id="newDestinationUrl" name="new_destination_url" 
                                   placeholder="https://nueva-url.com" required>
                            <div class="form-text">Ingrese la nueva URL a la que debe redirigir este QR</div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nota:</strong> El código QR seguirá siendo el mismo, solo cambiará su destino.
                            Los usuarios que ya tengan el QR escaneado o guardado seguirán usando la misma URL.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Destino
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteRedirect(id) {
            document.getElementById('deleteId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        function editRedirect(id, currentUrl) {
            document.getElementById('editId').value = id;
            document.getElementById('editQrId').value = id;
            document.getElementById('editCurrentUrl').value = currentUrl;
            document.getElementById('newDestinationUrl').value = currentUrl;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>