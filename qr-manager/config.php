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
define('CATEGORIES_FILE', __DIR__ . '/categories.json');

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

// Funciones de Categorías
function loadCategories() {
    return loadJsonFile(CATEGORIES_FILE);
}

function saveCategories($categories) {
    return saveJsonFile(CATEGORIES_FILE, $categories);
}

function getCategoryById($id) {
    $categories = loadCategories();
    foreach ($categories as $category) {
        if ($category['id'] == $id) {
            return $category;
        }
    }
    return null;
}

function createCategory($name, $description, $color, $icon, $createdBy) {
    $categories = loadCategories();
    
    // Generar nuevo ID
    $maxId = 0;
    foreach ($categories as $category) {
        if ($category['id'] > $maxId) {
            $maxId = $category['id'];
        }
    }
    
    $newCategory = [
        'id' => $maxId + 1,
        'name' => $name,
        'description' => $description,
        'color' => $color,
        'icon' => $icon,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $createdBy
    ];
    
    $categories[] = $newCategory;
    saveCategories($categories);
    
    return $newCategory;
}

// Funciones de Personalización Visual
function getDefaultQrStyle() {
    return [
        'size' => 300,
        'error_correction' => 'M',
        'foreground_color' => '#000000',
        'background_color' => '#FFFFFF',
        'logo_file' => '',
        'logo_size' => 60,
        'frame_style' => 'none',
        'frame_color' => '#000000',
        'corner_style' => 'square',
        'data_style' => 'square'
    ];
}

function generateCustomQR($data, $style = []) {
    // Combinar estilo por defecto con el personalizado
    $defaultStyle = getDefaultQrStyle();
    $qrStyle = array_merge($defaultStyle, $style);
    
    // Construir URL para QR Server API con parámetros personalizados
    $baseUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    
    $params = [
        'data' => $data,
        'size' => $qrStyle['size'] . 'x' . $qrStyle['size'],
        'ecc' => $qrStyle['error_correction'],
        'color' => str_replace('#', '', $qrStyle['foreground_color']),
        'bgcolor' => str_replace('#', '', $qrStyle['background_color'])
    ];
    
    // Agregar formato si se especifica
    if (isset($qrStyle['format'])) {
        $params['format'] = $qrStyle['format'];
    }
    
    $qrUrl = $baseUrl . '?' . http_build_query($params);
    
    return $qrUrl;
}

function generateQRWithLogo($data, $style = [], $logoPath = '') {
    // Esta función maneja la superposición de logo usando canvas del lado cliente
    // Retorna la URL base del QR sin logo para procesar en frontend
    return generateCustomQR($data, $style);
}

function getQrFrameStyles() {
    return [
        'none' => 'Sin marco',
        'solid' => 'Marco sólido',
        'rounded' => 'Marco redondeado',
        'gradient' => 'Marco degradado',
        'shadow' => 'Marco con sombra'
    ];
}

function getQrCornerStyles() {
    return [
        'square' => 'Esquinas cuadradas',
        'rounded' => 'Esquinas redondeadas',
        'circle' => 'Esquinas circulares',
        'leaf' => 'Estilo hoja'
    ];
}

function getQrDataStyles() {
    return [
        'square' => 'Puntos cuadrados',
        'circle' => 'Puntos circulares',
        'rounded' => 'Puntos redondeados',
        'diamond' => 'Puntos diamante'
    ];
}

function saveQrStyle($qrId, $style) {
    $stylesFile = __DIR__ . '/qr_styles.json';
    $styles = file_exists($stylesFile) ? json_decode(file_get_contents($stylesFile), true) : [];
    
    $styles[$qrId] = $style;
    
    return file_put_contents($stylesFile, json_encode($styles, JSON_PRETTY_PRINT));
}

function loadQrStyle($qrId) {
    $stylesFile = __DIR__ . '/qr_styles.json';
    if (!file_exists($stylesFile)) {
        return getDefaultQrStyle();
    }
    
    $styles = json_decode(file_get_contents($stylesFile), true);
    return $styles[$qrId] ?? getDefaultQrStyle();
}

// Funciones de Filtrado y Búsqueda
function filterQrsByCategory($redirects, $categoryId = null) {
    if (!$categoryId) {
        return $redirects;
    }
    
    return array_filter($redirects, function($redirect) use ($categoryId) {
        return isset($redirect['category_id']) && $redirect['category_id'] == $categoryId;
    });
}

function searchQrs($redirects, $searchTerm = '') {
    if (empty($searchTerm)) {
        return $redirects;
    }
    
    $searchTerm = strtolower($searchTerm);
    
    return array_filter($redirects, function($redirect) use ($searchTerm) {
        return strpos(strtolower($redirect['id']), $searchTerm) !== false ||
               strpos(strtolower($redirect['destination_url']), $searchTerm) !== false;
    });
}
?>