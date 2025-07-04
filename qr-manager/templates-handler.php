<?php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_templates':
        $category = $_GET['category'] ?? null;
        $templates = getTemplatesByCategory($category);
        echo json_encode(['success' => true, 'templates' => $templates]);
        break;
        
    case 'get_template':
        $templateId = $_GET['id'] ?? '';
        $template = getTemplateById($templateId);
        
        if ($template) {
            echo json_encode(['success' => true, 'template' => $template]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Template no encontrado']);
        }
        break;
        
    case 'create_from_template':
        $templateId = $_POST['template_id'] ?? '';
        $templateData = $_POST['template_data'] ?? [];
        $customId = trim($_POST['custom_id'] ?? '');
        $categoryId = $_POST['category_id'] ?? null;
        $description = trim($_POST['description'] ?? '');
        
        $template = getTemplateById($templateId);
        if (!$template) {
            echo json_encode(['success' => false, 'message' => 'Template no encontrado']);
            exit;
        }
        
        // Generar URL desde template
        $destinationUrl = generateUrlFromTemplate($template, $templateData);
        
        // Validar URL generada
        if (!filter_var($destinationUrl, FILTER_VALIDATE_URL) && !str_starts_with($destinationUrl, 'WIFI:')) {
            echo json_encode(['success' => false, 'message' => 'URL generada no es válida: ' . $destinationUrl]);
            exit;
        }
        
        // Generar ID único
        $qrId = $customId ? sanitizeId($customId) : generateRandomId();
        
        // Verificar que el ID no exista
        if (qrIdExists($qrId)) {
            echo json_encode(['success' => false, 'message' => 'El ID especificado ya existe']);
            exit;
        }
        
        // Usar estilo del template
        $qrStyle = $template['style'];
        
        // Crear carpeta física
        $qrPath = QR_DIR . $qrId;
        if (!is_dir($qrPath)) {
            mkdir($qrPath, 0755, true);
        }
        
        $indexContent = "<?php\nheader('Location: ../../redirect.php?id=" . addslashes($qrId) . "');\nexit;\n?>";
        file_put_contents($qrPath . '/index.php', $indexContent);
        
        // Guardar estilo personalizado
        saveQrStyle($qrId, $qrStyle);
        
        // Crear nuevo QR
        $newRedirect = [
            'id' => $qrId,
            'destination_url' => $destinationUrl,
            'qr_url' => BASE_URL . '/redirect.php?id=' . $qrId,
            'category_id' => $categoryId,
            'description' => $description ?: $template['description'],
            'template_id' => $templateId,
            'template_data' => $templateData,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'],
            'style' => $qrStyle
        ];
        
        // Guardar en redirects.json
        $redirects = loadJsonFile(REDIRECTS_FILE);
        $redirects[] = $newRedirect;
        saveJsonFile(REDIRECTS_FILE, $redirects);
        
        echo json_encode([
            'success' => true, 
            'message' => 'QR creado desde template exitosamente',
            'qr_id' => $qrId,
            'destination_url' => $destinationUrl
        ]);
        break;
        
    case 'duplicate_qr':
        $originalId = $_POST['original_id'] ?? '';
        $newId = trim($_POST['new_id'] ?? '');
        $modifications = $_POST['modifications'] ?? [];
        
        $result = duplicateQR($originalId, $newId, $modifications);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>