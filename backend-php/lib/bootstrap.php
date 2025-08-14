<?php
declare(strict_types=1);

$__BASE_DIR = dirname(__DIR__);

init_env($__BASE_DIR);
set_default_timezone();
apply_cors_headers();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
	send_cors_preflight();
	exit;
}

function init_env(string $baseDir): void {
	$envFile = $baseDir . '/.env';
	$exampleFile = $baseDir . '/.env.example';
	$source = file_exists($envFile) ? $envFile : (file_exists($exampleFile) ? $exampleFile : null);
	if ($source) {
		$lines = @file($source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		foreach ($lines as $line) {
			if (str_starts_with(trim($line), '#')) { continue; }
			[$k, $v] = array_pad(explode('=', $line, 2), 2, '');
			$k = trim($k);
			$v = trim($v);
			$v = trim($v, "\"' ");
			if ($k !== '') {
				$_ENV[$k] = $v;
				$_SERVER[$k] = $v;
			}
		}
	}
}

function env(string $key, ?string $default = null): ?string {
	return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function set_default_timezone(): void {
	$tz = env('TZ', 'America/Argentina/Buenos_Aires') ?? 'America/Argentina/Buenos_Aires';
	@date_default_timezone_set($tz);
}

function apply_cors_headers(): void {
	$allowed = env('ALLOWED_ORIGINS', '*') ?? '*';
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
	$allowOrigin = '*';
	if ($allowed !== '*') {
		$whitelist = array_map('trim', explode(',', $allowed));
		if (in_array($origin, $whitelist, true)) {
			$allowOrigin = $origin;
		} else {
			$allowOrigin = $whitelist[0] ?? '*';
		}
	}
	header('Access-Control-Allow-Origin: ' . $allowOrigin);
	header('Vary: Origin');
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
}

function send_cors_preflight(): void {
	apply_cors_headers();
	http_response_code(204);
}

function respond_json($data, int $status = 200): void {
	apply_cors_headers();
	header('Content-Type: application/json; charset=utf-8');
	http_response_code($status);
	echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function read_json_input(): array {
	$raw = file_get_contents('php://input');
	if ($raw === false || $raw === '') { return []; }
	$decoded = json_decode($raw, true);
	if (!is_array($decoded)) {
		respond_json(['error' => 'Body JSON inválido'], 400);
		exit;
	}
	return $decoded;
}

function get_bearer_token(): ?string {
	$h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
	if (!$h) { return null; }
	if (stripos($h, 'Bearer ') === 0) {
		return trim(substr($h, 7));
	}
	return null;
}

function require_auth(): void {
	$token = get_bearer_token();
	$expected = env('API_TOKEN', '') ?? '';
	if ($expected === '') {
		respond_json(['error' => 'API mal configurada: falta API_TOKEN'], 500);
		exit;
	}
	if (!$token || !hash_equals($expected, $token)) {
		respond_json(['error' => 'No autorizado'], 401);
		exit;
	}
}

function rate_limit(): void {
	$windowSeconds = (int)(env('RATE_LIMIT_WINDOW_SECONDS', '60') ?? '60');
	$maxRequests = (int)(env('RATE_LIMIT_MAX_REQUESTS', '120') ?? '120');
	$key = get_bearer_token() ?: ($_SERVER['REMOTE_ADDR'] ?? 'anon');
	$now = time();
	$baseDir = dirname(__DIR__);
	$storeFile = $baseDir . '/logs/rate_limit.json';
	if (!is_dir(dirname($storeFile))) {
		@mkdir(dirname($storeFile), 0777, true);
	}
	$bucket = ['start' => $now, 'count' => 0];
	if (file_exists($storeFile)) {
		$raw = @file_get_contents($storeFile);
		$all = json_decode($raw ?: '[]', true);
		if (isset($all[$key])) { $bucket = $all[$key]; }
		if (!isset($bucket['start']) || !isset($bucket['count'])) { $bucket = ['start' => $now, 'count' => 0]; }
		if (($now - (int)$bucket['start']) >= $windowSeconds) {
			$bucket = ['start' => $now, 'count' => 0];
		}
		$bucket['count']++;
		$all[$key] = $bucket;
		@file_put_contents($storeFile, json_encode($all), LOCK_EX);
	} else {
		$all = [];
		$bucket['count'] = 1;
		$all[$key] = $bucket;
		@file_put_contents($storeFile, json_encode($all), LOCK_EX);
	}
	if ($bucket['count'] > $maxRequests) {
		$retry = $windowSeconds - ($now - (int)$bucket['start']);
		header('Retry-After: ' . max(1, $retry));
		respond_json(['error' => 'Rate limit excedido'], 429);
		exit;
	}
}

function enforce_security(): void {
	apply_cors_headers();
	if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
		send_cors_preflight();
		exit;
	}
	require_auth();
	rate_limit();
}

function log_action(string $type, array $data = []): void {
	$baseDir = dirname(__DIR__);
	$file = $baseDir . '/logs/actions.jsonl';
	if (!is_dir(dirname($file))) { @mkdir(dirname($file), 0777, true); }
	$record = [
		'timestamp' => (new DateTimeImmutable('now', Dates::tz()))->format(DATE_ATOM),
		'type' => $type,
		'data' => $data,
		'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
	];
	@file_put_contents($file, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}