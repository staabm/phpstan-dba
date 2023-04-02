<?php

namespace SqlAstNarrowing;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(*) as myemail from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>}>', $stmt);

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        assertType('PDOStatement<array{myemail: int<0, max>, 0: int<0, max>, count(email): int<0, max>, 1: int<0, max>}>', $stmt);
    }

    public function coalesce(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT COALESCE(null, eladaid) as col from ak');
        assertType('PDOStatement<array{col: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT COALESCE(null, eladaid, null, akid, null) as col from ak');
        assertType('PDOStatement<array{col: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}>', $stmt);

        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        // can't return 500, as freigabe1u1 cannot be null
        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1, 500) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
    }

    public function ifnull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, 5000) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, 123.23) as col from ada');
        assertType('PDOStatement<array{col: 123.23|int<-32768, 32767>, 0: 123.23|int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, "default") as col from ada');
        assertType("PDOStatement<array{col: 'default'|int<-32768, 32767>, 0: 'default'|int<-32768, 32767>}>", $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(freigabe1u1, gesperrt) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT ifnull(gesperrt, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT IFNULL(MAX(eladaid),0)+1 as priority from ak');
        assertType('PDOStatement<array{priority: int, 0: int}>', $stmt); // could be more precise integer range
    }

    public function nullif(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT nullif(freigabe1u1, 500000) as col from ada');
        assertType('PDOStatement<array{col: 500000|int<-32768, 32767>, 0: 500000|int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, "default") as col from ada');
        assertType("PDOStatement<array{col: 'default'|int<-32768, 32767>, 0: 'default'|int<-32768, 32767>}>", $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(freigabe1u1, gesperrt) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT nullif(gesperrt, freigabe1u1) as col from ada');
        assertType('PDOStatement<array{col: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
    }

    public function if(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, "a", 1) as col from ada');
        assertType("PDOStatement<array{col: 1|'a', 0: 1|'a'}>", $stmt);

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, freigabe1u1, "nope") as col from ada');
        assertType("PDOStatement<array{col: 'nope'|int<-32768, 32767>, 0: 'nope'|int<-32768, 32767>}>", $stmt); // could be 'nope'|int<100, 127>

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, if(gesperrt <> 1, "a", "b"), "other") as col from ada');
        assertType("PDOStatement<array{col: 'a'|'b'|'other', 0: 'a'|'b'|'other'}>", $stmt);
    }

    public function caseWhen(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT CASE 1 WHEN 1 THEN 'one' WHEN 2 THEN 'two' ELSE 'more' END as val from ada");
        assertType("PDOStatement<array{val: 'more'|'one'|'two', 0: 'more'|'one'|'two'}>", $stmt);

        $stmt = $pdo->query("SELECT
        CASE
            WHEN freigabe1u1 > 50 THEN 'big-one'
            WHEN freigabe1u1 = 50 THEN 'normal'
            ELSE freigabe1u1
        END as val from ada");
        assertType("PDOStatement<array{val: 'big-one'|'normal'|int<-32768, 32767>, 0: 'big-one'|'normal'|int<-32768, 32767>}>", $stmt); // could be 'big-one'|'normal'|int<-128, 49>
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

    public function avg(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT avg(freigabe1u1) as avg from ada');
        assertType('PDOStatement<array{avg: numeric-string|null, 0: numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(eladaid) as avg from ak');
        assertType('PDOStatement<array{avg: numeric-string|null, 0: numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(coalesce(eladaid, 9999999999999999)) as avg from ak');
        assertType('PDOStatement<array{avg: numeric-string|null, 0: numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(email) as avg from ada');
        assertType('PDOStatement<array{avg: float|null, 0: float|null}>', $stmt);

        // Test numeric-string input
        $stmt = $pdo->query('SELECT avg(concat("0")) as avg from ada');
        assertType('PDOStatement<array{avg: float|null, 0: float|null}>', $stmt);

        // Test non-falsy-string input
        $stmt = $pdo->query('SELECT avg(concat("foo")) as avg from ada');
        assertType('PDOStatement<array{avg: float|null, 0: float|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(ifnull(email, adaid)) as avg from ada');
        assertType('PDOStatement<array{avg: float|numeric-string|null, 0: float|numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT avg(null) as avg from ada');
        assertType('PDOStatement<array{avg: null, 0: null}>', $stmt);
    }

    public function minMax(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT min(freigabe1u1) as min from ada');
        assertType('PDOStatement<array{min: int<-32768, 32767>|null, 0: int<-32768, 32767>|null}>', $stmt);
        $stmt = $pdo->query('SELECT max(freigabe1u1) as max from ada');
        assertType('PDOStatement<array{max: int<-32768, 32767>|null, 0: int<-32768, 32767>|null}>', $stmt);

        $stmt = $pdo->query('SELECT min(eladaid) as min from ak');
        assertType('PDOStatement<array{min: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);
        $stmt = $pdo->query('SELECT max(eladaid) as max from ak');
        assertType('PDOStatement<array{max: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT min(email) as min from ada');
        assertType('PDOStatement<array{min: string|null, 0: string|null}>', $stmt);
        $stmt = $pdo->query('SELECT max(email) as max from ada');
        assertType('PDOStatement<array{max: string|null, 0: string|null}>', $stmt);

        $stmt = $pdo->query('SELECT min(ifnull(email, adaid)) as min from ada');
        assertType('PDOStatement<array{min: int<-32768, 32767>|string|null, 0: int<-32768, 32767>|string|null}>', $stmt);
        $stmt = $pdo->query('SELECT max(ifnull(email, adaid)) as max from ada');
        assertType('PDOStatement<array{max: int<-32768, 32767>|string|null, 0: int<-32768, 32767>|string|null}>', $stmt);

        $stmt = $pdo->query('SELECT min(null) as min from ada');
        assertType('PDOStatement<array{min: null, 0: null}>', $stmt);
        $stmt = $pdo->query('SELECT max(null) as max from ada');
        assertType('PDOStatement<array{max: null, 0: null}>', $stmt);
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
        assertType('PDOStatement<array{abs: int<0, 32767>, 0: int<0, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT abs(eladaid) as abs from ak');
        assertType('PDOStatement<array{abs: int<0, 2147483647>|null, 0: int<0, 2147483647>|null}>', $stmt);
    }

    public function round(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT round(null) as abs from ada');
        assertType('PDOStatement<array{abs: null, 0: null}>', $stmt);

        $stmt = $pdo->query('SELECT round(freigabe1u1) as abs from ada');
        assertType('PDOStatement<array{abs: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT round(1.12, 1) as abs from ak');
        assertType('PDOStatement<array{abs: float|int, 0: float|int}>', $stmt);
    }

    public function sum(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT sum(null) as sum from ak');
        assertType('PDOStatement<array{sum: null, 0: null}>', $stmt);

        $stmt = $pdo->query('SELECT sum(akid) as sum from ak');
        assertType('PDOStatement<array{sum: int, 0: int}>', $stmt);

        $stmt = $pdo->query('SELECT sum(eladaid) as sum from ak');
        assertType('PDOStatement<array{sum: int|null, 0: int|null}>', $stmt);

        $stmt = $pdo->query('SELECT sum(c_double) as sum from typemix');
        assertType('PDOStatement<array{sum: float, 0: float}>', $stmt);
    }

    public function strReplace(PDO $pdo)
    {
        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', 'w', 'Ww') as str from ada");
        assertType('PDOStatement<array{str: non-empty-string, 0: non-empty-string}>', $stmt);

        $stmt = $pdo->query("SELECT REPLACE(email, 'w', 'Ww') as str from ada");
        assertType('PDOStatement<array{str: string, 0: string}>', $stmt);

        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', 'w', c_varchar25) as str from typemix");
        assertType('PDOStatement<array{str: string|null, 0: string|null}>', $stmt);

        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', c_varchar25, 'Ww') as str from typemix");
        assertType('PDOStatement<array{str: non-empty-string|null, 0: non-empty-string|null}>', $stmt);

        $stmt = $pdo->query("SELECT REPLACE(c_varchar25, 'w', 'Ww') as str from typemix");
        assertType('PDOStatement<array{str: string|null, 0: string|null}>', $stmt);
    }

    public function joinNullableInCondition(PDO $pdo): void
    {
        // nullable column gets non-nullable on inner join
        // join condition intersects integer-ranges
        $stmt = $pdo->query('SELECT adaid, eladaid from ada join ak on (adaid = eladaid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada inner join ak on (adaid = eladaid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left join ak on (adaid = eladaid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left outer join ak on (adaid = eladaid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}>', $stmt);
    }

    public function joinNonNullableInCondition(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT adaid, akid from ada join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, akid from ada inner join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, akid from ada left join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, akid from ada left outer join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}>', $stmt);
    }

    public function joinSelectOutsideCondition(PDO $pdo): void
    {
        // nullable col from joined table
        $stmt = $pdo->query('SELECT adaid, eladaid from ada join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada inner join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left outer join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}>', $stmt);

        // non-nullable col from joined table
        $stmt = $pdo->query('SELECT adaid, eadavk from ada join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string, 1: numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eadavk from ada inner join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string, 1: numeric-string}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eadavk from ada left join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string|null, 1: numeric-string|null}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eadavk from ada left outer join ak on (adaid = akid)');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string|null, 1: numeric-string|null}>', $stmt);
    }

    public function multipleJoins(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT adaid, eladaid, c_int, c_char5 from ada inner join ak on adaid = eladaid inner join typemix on adaid = c_int');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>, c_int: int<-32768, 32767>, 2: int<-32768, 32767>, c_char5: string, 3: string}>', $stmt);

        $stmt = $pdo->query('SELECT adaid, eladaid, c_int, c_char5 from ada left join ak on adaid = eladaid left join typemix on adaid = c_int');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null, c_int: int<-32768, 32767>|null, 2: int<-32768, 32767>|null, c_char5: string|null, 3: string|null}>', $stmt);
    }

    public function ignoredAstQueries(PDO $pdo): void
    {
        // in reality akid would be same type adaid (int<-32768, 32767>)
        $stmt = $pdo->query('SELECT akid from ada inner join (select akid from ak)t on akid = adaid');
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}>', $stmt);

        $stmt = $pdo->query('SELECT adaid from ada cross join ak');
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
    }

}
