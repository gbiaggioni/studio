<?php
declare(strict_types=1);

class TemplateRenderer {
	private string $templatesDir;

	public function __construct(?string $templatesDir = null) {
		$baseDir = dirname(__DIR__);
		$this->templatesDir = $templatesDir ?? ($baseDir . '/lib/templates');
	}

	public function render(string $templateName, array $vars): string {
		$path = rtrim($this->templatesDir, '/').'/'.$templateName.'.txt';
		if (!file_exists($path)) { return ''; }
		$content = (string)file_get_contents($path);
		// Loop handling for {{#clases}} ... {{/clases}}
		$content = $this->renderLoop($content, 'clases', $vars['clases'] ?? []);
		// Simple placeholders
		$flat = $this->flattenVars($vars);
		foreach ($flat as $k => $v) {
			$content = str_replace('{{'.$k.'}}', (string)$v, $content);
		}
		// Remove any unreplaced placeholders
		$content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
		return trim($content);
	}

	private function renderLoop(string $content, string $key, array $items): string {
		$pattern = '/\{\{#'.$key.'\}\}([\s\S]*?)\{\{\/'.$key+'\}\}/';
		if (!preg_match($pattern, $content, $m)) { return $content; }
		$template = $m[1];
		$rendered = '';
		foreach ($items as $item) {
			$block = $template;
			foreach ($item as $k => $v) {
				$block = str_replace('{{'.$k.'}}', (string)$v, $block);
			}
			$rendered .= $block;
		}
		return preg_replace($pattern, $rendered, $content);
	}

	private function flattenVars(array $vars): array {
		$out = [];
		foreach ($vars as $k => $v) {
			if (is_scalar($v) || $v === null) { $out[$k] = $v; }
			if (is_array($v)) {
				foreach ($v as $kk => $vv) {
					if (is_scalar($vv)) { $out[$k.'.'.$kk] = $vv; }
				}
			}
		}
		return $out;
	}
}