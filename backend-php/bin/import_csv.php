#!/usr/bin/env php
<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/JsonStore.php';

if ($argc < 2) {
	fwrite(STDERR, "Uso: import_csv.php archivo.csv\n");
	exit(1);
}
$file = $argv[1];
if (!file_exists($file)) { fwrite(STDERR, "No existe $file\n"); exit(1); }

$fp = fopen($file, 'r');
$headers = fgetcsv($fp);
$out = [];
while (($row = fgetcsv($fp)) !== false) {
	$rec = array_combine($headers, $row);
	$out[] = [
		'curso_id' => $rec['curso_id'] ?? '',
		'materia' => $rec['materia'] ?? '',
		'docente_id' => $rec['docente_id'] ?? '',
		'dia_semana' => (int)($rec['dia_semana'] ?? 0),
		'hora_inicio' => $rec['hora_inicio'] ?? '',
		'hora_fin' => $rec['hora_fin'] ?? '',
		'aula' => $rec['aula'] ?? '',
	];
}
fclose($fp);

$store = new JsonStore($baseDir . '/data/horarios.json');
$store->writeAll($out);

fwrite(STDOUT, "Importados ".count($out)." registros a data/horarios.json\n");