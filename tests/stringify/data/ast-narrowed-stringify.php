<?php

namespace StringifyAstNarrowingTest;

use PDO;
use function PHPStan\Testing\assertType;

class SqlAstNarrowing
{
    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: numeric-string|null, 0: numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: numeric-string|null, 0: numeric-string|null, count(email): numeric-string|null, 1: numeric-string|null}>', $stmt);
    }
}
