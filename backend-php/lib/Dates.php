<?php
declare(strict_types=1);

class Dates {
	public static function tz(): \DateTimeZone {
		$tz = env('TZ', 'America/Argentina/Buenos_Aires') ?? 'America/Argentina/Buenos_Aires';
		return new \DateTimeZone($tz);
	}

	public static function businessDays(string $desde, string $hasta, array $feriados = []): array {
		$start = new \DateTimeImmutable($desde, self::tz());
		$end = new \DateTimeImmutable($hasta, self::tz());
		if ($end < $start) { return []; }
		$feriadosSet = [];
		foreach ($feriados as $f) {
			if (isset($f['fecha'])) { $feriadosSet[$f['fecha']] = true; }
		}
		$days = [];
		for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
			$dow = (int)$d->format('N'); // 1..7
			$dateStr = $d->format('Y-m-d');
			if ($dow >= 6) { continue; } // skip weekend
			if (isset($feriadosSet[$dateStr])) { continue; }
			$days[] = $dateStr;
		}
		return $days;
	}

	public static function clasesAfectadas(string $docenteId, string $desde, string $hasta, array $horarios, array $feriados = []): array {
		$days = self::businessDays($desde, $hasta, $feriados);
		$resultados = [];
		foreach ($days as $date) {
			$dow = (int)(new \DateTimeImmutable($date, self::tz()))->format('N');
			foreach ($horarios as $h) {
				if (($h['docente_id'] ?? '') !== $docenteId) { continue; }
				if ((int)($h['dia_semana'] ?? 0) !== $dow) { continue; }
				$resultados[] = [
					'fecha' => $date,
					'curso_id' => $h['curso_id'],
					'materia' => $h['materia'],
					'hora_inicio' => $h['hora_inicio'],
					'hora_fin' => $h['hora_fin'],
					'aula' => $h['aula'] ?? null,
				];
			}
		}
		return $resultados;
	}

	public static function agruparPorCurso(array $clases): array {
		$grupos = [];
		foreach ($clases as $c) {
			$cid = $c['curso_id'];
			if (!isset($grupos[$cid])) { $grupos[$cid] = []; }
			$grupos[$cid][] = $c;
		}
		return $grupos;
	}
}