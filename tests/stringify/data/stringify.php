<?php

namespace InferenceStringifyTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function stringifyTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: numeric-string, gesperrt: numeric-string, freigabe1u1: numeric-string}>', $stmt);

        foreach ($stmt as $row) {
            assertType('numeric-string', $row['adaid']);
            assertType('string', $row['email']);
            assertType('numeric-string', $row['gesperrt']);
            assertType('numeric-string', $row['freigabe1u1']);
        }
    }
}

class SqlAstNarrowing
{
    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: numeric-string, 0: numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: numeric-string, 0: numeric-string, count(email): numeric-string, 1: numeric-string}>', $stmt);
    }
}
