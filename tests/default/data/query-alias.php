<?php

namespace QueryWithAlias;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function leftJoinQuery(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>|null, 1: int<0, 4294967295>|null, gesperrt: int<-128, 127>|null, 2: int<-128, 127>|null}';

        $query = 'SELECT a.email, b.adaid, b.gesperrt FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);

        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>|null, gesperrt: int<-128, 127>|null}'.$bothType.'>', $stmt);
    }
}
