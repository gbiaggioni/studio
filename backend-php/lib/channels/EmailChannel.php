<?php
declare(strict_types=1);

class EmailChannel implements Channel {
	public function send(string $to, string $message): array {
		$from = env('SMTP_FROM', 'Secretaría <no-reply@example.com>') ?? 'Secretaría <no-reply@example.com>';
		$subject = 'Aviso de ausencia docente';
		$headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=utf-8';
		$ok = @mail($to, $subject, $message, $headers);
		return ['ok' => (bool)$ok, 'id' => $ok ? uniqid('mail_', true) : null, 'error' => $ok ? null : 'falló mail()'];
	}
}