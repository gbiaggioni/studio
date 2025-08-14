<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/JsonStore.php';
require_once $baseDir . '/lib/Validator.php';

$store = new JsonStore($baseDir . '/data/horarios.json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
	$docenteId = $_GET['docente_id'] ?? null;
	$all = $store->readAll();
	if ($docenteId) {
		$all = array_values(array_filter($all, fn($h) => ($h['docente_id'] ?? '') === $docenteId));
	}
	respond_json($all);
}

$input = read_json_input();

if ($method === 'POST') {
	$errors = Validator::validateHorario($input);
	if ($errors) { respond_json(['error' => 'Validación fallida', 'detalles' => $errors], 422); }
	$items = $store->readAll();
	$items[] = $input;
	$store->writeAll($items);
	log_action('horario.create', ['curso' => $input['curso_id'] ?? null, 'docente' => $input['docente_id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'PUT') {
	// For simplicity, identify by index passed via query ?index=
	$index = isset($_GET['index']) ? (int)$_GET['index'] : null;
	if ($index === null) { respond_json(['error' => 'Falta index'], 400); }
	$errors = Validator::validateHorario($input);
	if ($errors) { respond_json(['error' => 'Validación fallida', 'detalles' => $errors], 422); }
	$items = $store->readAll();
	if (!isset($items[$index])) { respond_json(['error' => 'No encontrado'], 404); }
	$items[$index] = array_merge($items[$index], $input);
	$store->writeAll($items);
	log_action('horario.update', ['index' => $index, 'curso' => $input['curso_id'] ?? null, 'docente' => $input['docente_id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'DELETE') {
	$index = isset($_GET['index']) ? (int)$_GET['index'] : null;
	if ($index === null) { respond_json(['error' => 'Falta index'], 400); }
	$items = $store->readAll();
	if (!isset($items[$index])) { respond_json(['error' => 'No encontrado'], 404); }
	array_splice($items, $index, 1);
	$store->writeAll($items);
	log_action('horario.delete', ['index' => $index]);
	respond_json(['ok' => true]);
}

respond_json(['error' => 'Método no permitido'], 405);