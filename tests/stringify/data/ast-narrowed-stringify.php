<?php

namespace StringifyAstNarrowingTest;

use PDO;
use function PHPStan\Testing\assertType;

class SqlAstNarrowing
{
    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        foreach ($stmt as $row) {
            assertType("array{myemail: numeric-string, 0: numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        foreach ($stmt as $row) {
            assertType("array{myemail: numeric-string, 0: numeric-string, 'count(email)': numeric-string, 1: numeric-string}", $row);
        }
    }
}
