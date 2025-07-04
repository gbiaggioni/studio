<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'get_config':
            $response = handleGetConfig();
            break;
            
        case 'save_config':
            $response = handleSaveConfig();
            break;
            
        case 'remove_security':
            $response = handleRemoveSecurity();
            break;
            
        case 'add_employee':
            $response = handleAddEmployee();
            break;
            
        case 'get_employee':
            $response = handleGetEmployee();
            break;
            
        case 'edit_employee':
            $response = handleEditEmployee();
            break;
            
        case 'delete_employee':
            $response = handleDeleteEmployee();
            break;
            
        default:
            $response['message'] = 'Acción no válida';
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ];
}

echo json_encode($response);
exit;

// ============ FUNCIONES DE CONFIGURACIÓN ============

function handleGetConfig() {
    $qrId = $_GET['qr_id'] ?? '';
    
    if (empty($qrId)) {
        return ['success' => false, 'message' => 'ID de QR requerido'];
    }
    
    $security = getSecuritySettings($qrId);
    
    if ($security) {
        // No enviar la contraseña hasheada
        unset($security['password']);
        
        return [
            'success' => true,
            'config' => $security
        ];
    }
    
    return ['success' => true, 'config' => null];
}

function handleSaveConfig() {
    $qrId = $_POST['qr_id'] ?? '';
    $securityEnabled = isset($_POST['security_enabled']);
    
    if (empty($qrId)) {
        return ['success' => false, 'message' => 'ID de QR requerido'];
    }
    
    // Verificar que el QR existe
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $qrExists = false;
    foreach ($redirects as $redirect) {
        if ($redirect['id'] === $qrId) {
            $qrExists = true;
            break;
        }
    }
    
    if (!$qrExists) {
        return ['success' => false, 'message' => 'QR no encontrado'];
    }
    
    // Preparar configuración
    $config = [
        'security_enabled' => $securityEnabled,
        'security_type' => $_POST['security_type'] ?? 'none',
        'password' => $_POST['password'] ?? '',
        'password_hint' => $_POST['password_hint'] ?? '',
        'expiry_date' => $_POST['expiry_date'] ?? null,
        'allowed_ips' => json_decode($_POST['allowed_ips'] ?? '[]', true),
        'capture_form' => [
            'enabled' => false, // Para implementar después
            'title' => 'Información Requerida',
            'message' => 'Por favor, proporciona tu información para continuar.',
            'fields' => []
        ],
        'email_verification' => [
            'enabled' => false, // Para implementar después
            'allowed_domains' => []
        ],
        'employee_only' => isset($_POST['employee_only']),
        'max_uses' => !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null,
        'blocked_countries' => [],
        'require_user_agent' => false,
        'custom_redirect_delay' => (int)($_POST['custom_redirect_delay'] ?? 0),
        'access_log' => isset($_POST['access_log'])
    ];
    
    // Validaciones
    if ($securityEnabled) {
        if ($config['security_type'] === 'password' && empty($config['password'])) {
            return ['success' => false, 'message' => 'Contraseña requerida para protección por contraseña'];
        }
        
        if ($config['expiry_date'] && strtotime($config['expiry_date']) < time()) {
            return ['success' => false, 'message' => 'La fecha de caducidad debe ser futura'];
        }
        
        if ($config['max_uses'] && $config['max_uses'] < 1) {
            return ['success' => false, 'message' => 'El máximo de usos debe ser mayor a 0'];
        }
        
        if ($config['custom_redirect_delay'] < 0 || $config['custom_redirect_delay'] > 30) {
            return ['success' => false, 'message' => 'El delay debe estar entre 0 y 30 segundos'];
        }
        
        // Validar IPs
        foreach ($config['allowed_ips'] as $ip) {
            if (!validateIpOrCidr($ip)) {
                return ['success' => false, 'message' => "IP o CIDR inválido: $ip"];
            }
        }
    }
    
    // Crear o actualizar configuración
    if ($securityEnabled) {
        createSecuritySettings($qrId, $config, $_SESSION['username']);
    } else {
        // Remover configuración si se deshabilita
        $settings = loadSecuritySettings();
        unset($settings[$qrId]);
        saveSecuritySettings($settings);
    }
    
    return [
        'success' => true,
        'message' => $securityEnabled ? 'Configuración guardada exitosamente' : 'Protección removida exitosamente'
    ];
}

function handleRemoveSecurity() {
    $qrId = $_POST['qr_id'] ?? '';
    
    if (empty($qrId)) {
        return ['success' => false, 'message' => 'ID de QR requerido'];
    }
    
    $settings = loadSecuritySettings();
    
    if (!isset($settings[$qrId])) {
        return ['success' => false, 'message' => 'No hay configuración de seguridad para este QR'];
    }
    
    unset($settings[$qrId]);
    saveSecuritySettings($settings);
    
    return ['success' => true, 'message' => 'Protección removida exitosamente'];
}

// ============ FUNCIONES DE EMPLEADOS ============

function handleAddEmployee() {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    
    if (empty($email) || empty($name) || empty($department)) {
        return ['success' => false, 'message' => 'Todos los campos son requeridos'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    
    $result = addEmployee($email, $name, $department, $_SESSION['username']);
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Empleado agregado exitosamente',
            'employee' => $result
        ];
    } else {
        return ['success' => false, 'message' => 'El email ya está registrado'];
    }
}

function handleGetEmployee() {
    $employeeId = (int)($_GET['id'] ?? 0);
    
    if ($employeeId <= 0) {
        return ['success' => false, 'message' => 'ID de empleado inválido'];
    }
    
    $employees = loadEmployees();
    
    foreach ($employees as $employee) {
        if ($employee['id'] == $employeeId) {
            return [
                'success' => true,
                'employee' => $employee
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Empleado no encontrado'];
}

function handleEditEmployee() {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    
    if ($employeeId <= 0) {
        return ['success' => false, 'message' => 'ID de empleado inválido'];
    }
    
    if (empty($email) || empty($name) || empty($department)) {
        return ['success' => false, 'message' => 'Todos los campos son requeridos'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    
    $employees = loadEmployees();
    $found = false;
    
    // Verificar que el email no esté en uso por otro empleado
    foreach ($employees as $employee) {
        if ($employee['email'] === $email && $employee['id'] != $employeeId) {
            return ['success' => false, 'message' => 'El email ya está en uso por otro empleado'];
        }
    }
    
    // Actualizar empleado
    foreach ($employees as &$employee) {
        if ($employee['id'] == $employeeId) {
            $employee['email'] = $email;
            $employee['name'] = $name;
            $employee['department'] = $department;
            $employee['updated_at'] = date('Y-m-d H:i:s');
            $employee['updated_by'] = $_SESSION['username'];
            $found = true;
            break;
        }
    }
    
    if ($found) {
        saveEmployees($employees);
        return ['success' => true, 'message' => 'Empleado actualizado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Empleado no encontrado'];
    }
}

function handleDeleteEmployee() {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    
    if ($employeeId <= 0) {
        return ['success' => false, 'message' => 'ID de empleado inválido'];
    }
    
    $employees = loadEmployees();
    $originalCount = count($employees);
    
    $employees = array_filter($employees, function($employee) use ($employeeId) {
        return $employee['id'] != $employeeId;
    });
    
    if (count($employees) < $originalCount) {
        $employees = array_values($employees); // Reindexar
        saveEmployees($employees);
        return ['success' => true, 'message' => 'Empleado eliminado exitosamente'];
    } else {
        return ['success' => false, 'message' => 'Empleado no encontrado'];
    }
}

// ============ FUNCIONES AUXILIARES ============

function validateIpOrCidr($ip) {
    // Validar IP simple
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return true;
    }
    
    // Validar CIDR
    if (strpos($ip, '/') !== false) {
        list($subnet, $mask) = explode('/', $ip, 2);
        
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $mask = (int)$mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }
        
        return true;
    }
    
    return false;
}
?>