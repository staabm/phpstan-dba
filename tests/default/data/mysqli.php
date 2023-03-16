<?php

namespace MysqliTest;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function ooQuerySelected(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<-32768, 32767>}>', $result);

        $field = 'email';
        if (rand(0, 1)) {
            $field = 'adaid';
        }

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
            assertType('int<-32768, 32767>|string', $row[$field]);
        }
    }

    public function ooQuery(mysqli $mysqli, string $query)
    {
        $result = $mysqli->query($query);
        assertType('mysqli_result|true', $result);
    }

    public function fnQuerySelected(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<-32768, 32767>}>', $result);

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }

    public function fnQuery(mysqli $mysqli, string $query)
    {
        $result = mysqli_query($mysqli, $query);
        assertType('mysqli_result|true', $result);
    }

    public function mysqlTypes(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT * FROM typemix');
        assertType('mysqli_result<array{pid: int<0, 4294967295>, c_char5: string, c_varchar255: string, c_varchar25: string|null, c_varbinary255: string, c_varbinary25: string|null, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_year: int<0, 2155>|null, c_tiny_text: string|null, c_medium_text: string|null, c_text: string|null, c_long_text: string|null, c_enum: string, c_set: string, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_tinyint: int<-128, 127>, c_smallint: int<-32768, 32767>, c_mediumint: int<-8388608, 8388607>, c_bigint: int, c_double: float, c_real: float, c_float: float, c_boolean: int<-128, 127>, c_blob: string, c_tinyblob: string, c_mediumblog: string, c_longblob: string, c_unsigned_tinyint: int<0, 255>, c_unsigned_int: int<0, 4294967295>, c_unsigned_smallint: int<0, 65535>, c_unsigned_mediumint: int<0, 16777215>, c_unsigned_bigint: int<0, max>, c_json: string|null, c_json_not_null: string, c_decimal: numeric-string|null, c_decimal_not_null: numeric-string}>', $result);
    }
}
