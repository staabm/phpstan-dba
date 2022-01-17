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

        $numericFetch = $result->fetchNumeric();
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}', $numericFetch);

        $numericFetch = $result->fetchAssociative();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $numericFetch);

        $numericFetch = $result->fetchAllNumeric();
        assertType('array<int, array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $numericFetch);

        $numericFetch = $result->fetchAllAssociative();
        assertType('array<int, array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $numericFetch);
    }
}
