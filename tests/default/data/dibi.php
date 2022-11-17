<?php

namespace DibiMysqlTests;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function fetch(\Dibi\Connection $connection)
    {
        $row = $connection->fetch('SELECT email, adaid FROM ada where email = %s', 'email@github.com');
        assertType('array{email: string, adaid: int<-32768, 32767>}|null', $row);
    }

    public function fetchAll(\Dibi\Connection $connection)
    {
        $row = $connection->fetchAll('SELECT email, adaid FROM ada');
        assertType('array<int, array{email: string, adaid: int<-32768, 32767>}>', $row);
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
    }

}
