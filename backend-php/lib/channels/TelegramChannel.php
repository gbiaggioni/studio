<?php
declare(strict_types=1);

class TelegramChannel implements Channel {
	public function send(string $to, string $message): array {
		$token = env('TELEGRAM_BOT_TOKEN', '') ?? '';
		if ($token === '') {
			return ['ok' => false, 'id' => null, 'error' => 'TELEGRAM_BOT_TOKEN no configurado'];
		}
		$chatId = ltrim($to, '@');
		$url = 'https://api.telegram.org/bot' . urlencode($token) . '/sendMessage';
		$payload = http_build_query(['chat_id' => $chatId, 'text' => $message]);
		$resp = @file_get_contents($url, false, stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
				'content' => $payload,
				'timeout' => 10,
			]
		]));
		if ($resp === false) {
			return ['ok' => false, 'id' => null, 'error' => 'Error enviando a Telegram'];
		}
		$data = json_decode($resp, true);
		return ['ok' => ($data['ok'] ?? false) === true, 'id' => (string)($data['result']['message_id'] ?? ''), 'error' => ($data['ok'] ?? false) ? null : 'Telegram API error'];
	}
}