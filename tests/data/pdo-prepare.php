<?php

namespace PdoPrepareTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function prepareSelected(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);

        foreach ($stmt as $row) {
            assertType('int<0, 4294967295>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }
}
