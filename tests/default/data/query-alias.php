<?php

namespace QueryWithAlias;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    // TODO pgsql: doesn't resolve null?
    public function leftJoinQuery(PDO $pdo)
    {
        $query = 'SELECT a.email, b.adaid FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);

        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>|null}', $row);
        }
    }
}
