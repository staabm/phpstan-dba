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

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, 123.23) as col from ada');
        assertType('PDOStatement<array{col: 123.23|int<-128, 127>, 0: 123.23|int<-128, 127>}>', $stmt);

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

    public function concat(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT concat(akid, 5000) as col from ak');
        assertType('PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT concat(eladaid, 5000) as col from ak');
        assertType("PDOStatement<array{col: (non-empty-string&numeric-string)|null, 0: (non-empty-string&numeric-string)|null}>", $stmt);

        $stmt = $pdo->query('SELECT concat(eladaid, akid) as col from ak');
        assertType("PDOStatement<array{col: (non-empty-string&numeric-string)|null, 0: (non-empty-string&numeric-string)|null}>", $stmt);

        $stmt = $pdo->query('SELECT concat(eladaid, null) as col from ak');
        assertType('PDOStatement<array{col: null, 0: null}>', $stmt);

        $stmt = $pdo->query('SELECT concat("abc", akid, 5000) as col from ak');
        assertType('PDOStatement<array{col: non-falsy-string, 0: non-falsy-string}>', $stmt);
    }

    public function concat_ws(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT concat_ws(akid, 5000) as col from ak');
        assertType('PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT concat_ws(eladaid, 5000) as col from ak');
        assertType("PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>", $stmt);

        $stmt = $pdo->query('SELECT concat_ws(eladaid, akid) as col from ak');
        assertType("PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>", $stmt);

        $stmt = $pdo->query('SELECT concat_ws(eladaid, null) as col from ak');
        assertType('PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT concat_ws("abc", akid, 5000) as col from ak');
        assertType('PDOStatement<array{col: non-falsy-string, 0: non-falsy-string}>', $stmt);
    }

    public function length(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT length(akid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query('SELECT char_length(eladaid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query('SELECT character_length(eladaid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query("SELECT FIELD('Bb', 'Aa', 'Bb', 'Cc', 'Dd', 'Ff') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>, 0: int<0, max>}>", $stmt);
    }

    public function instr(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT instr('foobarbar', 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query("SELECT instr(eladaid, 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>|null, 0: int<0, max>|null}>", $stmt);

        $stmt = $pdo->query("SELECT instr(akid, 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query("SELECT locate('foo', eladaid, 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>|null, 0: int<0, max>|null}>", $stmt);

        $stmt = $pdo->query("SELECT locate(eladaid, 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>|null, 0: int<0, max>|null}>", $stmt);

        $stmt = $pdo->query("SELECT locate(akid, 'bar') as field from ak");
        assertType("PDOStatement<array{field: int<0, max>, 0: int<0, max>}>", $stmt);
    }
}
