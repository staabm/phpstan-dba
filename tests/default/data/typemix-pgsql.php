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
        assertType('mysqli_result<' . self::PGSQL_DATATYPES . '>', $result);
    }

    public function typemixPdoMysql(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);
        assertType('PDOStatement<' . self::PGSQL_DATATYPES . '>', $stmt);
    }
}
