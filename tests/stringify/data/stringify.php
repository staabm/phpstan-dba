<?php

namespace InferenceStringifyTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function stringifyTypes(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: numeric-string, 1: numeric-string, gesperrt: numeric-string, 2: numeric-string, freigabe1u1: numeric-string, 3: numeric-string}';

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: numeric-string, gesperrt: numeric-string, freigabe1u1: numeric-string}'.$bothType.'>', $stmt);

        foreach ($stmt as $row) {
            assertType('numeric-string', $row['adaid']);
            assertType('string', $row['email']);
            assertType('numeric-string', $row['gesperrt']);
            assertType('numeric-string', $row['freigabe1u1']);
        }
    }
}
