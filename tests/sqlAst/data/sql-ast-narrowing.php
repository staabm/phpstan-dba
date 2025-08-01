<?php

namespace SqlAstNarrowing;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function whereIsNotNull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT c_json FROM typemix');
        foreach ($stmt as $row) {
            assertType('array{c_json: string|null, 0: string|null}', $row);
        }

        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType('array{c_json: string, 0: string}', $row);
        }

        // condition in parentheses
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE (c_json IS NOT NULL)');
        foreach ($stmt as $row) {
            assertType('array{c_json: string, 0: string}', $row);
        }

        // selected with an alias
        $stmt = $pdo->query('SELECT c_json as col FROM typemix WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType('array{col: string, 0: string}', $row);
        }

        // affects input to a function
        $stmt = $pdo->query('SELECT ifnull(null, c_json) as col FROM typemix WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType('array{col: string, 0: string}', $row);
        }

        // selected with an asterisk
        $stmt = $pdo->query('SELECT * FROM typemix WHERE c_json IS NOT NULL');
        assertType('string', $stmt->fetch()['c_json']);

        // compound where condition (AND)
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NOT NULL AND c_int=1');
        foreach ($stmt as $row) {
            assertType('array{c_json: string, 0: string}', $row);
        }

        // compound where condition (OR)
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NOT NULL OR c_int=1');
        foreach ($stmt as $row) {
            assertType('array{c_json: string|null, 0: string|null}', $row);
        }

        // subquery does not impact outer where condition
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_text IN (SELECT c_json FROM typemix WHERE c_json IS NOT NULL)');
        foreach ($stmt as $row) {
            assertType('array{c_json: string|null, 0: string|null}', $row);
        }
    }

    public function whereIsNull(PDO $pdo): void
    {
        // empty intersection
        $stmt = $pdo->query('SELECT c_json_not_null FROM typemix WHERE c_json_not_null IS NULL');
        foreach ($stmt as $row) {
            assertType('array{c_json_not_null: *NEVER*, 0: *NEVER*}', $row);
        }

        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NULL');
        foreach ($stmt as $row) {
            assertType('array{c_json: null, 0: null}', $row);
        }

        // condition in parentheses
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE (c_json IS NULL)');
        foreach ($stmt as $row) {
            assertType('array{c_json: null, 0: null}', $row);
        }

        // selected with an alias
        $stmt = $pdo->query('SELECT c_json as col FROM typemix WHERE c_json IS NULL');
        foreach ($stmt as $row) {
            assertType('array{col: null, 0: null}', $row);
        }

        // affects input to a function
        $stmt = $pdo->query('SELECT ifnull(c_json, "default") as col FROM typemix WHERE c_json IS NULL');
        foreach ($stmt as $row) {
            assertType("array{col: 'default', 0: 'default'}", $row);
        }

        // selected with an asterisk
        $stmt = $pdo->query('SELECT * FROM typemix WHERE c_json IS NULL');
        assertType('null', $stmt->fetch()['c_json']);

        // compound where condition (AND)
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NULL AND c_int=1');
        foreach ($stmt as $row) {
            assertType('array{c_json: null, 0: null}', $row);
        }

        // compound where condition (OR)
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_json IS NULL OR c_int=1');
        foreach ($stmt as $row) {
            assertType('array{c_json: string|null, 0: string|null}', $row);
        }

        // subquery does not impact outer where condition
        $stmt = $pdo->query('SELECT c_json FROM typemix WHERE c_text IN (SELECT c_json FROM typemix WHERE c_json IS NULL)');
        foreach ($stmt as $row) {
            assertType('array{c_json: string|null, 0: string|null}', $row);
        }
    }

    public function noFromTable(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT 3');
        foreach ($stmt as $row) {
            assertType('array{3: 3, 0: 3}', $row);
        }

        $stmt = $pdo->query('SELECT 3 as col');
        foreach ($stmt as $row) {
            assertType('array{col: 3, 0: 3}', $row);
        }
    }

    public function count(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT count(*) as myemail from ada');
        foreach ($stmt as $row) {
            assertType('array{myemail: int<0, max>, 0: int<0, max>}', $row);
        }

        $stmt = $pdo->query('SELECT count(email) as myemail from ada');
        foreach ($stmt as $row) {
            assertType('array{myemail: int<0, max>, 0: int<0, max>}', $row);
        }

        $stmt = $pdo->query('SELECT count(email) as myemail, count(email) from ada');
        foreach ($stmt as $row) {
            assertType("array{myemail: int<0, max>, 0: int<0, max>, 'count(email)': int<0, max>, 1: int<0, max>}", $row);
        }
    }

    public function coalesce(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT COALESCE(null, eladaid) as col from ak');
        foreach ($stmt as $row) {
            assertType('array{col: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}', $row);
        }

        $stmt = $pdo->query('SELECT COALESCE(null, eladaid, null, akid, null) as col from ak');
        foreach ($stmt as $row) {
            assertType('array{col: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}', $row);
        }

        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1) as col from ada');
        foreach ($stmt as $row) {
            assertType('array{col: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }

        // can't return 500, as freigabe1u1 cannot be null
        $stmt = $pdo->query('SELECT COALESCE(freigabe1u1, 500) as col from ada');
        foreach ($stmt as $row) {
            assertType('array{col: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    public function ifnull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT ifnull(null, "default") as col');
        foreach ($stmt as $row) {
            assertType("array{col: 'default', 0: 'default'}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_int, "default") as col from typemix');
        foreach ($stmt as $row) {
            assertType('array{col: lowercase-string&numeric-string&uppercase-string, 0: lowercase-string&numeric-string&uppercase-string}', $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_nullable_tinyint, "default") as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: 'default'|(lowercase-string&numeric-string&uppercase-string), 0: 'default'|(lowercase-string&numeric-string&uppercase-string)}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_nullable_tinyint, 5000) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: 5000|int<-128, 127>, 0: 5000|int<-128, 127>}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_int, c_float) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: float, 0: float}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_float, 123.23) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: float, 0: float}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_int, 123.23) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: lowercase-string&numeric-string&uppercase-string, 0: lowercase-string&numeric-string&uppercase-string}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(123.23, c_int) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: '123.23', 0: '123.23'}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_smallint, c_smallint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-32768, 32767>, 0: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_smallint, c_tinyint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-32768, 32767>, 0: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_tinyint, c_smallint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>, 0: int<-128, 127>}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_nullable_tinyint, c_smallint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-32768, 32767>, 0: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT ifnull(c_nullable_tinyint, c_nullable_tinyint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }

        $stmt = $pdo->query('SELECT IFNULL(MAX(eladaid),0)+1 as priority from ak');
        foreach ($stmt as $row) {
            // could be more precise integer range
            assertType("array{priority: int, 0: int}", $row);
        }
    }

    public function nullif(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT nullif(2, 2) as col');
        foreach ($stmt as $row) {
            assertType("array{col: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT nullif(2, 3) as col');
        foreach ($stmt as $row) {
            assertType("array{col: 2, 0: 2}", $row);
        }

        // Test an integer range against a constant int inside the range
        $stmt = $pdo->query('SELECT nullif(c_tinyint, 3) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }

        // Test an integer range against a constant int outside the range
        $stmt = $pdo->query('SELECT nullif(c_tinyint, 5000) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>, 0: int<-128, 127>}", $row);
        }

        // Test an integer range against a constant string
        $stmt = $pdo->query('SELECT nullif(c_tinyint, "default") as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>, 0: int<-128, 127>}", $row);
        }

        $stmt = $pdo->query('SELECT nullif(c_tinyint, c_tinyint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }

        $stmt = $pdo->query('SELECT nullif(c_tinyint, c_smallint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }

        $stmt = $pdo->query('SELECT nullif(c_smallint, c_tinyint) as col from typemix');
        foreach ($stmt as $row) {
            assertType("array{col: int<-32768, 32767>|null, 0: int<-32768, 32767>|null}", $row);
        }
    }

    public function if(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, "a", 1) as col from ada');
        foreach ($stmt as $row) {
            assertType("array{col: 1|'a', 0: 1|'a'}", $row);
        }

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, freigabe1u1, "nope") as col from ada');
        foreach ($stmt as $row) {
            assertType("array{col: 'nope'|int<-32768, 32767>, 0: 'nope'|int<-32768, 32767>}", $row); // could be 'nope'|int<100, 127>
        }

        $stmt = $pdo->query('SELECT if(freigabe1u1 > 100, if(gesperrt <> 1, "a", "b"), "other") as col from ada');
        foreach ($stmt as $row) {
            assertType("array{col: 'a'|'b'|'other', 0: 'a'|'b'|'other'}", $row);
        }
    }

    public function caseWhen(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT CASE 1 WHEN 1 THEN 'one' WHEN 2 THEN 'two' ELSE 'more' END as val from ada");
        foreach ($stmt as $row) {
            assertType("array{val: 'more'|'one'|'two', 0: 'more'|'one'|'two'}", $row);
        }

        $stmt = $pdo->query("SELECT
        CASE
            WHEN freigabe1u1 > 50 THEN 'big-one'
            WHEN freigabe1u1 = 50 THEN 'normal'
            ELSE freigabe1u1
        END as val from ada");
        foreach ($stmt as $row) {
            // could be 'big-one'|'normal'|int<-128, 49>
            assertType("array{val: 'big-one'|'normal'|int<-32768, 32767>, 0: 'big-one'|'normal'|int<-32768, 32767>}", $row);
        }
    }

    public function concat(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT concat(akid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT concat(eladaid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: (non-empty-string&numeric-string)|null, 0: (non-empty-string&numeric-string)|null}", $row);
        }

        $stmt = $pdo->query('SELECT concat(eladaid, akid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: (non-empty-string&numeric-string)|null, 0: (non-empty-string&numeric-string)|null}", $row);
        }

        $stmt = $pdo->query('SELECT concat(eladaid, null) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT concat("abc", akid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-falsy-string, 0: non-falsy-string}", $row);
        }
    }

    public function concat_ws(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT concat_ws(akid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT concat_ws(eladaid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT concat_ws(eladaid, akid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT concat_ws(eladaid, null) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT concat_ws("abc", akid, 5000) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-falsy-string, 0: non-falsy-string}", $row);
        }
    }

    public function posIntReturn(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT length(akid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query('SELECT char_length(eladaid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query('SELECT character_length(eladaid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query('SELECT octet_length(eladaid) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query("SELECT FIELD('Bb', 'Aa', 'Bb', 'Cc', 'Dd', 'Ff') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>, 0: int<0, max>}", $row);
        }
    }

    public function instr(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT instr('foobarbar', 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query("SELECT instr(eladaid, 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>|null, 0: int<0, max>|null}", $row);
        }

        $stmt = $pdo->query("SELECT instr(akid, 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>, 0: int<0, max>}", $row);
        }

        $stmt = $pdo->query("SELECT locate('foo', eladaid, 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>|null, 0: int<0, max>|null}", $row);
        }

        $stmt = $pdo->query("SELECT locate(eladaid, 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>|null, 0: int<0, max>|null}", $row);
        }

        $stmt = $pdo->query("SELECT locate(akid, 'bar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: int<0, max>, 0: int<0, max>}", $row);
        }
    }

    public function strcase(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT lcase(c_varbinary255) as field from typemix");
        foreach ($stmt as $row) {
            assertType("array{field: string, 0: string}", $row);
        }

        $stmt = $pdo->query("SELECT ucase(c_varbinary25) as field from typemix");
        foreach ($stmt as $row) {
            assertType("array{field: string|null, 0: string|null}", $row);
        }

        $stmt = $pdo->query("SELECT lower(c_varbinary255) as field from typemix");
        foreach ($stmt as $row) {
            assertType("array{field: string, 0: string}", $row);
        }

        $stmt = $pdo->query("SELECT lower(c_varbinary25) as field from typemix");
        foreach ($stmt as $row) {
            assertType("array{field: string|null, 0: string|null}", $row);
        }

        $stmt = $pdo->query("SELECT lower(null) as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: null, 0: null}", $row);
        }

        $stmt = $pdo->query("SELECT lower('foobarbar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: 'foobarbar', 0: 'foobarbar'}", $row);
        }

        $stmt = $pdo->query("SELECT lower('FOO') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: 'foo', 0: 'foo'}", $row);
        }

        $stmt = $pdo->query("SELECT upper('foobarbar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: 'FOOBARBAR', 0: 'FOOBARBAR'}", $row);
        }

        $stmt = $pdo->query("SELECT upper('fooBARbar') as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: 'FOOBARBAR', 0: 'FOOBARBAR'}", $row);
        }

        $stmt = $pdo->query("SELECT lower(upper('foobarbar')) as field from ak");
        foreach ($stmt as $row) {
            assertType("array{field: 'foobarbar', 0: 'foobarbar'}", $row);
        }

        $stmt = $pdo->query('SELECT lower(concat(akid, 5000)) as col from ak');
        foreach ($stmt as $row) {
            assertType("array{col: non-empty-string&numeric-string, 0: non-empty-string&numeric-string}", $row);
        }
    }

    public function avg(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT avg(freigabe1u1) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: numeric-string|null, 0: numeric-string|null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(eladaid) as avg from ak');
        foreach ($stmt as $row) {
            assertType("array{avg: numeric-string|null, 0: numeric-string|null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(coalesce(eladaid, 9999999999999999)) as avg from ak');
        foreach ($stmt as $row) {
            assertType("array{avg: numeric-string|null, 0: numeric-string|null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(email) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: float|null, 0: float|null}", $row);
        }

        // Test numeric-string input
        $stmt = $pdo->query('SELECT avg(concat("0")) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: float|null, 0: float|null}", $row);
        }

        // Test non-falsy-string input
        $stmt = $pdo->query('SELECT avg(concat("foo")) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: float|null, 0: float|null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(ifnull(email, adaid)) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: float|null, 0: float|null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(null) as avg from ada');
        foreach ($stmt as $row) {
            assertType("array{avg: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT avg(c_tinyint) as avg from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{avg: numeric-string, 0: numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT avg(c_nullable_tinyint) as avg from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{avg: numeric-string|null, 0: numeric-string|null}", $row);
        }
    }

    public function minMax(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT min(freigabe1u1) as min from ada');
        foreach ($stmt as $row) {
            assertType("array{min: int<-32768, 32767>|null, 0: int<-32768, 32767>|null}", $row);
        }
        $stmt = $pdo->query('SELECT max(freigabe1u1) as max from ada');
        foreach ($stmt as $row) {
            assertType("array{max: int<-32768, 32767>|null, 0: int<-32768, 32767>|null}", $row);
        }

        $stmt = $pdo->query('SELECT min(eladaid) as min from ak');
        foreach ($stmt as $row) {
            assertType("array{min: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}", $row);
        }
        $stmt = $pdo->query('SELECT max(eladaid) as max from ak');
        foreach ($stmt as $row) {
            assertType("array{max: int<-2147483648, 2147483647>|null, 0: int<-2147483648, 2147483647>|null}", $row);
        }

        $stmt = $pdo->query('SELECT min(email) as min from ada');
        foreach ($stmt as $row) {
            assertType("array{min: string|null, 0: string|null}", $row);
        }
        $stmt = $pdo->query('SELECT max(email) as max from ada');
        foreach ($stmt as $row) {
            assertType("array{max: string|null, 0: string|null}", $row);
        }

        $stmt = $pdo->query('SELECT min(ifnull(email, adaid)) as min from ada');
        foreach ($stmt as $row) {
            assertType("array{min: string|null, 0: string|null}", $row);
        }
        $stmt = $pdo->query('SELECT max(ifnull(email, adaid)) as max from ada');
        foreach ($stmt as $row) {
            assertType("array{max: string|null, 0: string|null}", $row);
        }

        $stmt = $pdo->query('SELECT min(null) as min from ada');
        foreach ($stmt as $row) {
            assertType("array{min: null, 0: null}", $row);
        }
        $stmt = $pdo->query('SELECT max(null) as max from ada');
        foreach ($stmt as $row) {
            assertType("array{max: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT min(c_tinyint) as min from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{min: int<-128, 127>, 0: int<-128, 127>}", $row);
        }
        $stmt = $pdo->query('SELECT max(c_tinyint) as max from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{max: int<-128, 127>, 0: int<-128, 127>}", $row);
        }

        $stmt = $pdo->query('SELECT min(c_nullable_tinyint) as min from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{min: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }
        $stmt = $pdo->query('SELECT max(c_nullable_tinyint) as max from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{max: int<-128, 127>|null, 0: int<-128, 127>|null}", $row);
        }
    }

    public function isNull(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT isnull(akid) as n1 from ak');
        foreach ($stmt as $row) {
            assertType("array{n1: 0, 0: 0}", $row);
        }

        $stmt = $pdo->query('SELECT isnull(eladaid) as n1 from ak');
        foreach ($stmt as $row) {
            assertType("array{n1: 0|1, 0: 0|1}", $row);
        }
    }

    public function abs(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT abs(null) as abs from ada');
        foreach ($stmt as $row) {
            assertType("array{abs: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT abs(freigabe1u1) as abs from ada');
        foreach ($stmt as $row) {
            assertType("array{abs: int<0, 32767>, 0: int<0, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT abs(eladaid) as abs from ak');
        foreach ($stmt as $row) {
            assertType("array{abs: int<0, 2147483647>|null, 0: int<0, 2147483647>|null}", $row);
        }
    }

    public function round(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT round(null) as abs from ada');
        foreach ($stmt as $row) {
            assertType("array{abs: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT round(freigabe1u1) as abs from ada');
        foreach ($stmt as $row) {
            assertType("array{abs: int<-32768, 32767>, 0: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT round(1.12, 1) as abs from ak');
        foreach ($stmt as $row) {
            assertType("array{abs: float|int, 0: float|int}", $row);
        }
    }

    public function sum(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT sum(null) as sum from ak');
        foreach ($stmt as $row) {
            assertType("array{sum: null, 0: null}", $row);
        }

        $stmt = $pdo->query('SELECT sum(akid) as sum from ak');
        foreach ($stmt as $row) {
            assertType("array{sum: int|null, 0: int|null}", $row);
        }

        $stmt = $pdo->query('SELECT sum(eladaid) as sum from ak');
        foreach ($stmt as $row) {
            assertType("array{sum: int|null, 0: int|null}", $row);
        }

        $stmt = $pdo->query('SELECT sum(c_double) as sum from typemix');
        foreach ($stmt as $row) {
            assertType("array{sum: float|null, 0: float|null}", $row);
        }

        $stmt = $pdo->query('SELECT sum(c_tinyint) as sum from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{sum: int, 0: int}", $row);
        }

        $stmt = $pdo->query('SELECT sum(c_nullable_tinyint) as sum from typemix GROUP BY c_int');
        foreach ($stmt as $row) {
            assertType("array{sum: int|null, 0: int|null}", $row);
        }
    }

    public function strReplace(PDO $pdo)
    {
        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', 'w', 'Ww') as str from ada");
        foreach ($stmt as $row) {
            assertType("array{str: non-empty-string, 0: non-empty-string}", $row);
        }

        $stmt = $pdo->query("SELECT REPLACE(email, 'w', 'Ww') as str from ada");
        foreach ($stmt as $row) {
            assertType("array{str: string, 0: string}", $row);
        }

        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', 'w', c_varchar25) as str from typemix");
        foreach ($stmt as $row) {
            assertType("array{str: string|null, 0: string|null}", $row);
        }

        $stmt = $pdo->query("SELECT REPLACE('www.mysql.com', c_varchar25, 'Ww') as str from typemix");
        foreach ($stmt as $row) {
            assertType("array{str: non-empty-string|null, 0: non-empty-string|null}", $row);
        }

        $stmt = $pdo->query("SELECT REPLACE(c_varchar25, 'w', 'Ww') as str from typemix");
        foreach ($stmt as $row) {
            assertType("array{str: string|null, 0: string|null}", $row);
        }
    }

    public function joinNullableInCondition(PDO $pdo): void
    {
        // nullable column gets non-nullable on inner join
        // join condition intersects integer-ranges
        $stmt = $pdo->query('SELECT adaid, eladaid from ada join ak on (adaid = eladaid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada inner join ak on (adaid = eladaid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left join ak on (adaid = eladaid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left outer join ak on (adaid = eladaid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}", $row);
        }
    }

    public function joinNonNullableInCondition(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT adaid, akid from ada join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>, 1: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, akid from ada inner join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>, 1: int<-32768, 32767>}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, akid from ada left join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, akid from ada left outer join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, akid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null}", $row);
        }
    }

    public function joinSelectOutsideCondition(PDO $pdo): void
    {
        // nullable col from joined table
        $stmt = $pdo->query('SELECT adaid, eladaid from ada join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada inner join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid from ada left outer join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-2147483648, 2147483647>|null, 1: int<-2147483648, 2147483647>|null}", $row);
        }

        // non-nullable col from joined table
        $stmt = $pdo->query('SELECT adaid, eadavk from ada join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string, 1: numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eadavk from ada inner join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string, 1: numeric-string}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eadavk from ada left join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string|null, 1: numeric-string|null}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eadavk from ada left outer join ak on (adaid = akid)');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eadavk: numeric-string|null, 1: numeric-string|null}", $row);
        }
    }

    public function joinWhereCondition(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT c_json FROM typemix LEFT JOIN ada ON (c_int = adaid) WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType("array{c_json: string, 0: string}", $row);
        }

        $stmt = $pdo->query('SELECT c_json FROM typemix RIGHT JOIN ada ON (c_int = adaid) WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType("array{c_json: string, 0: string}", $row);
        }

        $stmt = $pdo->query('SELECT c_json FROM ada LEFT JOIN typemix ON (c_json = c_json) WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType("array{c_json: string, 0: string}", $row);
        }

        $stmt = $pdo->query('SELECT c_json FROM ada RIGHT JOIN typemix ON (c_json = c_json) WHERE c_json IS NOT NULL');
        foreach ($stmt as $row) {
            assertType("array{c_json: string, 0: string}", $row);
        }
    }

    public function multipleJoins(PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT adaid, eladaid, c_int, c_char5 from ada inner join ak on adaid = eladaid inner join typemix on adaid = c_int');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>, 1: int<-32768, 32767>, c_int: int<-32768, 32767>, 2: int<-32768, 32767>, c_char5: string, 3: string}", $row);
        }

        $stmt = $pdo->query('SELECT adaid, eladaid, c_int, c_char5 from ada left join ak on adaid = eladaid left join typemix on adaid = c_int');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>, eladaid: int<-32768, 32767>|null, 1: int<-32768, 32767>|null, c_int: int<-32768, 32767>|null, 2: int<-32768, 32767>|null, c_char5: string|null, 3: string|null}", $row);
        }
    }

    public function ignoredAstQueries(PDO $pdo): void
    {
        // in reality akid would be same type adaid (int<-32768, 32767>)
        $stmt = $pdo->query('SELECT akid from ada inner join (select akid from ak)t on akid = adaid');
        foreach ($stmt as $row) {
            assertType("array{akid: int<-2147483648, 2147483647>, 0: int<-2147483648, 2147483647>}", $row);
        }

        $stmt = $pdo->query('SELECT adaid from ada cross join ak');
        foreach ($stmt as $row) {
            assertType("array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}", $row);
        }
    }

}
