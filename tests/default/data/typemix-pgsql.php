<?php

namespace TypemixTest;

use mysqli;
use PDO;
use function PHPStan\Testing\assertType;

class TypemixPgsql
{
    private const PGSQL_DATATYPES = 'array{pid: int<1, 2147483647>, c_varchar5: string, c_varchar25: string|null, c_varchar255: string, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_text: string|null, c_enum: mixed, c_bit255: int, c_bit25: int|null, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_smallint: int<-32768, 32767>, c_bigint: int, c_float: float, c_boolean: bool, c_json: string, c_json_nullable: string|null, c_jsonb: string, c_jsonb_nullable: string|null}';

    public function typemixMysqli(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT * FROM typemix');

        foreach($result as $value) {
            assertType('int<1, 2147483647>', $value['pid']);
            assertType('string', $value['c_varchar5']);
            assertType('string|null', $value['c_varchar25']);
            assertType('string', $value['c_varchar255']);
            assertType('string|null', $value['c_date']);
            assertType('string|null', $value['c_time']);
            assertType('string|null', $value['c_datetime']);
            assertType('string|null', $value['c_timestamp']);
            assertType('string|null', $value['c_text']);
            assertType('mixed', $value['c_enum']);
            assertType('int', $value['c_bit255']);
            assertType('int|null', $value['c_bit25']);
            assertType('int|null', $value['c_bit']);
            assertType('int<-2147483648, 2147483647>', $value['c_int']);
            assertType('int<-32768, 32767>', $value['c_smallint']);
            assertType('int', $value['c_bigint']);
            assertType('float', $value['c_float']);
            assertType('bool', $value['c_boolean']);
            assertType('string', $value['c_json']);
            assertType('string|null', $value['c_json_nullable']);
            assertType('string', $value['c_jsonb']);
            assertType('string|null', $value['c_jsonb_nullable']);
        }
    }

    public function typemixPdoPgsql(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);

        foreach($stmt as $value) {
            assertType('int<1, 2147483647>', $value['pid']);
            assertType('string', $value['c_varchar5']);
            assertType('string|null', $value['c_varchar25']);
            assertType('string', $value['c_varchar255']);
            assertType('string|null', $value['c_date']);
            assertType('string|null', $value['c_time']);
            assertType('string|null', $value['c_datetime']);
            assertType('string|null', $value['c_timestamp']);
            assertType('string|null', $value['c_text']);
            assertType('mixed', $value['c_enum']);
            assertType('int', $value['c_bit255']);
            assertType('int|null', $value['c_bit25']);
            assertType('int|null', $value['c_bit']);
            assertType('int<-2147483648, 2147483647>', $value['c_int']);
            assertType('int<-32768, 32767>', $value['c_smallint']);
            assertType('int', $value['c_bigint']);
            assertType('float', $value['c_float']);
            assertType('bool', $value['c_boolean']);
            assertType('string', $value['c_json']);
            assertType('string|null', $value['c_json_nullable']);
            assertType('string', $value['c_jsonb']);
            assertType('string|null', $value['c_jsonb_nullable']);
        }
    }
}
