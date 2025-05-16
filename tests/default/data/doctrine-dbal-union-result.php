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
            $result = $stmt->executeQuery([]);
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false', $result->fetchAssociative());

            $result = $stmt->execute([]);
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false', $result->fetchAssociative());

            $fetch = $result->fetchOne();
            assertType('int<-32768, 32767>|string|false', $fetch);

            $fetch = $result->fetchAssociative();
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false', $fetch);
        }
    }
}
