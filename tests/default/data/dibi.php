<?php

namespace DibiMysqlTests;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function fetch(\Dibi\Connection $connection)
    {
        $row = $connection->fetch('SELECT email, adaid FROM ada where adaid = %i', 1);
        assertType('array{email: string, adaid: int<-32768, 32767>}|null', $row);

        $row = $connection->fetch('SELECT c_datetime FROM typemix');
        assertType('array{c_datetime: DateTimeImmutable|null}|null', $row);
    }

    public function fetchAll(\Dibi\Connection $connection)
    {
        $row = $connection->fetchAll('SELECT email, adaid FROM ada');
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $row);
    }

    public function fetchPairs(\Dibi\Connection $connection)
    {
        $row = $connection->fetchPairs('SELECT adaid, email FROM ada');
        assertType('array<int<-32768, 32767>, string>', $row);
    }

    public function fetchSingle(\Dibi\Connection $connection)
    {
        $row = $connection->fetchSingle('SELECT email FROM ada');
        assertType('string|null', $row);

        $row = $connection->fetchSingle('SELECT max(adaid) FROM ada');
        assertType('int<-32768, 32767>|null', $row);
    }

}
