<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') { respond_json(['error' => 'Método no permitido'], 405); }

$zipName = 'backup-' . date('Ymd-His') . '.zip';
$tmp = sys_get_temp_dir() . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
	respond_json(['error' => 'No se pudo crear ZIP'], 500);
}

$addDir = function(string $path, string $zipPrefix = '') use ($zip) {
	$path = rtrim($path, '/');
	if (!is_dir($path)) { return; }
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
	foreach ($files as $file) {
		$localName = $zipPrefix . substr($file->getPathname(), strlen(dirname($path)) + 1);
		$zip->addFile($file->getPathname(), $localName);
	}
};

$addDir($baseDir . '/data', 'data/');
$addDir($baseDir . '/logs', 'logs/');
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename=' . $zipName);
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
@unlink($tmp);