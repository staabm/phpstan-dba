<?php

namespace PdoTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo {
	public function querySelected(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT name, id FROM foo', PDO::FETCH_ASSOC);
		assertType('PDOStatement<array{id: int<0, max>, name: string}>', $stmt);

		foreach($stmt as $row) {
			assertType('int<0, max>', $row['id']);
			assertType('string', $row['name']);
		}
	}

	public function dynamicQuery(PDO $pdo, string $query)
	{
		$stmt = $pdo->query($query, PDO::FETCH_ASSOC);
		assertType('PDOStatement<array>|false', $stmt);
	}
/*
	public function queryAll(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT * FROM foo',  PDO::FETCH_ASSOC);

		foreach($stmt as $row) {
			assertType('positive-int', $row['id']);
			assertType('string', $row['name']);
		}
	}
*/
}
