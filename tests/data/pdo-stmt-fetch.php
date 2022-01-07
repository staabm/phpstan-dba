<?php

namespace PdoStmtFetchTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function fetchAll(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);

		// default fetch-mode is BOTH
		$all = $stmt->fetchAll();
		assertType('array<int, array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $all);

		$all = $stmt->fetchAll(PDO::FETCH_BOTH);
		assertType('array<int, array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $all);

		$all = $stmt->fetchAll(PDO::FETCH_NUM);
		assertType('array<int, array{string, int<0, 4294967295>}>', $all);
    }
}
