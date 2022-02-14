<?php

namespace Bug276;

use PDO;

class Foo
{
    public function run(PDO $pdo)
    {
        $queries = ['SELECT 1', 'SELECT 1'];

        foreach ($queries as $test) {
            $test = $pdo->prepare($test); // reusing variable
            $test->execute();
        }
    }
}
