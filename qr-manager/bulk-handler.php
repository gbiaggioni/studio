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
    case 'export':
        $format = $_GET['format'] ?? 'json';
        $filters = [
            'category_id' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? ''
        ];
        
        $exportData = exportQRs($format, $filters);
        $filename = 'qr-export-' . date('Y-m-d-H-i-s');
        
        switch ($format) {
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                echo $exportData;
                break;
                
            case 'json':
            default:
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                echo $exportData;
                break;
        }
        exit;
        
    case 'import':
        if (!isset($_FILES['import_file'])) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionó archivo']);
            exit;
        }
        
        $file = $_FILES['import_file'];
        $format = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
            exit;
        }
        
        $fileContent = file_get_contents($file['tmp_name']);
        $result = importQRs($fileContent, $format);
        
        echo json_encode($result);
        break;
        
    case 'bulk_update':
        $qrIds = $_POST['qr_ids'] ?? [];
        $updates = $_POST['updates'] ?? [];
        
        if (empty($qrIds)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionaron QRs']);
            exit;
        }
        
        $updated = bulkUpdateQRs($qrIds, $updates);
        
        echo json_encode([
            'success' => true,
            'message' => "Se actualizaron $updated QRs exitosamente",
            'updated_count' => $updated
        ]);
        break;
        
    case 'bulk_delete':
        $qrIds = $_POST['qr_ids'] ?? [];
        
        if (empty($qrIds)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionaron QRs']);
            exit;
        }
        
        $deleted = bulkDeleteQRs($qrIds);
        
        echo json_encode([
            'success' => true,
            'message' => "Se eliminaron $deleted QRs exitosamente",
            'deleted_count' => $deleted
        ]);
        break;
        
    case 'get_advanced_stats':
        $stats = getAdvancedStats();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>