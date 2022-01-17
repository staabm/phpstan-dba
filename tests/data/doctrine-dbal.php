<?php

namespace DoctrineDbalTest;

use Doctrine\DBAL\Driver\Connection;
use function PHPStan\Testing\assertType;

class Foo
{
    public function foo(Connection $conn)
    {
        $result = $conn->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $result);
    }
}
