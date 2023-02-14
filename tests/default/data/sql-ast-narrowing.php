<?php

namespace SqlAstNarrowing;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function count(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>, count(email): int, 1: int<0, max>}>', $stmt);
    }

    public function coalesce(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT COALESCE(null, eladaid) as col from ak');
        assertType('PDOStatement<array{col: int<0, 255>|null, 0: int<0, 255>|null}>', $stmt);

        $stmt = $pdo->query('SELECT COALESCE(null, eladaid, null, akid, null) as col from ak');
        assertType('PDOStatement<array{col: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}>', $stmt);
    }
}
