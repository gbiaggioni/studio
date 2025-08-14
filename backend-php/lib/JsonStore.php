<?php
declare(strict_types=1);

class JsonStore {
	private string $filePath;

	public function __construct(string $filePath) {
		$this->filePath = $filePath;
		$dir = dirname($this->filePath);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		if (!file_exists($this->filePath)) {
			@file_put_contents($this->filePath, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		}
	}

	public function readAll(): array {
		$fp = fopen($this->filePath, 'r');
		if ($fp === false) { return []; }
		flock($fp, LOCK_SH);
		$size = filesize($this->filePath);
		$content = $size > 0 ? fread($fp, $size) : '';
		flock($fp, LOCK_UN);
		fclose($fp);
		$decoded = json_decode($content ?: '[]', true);
		return is_array($decoded) ? $decoded : [];
	}

	public function writeAll(array $data): void {
		$tmpPath = $this->filePath . '.tmp';
		$fp = fopen($tmpPath, 'w');
		if ($fp === false) { throw new RuntimeException('No se puede abrir archivo temporal para escribir'); }
		flock($fp, LOCK_EX);
		fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		@rename($tmpPath, $this->filePath);
	}

	public function append(array $item): void {
		$data = $this->readAll();
		$data[] = $item;
		$this->writeAll($data);
	}
}