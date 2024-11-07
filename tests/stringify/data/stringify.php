<?php

namespace InferenceStringifyTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function stringifyTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType("array{email: string, adaid: numeric-string, gesperrt: numeric-string, freigabe1u1: numeric-string}", $row);
            assertType('numeric-string', $row['adaid']);
            assertType('string', $row['email']);
            assertType('numeric-string', $row['gesperrt']);
            assertType('numeric-string', $row['freigabe1u1']);
        }
    }
}
