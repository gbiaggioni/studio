<?php
declare(strict_types=1);

class WhatsAppChannel implements Channel {
	public function send(string $to, string $message): array {
		$sid = env('WHATSAPP_ACCOUNT_SID', '') ?? '';
		$token = env('WHATSAPP_AUTH_TOKEN', '') ?? '';
		$from = env('WHATSAPP_FROM', '') ?? '';
		if ($sid === '' || $token === '' || $from === '') {
			return ['ok' => false, 'id' => null, 'error' => 'Credenciales WhatsApp no configuradas'];
		}
		$url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';
		$payload = http_build_query([
			'From' => $from,
			'To' => 'whatsapp:' . $to,
			'Body' => $message,
		]);
		$auth = base64_encode($sid . ':' . $token);
		$resp = @file_get_contents($url, false, stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Authorization: Basic $auth\r\nContent-Type: application/x-www-form-urlencoded\r\n",
				'content' => $payload,
				'timeout' => 15,
			]
		]));
		if ($resp === false) {
			return ['ok' => false, 'id' => null, 'error' => 'Error enviando WhatsApp'];
		}
		$data = json_decode($resp, true);
		$ok = isset($data['sid']);
		return ['ok' => $ok, 'id' => $ok ? (string)$data['sid'] : null, 'error' => $ok ? null : 'Twilio error'];
	}
}