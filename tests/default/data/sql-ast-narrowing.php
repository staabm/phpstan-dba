<?php

namespace SqlAstNarrowing;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function execute(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>, count(email): int, 1: int<0, max>}>', $stmt);
    }
}
