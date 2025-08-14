<?php
declare(strict_types=1);

interface Channel {
	public function send(string $to, string $message): array;
}

class Notifier {
	private Channel $email;
	private Channel $telegram;
	private Channel $whatsapp;
	private Channel $sms;
	private bool $simulate;
	private string $logFile;

	public function __construct() {
		$this->email = new EmailChannel();
		$this->telegram = new TelegramChannel();
		$this->whatsapp = new WhatsAppChannel();
		$this->sms = new SmsChannel();
		$this->simulate = (env('DELIVER_NOTIFICATIONS', 'false') ?? 'false') !== 'true';
		$baseDir = dirname(__DIR__);
		$this->logFile = $baseDir . '/logs/notifications.jsonl';
		if (!is_dir(dirname($this->logFile))) {
			@mkdir(dirname($this->logFile), 0777, true);
		}
	}

	public function send(string $channel, string $to, string $message, array $meta = []): array {
		$channel = strtolower($channel);
		$ch = $this->selectChannel($channel);
		$result = ['ok' => true, 'id' => null, 'error' => null, 'simulated' => $this->simulate];
		if ($this->simulate) {
			$result['id'] = 'sim-' . bin2hex(random_bytes(6));
		} else {
			$result = $ch->send($to, $message);
		}
		$this->logAttempt($channel, $to, $message, $result, $meta);
		return $result;
	}

	public function chooseChannelForRecipient(array $recipient): array {
		$prefer = strtolower(trim($recipient['preferencia_canal'] ?? ''));
		if ($prefer === 'whatsapp' && !empty($recipient['whatsapp'])) { return ['channel' => 'whatsapp', 'to' => $recipient['whatsapp']]; }
		if ($prefer === 'telegram' && !empty($recipient['telegram_user'])) { return ['channel' => 'telegram', 'to' => $recipient['telegram_user']]; }
		if ($prefer === 'sms' && !empty($recipient['telefono'])) { return ['channel' => 'sms', 'to' => $recipient['telefono']]; }
		if ($prefer === 'email' && !empty($recipient['email'])) { return ['channel' => 'email', 'to' => $recipient['email']]; }
		if (!empty($recipient['whatsapp'])) { return ['channel' => 'whatsapp', 'to' => $recipient['whatsapp']]; }
		if (!empty($recipient['telegram_user'])) { return ['channel' => 'telegram', 'to' => $recipient['telegram_user']]; }
		if (!empty($recipient['telefono'])) { return ['channel' => 'sms', 'to' => $recipient['telefono']]; }
		if (!empty($recipient['email'])) { return ['channel' => 'email', 'to' => $recipient['email']]; }
		return ['channel' => 'email', 'to' => $recipient['email'] ?? ''];
	}

	private function selectChannel(string $channel): Channel {
		switch ($channel) {
			case 'email': return $this->email;
			case 'telegram': return $this->telegram;
			case 'whatsapp': return $this->whatsapp;
			case 'sms': return $this->sms;
			default: return $this->email;
		}
	}

	private function logAttempt(string $channel, string $to, string $message, array $result, array $meta = []): void {
		$record = [
			'timestamp' => (new \DateTimeImmutable('now', Dates::tz()))->format(DATE_ATOM),
			'channel' => $channel,
			'to' => $to,
			'message_preview' => mb_substr($message, 0, 200),
			'result' => $result,
			'meta' => $meta,
		];
		@file_put_contents($this->logFile, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
	}
}