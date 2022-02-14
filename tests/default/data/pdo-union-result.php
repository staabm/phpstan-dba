<?php

namespace PdoUnionResult;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function doFoo(PDO $pdo)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            assertType('array{adaid: int<0, 4294967295>}|array{email: string}|false', $result);
        }
    }

    public function doBar(PDO $pdo)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_NUM);
            assertType('array{int<0, 4294967295>|string}|false', $result);
        }
    }
}
