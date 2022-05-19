<?php

namespace PdoMysqlTests;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function execute(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email <=> :email');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':email' => null]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);
    }

    public function mysqlTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{pid: int<0, 4294967295>, c_char5: string, c_varchar255: string, c_varchar25: string|null, c_varbinary255: string, c_varbinary25: string|null, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_year: int<0, 2155>|null, c_tiny_text: string|null, c_medium_text: string|null, c_text: string|null, c_long_text: string|null, c_enum: string, c_set: string, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_tinyint: int<-128, 127>, c_smallint: int<-32768, 32767>, c_mediumint: int<-8388608, 8388607>, c_bigint: int, c_double: float, c_real: float, c_boolean: int<-128, 127>, c_blob: string, c_tinyblob: string, c_mediumblog: string, c_longblob: string, c_unsigned_tinyint: int<0, 255>, c_unsigned_int: int<0, 4294967295>, c_unsigned_smallint: int<0, 65535>, c_unsigned_mediumint: int<0, 16777215>, c_unsigned_bigint: int<0, max>, c_json: string|null, c_json_not_null: string}>', $stmt);
    }

    public function aggregateFunctions(PDO $pdo)
    {
        $query = 'SELECT MAX(adaid), MIN(adaid), COUNT(adaid), AVG(adaid) FROM ada WHERE adaid = 1';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{MAX(adaid): int<-32768, 32767>|null, MIN(adaid): int<-32768, 32767>|null, COUNT(adaid): int, AVG(adaid): float|null}>', $stmt);
    }

    public function placeholderInDataPrepared(PDO $pdo)
    {
        // double quotes within the query
        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);

        // single quotes within the query
        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $stmt);
    }

    public function placeholderInDataQuery(PDO $pdo)
    {
        // double quotes within the query
        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }
}
