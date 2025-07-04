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
define('TEMPLATES_FILE', __DIR__ . '/templates.json');
define('FOLDERS_FILE', __DIR__ . '/folders.json');

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

// Funciones de Templates
function loadTemplates() {
    return loadJsonFile(TEMPLATES_FILE);
}

function getTemplateById($id) {
    $templates = loadTemplates();
    foreach ($templates as $template) {
        if ($template['id'] == $id) {
            return $template;
        }
    }
    return null;
}

function getTemplatesByCategory($category = null) {
    $templates = loadTemplates();
    if (!$category) {
        return $templates;
    }
    
    return array_filter($templates, function($template) use ($category) {
        return $template['category'] === $category;
    });
}

function generateUrlFromTemplate($template, $data) {
    $url = $template['url_pattern'];
    
    foreach ($data as $key => $value) {
        $url = str_replace('{' . $key . '}', urlencode($value), $url);
    }
    
    return $url;
}

// Funciones de Carpetas Jerárquicas
function loadFolders() {
    if (!file_exists(FOLDERS_FILE)) {
        $defaultFolders = [
            [
                'id' => 1,
                'name' => 'Raíz',
                'parent_id' => null,
                'path' => '/',
                'color' => '#6c757d',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 'system'
            ]
        ];
        saveJsonFile(FOLDERS_FILE, $defaultFolders);
        return $defaultFolders;
    }
    return loadJsonFile(FOLDERS_FILE);
}

function saveFolders($folders) {
    return saveJsonFile(FOLDERS_FILE, $folders);
}

function createFolder($name, $parentId, $color, $createdBy) {
    $folders = loadFolders();
    
    // Generar nuevo ID
    $maxId = 0;
    foreach ($folders as $folder) {
        if ($folder['id'] > $maxId) {
            $maxId = $folder['id'];
        }
    }
    
    // Generar path
    $path = '/';
    if ($parentId) {
        $parent = getFolderById($parentId);
        if ($parent) {
            $path = $parent['path'] . $name . '/';
        }
    } else {
        $path = '/' . $name . '/';
    }
    
    $newFolder = [
        'id' => $maxId + 1,
        'name' => $name,
        'parent_id' => $parentId,
        'path' => $path,
        'color' => $color,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $createdBy
    ];
    
    $folders[] = $newFolder;
    saveFolders($folders);
    
    return $newFolder;
}

function getFolderById($id) {
    $folders = loadFolders();
    foreach ($folders as $folder) {
        if ($folder['id'] == $id) {
            return $folder;
        }
    }
    return null;
}

function getFolderTree() {
    $folders = loadFolders();
    
    // Organizar en árbol
    $tree = [];
    $folderMap = [];
    
    // Crear mapa de carpetas
    foreach ($folders as $folder) {
        $folderMap[$folder['id']] = $folder;
        $folderMap[$folder['id']]['children'] = [];
    }
    
    // Construir árbol
    foreach ($folders as $folder) {
        if ($folder['parent_id'] === null) {
            $tree[] = &$folderMap[$folder['id']];
        } else {
            if (isset($folderMap[$folder['parent_id']])) {
                $folderMap[$folder['parent_id']]['children'][] = &$folderMap[$folder['id']];
            }
        }
    }
    
    return $tree;
}

// Funciones de Exportar/Importar
function exportQRs($format = 'json', $filters = []) {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $categories = loadCategories();
    
    // Aplicar filtros si existen
    if (!empty($filters['category_id'])) {
        $redirects = filterQrsByCategory($redirects, $filters['category_id']);
    }
    
    if (!empty($filters['search'])) {
        $redirects = searchQrs($redirects, $filters['search']);
    }
    
    // Enriquecer datos con información de categoría
    foreach ($redirects as &$redirect) {
        if (isset($redirect['category_id'])) {
            $category = getCategoryById($redirect['category_id']);
            $redirect['category_name'] = $category ? $category['name'] : 'Sin categoría';
        } else {
            $redirect['category_name'] = 'Sin categoría';
        }
        
        // Agregar analytics básicos
        $analytics = getQrAnalytics($redirect['id']);
        $redirect['total_clicks'] = count($analytics);
        $redirect['last_access'] = !empty($analytics) ? end($analytics)['timestamp'] : null;
    }
    
    switch ($format) {
        case 'csv':
            return exportToCSV($redirects);
        case 'excel':
            return exportToExcel($redirects);
        case 'json':
        default:
            return json_encode([
                'export_date' => date('Y-m-d H:i:s'),
                'total_qrs' => count($redirects),
                'qrs' => $redirects,
                'categories' => $categories
            ], JSON_PRETTY_PRINT);
    }
}

function exportToCSV($redirects) {
    $csv = "ID,URL Destino,URL QR,Categoría,Descripción,Total Clicks,Último Acceso,Creado,Creado Por\n";
    
    foreach ($redirects as $redirect) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $redirect['id'],
            $redirect['destination_url'],
            $redirect['qr_url'],
            $redirect['category_name'],
            $redirect['description'] ?? '',
            $redirect['total_clicks'],
            $redirect['last_access'] ?? '',
            $redirect['created_at'],
            $redirect['created_by']
        );
    }
    
    return $csv;
}

function importQRs($data, $format = 'json') {
    switch ($format) {
        case 'json':
            return importFromJSON($data);
        case 'csv':
            return importFromCSV($data);
        default:
            return ['success' => false, 'message' => 'Formato no soportado'];
    }
}

function importFromJSON($jsonData) {
    try {
        $data = json_decode($jsonData, true);
        
        if (!isset($data['qrs']) || !is_array($data['qrs'])) {
            return ['success' => false, 'message' => 'Formato JSON inválido'];
        }
        
        $redirects = loadJsonFile(REDIRECTS_FILE);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($data['qrs'] as $qrData) {
            // Validar datos mínimos
            if (!isset($qrData['destination_url']) || !isset($qrData['id'])) {
                $errors[] = 'QR sin URL o ID: ' . json_encode($qrData);
                continue;
            }
            
            // Verificar si el ID ya existe
            $exists = false;
            foreach ($redirects as $existing) {
                if ($existing['id'] === $qrData['id']) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $skipped++;
                continue;
            }
            
            // Crear nuevo QR
            $newRedirect = [
                'id' => $qrData['id'],
                'destination_url' => $qrData['destination_url'],
                'qr_url' => BASE_URL . '/redirect.php?id=' . $qrData['id'],
                'category_id' => $qrData['category_id'] ?? null,
                'description' => $qrData['description'] ?? '',
                'created_at' => $qrData['created_at'] ?? date('Y-m-d H:i:s'),
                'created_by' => $qrData['created_by'] ?? 'imported',
                'style' => $qrData['style'] ?? getDefaultQrStyle()
            ];
            
            // Crear carpeta física
            $qrPath = QR_DIR . $newRedirect['id'];
            if (!is_dir($qrPath)) {
                mkdir($qrPath, 0755, true);
            }
            
            $indexContent = "<?php\nheader('Location: ../../redirect.php?id=" . addslashes($newRedirect['id']) . "');\nexit;\n?>";
            file_put_contents($qrPath . '/index.php', $indexContent);
            
            $redirects[] = $newRedirect;
            $imported++;
        }
        
        saveJsonFile(REDIRECTS_FILE, $redirects);
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al procesar JSON: ' . $e->getMessage()];
    }
}

// Funciones de Duplicación
function duplicateQR($originalId, $newId = null, $modifications = []) {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    
    // Buscar QR original
    $original = null;
    foreach ($redirects as $redirect) {
        if ($redirect['id'] === $originalId) {
            $original = $redirect;
            break;
        }
    }
    
    if (!$original) {
        return ['success' => false, 'message' => 'QR original no encontrado'];
    }
    
    // Generar nuevo ID si no se proporciona
    if (!$newId) {
        $newId = $originalId . '-copy';
        $counter = 1;
        while (qrIdExists($newId)) {
            $newId = $originalId . '-copy-' . $counter;
            $counter++;
        }
    } else {
        if (qrIdExists($newId)) {
            return ['success' => false, 'message' => 'El nuevo ID ya existe'];
        }
    }
    
    // Crear copia con modificaciones
    $duplicate = $original;
    $duplicate['id'] = $newId;
    $duplicate['qr_url'] = BASE_URL . '/redirect.php?id=' . $newId;
    $duplicate['created_at'] = date('Y-m-d H:i:s');
    $duplicate['created_by'] = $_SESSION['username'] ?? 'system';
    
    // Eliminar campos de actualización
    unset($duplicate['updated_at'], $duplicate['updated_by']);
    
    // Aplicar modificaciones
    foreach ($modifications as $key => $value) {
        if ($key !== 'id') { // No permitir cambiar ID aquí
            $duplicate[$key] = $value;
        }
    }
    
    // Crear carpeta física
    $qrPath = QR_DIR . $newId;
    if (!is_dir($qrPath)) {
        mkdir($qrPath, 0755, true);
    }
    
    $indexContent = "<?php\nheader('Location: ../../redirect.php?id=" . addslashes($newId) . "');\nexit;\n?>";
    file_put_contents($qrPath . '/index.php', $indexContent);
    
    // Guardar estilo si existe
    if (isset($original['style'])) {
        saveQrStyle($newId, $duplicate['style']);
    }
    
    // Agregar a la lista
    $redirects[] = $duplicate;
    saveJsonFile(REDIRECTS_FILE, $redirects);
    
    return [
        'success' => true,
        'new_id' => $newId,
        'message' => 'QR duplicado exitosamente'
    ];
}

function qrIdExists($id) {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    foreach ($redirects as $redirect) {
        if ($redirect['id'] === $id) {
            return true;
        }
    }
    return false;
}

// Funciones de Gestión Masiva
function bulkUpdateQRs($qrIds, $updates) {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $updated = 0;
    
    foreach ($redirects as &$redirect) {
        if (in_array($redirect['id'], $qrIds)) {
            foreach ($updates as $key => $value) {
                if ($key !== 'id') { // Proteger ID
                    $redirect[$key] = $value;
                }
            }
            $redirect['updated_at'] = date('Y-m-d H:i:s');
            $redirect['updated_by'] = $_SESSION['username'] ?? 'system';
            $updated++;
        }
    }
    
    if ($updated > 0) {
        saveJsonFile(REDIRECTS_FILE, $redirects);
    }
    
    return $updated;
}

function bulkDeleteQRs($qrIds) {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $deleted = 0;
    
    foreach ($qrIds as $qrId) {
        // Eliminar carpeta física
        $qrPath = QR_DIR . $qrId;
        if (is_dir($qrPath)) {
            if (file_exists($qrPath . '/index.php')) {
                unlink($qrPath . '/index.php');
            }
            rmdir($qrPath);
        }
        $deleted++;
    }
    
    // Filtrar QRs eliminados
    $redirects = array_filter($redirects, function($redirect) use ($qrIds) {
        return !in_array($redirect['id'], $qrIds);
    });
    
    $redirects = array_values($redirects);
    saveJsonFile(REDIRECTS_FILE, $redirects);
    
    return $deleted;
}

function getAdvancedStats() {
    $redirects = loadJsonFile(REDIRECTS_FILE);
    $categories = loadCategories();
    $analytics = loadJsonFile(ANALYTICS_FILE);
    
    // Estadísticas por categoría
    $categoryStats = [];
    foreach ($categories as $category) {
        $categoryStats[$category['id']] = [
            'name' => $category['name'],
            'color' => $category['color'],
            'qr_count' => 0,
            'total_clicks' => 0
        ];
    }
    
    $uncategorizedCount = 0;
    $uncategorizedClicks = 0;
    
    foreach ($redirects as $redirect) {
        $clicks = count(getQrAnalytics($redirect['id']));
        
        if (isset($redirect['category_id']) && isset($categoryStats[$redirect['category_id']])) {
            $categoryStats[$redirect['category_id']]['qr_count']++;
            $categoryStats[$redirect['category_id']]['total_clicks'] += $clicks;
        } else {
            $uncategorizedCount++;
            $uncategorizedClicks += $clicks;
        }
    }
    
    if ($uncategorizedCount > 0) {
        $categoryStats['uncategorized'] = [
            'name' => 'Sin categoría',
            'color' => '#6c757d',
            'qr_count' => $uncategorizedCount,
            'total_clicks' => $uncategorizedClicks
        ];
    }
    
    return [
        'total_qrs' => count($redirects),
        'total_categories' => count($categories),
        'total_clicks' => count($analytics),
        'category_stats' => $categoryStats,
        'creation_trends' => getCreationTrends($redirects),
        'top_performers' => getTopPerformingQRs($redirects, 10)
    ];
}

function getCreationTrends($redirects) {
    $trends = [];
    
    foreach ($redirects as $redirect) {
        $month = date('Y-m', strtotime($redirect['created_at']));
        $trends[$month] = ($trends[$month] ?? 0) + 1;
    }
    
    return $trends;
}

function getTopPerformingQRs($redirects, $limit = 10) {
    $performance = [];
    
    foreach ($redirects as $redirect) {
        $clicks = count(getQrAnalytics($redirect['id']));
        $performance[] = [
            'id' => $redirect['id'],
            'destination_url' => $redirect['destination_url'],
            'clicks' => $clicks,
            'created_at' => $redirect['created_at']
        ];
    }
    
    // Ordenar por clicks
    usort($performance, function($a, $b) {
        return $b['clicks'] - $a['clicks'];
    });
    
    return array_slice($performance, 0, $limit);
}
?>