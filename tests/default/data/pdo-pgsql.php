<?php

namespace PdoTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function mysqlTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{pid: int<1, 2147483647>, c_varchar5: string, c_varchar25: string|null, c_varchar255: string, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_text: string|null, c_enum: mixed, c_bit255: int, c_bit25: int|null, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_smallint: int<-32768, 32767>, c_bigint: int, c_float: float, c_boolean: mixed}>', $stmt);
    }

    public function aggregateFunctions(PDO $pdo)
    {
        $query = 'SELECT MAX(adaid), MIN(adaid), COUNT(adaid), AVG(adaid) FROM ada WHERE adaid = 1';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{max: int<-32768, 32767>|null, min: int<-32768, 32767>|null, count: int, avg: float|null}>', $stmt);
    }
}
