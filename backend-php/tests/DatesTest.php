<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/Dates.php';

use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase {
	public function testClasesAfectadasSimple(): void {
		$horarios = [
			['curso_id' => '1A', 'materia' => 'Matemática', 'docente_id' => 'DOC-001', 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '09:20', 'aula' => 'A-3'],
		];
		$feriados = [];
		$clases = Dates::clasesAfectadas('DOC-001', '2025-08-18', '2025-08-19', $horarios, $feriados);
		$this->assertNotEmpty($clases);
		$this->assertSame('1A', $clases[0]['curso_id']);
	}
}