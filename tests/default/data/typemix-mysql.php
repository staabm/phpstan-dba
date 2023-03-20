<?php

namespace TypemixTest;

use mysqli;
use PDO;
use function PHPStan\Testing\assertType;

class TypemixMysql
{
    private const MYSQL_DATATYPES = 'array{pid: int<0, 4294967295>, c_char5: string, c_varchar255: string, c_varchar25: string|null, c_varbinary255: string, c_varbinary25: string|null, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_year: int<0, 2155>|null, c_tiny_text: string|null, c_medium_text: string|null, c_text: string|null, c_long_text: string|null, c_enum: string, c_set: string, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_tinyint: int<-128, 127>, c_smallint: int<-32768, 32767>, c_mediumint: int<-8388608, 8388607>, c_bigint: int, c_double: float, c_real: float, c_float: float, c_boolean: int<-128, 127>, c_blob: string, c_tinyblob: string, c_mediumblog: string, c_longblob: string, c_unsigned_tinyint: int<0, 255>, c_unsigned_int: int<0, 4294967295>, c_unsigned_smallint: int<0, 65535>, c_unsigned_mediumint: int<0, 16777215>, c_unsigned_bigint: int<0, max>, c_json: string|null, c_json_not_null: string, c_decimal: numeric-string|null, c_decimal_not_null: numeric-string}';

    public function typemixMysqli(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT * FROM typemix');
        assertType('mysqli_result<' . self::MYSQL_DATATYPES . '>', $result);
    }

    public function typemixPdoMysql(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);
        assertType('PDOStatement<' . self::MYSQL_DATATYPES . '>', $stmt);
    }
}
