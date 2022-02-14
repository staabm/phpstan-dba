<?php

namespace Bug276;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function run(PDO $pdo)
    {
        $queries = ['SELECT 1'];

        foreach ($queries as $test) {
            $test = $pdo->prepare($test); // reusing variable
            assertType('bool', $test->execute());
        }
    }
}
