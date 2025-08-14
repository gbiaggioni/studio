<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/JsonStore.php';
require_once $baseDir . '/lib/Validator.php';

$store = new JsonStore($baseDir . '/data/cursos.json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
	$cursos = $store->readAll();
	respond_json($cursos);
}

$input = read_json_input();

if ($method === 'POST') {
	$errors = Validator::validateCurso($input);
	if ($errors) { respond_json(['error' => 'Validación fallida', 'detalles' => $errors], 422); }
	$cursos = $store->readAll();
	$cursos[] = $input;
	$store->writeAll($cursos);
	log_action('curso.create', ['curso' => $input['id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'PUT') {
	if (!isset($input['id'])) { respond_json(['error' => 'Falta id'], 400); }
	$cursos = $store->readAll();
	$updated = false;
	foreach ($cursos as $i => $c) {
		if ($c['id'] === $input['id']) { $cursos[$i] = array_merge($c, $input); $updated = true; break; }
	}
	if (!$updated) { respond_json(['error' => 'No encontrado'], 404); }
	$store->writeAll($cursos);
	log_action('curso.update', ['curso' => $input['id'] ?? null]);
	respond_json(['ok' => true]);
}

if ($method === 'DELETE') {
	$id = $_GET['id'] ?? null;
	if (!$id) { respond_json(['error' => 'Falta id'], 400); }
	$cursos = $store->readAll();
	$new = [];
	$deleted = false;
	foreach ($cursos as $c) {
		if ($c['id'] === $id) { $deleted = true; continue; }
		$new[] = $c;
	}
	if (!$deleted) { respond_json(['error' => 'No encontrado'], 404); }
	$store->writeAll($new);
	log_action('curso.delete', ['curso' => $id]);
	respond_json(['ok' => true]);
}

respond_json(['error' => 'Método no permitido'], 405);