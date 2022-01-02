<?php

namespace PdoTest;

use PDO;
use rex;
use function PHPStan\Testing\assertType;

class Foo
{
	public function dynamicTableQuery(PDO $pdo, int $typeId) {
		$stmt = $pdo->query('SELECT * FROM ' . rex::getTablePrefix() . 'media_manager_type WHERE id=' . $typeId, PDO::FETCH_ASSOC);
		assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);
	}
}
