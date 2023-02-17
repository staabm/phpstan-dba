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

        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        // can't return 500, as freigabe1u1 cannot be null
        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1, 500) as col from ada');
        assertType('PDOStatement<array{col: int<-128, 127>, 0: int<-128, 127>}>', $stmt);
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

    public function if(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, "a", 1) as col from ada');
        assertType("PDOStatement<array{col: 1|'a', 0: 1|'a'}>", $stmt);

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, freigabe1u1, "nope") as col from ada');
        assertType("PDOStatement<array{col: 'nope'|int<-128, 127>, 0: 'nope'|int<-128, 127>}>", $stmt); // could be 'nope'|int<100, 127>

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, if(gesperrt <> 1, "a", "b"), "other") as col from ada');
        assertType("PDOStatement<array{col: 'a'|'b'|'other', 0: 'a'|'b'|'other'}>", $stmt);
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

    public function posIntReturn(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT length(akid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query('SELECT char_length(eladaid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query('SELECT character_length(eladaid) as col from ak');
        assertType("PDOStatement<array{col: int<0, max>, 0: int<0, max>}>", $stmt);

        $stmt = $pdo->query('SELECT octet_length(eladaid) as col from ak');
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

    public function strcase(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT lcase(c_varbinary255) as field from typemix");
        assertType("PDOStatement<array{field: string, 0: string}>", $stmt);

        $stmt = $pdo->query("SELECT ucase(c_varbinary25) as field from typemix");
        assertType("PDOStatement<array{field: string|null, 0: string|null}>", $stmt);

        $stmt = $pdo->query("SELECT lower(c_varbinary255) as field from typemix");
        assertType("PDOStatement<array{field: string, 0: string}>", $stmt);

        $stmt = $pdo->query("SELECT lower(c_varbinary25) as field from typemix");
        assertType("PDOStatement<array{field: string|null, 0: string|null}>", $stmt);

        $stmt = $pdo->query("SELECT lower(null) as field from ak");
        assertType("PDOStatement<array{field: null, 0: null}>", $stmt);

        $stmt = $pdo->query("SELECT lower('foobarbar') as field from ak");
        assertType("PDOStatement<array{field: 'foobarbar', 0: 'foobarbar'}>", $stmt);

        $stmt = $pdo->query("SELECT lower('FOO') as field from ak");
        assertType("PDOStatement<array{field: 'foo', 0: 'foo'}>", $stmt);

        $stmt = $pdo->query("SELECT upper('foobarbar') as field from ak");
        assertType("PDOStatement<array{field: 'FOOBARBAR', 0: 'FOOBARBAR'}>", $stmt);

        $stmt = $pdo->query("SELECT upper('fooBARbar') as field from ak");
        assertType("PDOStatement<array{field: 'FOOBARBAR', 0: 'FOOBARBAR'}>", $stmt);

        $stmt = $pdo->query("SELECT lower(upper('foobarbar')) as field from ak");
        assertType("PDOStatement<array{field: 'foobarbar', 0: 'foobarbar'}>", $stmt);

        $stmt = $pdo->query('SELECT lower(concat(akid, 5000)) as col from ak');
        assertType('PDOStatement<array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}>', $stmt);
    }

    public function avgMinMax(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT avg(freigabe1u1) as avg from ada');
        assertType('PDOStatement<array{avg: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT min(freigabe1u1) as min from ada');
        assertType('PDOStatement<array{min: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT max(freigabe1u1) as max from ada');
        assertType('PDOStatement<array{max: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT avg(eladaid) as avg from ak');
        assertType('PDOStatement<array{avg: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT min(eladaid) as min from ak');
        assertType('PDOStatement<array{min: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT max(eladaid) as max from ak');
        assertType('PDOStatement<array{max: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(coalesce(eladaid, 9999999999999999)) as avg from ak');
        assertType('PDOStatement<array{avg: 9999999999999999|int<-2147483648, 2147483647>, 0: 9999999999999999|int<-2147483648, 2147483647>}>', $stmt);
    }

    public function isNull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT isnull(akid) as n1 from ak');
        assertType('PDOStatement<array{n1: 0, 0: 0}>', $stmt);

        $stmt = $pdo->query('SELECT isnull(eladaid) as n1 from ak');
        assertType('PDOStatement<array{n1: 0|1, 0: 0|1}>', $stmt);
    }

    public function abs(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT abs(null) as abs from ada');
        assertType('PDOStatement<array{abs: null, 0: null}>', $stmt);

        $stmt = $pdo->query('SELECT abs(freigabe1u1) as abs from ada');
        assertType('PDOStatement<array{abs: int<0, 127>, 0: int<0, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT abs(eladaid) as abs from ak');
        assertType('PDOStatement<array{abs: int<0, 2147483647>|null, 0: int<0, 2147483647>|null}>', $stmt);
    }

    public function round(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT round(null) as abs from ada');
        assertType('PDOStatement<array{abs: null, 0: null}>', $stmt);

        $stmt = $pdo->query('SELECT round(freigabe1u1) as abs from ada');
        assertType('PDOStatement<array{abs: int<-128, 127>, 0: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT round(1.12, 1) as abs from ak');
        assertType('PDOStatement<array{abs: float|int, 0: float|int}>', $stmt);
    }
}
