<?php
require_once 'config.php';
requireLogin();

$message = '';
$messageType = '';

// Cargar redirecciones existentes
$redirects = loadJsonFile(REDIRECTS_FILE);

// Cargar usuarios existentes  
$users = loadJsonFile(USERS_FILE);

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
            
        case 'create_user':
            $newUsername = trim($_POST['new_username'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $newRole = trim($_POST['new_role'] ?? 'admin');
            
            if (empty($newUsername) || empty($newPassword)) {
                $message = 'El usuario y contraseña son obligatorios';
                $messageType = 'danger';
                break;
            }
            
            if (strlen($newPassword) < 6) {
                $message = 'La contraseña debe tener al menos 6 caracteres';
                $messageType = 'danger';
                break;
            }
            
            // Verificar que el usuario no exista
            $userExists = false;
            foreach ($users as $user) {
                if ($user['username'] === $newUsername) {
                    $userExists = true;
                    break;
                }
            }
            
            if ($userExists) {
                $message = 'El usuario ya existe';
                $messageType = 'danger';
                break;
            }
            
            // Generar nuevo ID
            $maxId = 0;
            foreach ($users as $user) {
                if ($user['id'] > $maxId) {
                    $maxId = $user['id'];
                }
            }
            
            // Crear nuevo usuario
            $newUser = [
                'id' => $maxId + 1,
                'username' => $newUsername,
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'role' => $newRole,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['username']
            ];
            
            $users[] = $newUser;
            saveJsonFile(USERS_FILE, $users);
            
            $message = 'Usuario creado exitosamente: ' . $newUsername;
            $messageType = 'success';
            break;
            
        case 'edit_user':
            $editUserId = $_POST['edit_user_id'] ?? '';
            $editUsername = trim($_POST['edit_username'] ?? '');
            $editRole = trim($_POST['edit_role'] ?? '');
            $editPassword = trim($_POST['edit_password'] ?? '');
            
            if (empty($editUsername)) {
                $message = 'El nombre de usuario es obligatorio';
                $messageType = 'danger';
                break;
            }
            
            // No permitir editar el propio usuario si es el último admin
            $adminCount = 0;
            foreach ($users as $user) {
                if ($user['role'] === 'admin') {
                    $adminCount++;
                }
            }
            
            $currentUser = null;
            foreach ($users as $user) {
                if ($user['id'] == $editUserId) {
                    $currentUser = $user;
                    break;
                }
            }
            
            if ($currentUser && $currentUser['role'] === 'admin' && $editRole !== 'admin' && $adminCount <= 1) {
                $message = 'No se puede cambiar el rol del último administrador';
                $messageType = 'danger';
                break;
            }
            
            // Verificar que el username no esté en uso por otro usuario
            $usernameInUse = false;
            foreach ($users as $user) {
                if ($user['username'] === $editUsername && $user['id'] != $editUserId) {
                    $usernameInUse = true;
                    break;
                }
            }
            
            if ($usernameInUse) {
                $message = 'El nombre de usuario ya está en uso';
                $messageType = 'danger';
                break;
            }
            
            // Actualizar usuario
            $found = false;
            foreach ($users as &$user) {
                if ($user['id'] == $editUserId) {
                    $user['username'] = $editUsername;
                    $user['role'] = $editRole;
                    if (!empty($editPassword)) {
                        if (strlen($editPassword) < 6) {
                            $message = 'La contraseña debe tener al menos 6 caracteres';
                            $messageType = 'danger';
                            break 2;
                        }
                        $user['password'] = password_hash($editPassword, PASSWORD_DEFAULT);
                    }
                    $user['updated_at'] = date('Y-m-d H:i:s');
                    $user['updated_by'] = $_SESSION['username'];
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                saveJsonFile(USERS_FILE, $users);
                $message = 'Usuario actualizado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Usuario no encontrado';
                $messageType = 'danger';
            }
            break;
            
        case 'delete_user':
            $deleteUserId = $_POST['delete_user_id'] ?? '';
            
            // No permitir eliminar el propio usuario
            if ($deleteUserId == $_SESSION['user_id']) {
                $message = 'No puedes eliminar tu propio usuario';
                $messageType = 'danger';
                break;
            }
            
            // Verificar que no sea el último admin
            $adminCount = 0;
            $userToDelete = null;
            foreach ($users as $user) {
                if ($user['role'] === 'admin') {
                    $adminCount++;
                }
                if ($user['id'] == $deleteUserId) {
                    $userToDelete = $user;
                }
            }
            
            if ($userToDelete && $userToDelete['role'] === 'admin' && $adminCount <= 1) {
                $message = 'No se puede eliminar el último administrador';
                $messageType = 'danger';
                break;
            }
            
            // Eliminar usuario
            $users = array_filter($users, function($user) use ($deleteUserId) {
                return $user['id'] != $deleteUserId;
            });
            
            $users = array_values($users);
            saveJsonFile(USERS_FILE, $users);
            
            $message = 'Usuario eliminado exitosamente';
            $messageType = 'success';
            break;
    }
}

// Recargar datos después de cambios
$redirects = loadJsonFile(REDIRECTS_FILE);
$users = loadJsonFile(USERS_FILE);
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

        <!-- Navegación por pestañas -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-management" 
                        type="button" role="tab" aria-controls="qr-management" aria-selected="true">
                    <i class="fas fa-qrcode me-2"></i>
                    Gestión de QRs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#user-management" 
                        type="button" role="tab" aria-controls="user-management" aria-selected="false">
                    <i class="fas fa-users me-2"></i>
                    Gestión de Usuarios
                </button>
            </li>
        </ul>

        <!-- Contenido de pestañas -->
        <div class="tab-content" id="adminTabsContent">\
            <!-- Pestaña QR Management -->
            <div class="tab-pane fade show active" id="qr-management" role="tabpanel" aria-labelledby="qr-tab">

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
        
        </div> <!-- Fin pestaña QR Management -->
        
        <!-- Pestaña User Management -->
        <div class="tab-pane fade" id="user-management" role="tabpanel" aria-labelledby="users-tab">
            <div class="row">
                <!-- Formulario de creación de usuario -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-plus me-2"></i>
                                Crear Nuevo Usuario
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create_user">
                                
                                <div class="mb-3">
                                    <label for="new_username" class="form-label">Nombre de Usuario *</label>
                                    <input type="text" class="form-control" id="new_username" name="new_username" 
                                           placeholder="usuario" required pattern="[a-zA-Z0-9_]+" maxlength="20">
                                    <div class="form-text">Solo letras, números y guiones bajos</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           placeholder="Mínimo 6 caracteres" required minlength="6">
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_role" class="form-label">Rol</label>
                                    <select class="form-select" id="new_role" name="new_role">
                                        <option value="admin">Administrador</option>
                                        <option value="manager">Manager</option>
                                        <option value="user">Usuario</option>
                                    </select>
                                    <div class="form-text">Nivel de acceso del usuario</div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Crear Usuario
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Usuarios del Sistema (<?php echo count($users); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay usuarios en el sistema</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuario</th>
                                                <th>Rol</th>
                                                <th>Creado</th>
                                                <th>Última Actualización</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr class="<?php echo $user['id'] == $_SESSION['user_id'] ? 'table-info' : ''; ?>">
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($user['id']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user me-2"></i>
                                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                                <span class="badge bg-primary ms-2">Tú</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $roleColors = [
                                                            'admin' => 'danger',
                                                            'manager' => 'warning', 
                                                            'user' => 'info'
                                                        ];
                                                        $roleNames = [
                                                            'admin' => 'Administrador',
                                                            'manager' => 'Manager',
                                                            'user' => 'Usuario'
                                                        ];
                                                        $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                                        $roleName = $roleNames[$user['role']] ?? $user['role'];
                                                        ?>
                                                        <span class="badge bg-<?php echo $roleColor; ?>">
                                                            <?php echo htmlspecialchars($roleName); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?><br>
                                                            <?php if (isset($user['created_by'])): ?>
                                                                <span class="text-muted">por <?php echo htmlspecialchars($user['created_by']); ?></span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php if (isset($user['updated_at'])): ?>
                                                                <?php echo htmlspecialchars($user['updated_at']); ?><br>
                                                                <span class="text-muted">por <?php echo htmlspecialchars($user['updated_by'] ?? 'N/A'); ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted">Sin modificaciones</span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Activo</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                    onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')" 
                                                                    title="Editar usuario">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                                        title="Eliminar usuario">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
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
        </div> <!-- Fin pestaña User Management -->
        
        </div> <!-- Fin tab-content -->
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

    <!-- Modal para editar usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        Editar Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="edit_user_id" id="editUserId">
                        
                        <div class="mb-3">
                            <label for="editUserUsername" class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="editUserUsername" name="edit_username" 
                                   required pattern="[a-zA-Z0-9_]+" maxlength="20">
                            <div class="form-text">Solo letras, números y guiones bajos</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserRole" class="form-label">Rol</label>
                            <select class="form-select" id="editUserRole" name="edit_role">
                                <option value="admin">Administrador</option>
                                <option value="manager">Manager</option>
                                <option value="user">Usuario</option>
                            </select>
                            <div class="form-text">Nivel de acceso del usuario</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editUserPassword" class="form-label">Nueva Contraseña (Opcional)</label>
                            <input type="password" class="form-control" id="editUserPassword" name="edit_password" 
                                   placeholder="Dejar vacío para mantener la actual" minlength="6">
                            <div class="form-text">Mínimo 6 caracteres. Dejar vacío para no cambiar</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Los cambios en los permisos tomarán efecto en el próximo inicio de sesión del usuario.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar usuario -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación de Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¡Cuidado!</strong> Esta acción no se puede deshacer.
                    </div>
                    <p>¿Está seguro de que desea eliminar el usuario <strong id="deleteUserName"></strong>?</p>
                    <p class="text-muted">El usuario perderá acceso inmediatamente al sistema.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="delete_user_id" id="deleteUserIdInput">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>
                            Eliminar Usuario
                        </button>
                    </form>
                </div>
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
        
        function editUser(id, username, role) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUserUsername').value = username;
            document.getElementById('editUserRole').value = role;
            document.getElementById('editUserPassword').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        function deleteUser(id, username) {
            document.getElementById('deleteUserIdInput').value = id;
            document.getElementById('deleteUserName').textContent = username;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
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