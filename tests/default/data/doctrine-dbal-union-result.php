<?php

namespace PdoUnionResult;

use Doctrine\DBAL\Connection;
use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function doBar(Connection $conn)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $conn->query($query);
            assertType('Doctrine\DBAL\Result<array{email: string, 0: string, int<-128, 127>, 3: int<-128, 127>}>', $result);
        }
    }
}
