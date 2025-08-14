<?php
declare(strict_types=1);

class Validator {
	public static function validateDocente(array $docente): array {
		$errors = [];
		$required = ['id', 'apellido', 'nombre', 'activo'];
		foreach ($required as $k) {
			if (!array_key_exists($k, $docente)) { $errors[] = "Falta $k"; }
		}
		if (isset($docente['email']) && $docente['email'] !== '' && !filter_var($docente['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Email inválido';
		}
		if (isset($docente['materias']) && !is_array($docente['materias'])) {
			$errors[] = 'materias debe ser array de strings';
		}
		return $errors;
	}

	public static function validateCurso(array $curso): array {
		$errors = [];
		$required = ['id', 'turno', 'preceptores'];
		foreach ($required as $k) {
			if (!array_key_exists($k, $curso)) { $errors[] = "Falta $k"; }
		}
		if (isset($curso['preceptores']) && !is_array($curso['preceptores'])) {
			$errors[] = 'preceptores debe ser array';
		}
		return $errors;
	}

	public static function validateHorario(array $horario): array {
		$errors = [];
		$required = ['curso_id', 'materia', 'docente_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'aula'];
		foreach ($required as $k) {
			if (!array_key_exists($k, $horario)) { $errors[] = "Falta $k"; }
		}
		$dia = (int)($horario['dia_semana'] ?? 0);
		if ($dia < 1 || $dia > 7) { $errors[] = 'dia_semana debe estar entre 1 y 7'; }
		if (!self::validateHora($horario['hora_inicio'] ?? '')) { $errors[] = 'hora_inicio inválida'; }
		if (!self::validateHora($horario['hora_fin'] ?? '')) { $errors[] = 'hora_fin inválida'; }
		return $errors;
	}

	public static function validateLicencia(array $lic): array {
		$errors = [];
		$required = ['docente_id', 'desde', 'hasta', 'motivo'];
		foreach ($required as $k) {
			if (!array_key_exists($k, $lic)) { $errors[] = "Falta $k"; }
		}
		if (!self::validateFecha($lic['desde'] ?? '')) { $errors[] = 'desde inválida (YYYY-MM-DD)'; }
		if (!self::validateFecha($lic['hasta'] ?? '')) { $errors[] = 'hasta inválida (YYYY-MM-DD)'; }
		if (empty($errors)) {
			if (($lic['desde'] ?? '') > ($lic['hasta'] ?? '')) { $errors[] = 'desde no puede ser mayor que hasta'; }
		}
		return $errors;
	}

	public static function validateFecha(string $date): bool {
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { return false; }
		[$y, $m, $d] = array_map('intval', explode('-', $date));
		return checkdate($m, $d, $y);
	}

	public static function validateHora(string $time): bool {
		return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
	}

	public static function sanitizeRecursive($value) {
		if (is_array($value)) {
			$san = [];
			foreach ($value as $k => $v) { $san[$k] = self::sanitizeRecursive($v); }
			return $san;
		}
		if (is_string($value)) {
			$v = trim($value);
			$v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
			return $v;
		}
		return $value;
	}
}