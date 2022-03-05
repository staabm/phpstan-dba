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
            assertType('Doctrine\DBAL\Statement<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>|Doctrine\DBAL\Statement<array{email: string, 0: string}>', $stmt);

            $result = $stmt->executeQuery([]);
            assertType('Doctrine\DBAL\Result<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>|Doctrine\DBAL\Result<array{email: string, 0: string}>', $result);

            $result = $stmt->execute([]);
            assertType('Doctrine\DBAL\Result<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>|Doctrine\DBAL\Result<array{email: string, 0: string}>', $result);

            $fetch = $result->fetchOne();
            assertType('int<-32768, 32767>|string|false', $fetch);

            $fetch = $result->fetchAssociative();
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false', $fetch);
        }
    }

    public function doBar(Connection $conn)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $conn->query($query);
            assertType('Doctrine\DBAL\Result<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>|Doctrine\DBAL\Result<array{email: string, 0: string}>', $result);
        }
    }
}
