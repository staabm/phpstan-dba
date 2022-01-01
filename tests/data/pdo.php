<?php

namespace PdoTest;

use PDO;
use rex;
use function PHPStan\Testing\assertType;

class Foo
{
	public function dynamicTableQuery(PDO $pdo) {
		$stmt = $pdo->query('SELECT email, adaid FROM '. rex::getTablePrefix(), PDO::FETCH_ASSOC);
		assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);
	}


}
