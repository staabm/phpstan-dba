<?php

namespace PdoCteMysqlTest;

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
            assertType('array{gesperrt: int<-128, 127>, total: int}', $row);
        }
    }

    public function recursiveCte(PDO $pdo)
    {
        $query = 'WITH RECURSIVE cnt(n) AS ('
            .'SELECT 1 UNION ALL SELECT n + 1 FROM cnt WHERE n < 5'
            .') SELECT CAST(n AS SIGNED) AS n FROM cnt';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{n: int|null}', $row);
        }
    }
}
