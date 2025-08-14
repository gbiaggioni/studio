<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/JsonStore.php';
require_once $baseDir . '/lib/Validator.php';

$store = new JsonStore($baseDir . '/data/docentes.json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
	$docentes = $store->readAll();
	respond_json($docentes);
}

$input = read_json_input();

if ($method === 'POST') {
	$errors = Validator::validateDocente($input);
	if ($errors) {
		respond_json(['error' => 'Validación fallida', 'detalles' => $errors], 422);
	}
	$docentes = $store->readAll();
	$docentes[] = $input;
	$store->writeAll($docentes);
	log_action('docente.create', ['docente' => $input['id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'PUT') {
	if (!isset($input['id'])) {
		respond_json(['error' => 'Falta id'], 400);
	}
	$docentes = $store->readAll();
	$updated = false;
	foreach ($docentes as $i => $d) {
		if ($d['id'] === $input['id']) {
			$docentes[$i] = array_merge($d, $input);
			$updated = true;
			break;
		}
	}
	if (!$updated) {
		respond_json(['error' => 'No encontrado'], 404);
	}
	$store->writeAll($docentes);
	log_action('docente.update', ['docente' => $input['id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'DELETE') {
	$id = $_GET['id'] ?? null;
	if (!$id) {
		respond_json(['error' => 'Falta id'], 400);
	}
	$docentes = $store->readAll();
	$new = [];
	$deleted = false;
	foreach ($docentes as $d) {
		if ($d['id'] === $id) {
			$deleted = true;
			continue;
		}
		$new[] = $d;
	}
	if (!$deleted) {
		respond_json(['error' => 'No encontrado'], 404);
	}
	$store->writeAll($new);
	log_action('docente.delete', ['docente' => $id]);
	respond_json(['ok' => true]);
}

respond_json(['error' => 'Método no permitido'], 405);