<?php

namespace PdoColumnCountTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
	public function querySelected(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
		assertType('4', $stmt->columnCount());
	}

}
