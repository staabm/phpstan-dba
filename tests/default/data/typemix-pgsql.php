<?php

namespace TypemixTest;

use PDO;
use function PHPStan\Testing\assertType;

class TypemixPgsql
{
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
