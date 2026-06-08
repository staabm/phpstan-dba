<?php

namespace PdoCtePgsqlTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function cteWithAggregation(PDO $pdo)
    {
        $query = 'WITH counts AS (SELECT gesperrt, COUNT(*) AS total FROM ada GROUP BY gesperrt) '
            .'SELECT gesperrt, total FROM counts';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{gesperrt: int<-2147483648, 2147483647>, total: int|null}', $row);
        }
    }

    public function recursiveCte(PDO $pdo)
    {
        $query = 'WITH RECURSIVE cnt(n) AS ('
            .'SELECT 1 UNION ALL SELECT n + 1 FROM cnt WHERE n < 5'
            .') SELECT CAST(n AS BIGINT) AS n FROM cnt';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{n: int|null}', $row);
        }
    }
}
