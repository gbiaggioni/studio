<?php
require_once 'config.php';
requireLogin();

$message = '';
$messageType = '';

// Cargar redirecciones existentes
$redirects = loadJsonFile(REDIRECTS_FILE);

// Cargar usuarios existentes  
$users = loadJsonFile(USERS_FILE);

// Cargar categorías
$categories = loadCategories();

// Cargar templates
$templates = loadTemplates();

// Procesar acciones
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $destinationUrl = trim($_POST['destination_url'] ?? '');
            $customId = trim($_POST['custom_id'] ?? '');
            $categoryId = $_POST['category_id'] ?? null;
            $description = trim($_POST['description'] ?? '');
            
            // Obtener datos de personalización visual
            $qrStyle = [
                'size' => $_POST['qr_size'] ?? 300,
                'foreground_color' => $_POST['foreground_color'] ?? '#000000',
                'background_color' => $_POST['background_color'] ?? '#FFFFFF',
                'error_correction' => $_POST['error_correction'] ?? 'M',
                'frame_style' => $_POST['frame_style'] ?? 'none',
                'frame_color' => $_POST['frame_color'] ?? '#000000',
                'corner_style' => $_POST['corner_style'] ?? 'square',
                'data_style' => $_POST['data_style'] ?? 'square'
            ];
            
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
            
            // Crear archivo index.php en la carpeta que redirige al sistema centralizado
            $indexContent = "<?php\nheader('Location: ../../redirect.php?id=" . addslashes($qrId) . "');\nexit;\n?>";
            file_put_contents($qrPath . '/index.php', $indexContent);
            
            // Guardar estilo personalizado
            saveQrStyle($qrId, $qrStyle);
            
            // Guardar en redirects.json
            $newRedirect = [
                'id' => $qrId,
                'destination_url' => $destinationUrl,
                'qr_url' => BASE_URL . '/redirect.php?id=' . $qrId,
                'category_id' => $categoryId,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['username'],
                'style' => $qrStyle
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
                    
                    // No necesitamos actualizar archivo físico porque el sistema centralizado maneja las redirecciones
                    
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
            
        case 'create_category':
            $categoryName = trim($_POST['category_name'] ?? '');
            $categoryDescription = trim($_POST['category_description'] ?? '');
            $categoryColor = $_POST['category_color'] ?? '#3498db';
            $categoryIcon = $_POST['category_icon'] ?? 'fas fa-folder';
            
            if (empty($categoryName)) {
                $message = 'El nombre de la categoría es obligatorio';
                $messageType = 'danger';
                break;
            }
            
            // Verificar que no exista una categoría con el mismo nombre
            $categoryExists = false;
            foreach ($categories as $category) {
                if (strtolower($category['name']) === strtolower($categoryName)) {
                    $categoryExists = true;
                    break;
                }
            }
            
            if ($categoryExists) {
                $message = 'Ya existe una categoría con ese nombre';
                $messageType = 'danger';
                break;
            }
            
            $newCategory = createCategory($categoryName, $categoryDescription, $categoryColor, $categoryIcon, $_SESSION['username']);
            
            $message = 'Categoría creada exitosamente: ' . $categoryName;
            $messageType = 'success';
            break;
    }
}

// Recargar datos después de cambios
$redirects = loadJsonFile(REDIRECTS_FILE);
$users = loadJsonFile(USERS_FILE);
$categories = loadCategories();

// Procesar filtros y búsqueda
$categoryFilter = $_GET['category'] ?? null;
$searchTerm = $_GET['search'] ?? '';

if ($categoryFilter) {
    $redirects = filterQrsByCategory($redirects, $categoryFilter);
}

if ($searchTerm) {
    $redirects = searchQrs($redirects, $searchTerm);
}

// Cargar datos de analytics
$analyticsSummary = getAnalyticsSummary();
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
        .category-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        .qr-preview {
            max-width: 200px;
            max-height: 200px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
        }
        .color-picker-wrapper {
            position: relative;
            display: inline-block;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
            margin-left: 10px;
        }
        .style-selector {
            margin-bottom: 15px;
        }
        .visual-options {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .filter-bar {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-management" 
                        type="button" role="tab" aria-controls="categories-management" aria-selected="false">
                    <i class="fas fa-folder me-2"></i>
                    Categorías
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates-management" 
                        type="button" role="tab" aria-controls="templates-management" aria-selected="false">
                    <i class="fas fa-magic me-2"></i>
                    Templates
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk-management" 
                        type="button" role="tab" aria-controls="bulk-management" aria-selected="false">
                    <i class="fas fa-cogs me-2"></i>
                    Gestión Masiva
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics-management" 
                        type="button" role="tab" aria-controls="analytics-management" aria-selected="false">
                    <i class="fas fa-chart-bar me-2"></i>
                    Analytics
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
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Crear Nueva Redirección QR
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="createQrForm">
                            <input type="hidden" name="action" value="create">
                            
                            <!-- Información básica -->
                            <div class="mb-3">
                                <label for="destination_url" class="form-label">URL de Destino *</label>
                                <input type="url" class="form-control" id="destination_url" name="destination_url" 
                                       placeholder="https://ejemplo.com" required onchange="updateQrPreview()">
                                <div class="form-text">URL completa a la que redirigirá el código QR</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="custom_id" class="form-label">ID Personalizado</label>
                                        <input type="text" class="form-control" id="custom_id" name="custom_id" 
                                               placeholder="mi-qr-personalizado" pattern="[a-zA-Z0-9\-_]+">
                                        <div class="form-text">Opcional: deje vacío para generar automáticamente</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoría</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Sin categoría</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Descripción opcional del QR"></textarea>
                            </div>
                            
                            <!-- Vista previa -->
                            <div class="mb-3">
                                <label class="form-label">Vista Previa</label>
                                <div class="qr-preview" id="qrPreview">
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                    <p class="text-muted mt-2">Vista previa del QR</p>
                                </div>
                            </div>
                            
                            <!-- Personalización Visual -->
                            <div class="visual-options">
                                <h6><i class="fas fa-palette me-2"></i>Personalización Visual</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="foreground_color" class="form-label">Color Principal</label>
                                            <div class="d-flex align-items-center">
                                                <input type="color" class="form-control form-control-color" 
                                                       id="foreground_color" name="foreground_color" value="#000000" 
                                                       onchange="updateQrPreview()">
                                                <span class="ms-2 small">QR</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="background_color" class="form-label">Color Fondo</label>
                                            <div class="d-flex align-items-center">
                                                <input type="color" class="form-control form-control-color" 
                                                       id="background_color" name="background_color" value="#FFFFFF" 
                                                       onchange="updateQrPreview()">
                                                <span class="ms-2 small">Fondo</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="qr_size" class="form-label">Tamaño</label>
                                            <select class="form-select" id="qr_size" name="qr_size" onchange="updateQrPreview()">
                                                <option value="200">200x200 (Pequeño)</option>
                                                <option value="300" selected>300x300 (Mediano)</option>
                                                <option value="400">400x400 (Grande)</option>
                                                <option value="500">500x500 (Extra Grande)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="error_correction" class="form-label">Corrección Error</label>
                                            <select class="form-select" id="error_correction" name="error_correction" onchange="updateQrPreview()">
                                                <option value="L">Baja (7%)</option>
                                                <option value="M" selected>Media (15%)</option>
                                                <option value="Q">Alta (25%)</option>
                                                <option value="H">Muy Alta (30%)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="frame_style" class="form-label">Estilo Marco</label>
                                            <select class="form-select" id="frame_style" name="frame_style">
                                                <option value="none">Sin marco</option>
                                                <option value="solid">Marco sólido</option>
                                                <option value="rounded">Marco redondeado</option>
                                                <option value="gradient">Marco degradado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="frame_color" class="form-label">Color Marco</label>
                                            <input type="color" class="form-control form-control-color" 
                                                   id="frame_color" name="frame_color" value="#000000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-qrcode me-2"></i>
                                Crear Código QR Personalizado
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de redirecciones -->
            <div class="col-lg-7">
                <!-- Barra de filtros -->
                <div class="filter-bar">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Buscar por ID o URL..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Filtrar por Categoría</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Redirecciones QR 
                            <span class="badge bg-primary"><?php echo count($redirects); ?></span>
                        </h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                <i class="fas fa-plus me-1"></i>Nueva Categoría
                            </button>
                        </div>
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
                                            <th>QR Info</th>
                                            <th>Destino</th>
                                            <th>Código QR</th>
                                            <th>Categoría</th>
                                            <th>Estadísticas</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($redirects as $redirect): ?>
                                            <?php 
                                            $category = isset($redirect['category_id']) ? getCategoryById($redirect['category_id']) : null;
                                            $qrStyle = isset($redirect['style']) ? $redirect['style'] : getDefaultQrStyle();
                                            $qrAnalytics = getQrAnalytics($redirect['id']);
                                            $clickCount = count($qrAnalytics);
                                            ?>
                                            <tr>
                                                <!-- QR Info -->
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <div>
                                                            <strong><code><?php echo htmlspecialchars($redirect['id']); ?></code></strong>
                                                            <?php if (!empty($redirect['description'])): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($redirect['description']); ?></small>
                                                            <?php endif; ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                Creado: <?php echo date('d/m/Y', strtotime($redirect['created_at'])); ?>
                                                                por <?php echo htmlspecialchars($redirect['created_by']); ?>
                                                            </small>
                                                            <?php if (isset($redirect['updated_at'])): ?>
                                                                <br><small class="text-warning">
                                                                    Editado: <?php echo date('d/m/Y', strtotime($redirect['updated_at'])); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                
                                                <!-- Destino -->
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($redirect['destination_url']); ?>" 
                                                       target="_blank" class="text-decoration-none">
                                                        <?php echo htmlspecialchars(substr($redirect['destination_url'], 0, 40) . (strlen($redirect['destination_url']) > 40 ? '...' : '')); ?>
                                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                                    </a>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-link me-1"></i>
                                                        <a href="<?php echo htmlspecialchars($redirect['qr_url']); ?>" 
                                                           target="_blank" class="text-muted">
                                                            <?php echo htmlspecialchars(substr($redirect['qr_url'], 0, 35) . '...'); ?>
                                                        </a>
                                                    </small>
                                                </td>
                                                
                                                <!-- Código QR Personalizado -->
                                                <td>
                                                    <?php 
                                                    $customQrUrl = generateCustomQR($redirect['qr_url'], $qrStyle);
                                                    ?>
                                                    <img src="<?php echo $customQrUrl; ?>&size=80x80" 
                                                         class="qr-code" alt="QR Code" style="max-width: 80px; max-height: 80px;">
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $qrStyle['size']; ?>px • 
                                                        <span style="color: <?php echo $qrStyle['foreground_color']; ?>">●</span>
                                                        <span style="color: <?php echo $qrStyle['background_color']; ?>">●</span>
                                                    </small>
                                                </td>
                                                
                                                <!-- Categoría -->
                                                <td>
                                                    <?php if ($category): ?>
                                                        <span class="badge category-badge" 
                                                              style="background-color: <?php echo $category['color']; ?>">
                                                            <i class="<?php echo $category['icon']; ?> me-1"></i>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary category-badge">
                                                            <i class="fas fa-folder me-1"></i>Sin categoría
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <!-- Estadísticas -->
                                                <td>
                                                    <div class="text-center">
                                                        <strong class="text-primary"><?php echo $clickCount; ?></strong>
                                                        <br><small class="text-muted">clicks</small>
                                                        <?php if ($clickCount > 0): ?>
                                                            <?php 
                                                            $lastAccess = end($qrAnalytics);
                                                            $daysSinceLastAccess = floor((time() - strtotime($lastAccess['timestamp'])) / 86400);
                                                            ?>
                                                            <br><small class="text-info">
                                                                Último: hace <?php echo $daysSinceLastAccess; ?> día<?php echo $daysSinceLastAccess != 1 ? 's' : ''; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                
                                                <!-- Acciones -->
                                                <td>
                                                    <div class="btn-group-vertical" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-info mb-1" 
                                                                onclick="viewQrDetails('<?php echo htmlspecialchars($redirect['id']); ?>')" 
                                                                title="Ver detalles y descargar">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-warning mb-1" 
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
        
        <!-- Pestaña Categorías -->
        <div class="tab-pane fade" id="categories-management" role="tabpanel" aria-labelledby="categories-tab">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-folder me-2"></i>
                                Categorías Existentes
                                <span class="badge bg-primary"><?php echo count($categories); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categories)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay categorías creadas aún</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                        <i class="fas fa-plus me-2"></i>Crear Primera Categoría
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Categoría</th>
                                                <th>Descripción</th>
                                                <th>QRs Asignados</th>
                                                <th>Creado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category): ?>
                                                <?php 
                                                // Contar QRs en esta categoría
                                                $allRedirects = loadJsonFile(REDIRECTS_FILE);
                                                $qrsInCategory = array_filter($allRedirects, function($r) use ($category) {
                                                    return isset($r['category_id']) && $r['category_id'] == $category['id'];
                                                });
                                                $qrCount = count($qrsInCategory);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge me-3" style="background-color: <?php echo $category['color']; ?>; padding: 8px;">
                                                                <i class="<?php echo $category['icon']; ?>"></i>
                                                            </span>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                                <br>
                                                                <small class="text-muted">ID: <?php echo $category['id']; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($category['description'])): ?>
                                                            <?php echo htmlspecialchars($category['description']); ?>
                                                        <?php else: ?>
                                                            <em class="text-muted">Sin descripción</em>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="text-center">
                                                            <span class="badge bg-info"><?php echo $qrCount; ?></span>
                                                            <br>
                                                            <small class="text-muted">QRs</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                                                            <br>
                                                            <span class="text-muted">por <?php echo htmlspecialchars($category['created_by']); ?></span>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="?category=<?php echo $category['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary" 
                                                               title="Ver QRs de esta categoría">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                    onclick="editCategory('<?php echo htmlspecialchars(json_encode($category)); ?>')" 
                                                                    title="Editar categoría">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($qrCount == 0): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" 
                                                                    title="Eliminar categoría">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                    title="No se puede eliminar: tiene QRs asignados" disabled>
                                                                <i class="fas fa-lock"></i>
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
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>
                                Gestión de Categorías
                            </h5>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#categoryModal">
                                <i class="fas fa-plus me-2"></i>
                                Nueva Categoría
                            </button>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>¿Para qué sirven las categorías?</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Organización:</strong> Agrupa QRs por propósito</li>
                                    <li><strong>Filtrado:</strong> Encuentra QRs rápidamente</li>
                                    <li><strong>Analytics:</strong> Analiza rendimiento por categoría</li>
                                    <li><strong>Branding:</strong> Colores e iconos personalizados</li>
                                </ul>
                            </div>
                            
                            <!-- Estadísticas de categorías -->
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-chart-pie me-2"></i>Estadísticas</h6>
                                    
                                    <?php 
                                    $allRedirects = loadJsonFile(REDIRECTS_FILE);
                                    $categoryStats = [];
                                    $uncategorized = 0;
                                    
                                    foreach ($allRedirects as $redirect) {
                                        if (isset($redirect['category_id'])) {
                                            $categoryStats[$redirect['category_id']] = ($categoryStats[$redirect['category_id']] ?? 0) + 1;
                                        } else {
                                            $uncategorized++;
                                        }
                                    }
                                    ?>
                                    
                                    <?php foreach ($categories as $category): ?>
                                        <?php $count = $categoryStats[$category['id']] ?? 0; ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge me-2" style="background-color: <?php echo $category['color']; ?>; width: 12px; height: 12px;"></span>
                                                <small><?php echo htmlspecialchars($category['name']); ?></small>
                                            </div>
                                            <small><strong><?php echo $count; ?></strong></small>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($uncategorized > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-secondary me-2" style="width: 12px; height: 12px;"></span>
                                                <small>Sin categoría</small>
                                            </div>
                                            <small><strong><?php echo $uncategorized; ?></strong></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fin pestaña Categorías -->
        
        <!-- Pestaña Templates -->
        <div class="tab-pane fade" id="templates-management" role="tabpanel" aria-labelledby="templates-tab">
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-magic me-2"></i>
                                Templates Predefinidos
                                <span class="badge bg-primary"><?php echo count($templates); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Filtros de templates -->
                            <div class="filter-bar mb-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select class="form-select" id="templateCategoryFilter" onchange="filterTemplates()">
                                            <option value="">Todas las categorías</option>
                                            <option value="Redes Sociales">Redes Sociales</option>
                                            <option value="Contacto">Contacto</option>
                                            <option value="Restaurante">Restaurante</option>
                                            <option value="Tecnología">Tecnología</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Eventos">Eventos</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="templateSearch" 
                                               placeholder="Buscar templates..." onkeyup="filterTemplates()">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grid de templates -->
                            <div class="row g-3" id="templatesGrid">
                                <?php foreach ($templates as $template): ?>
                                <div class="col-md-6 template-card" data-category="<?php echo $template['category']; ?>" 
                                     data-name="<?php echo strtolower($template['name']); ?>">
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <i class="<?php echo $template['icon']; ?> fa-2x" 
                                                       style="color: <?php echo $template['style']['foreground_color']; ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h6>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($template['description']); ?></p>
                                                    <span class="badge bg-secondary"><?php echo $template['category']; ?></span>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-primary btn-sm w-100" 
                                                        onclick="openTemplateModal(<?php echo $template['id']; ?>)">
                                                    <i class="fas fa-magic me-1"></i>Usar Template
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                ¿Qué son los Templates?
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>Templates Inteligentes</h6>
                                <p class="mb-2 small">Los templates son plantillas prediseñadas que te permiten crear QRs profesionales en segundos.</p>
                                <ul class="mb-0 small">
                                    <li><strong>Redes Sociales:</strong> Instagram, Facebook, LinkedIn</li>
                                    <li><strong>Contacto:</strong> WhatsApp, vCard</li>
                                    <li><strong>Tecnología:</strong> WiFi automático</li>
                                    <li><strong>Marketing:</strong> Google Reviews</li>
                                </ul>
                            </div>
                            
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-chart-line me-2"></i>Estadísticas de Templates</h6>
                                    <?php 
                                    $templateCategories = [];
                                    foreach ($templates as $template) {
                                        $templateCategories[$template['category']] = ($templateCategories[$template['category']] ?? 0) + 1;
                                    }
                                    ?>
                                    <?php foreach ($templateCategories as $category => $count): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small><?php echo $category; ?></small>
                                            <strong><span class="badge bg-primary"><?php echo $count; ?></span></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fin pestaña Templates -->
        
        <!-- Pestaña Gestión Masiva -->
        <div class="tab-pane fade" id="bulk-management" role="tabpanel" aria-labelledby="bulk-tab">
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-download me-2"></i>
                                Exportar QRs
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="exportForm">
                                <div class="mb-3">
                                    <label for="exportFormat" class="form-label">Formato de Exportación</label>
                                    <select class="form-select" id="exportFormat" name="format">
                                        <option value="json">JSON (completo con configuración)</option>
                                        <option value="csv">CSV (datos tabulares)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="exportCategory" class="form-label">Filtrar por Categoría</label>
                                    <select class="form-select" id="exportCategory" name="category">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="exportSearch" class="form-label">Filtrar por Texto</label>
                                    <input type="text" class="form-control" id="exportSearch" name="search" 
                                           placeholder="Buscar por ID o URL...">
                                </div>
                                
                                <button type="button" class="btn btn-success w-100" onclick="exportQRs()">
                                    <i class="fas fa-download me-2"></i>
                                    Exportar QRs
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-copy me-2"></i>
                                Duplicar QR
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="duplicateForm">
                                <div class="mb-3">
                                    <label for="originalQrId" class="form-label">QR Original</label>
                                    <select class="form-select" id="originalQrId" required>
                                        <option value="">Seleccionar QR a duplicar</option>
                                        <?php 
                                        $allRedirects = loadJsonFile(REDIRECTS_FILE);
                                        foreach ($allRedirects as $redirect): 
                                        ?>
                                            <option value="<?php echo $redirect['id']; ?>">
                                                <?php echo $redirect['id']; ?> - <?php echo substr($redirect['destination_url'], 0, 50); ?>...
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="newQrId" class="form-label">Nuevo ID (opcional)</label>
                                    <input type="text" class="form-control" id="newQrId" 
                                           placeholder="Deja vacío para generar automáticamente">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="newDestinationUrl" class="form-label">Nueva URL (opcional)</label>
                                    <input type="url" class="form-control" id="newDestinationUrl" 
                                           placeholder="Deja vacío para mantener la original">
                                </div>
                                
                                <button type="button" class="btn btn-warning w-100" onclick="duplicateQR()">
                                    <i class="fas fa-copy me-2"></i>
                                    Duplicar QR
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-upload me-2"></i>
                                Importar QRs
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Importante:</strong> Solo se importan QRs con IDs únicos. Los duplicados se omitirán.
                            </div>
                            
                            <form id="importForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="importFile" class="form-label">Seleccionar Archivo</label>
                                    <input type="file" class="form-control" id="importFile" name="import_file" 
                                           accept=".json,.csv" required>
                                    <div class="form-text">Formatos soportados: JSON, CSV</div>
                                </div>
                                
                                <button type="button" class="btn btn-primary w-100" onclick="importQRs()">
                                    <i class="fas fa-upload me-2"></i>
                                    Importar QRs
                                </button>
                            </form>
                            
                            <div id="importProgress" class="mt-3" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 100%"></div>
                                </div>
                                <small class="text-muted">Procesando archivo...</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Estadísticas Avanzadas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="advancedStats">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-info w-100 mt-3" onclick="loadAdvancedStats()">
                                <i class="fas fa-sync me-2"></i>
                                Actualizar Estadísticas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- Fin pestaña Gestión Masiva -->
        
        <!-- Pestaña Analytics -->
        <div class="tab-pane fade" id="analytics-management" role="tabpanel" aria-labelledby="analytics-tab">
            
            <!-- Métricas generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-mouse-pointer fa-2x text-primary mb-2"></i>
                            <h5 class="card-title"><?php echo number_format($analyticsSummary['total_clicks']); ?></h5>
                            <p class="card-text text-muted">Total Clicks</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-qrcode fa-2x text-success mb-2"></i>
                            <h5 class="card-title"><?php echo number_format($analyticsSummary['unique_qrs']); ?></h5>
                            <p class="card-text text-muted">QRs Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                            <h5 class="card-title"><?php echo number_format($analyticsSummary['today_clicks']); ?></h5>
                            <p class="card-text text-muted">Hoy</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-calendar-week fa-2x text-info mb-2"></i>
                            <h5 class="card-title"><?php echo number_format($analyticsSummary['week_clicks']); ?></h5>
                            <p class="card-text text-muted">Esta Semana</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Gráfico de dispositivos -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-mobile-alt me-2"></i>
                                Dispositivos Utilizados
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="deviceChart" width="400" height="200"></canvas>
                            <div class="mt-3">
                                <div class="row text-center">
                                    <div class="col">
                                        <strong><?php echo $analyticsSummary['device_breakdown']['mobile']; ?></strong>
                                        <br><small class="text-muted">Móvil</small>
                                    </div>
                                    <div class="col">
                                        <strong><?php echo $analyticsSummary['device_breakdown']['desktop']; ?></strong>
                                        <br><small class="text-muted">Desktop</small>
                                    </div>
                                    <div class="col">
                                        <strong><?php echo $analyticsSummary['device_breakdown']['tablet']; ?></strong>
                                        <br><small class="text-muted">Tablet</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top países -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-globe me-2"></i>
                                Top Países
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($analyticsSummary['country_breakdown'])): ?>
                                <p class="text-muted text-center">No hay datos de ubicación aún</p>
                            <?php else: ?>
                                <?php $maxCount = max($analyticsSummary['country_breakdown']); ?>
                                <?php foreach ($analyticsSummary['country_breakdown'] as $country => $count): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?php echo htmlspecialchars($country); ?></span>
                                            <strong><?php echo $count; ?></strong>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo ($count / $maxCount) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <!-- Top QRs más usados -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>
                                QRs Más Populares
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($analyticsSummary['top_qrs'])): ?>
                                <p class="text-muted text-center">No hay datos de uso aún</p>
                            <?php else: ?>
                                <?php $maxClicks = max($analyticsSummary['top_qrs']); ?>
                                <?php $position = 1; ?>
                                <?php foreach ($analyticsSummary['top_qrs'] as $qrId => $clicks): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>
                                                <span class="badge bg-secondary me-2">#<?php echo $position; ?></span>
                                                <code><?php echo htmlspecialchars($qrId); ?></code>
                                            </span>
                                            <strong><?php echo $clicks; ?> clicks</strong>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo ($clicks / $maxClicks) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php $position++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Actividad reciente -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Actividad Reciente
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($analyticsSummary['recent_activity'])): ?>
                                <p class="text-muted text-center">No hay actividad reciente</p>
                            <?php else: ?>
                                <?php foreach ($analyticsSummary['recent_activity'] as $activity): ?>
                                    <div class="mb-3 pb-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><code><?php echo htmlspecialchars($activity['qr_id']); ?></code></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-<?php 
                                                        $deviceType = $activity['device_info']['type'] ?? 'desktop';
                                                        echo $deviceType === 'mobile' ? 'mobile-alt' : 
                                                            ($deviceType === 'tablet' ? 'tablet-alt' : 'desktop');
                                                    ?> me-1"></i>
                                                    <?php echo htmlspecialchars($activity['device_info']['type'] ?? 'unknown'); ?> • 
                                                    <?php echo htmlspecialchars($activity['location_info']['country'] ?? 'Unknown'); ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($activity['timestamp'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones de exportación -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-download me-2"></i>
                                Exportar Reportes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p>Descarga reportes detallados de analytics en diferentes formatos:</p>
                                    <div class="btn-group" role="group">
                                        <a href="export.php?format=csv" class="btn btn-outline-success">
                                            <i class="fas fa-file-csv me-2"></i>
                                            Exportar CSV
                                        </a>
                                        <a href="export.php?format=excel" class="btn btn-outline-primary">
                                            <i class="fas fa-file-excel me-2"></i>
                                            Exportar Excel
                                        </a>
                                        <a href="export.php?format=pdf" class="btn btn-outline-danger">
                                            <i class="fas fa-file-pdf me-2"></i>
                                            Exportar PDF
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <form method="GET" action="export.php" class="d-inline">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label small">Desde:</label>
                                                <input type="date" name="date_from" class="form-control form-control-sm" 
                                                       value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small">Hasta:</label>
                                                <input type="date" name="date_to" class="form-control form-control-sm" 
                                                       value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="format" value="csv">
                                        <button type="submit" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-filter me-1"></i>
                                            Filtrar y Exportar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div> <!-- Fin pestaña Analytics -->
        
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

    <!-- Modal para Template -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-magic me-2"></i>
                        <span id="templateModalTitle">Crear QR desde Template</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="templateForm">
                    <div class="modal-body">
                        <input type="hidden" id="selectedTemplateId" name="template_id">
                        
                        <!-- Template Info -->
                        <div id="templateInfo" class="alert alert-info mb-3">
                            <div class="d-flex align-items-center">
                                <i id="templateIcon" class="fas fa-magic fa-2x me-3"></i>
                                <div>
                                    <h6 id="templateName" class="mb-1"></h6>
                                    <p id="templateDescription" class="mb-0 small"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Template Fields (Dynamic) -->
                        <div id="templateFields"></div>
                        
                        <!-- QR Configuration -->
                        <hr>
                        <h6><i class="fas fa-cog me-2"></i>Configuración del QR</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="templateCustomId" class="form-label">ID Personalizado</label>
                                    <input type="text" class="form-control" id="templateCustomId" name="custom_id" 
                                           placeholder="Opcional: deje vacío para generar automáticamente">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="templateCategoryId" class="form-label">Categoría</label>
                                    <select class="form-select" id="templateCategoryId" name="category_id">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="templateDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="templateDescriptionInput" name="description" rows="2" 
                                      placeholder="Descripción opcional del QR"></textarea>
                        </div>
                        
                        <!-- Preview -->
                        <div class="alert alert-secondary">
                            <strong>Vista Previa URL:</strong>
                            <div id="urlPreview" class="font-monospace small">Completa los campos para ver la URL</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-magic me-2"></i>
                            Crear QR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para crear categoría -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-folder-plus me-2"></i>
                        Crear Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_category">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" 
                                   placeholder="Ej: Marketing, Productos, Eventos" required maxlength="50">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="category_description" name="category_description" 
                                      rows="3" placeholder="Descripción opcional de la categoría"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_color" class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color" 
                                           id="category_color" name="category_color" value="#3498db">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_icon" class="form-label">Icono</label>
                                    <select class="form-select" id="category_icon" name="category_icon">
                                        <option value="fas fa-folder">📁 Carpeta</option>
                                        <option value="fas fa-bullhorn">📢 Marketing</option>
                                        <option value="fas fa-shopping-cart">🛒 Productos</option>
                                        <option value="fas fa-calendar-alt">📅 Eventos</option>
                                        <option value="fas fa-address-book">📇 Contacto</option>
                                        <option value="fas fa-file-alt">📄 Documentos</option>
                                        <option value="fas fa-share-alt">🔗 Redes Sociales</option>
                                        <option value="fas fa-utensils">🍽️ Restaurante</option>
                                        <option value="fas fa-home">🏠 Inmobiliaria</option>
                                        <option value="fas fa-graduation-cap">🎓 Educación</option>
                                        <option value="fas fa-heartbeat">⚕️ Salud</option>
                                        <option value="fas fa-music">🎵 Entretenimiento</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Las categorías te ayudan a organizar y filtrar tus códigos QR de manera más eficiente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            Crear Categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles del QR y descargas -->
    <div class="modal fade" id="qrDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i>
                        Detalles del Código QR
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="qrDetailsContent">
                    <!-- Contenido cargado dinámicamente -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        // Función para actualizar vista previa del QR
        function updateQrPreview() {
            const destinationUrl = document.getElementById('destination_url').value;
            const foregroundColor = document.getElementById('foreground_color').value;
            const backgroundColor = document.getElementById('background_color').value;
            const qrSize = document.getElementById('qr_size').value;
            const errorCorrection = document.getElementById('error_correction').value;
            
            if (destinationUrl) {
                // Generar URL del QR con configuración personalizada
                const baseUrl = '<?php echo BASE_URL; ?>/redirect.php?id=preview';
                const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
                
                const params = new URLSearchParams({
                    'data': baseUrl,
                    'size': '200x200',
                    'ecc': errorCorrection,
                    'color': foregroundColor.replace('#', ''),
                    'bgcolor': backgroundColor.replace('#', '')
                });
                
                const qrUrl = qrApiUrl + '?' + params.toString();
                
                document.getElementById('qrPreview').innerHTML = 
                    `<img src="${qrUrl}" alt="Vista previa QR" class="img-fluid" style="max-width: 200px;">
                     <br><small class="text-muted mt-2">${qrSize}px • Corrección: ${errorCorrection}</small>`;
            } else {
                document.getElementById('qrPreview').innerHTML = 
                    `<i class="fas fa-qrcode fa-3x text-muted"></i>
                     <p class="text-muted mt-2">Ingrese una URL para ver la vista previa</p>`;
            }
        }
        
        // Función para ver detalles del QR
        function viewQrDetails(qrId) {
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('qrDetailsModal'));
            modal.show();
            
            // Cargar contenido del QR
            fetch('qr-details.php?id=' + encodeURIComponent(qrId))
                .then(response => response.text())
                .then(html => {
                    document.getElementById('qrDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('qrDetailsContent').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar los detalles del QR.</div>';
                });
        }
        
        // Función para descargar QR en diferentes formatos
        function downloadQR(qrId, format, size) {
            const downloadUrl = `qr-download.php?id=${encodeURIComponent(qrId)}&format=${format}&size=${size}`;
            
            // Crear enlace temporal para descarga
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `qr-${qrId}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // ============ NUEVAS FUNCIONES DE TEMPLATES ============
        
        // Filtrar templates
        function filterTemplates() {
            const categoryFilter = document.getElementById('templateCategoryFilter').value;
            const searchTerm = document.getElementById('templateSearch').value.toLowerCase();
            const templateCards = document.querySelectorAll('.template-card');
            
            templateCards.forEach(card => {
                const category = card.dataset.category;
                const name = card.dataset.name;
                
                const categoryMatch = !categoryFilter || category === categoryFilter;
                const searchMatch = !searchTerm || name.includes(searchTerm);
                
                if (categoryMatch && searchMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Abrir modal de template
        function openTemplateModal(templateId) {
            fetch(`templates-handler.php?action=get_template&id=${templateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const template = data.template;
                        
                        // Llenar información del template
                        document.getElementById('selectedTemplateId').value = template.id;
                        document.getElementById('templateIcon').className = template.icon + ' fa-2x me-3';
                        document.getElementById('templateName').textContent = template.name;
                        document.getElementById('templateDescription').textContent = template.description;
                        
                        // Generar campos dinámicos
                        const fieldsContainer = document.getElementById('templateFields');
                        fieldsContainer.innerHTML = '';
                        
                        template.fields.forEach(field => {
                            const fieldHtml = generateTemplateField(field);
                            fieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
                        });
                        
                        // Mostrar modal
                        const modal = new bootstrap.Modal(document.getElementById('templateModal'));
                        modal.show();
                        
                        // Agregar event listeners para preview
                        addTemplateFieldListeners(template);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar template');
                });
        }
        
        // Generar HTML para campo de template
        function generateTemplateField(field) {
            let fieldHtml = `<div class="mb-3">
                <label for="field_${field.name}" class="form-label">${field.label}`;
            
            if (field.required) {
                fieldHtml += ' <span class="text-danger">*</span>';
            }
            
            fieldHtml += '</label>';
            
            switch (field.type) {
                case 'textarea':
                    fieldHtml += `<textarea class="form-control template-field" id="field_${field.name}" 
                                    name="${field.name}" placeholder="${field.placeholder || ''}" 
                                    ${field.required ? 'required' : ''}></textarea>`;
                    break;
                case 'select':
                    fieldHtml += `<select class="form-select template-field" id="field_${field.name}" 
                                    name="${field.name}" ${field.required ? 'required' : ''}>`;
                    field.options.forEach(option => {
                        const selected = option === field.default ? 'selected' : '';
                        fieldHtml += `<option value="${option}" ${selected}>${option}</option>`;
                    });
                    fieldHtml += '</select>';
                    break;
                default:
                    fieldHtml += `<input type="${field.type}" class="form-control template-field" 
                                    id="field_${field.name}" name="${field.name}" 
                                    placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}>`;
            }
            
            if (field.help) {
                fieldHtml += `<div class="form-text">${field.help}</div>`;
            }
            
            fieldHtml += '</div>';
            return fieldHtml;
        }
        
        // Agregar listeners para vista previa
        function addTemplateFieldListeners(template) {
            const fields = document.querySelectorAll('.template-field');
            fields.forEach(field => {
                field.addEventListener('input', () => updateUrlPreview(template));
            });
            updateUrlPreview(template);
        }
        
        // Actualizar vista previa de URL
        function updateUrlPreview(template) {
            let url = template.url_pattern;
            const fields = document.querySelectorAll('.template-field');
            
            fields.forEach(field => {
                const placeholder = `{${field.name}}`;
                const value = field.value || `[${field.name}]`;
                url = url.replace(placeholder, encodeURIComponent(value));
            });
            
            document.getElementById('urlPreview').textContent = url;
        }
        
        // Manejar envío de formulario de template
        document.getElementById('templateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Agregar datos de template
            const templateData = {};
            const templateFields = document.querySelectorAll('.template-field');
            templateFields.forEach(field => {
                templateData[field.name] = field.value;
            });
            
            formData.append('action', 'create_from_template');
            formData.append('template_data', JSON.stringify(templateData));
            
            fetch('templates-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('QR creado exitosamente desde template!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear QR desde template');
            });
        });
        
        // ============ FUNCIONES DE GESTIÓN MASIVA ============
        
        // Exportar QRs
        function exportQRs() {
            const format = document.getElementById('exportFormat').value;
            const category = document.getElementById('exportCategory').value;
            const search = document.getElementById('exportSearch').value;
            
            const params = new URLSearchParams({
                action: 'export',
                format: format,
                category: category,
                search: search
            });
            
            window.location.href = `bulk-handler.php?${params.toString()}`;
        }
        
        // Importar QRs
        function importQRs() {
            const fileInput = document.getElementById('importFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Por favor selecciona un archivo');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'import');
            formData.append('import_file', file);
            
            // Mostrar progreso
            document.getElementById('importProgress').style.display = 'block';
            
            fetch('bulk-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('importProgress').style.display = 'none';
                
                if (data.success) {
                    alert(`Importación exitosa!
                    - Importados: ${data.imported}
                    - Omitidos: ${data.skipped}
                    ${data.errors.length > 0 ? '- Errores: ' + data.errors.length : ''}`);
                    location.reload();
                } else {
                    alert('Error en importación: ' + data.message);
                }
            })
            .catch(error => {
                document.getElementById('importProgress').style.display = 'none';
                console.error('Error:', error);
                alert('Error al importar archivo');
            });
        }
        
        // Duplicar QR
        function duplicateQR() {
            const originalId = document.getElementById('originalQrId').value;
            const newId = document.getElementById('newQrId').value;
            const newUrl = document.getElementById('newDestinationUrl').value;
            
            if (!originalId) {
                alert('Selecciona un QR original');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'duplicate_qr');
            formData.append('original_id', originalId);
            formData.append('new_id', newId);
            
            const modifications = {};
            if (newUrl) {
                modifications.destination_url = newUrl;
            }
            formData.append('modifications', JSON.stringify(modifications));
            
            fetch('templates-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`QR duplicado exitosamente!
                    Nuevo ID: ${data.new_id}`);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al duplicar QR');
            });
        }
        
        // Cargar estadísticas avanzadas
        function loadAdvancedStats() {
            fetch('bulk-handler.php?action=get_advanced_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAdvancedStats(data.stats);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('advancedStats').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar estadísticas</div>';
                });
        }
        
        // Mostrar estadísticas avanzadas
        function displayAdvancedStats(stats) {
            const container = document.getElementById('advancedStats');
            
            let html = `
                <div class="row text-center mb-3">
                    <div class="col-md-4">
                        <h5 class="text-primary">${stats.total_qrs}</h5>
                        <small class="text-muted">QRs Totales</small>
                    </div>
                    <div class="col-md-4">
                        <h5 class="text-success">${stats.total_categories}</h5>
                        <small class="text-muted">Categorías</small>
                    </div>
                    <div class="col-md-4">
                        <h5 class="text-warning">${stats.total_clicks}</h5>
                        <small class="text-muted">Clicks Totales</small>
                    </div>
                </div>
                
                <h6><i class="fas fa-chart-pie me-2"></i>Por Categoría</h6>
            `;
            
            Object.values(stats.category_stats).forEach(category => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <span class="badge me-2" style="background-color: ${category.color}; width: 12px; height: 12px;"></span>
                            <small>${category.name}</small>
                        </div>
                        <small><strong>${category.qr_count} QRs (${category.total_clicks} clicks)</strong></small>
                    </div>
                `;
            });
            
            html += '<hr><h6><i class="fas fa-trophy me-2"></i>Top Performers</h6>';
            
            stats.top_performers.slice(0, 5).forEach((qr, index) => {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="badge bg-secondary me-2">#${index + 1}</span>
                            <small><code>${qr.id}</code></small>
                        </div>
                        <strong class="text-primary">${qr.clicks}</strong>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize charts when analytics tab is shown
        document.getElementById('analytics-tab').addEventListener('shown.bs.tab', function (e) {
            initializeCharts();
        });
        
        // Initialize charts if analytics tab is already active
        if (document.getElementById('analytics-tab').classList.contains('active')) {
            initializeCharts();
        }
        
        // Cargar estadísticas al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadAdvancedStats();
        });
        
        function initializeCharts() {
            // Device breakdown chart
            const deviceCtx = document.getElementById('deviceChart').getContext('2d');
            
            // Check if chart already exists and destroy it
            if (window.deviceChart instanceof Chart) {
                window.deviceChart.destroy();
            }
            
            const deviceData = {
                mobile: <?php echo $analyticsSummary['device_breakdown']['mobile']; ?>,
                desktop: <?php echo $analyticsSummary['device_breakdown']['desktop']; ?>,
                tablet: <?php echo $analyticsSummary['device_breakdown']['tablet']; ?>
            };
            
            window.deviceChart = new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Móvil', 'Desktop', 'Tablet'],
                    datasets: [{
                        data: [deviceData.mobile, deviceData.desktop, deviceData.tablet],
                        backgroundColor: [
                            '#28a745',
                            '#007bff', 
                            '#ffc107'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>