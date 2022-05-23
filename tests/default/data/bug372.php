<?php

namespace Bug372;

use function PHPStan\Testing\assertType;

class HelloWorld
{
    public function sayHello(\PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('array', $row);
        }
    }
}
