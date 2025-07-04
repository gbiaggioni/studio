<?php
require_once 'config.php';
requireLogin();

$format = $_GET['format'] ?? 'csv';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$qrId = $_GET['qr_id'] ?? null;

// Obtener datos de analytics filtrados
$analytics = getQrAnalytics($qrId, $dateFrom, $dateTo);

// Preparar datos para exportación
$exportData = [];
foreach ($analytics as $access) {
    $exportData[] = [
        'QR ID' => $access['qr_id'],
        'Destino' => $access['destination_url'],
        'Fecha y Hora' => $access['timestamp'],
        'IP' => $access['ip_address'],
        'País' => $access['location_info']['country'] ?? 'Unknown',
        'Ciudad' => $access['location_info']['city'] ?? 'Unknown',
        'Tipo Dispositivo' => $access['device_info']['type'] ?? 'unknown',
        'Navegador' => $access['device_info']['browser'] ?? 'unknown',
        'SO' => $access['device_info']['os'] ?? 'unknown',
        'Referrer' => $access['referrer'] ?: 'Directo'
    ];
}

switch ($format) {
    case 'csv':
        exportToCSV($exportData, $dateFrom, $dateTo);
        break;
    case 'excel':
        exportToExcel($exportData, $dateFrom, $dateTo);
        break;
    case 'pdf':
        exportToPDF($exportData, $dateFrom, $dateTo);
        break;
    default:
        exportToCSV($exportData, $dateFrom, $dateTo);
}

function exportToCSV($data, $dateFrom, $dateTo) {
    $filename = "qr_analytics_{$dateFrom}_to_{$dateTo}.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM para Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No hay datos para el período seleccionado']);
    }
    
    fclose($output);
}

function exportToExcel($data, $dateFrom, $dateTo) {
    // Para Excel, usaremos CSV con headers específicos que Excel reconoce mejor
    $filename = "qr_analytics_{$dateFrom}_to_{$dateTo}.xlsx";
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Crear contenido Excel simplificado (realmente CSV con extensión xlsx)
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Título del reporte
    fputcsv($output, ["Reporte de Analytics QR Manager"]);
    fputcsv($output, ["Período: {$dateFrom} a {$dateTo}"]);
    fputcsv($output, ["Generado: " . date('Y-m-d H:i:s')]);
    fputcsv($output, [""]);
    
    // Headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Resumen
        fputcsv($output, [""]);
        fputcsv($output, ["RESUMEN"]);
        fputcsv($output, ["Total de accesos:", count($data)]);
        
        // QRs únicos
        $uniqueQrs = array_unique(array_column($data, 'QR ID'));
        fputcsv($output, ["QRs únicos:", count($uniqueQrs)]);
        
        // Dispositivos
        $devices = array_count_values(array_column($data, 'Tipo Dispositivo'));
        foreach ($devices as $device => $count) {
            fputcsv($output, ["Dispositivo {$device}:", $count]);
        }
        
    } else {
        fputcsv($output, ['No hay datos para el período seleccionado']);
    }
    
    fclose($output);
}

function exportToPDF($data, $dateFrom, $dateTo) {
    $filename = "qr_analytics_{$dateFrom}_to_{$dateTo}.pdf";
    
    // Generar HTML para convertir a PDF
    $html = generatePDFHTML($data, $dateFrom, $dateTo);
    
    // Headers para PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Nota: Para una implementación completa de PDF, necesitarías una librería como TCPDF o mPDF
    // Por simplicidad, enviamos HTML que el browser puede imprimir a PDF
    echo $html;
}

function generatePDFHTML($data, $dateFrom, $dateTo) {
    $totalAccesses = count($data);
    $uniqueQrs = !empty($data) ? count(array_unique(array_column($data, 'QR ID'))) : 0;
    
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Analytics QR Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; text-align: center; }
        .summary-item { background: white; padding: 10px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Reporte Analytics QR Manager</h1>
        <p><strong>Período:</strong> ' . $dateFrom . ' a ' . $dateTo . '</p>
        <p><strong>Generado:</strong> ' . date('Y-m-d H:i:s') . '</p>
    </div>
    
    <div class="summary">
        <h3>📈 Resumen Ejecutivo</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>' . number_format($totalAccesses) . '</h4>
                <p>Total Accesos</p>
            </div>
            <div class="summary-item">
                <h4>' . number_format($uniqueQrs) . '</h4>
                <p>QRs Únicos</p>
            </div>
            <div class="summary-item">
                <h4>' . ($totalAccesses > 0 ? number_format($totalAccesses / max(1, $uniqueQrs), 1) : '0') . '</h4>
                <p>Promedio por QR</p>
            </div>
            <div class="summary-item">
                <h4>' . ($totalAccesses > 0 ? number_format($totalAccesses / max(1, (strtotime($dateTo) - strtotime($dateFrom)) / 86400), 1) : '0') . '</h4>
                <p>Promedio Diario</p>
            </div>
        </div>
    </div>';
    
    if (!empty($data)) {
        // Análisis de dispositivos
        $devices = array_count_values(array_column($data, 'Tipo Dispositivo'));
        $html .= '<h3>📱 Distribución por Dispositivos</h3>';
        foreach ($devices as $device => $count) {
            $percentage = round(($count / $totalAccesses) * 100, 1);
            $html .= "<p><strong>{$device}:</strong> {$count} accesos ({$percentage}%)</p>";
        }
        
        // Top países
        $countries = array_count_values(array_column($data, 'País'));
        arsort($countries);
        $topCountries = array_slice($countries, 0, 5, true);
        
        $html .= '<h3>🌍 Top 5 Países</h3>';
        $position = 1;
        foreach ($topCountries as $country => $count) {
            $percentage = round(($count / $totalAccesses) * 100, 1);
            $html .= "<p><strong>#{$position} {$country}:</strong> {$count} accesos ({$percentage}%)</p>";
            $position++;
        }
        
        // Tabla detallada
        $html .= '<div class="page-break"></div>';
        $html .= '<h3>📋 Detalle de Accesos</h3>';
        $html .= '<table>';
        
        // Headers
        $html .= '<tr>';
        foreach (array_keys($data[0]) as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';
        
        // Limitar a 500 registros para PDF
        $limitedData = array_slice($data, 0, 500);
        
        foreach ($limitedData as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        if (count($data) > 500) {
            $html .= '<p><em>Nota: Se muestran los primeros 500 registros de ' . count($data) . ' total.</em></p>';
        }
        
    } else {
        $html .= '<p>No hay datos para el período seleccionado.</p>';
    }
    
    $html .= '</body></html>';
    
    return $html;
}
?>