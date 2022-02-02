<?php

namespace InferenceStringifyTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function stringifyTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        foreach ($stmt as $row) {
            assertType('numeric-string', $row['adaid']);
            assertType('string', $row['email']);
            assertType('numeric-string', $row['gesperrt']);
            assertType('numeric-string', $row['freigabe1u1']);
        }
    }
}
