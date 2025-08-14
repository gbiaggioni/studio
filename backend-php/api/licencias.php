<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/JsonStore.php';
require_once $baseDir . '/lib/Validator.php';
require_once $baseDir . '/lib/Dates.php';
require_once $baseDir . '/lib/Notifier.php';
require_once $baseDir . '/lib/TemplateRenderer.php';
require_once $baseDir . '/lib/channels/EmailChannel.php';
require_once $baseDir . '/lib/channels/TelegramChannel.php';
require_once $baseDir . '/lib/channels/WhatsAppChannel.php';
require_once $baseDir . '/lib/channels/SmsChannel.php';

$method = $_SERVER['REQUEST_METHOD'];
$store = new JsonStore($baseDir . '/data/licencias.json');

if ($method === 'GET') {
	$docente = $_GET['docente_id'] ?? null;
	$all = $store->readAll();
	if ($docente) { $all = array_values(array_filter($all, fn($l) => ($l['docente_id'] ?? '') === $docente)); }
	respond_json($all);
}

if ($method !== 'POST' && $method !== 'PUT') {
	respond_json(['error' => 'Método no permitido'], 405);
}

$input = read_json_input();
$input = Validator::sanitizeRecursive($input);
$errors = Validator::validateLicencia($input);
if ($errors) { respond_json(['error' => 'Validación fallida', 'detalles' => $errors], 422); }

$docentes = (new JsonStore($baseDir . '/data/docentes.json'))->readAll();
$cursos = (new JsonStore($baseDir . '/data/cursos.json'))->readAll();
$horarios = (new JsonStore($baseDir . '/data/horarios.json'))->readAll();
$feriados = (new JsonStore($baseDir . '/data/feriados.json'))->readAll();

$clases = Dates::clasesAfectadas($input['docente_id'], $input['desde'], $input['hasta'], $horarios, $feriados);
$grupos = Dates::agruparPorCurso($clases);

if (($input['simulate'] ?? false) === true) {
	respond_json(['impacto' => ['cursos' => array_keys($grupos), 'clases' => $clases]]);
}

// Confirmar y notificar
$licencias = $store->readAll();
$id = $input['id'] ?? null;
if (!$id) { $id = 'LIC-' . str_pad((string)(count($licencias) + 1), 4, '0', STR_PAD_LEFT); }

$docente = null;
foreach ($docentes as $d) { if ($d['id'] === $input['docente_id']) { $docente = $d; break; } }
if (!$docente) { respond_json(['error' => 'Docente no encontrado'], 404); }

$notifier = new Notifier();
$notificaciones = [];

$renderer = new TemplateRenderer();

$prev = null;
if ($id) {
	foreach ($licencias as $l) { if (($l['id'] ?? null) === $id) { $prev = $l; break; } }
}
$rangoChanged = $prev ? (($prev['desde'] ?? '') !== $input['desde'] || ($prev['hasta'] ?? '') !== $input['hasta']) : false;
$prevCursosSet = [];
if ($prev && isset($prev['notificaciones'])) {
	foreach ($prev['notificaciones'] as $n) { if (isset($n['curso_id'])) { $prevCursosSet[$n['curso_id']] = true; } }
}
$newCourseIds = array_keys($grupos);
$toNotify = [];
if ($prev) {
	if ($rangoChanged) {
		$toNotify = $newCourseIds;
	} else {
		foreach ($newCourseIds as $cid) { if (!isset($prevCursosSet[$cid])) { $toNotify[] = $cid; } }
	}
} else {
	$toNotify = $newCourseIds;
}

foreach ($grupos as $cursoId => $clasesCurso) {
	if (!in_array($cursoId, $toNotify, true)) { continue; }
	$curso = null;
	foreach ($cursos as $c) { if ($c['id'] === $cursoId) { $curso = $c; break; } }
	if (!$curso) { continue; }
	$preceptores = $curso['preceptores'] ?? [];

	// Build vars for template
	$nombreDoc = trim(($docente['apellido'] ?? '') . ' ' . ($docente['nombre'] ?? ''));
	$clasesVars = array_map(function($c) use ($curso) {
		return [
			'curso' => $curso['id'],
			'materia' => $c['materia'],
			'fecha' => $c['fecha'],
			'hora_inicio' => $c['hora_inicio'],
			'hora_fin' => $c['hora_fin'],
			'aula' => $c['aula'] ?? '-',
		];
	}, $clasesCurso);
	$vars = ['docente' => $nombreDoc, 'desde' => $input['desde'], 'hasta' => $input['hasta'], 'clases' => $clasesVars];
	$subject = $renderer->render('subject', $vars);
	$body = $renderer->render('body', $vars);
	$message = $subject . "\n\n" . $body;

	$count = 0;
	foreach ($preceptores as $p) {
		if ($count >= 2) { break; }
		$choice = $notifier->chooseChannelForRecipient($p);
		$res = $notifier->send($choice['channel'], $choice['to'], $message, [
			'curso_id' => $cursoId,
			'docente_id' => $docente['id'],
			'licencia' => $id,
		]);
		$notificaciones[] = [
			'curso_id' => $cursoId,
			'fecha_envio' => (new DateTimeImmutable('now', Dates::tz()))->format(DATE_ATOM),
			'canal' => $choice['channel'],
			'estado' => $res['ok'] ? 'enviado' : 'fallido',
			'preceptor_id' => $p['id'] ?? null,
		];
		$count++;
	}
}

$registro = [
	'id' => $id,
	'docente_id' => $input['docente_id'],
	'desde' => $input['desde'],
	'hasta' => $input['hasta'],
	'motivo' => $input['motivo'],
	'creado_por' => $_SERVER['REMOTE_ADDR'] ?? 'api',
	'fecha_creacion' => (new DateTimeImmutable('now', Dates::tz()))->format(DATE_ATOM),
	'notificaciones' => $notificaciones,
];

$found = false;
foreach ($licencias as $i => $l) {
	if ($l['id'] === $id) { $licencias[$i] = $registro; $found = true; break; }
}
if (!$found) { $licencias[] = $registro; }
$store->writeAll($licencias);

log_action($found ? 'licencia.update' : 'licencia.create', ['licencia' => $id, 'docente' => $input['docente_id'], 'desde' => $input['desde'], 'hasta' => $input['hasta']]);

respond_json(['ok' => true, 'licencia' => $registro, 'impacto' => ['cursos' => array_keys($grupos), 'clases' => $clases]]);

function build_message(array $docente, array $curso, array $clasesCurso, array $lic): string {
	$nombreDoc = trim(($docente['apellido'] ?? '') . ' ' . ($docente['nombre'] ?? ''));
	$head = 'Aviso de ausencia docente — ' . $nombreDoc . ' (' . $lic['desde'] . ' → ' . $lic['hasta'] . ")\n";
	$body = 'Secretaría informa: el/la docente ' . $nombreDoc . ' estará ausente del ' . $lic['desde'] . ' al ' . $lic['hasta'] . ".\n";
	$body .= "Cursos afectados:\n";
	foreach ($clasesCurso as $c) {
		$body .= '- ' . $curso['id'] . ' — ' . $c['materia'] . ' — ' . $c['fecha'] . ' de ' . $c['hora_inicio'] . ' a ' . $c['hora_fin'] . ' (aula ' . ($c['aula'] ?? '-') . ")\n";
	}
	return $head . "\n" . $body;
}