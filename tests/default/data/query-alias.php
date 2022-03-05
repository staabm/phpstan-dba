<?php

namespace QueryWithAlias;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function leftJoinQuery(PDO $pdo)
    {
        $query = 'SELECT a.email, b.adaid, b.gesperrt FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);

        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>|null, gesperrt: int<-128, 127>|null}>', $stmt);
    }
}
