<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/api';

if ($method === 'OPTIONS') {
	send_cors_preflight();
	exit;
}

require_auth();
rate_limit();

$path = parse_url($uri, PHP_URL_PATH);

$routes = [
	'GET' => [
		'/api/docentes' => $baseDir . '/api/docentes.php',
		'/api/cursos' => $baseDir . '/api/cursos.php',
		'/api/horarios' => $baseDir . '/api/horarios.php',
		'/api/licencias' => $baseDir . '/api/licencias.php',
		'/api/notificar/prueba' => $baseDir . '/api/notificaciones.php',
		'/api/backup' => $baseDir . '/api/backup.php',
	],
	'POST' => [
		'/api/licencias' => $baseDir . '/api/licencias.php',
		'/api/notificar/prueba' => $baseDir . '/api/notificaciones.php',
		'/api/docentes' => $baseDir . '/api/docentes.php',
		'/api/cursos' => $baseDir . '/api/cursos.php',
		'/api/horarios' => $baseDir . '/api/horarios.php',
	],
	'PUT' => [
		'/api/docentes' => $baseDir . '/api/docentes.php',
		'/api/cursos' => $baseDir . '/api/cursos.php',
		'/api/horarios' => $baseDir . '/api/horarios.php',
		'/api/licencias' => $baseDir . '/api/licencias.php',
	],
	'DELETE' => [
		'/api/docentes' => $baseDir . '/api/docentes.php',
		'/api/cursos' => $baseDir . '/api/cursos.php',
		'/api/horarios' => $baseDir . '/api/horarios.php',
	]
];

if (isset($routes[$method][$path])) {
	require $routes[$method][$path];
	exit;
}

respond_json(['error' => 'Ruta no encontrada', 'method' => $method, 'path' => $path], 404);