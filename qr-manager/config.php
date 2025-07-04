<?php
session_start();

// Configuración del dominio
define('BASE_URL', 'http://localhost/qr-manager'); // Cambiar por tu dominio
define('QR_DIR', __DIR__ . '/qr/');
define('QR_URL', BASE_URL . '/qr/');

// Archivos de datos
define('USERS_FILE', __DIR__ . '/users.json');
define('REDIRECTS_FILE', __DIR__ . '/redirects.json');
define('ANALYTICS_FILE', __DIR__ . '/analytics.json');

// Funciones auxiliares
function loadJsonFile($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function saveJsonFile($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function generateRandomId($length = 8) {
    return bin2hex(random_bytes($length / 2));
}

function sanitizeId($id) {
    return preg_replace('/[^a-zA-Z0-9-_]/', '', $id);
}

// Funciones de Analytics
function logQrAccess($qrId, $destinationUrl) {
    $analytics = loadJsonFile(ANALYTICS_FILE);
    
    // Capturar información del acceso
    $accessData = [
        'id' => uniqid(),
        'qr_id' => $qrId,
        'destination_url' => $destinationUrl,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => getUserIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
        'device_info' => getDeviceInfo(),
        'location_info' => getLocationByIP(getUserIP())
    ];
    
    $analytics[] = $accessData;
    saveJsonFile(ANALYTICS_FILE, $analytics);
}

function getUserIP() {
    // Detectar IP real del usuario
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getDeviceInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $device = [
        'type' => 'unknown',
        'browser' => 'unknown',
        'os' => 'unknown'
    ];
    
    // Detectar tipo de dispositivo
    if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
        if (preg_match('/iPad/', $userAgent)) {
            $device['type'] = 'tablet';
        } else {
            $device['type'] = 'mobile';
        }
    } else {
        $device['type'] = 'desktop';
    }
    
    // Detectar browser
    if (preg_match('/Chrome/', $userAgent)) {
        $device['browser'] = 'Chrome';
    } elseif (preg_match('/Firefox/', $userAgent)) {
        $device['browser'] = 'Firefox';
    } elseif (preg_match('/Safari/', $userAgent)) {
        $device['browser'] = 'Safari';
    } elseif (preg_match('/Edge/', $userAgent)) {
        $device['browser'] = 'Edge';
    }
    
    // Detectar OS
    if (preg_match('/Windows/', $userAgent)) {
        $device['os'] = 'Windows';
    } elseif (preg_match('/Mac OS X/', $userAgent)) {
        $device['os'] = 'macOS';
    } elseif (preg_match('/Linux/', $userAgent)) {
        $device['os'] = 'Linux';
    } elseif (preg_match('/Android/', $userAgent)) {
        $device['os'] = 'Android';
    } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
        $device['os'] = 'iOS';
    }
    
    return $device;
}

function getLocationByIP($ip) {
    // Usar API gratuita para geolocalización
    if ($ip === '127.0.0.1' || $ip === '::1' || empty($ip)) {
        return ['country' => 'Local', 'city' => 'Local', 'region' => 'Local'];
    }
    
    try {
        // Usar ipapi.co (gratuita, 1000 requests/día)
        $url = "http://ipapi.co/{$ip}/json/";
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'QR Manager Analytics'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            return [
                'country' => $data['country_name'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'region' => $data['region'] ?? 'Unknown'
            ];
        }
    } catch (Exception $e) {
        // Si falla, devolver valores por defecto
    }
    
    return ['country' => 'Unknown', 'city' => 'Unknown', 'region' => 'Unknown'];
}

function getQrAnalytics($qrId = null, $dateFrom = null, $dateTo = null) {
    $analytics = loadJsonFile(ANALYTICS_FILE);
    
    // Filtrar por QR ID si se especifica
    if ($qrId) {
        $analytics = array_filter($analytics, function($access) use ($qrId) {
            return $access['qr_id'] === $qrId;
        });
    }
    
    // Filtrar por fechas si se especifican
    if ($dateFrom || $dateTo) {
        $analytics = array_filter($analytics, function($access) use ($dateFrom, $dateTo) {
            $accessDate = date('Y-m-d', strtotime($access['timestamp']));
            
            if ($dateFrom && $accessDate < $dateFrom) return false;
            if ($dateTo && $accessDate > $dateTo) return false;
            
            return true;
        });
    }
    
    return $analytics;
}

function getAnalyticsSummary() {
    $analytics = loadJsonFile(ANALYTICS_FILE);
    
    $summary = [
        'total_clicks' => count($analytics),
        'unique_qrs' => count(array_unique(array_column($analytics, 'qr_id'))),
        'today_clicks' => 0,
        'week_clicks' => 0,
        'month_clicks' => 0,
        'top_qrs' => [],
        'device_breakdown' => ['mobile' => 0, 'desktop' => 0, 'tablet' => 0],
        'country_breakdown' => [],
        'recent_activity' => []
    ];
    
    $today = date('Y-m-d');
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    $monthAgo = date('Y-m-d', strtotime('-30 days'));
    
    $qrCounts = [];
    $countries = [];
    
    foreach ($analytics as $access) {
        $accessDate = date('Y-m-d', strtotime($access['timestamp']));
        
        // Contar por fechas
        if ($accessDate === $today) $summary['today_clicks']++;
        if ($accessDate >= $weekAgo) $summary['week_clicks']++;
        if ($accessDate >= $monthAgo) $summary['month_clicks']++;
        
        // Contar por QR
        $qrCounts[$access['qr_id']] = ($qrCounts[$access['qr_id']] ?? 0) + 1;
        
        // Contar por dispositivo
        $deviceType = $access['device_info']['type'] ?? 'unknown';
        if (isset($summary['device_breakdown'][$deviceType])) {
            $summary['device_breakdown'][$deviceType]++;
        }
        
        // Contar por país
        $country = $access['location_info']['country'] ?? 'Unknown';
        $countries[$country] = ($countries[$country] ?? 0) + 1;
    }
    
    // Top 5 QRs más usados
    arsort($qrCounts);
    $summary['top_qrs'] = array_slice($qrCounts, 0, 5, true);
    
    // Top 5 países
    arsort($countries);
    $summary['country_breakdown'] = array_slice($countries, 0, 5, true);
    
    // Actividad reciente (últimos 10)
    $summary['recent_activity'] = array_slice(array_reverse($analytics), 0, 10);
    
    return $summary;
}
?>