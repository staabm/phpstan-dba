<?php

namespace SqlAstNarrowing;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>, count(email): int, 1: int<0, max>}>', $stmt);
    }

    public function coalesce(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT COALESCE(null, eladaid) as col from ak');
        assertType('PDOStatement<array{col: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT COALESCE(null, eladaid, null, akid, null) as col from ak');
        assertType('PDOStatement<array{col: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}>', $stmt);
    }

    public function ifnull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, 5000) as col from ada');
        assertType('PDOStatement<array{col: 5000|int<-128, 127>, 0: 5000|int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, "default") as col from ada');
        assertType("PDOStatement<array{col: 'default'|int<-128, 127>, 0: 'default'|int<-128, 127>}>", $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, gesperrt) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(gesperrt, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);
    }

    public function nullif(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT nullif(freigabe1u1, 5000) as col from ada');
        assertType('PDOStatement<array{col: 5000|int<-128, 127>, 0: 5000|int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, "default") as col from ada');
        assertType("PDOStatement<array{col: 'default'|int<-128, 127>, 0: 'default'|int<-128, 127>}>", $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, gesperrt) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(gesperrt, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);
    }
}
