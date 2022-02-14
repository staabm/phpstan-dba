<?php

namespace PdoUnionResult;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Foo
{
    public function doFoo(Connection $conn)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $stmt = $conn->prepare($query);
            assertType('Doctrine\DBAL\Result<array{email: string, 0: string, int<-128, 127>, 3: int<-128, 127>}>', $stmt);

            $result = $stmt->executeQuery([]);
            assertType('Doctrine\DBAL\Result<array{email: string, 0: string, int<-128, 127>, 3: int<-128, 127>}>', $result);
            $result = $stmt->execute([]);
            assertType('Doctrine\DBAL\Result<array{email: string, 0: string, int<-128, 127>, 3: int<-128, 127>}>', $result);
        }
    }

    public function doBar(Connection $conn)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $conn->query($query);
            assertType('Doctrine\DBAL\Result<array{email: string, 0: string, int<-128, 127>, 3: int<-128, 127>}>', $result);
        }
    }
}
