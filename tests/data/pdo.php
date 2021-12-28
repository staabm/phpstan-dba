<?php

namespace PdoTest;

use function PHPStan\Testing\assertType;

class Foo {
	public function query()
	{
		$pdo = new \PDO('sqlite::memory:');
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$stmt = $pdo->query('SELECT * FROM foo',  PDO::FETCH_ASSOC);

		foreach($stmt as $row) {
			assertType('positive-int', $row['id']);
			assertType('string', $row['name']);
		}

	}
}
