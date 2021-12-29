<?php

namespace PdoTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo {
	public function querySelected(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
		assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

		foreach($stmt as $row) {
			assertType('int<0, 4294967295>', $row['adaid']);
			assertType('string', $row['email']);
			assertType('int<-128, 127>', $row['gesperrt']);
			assertType('int<-128, 127>', $row['freigabe1u1']);
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
