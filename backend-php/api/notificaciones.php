<?php
declare(strict_types=1);

$baseDir = dirname(__DIR__);
require_once $baseDir . '/lib/bootstrap.php';
require_once $baseDir . '/lib/Notifier.php';
require_once $baseDir . '/lib/Dates.php';
require_once $baseDir . '/lib/channels/EmailChannel.php';
require_once $baseDir . '/lib/channels/TelegramChannel.php';
require_once $baseDir . '/lib/channels/WhatsAppChannel.php';
require_once $baseDir . '/lib/channels/SmsChannel.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
	respond_json(['status' => 'OK', 'detalle' => 'Use POST para enviar mensaje de prueba']);
}

if ($method !== 'POST') { respond_json(['error' => 'Método no permitido'], 405); }

$input = read_json_input();
$channel = strtolower($input['canal'] ?? 'email');
$to = $input['to'] ?? '';
$message = $input['mensaje'] ?? 'Mensaje de prueba de Secretaría';
if ($to === '') { respond_json(['error' => 'Falta to'], 400); }

$notifier = new Notifier();
$res = $notifier->send($channel, $to, $message, ['tipo' => 'prueba']);
respond_json(['resultado' => $res]);