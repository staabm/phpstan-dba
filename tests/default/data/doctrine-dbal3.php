<?php

namespace DoctrineDbal3Test;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Foo
{
    public function resultFetching(Connection $conn)
    {
        $result = $conn->query('SELECT email, adaid FROM ada');

        $columnCount = $result->columnCount();
        assertType('2', $columnCount);

        $fetch = $result->fetchOne();
        assertType('string|false', $fetch);

        $fetch = $result->fetchNumeric();
        assertType('array{string, int<-32768, 32767>}|false', $fetch);

        $fetch = $result->fetchFirstColumn();
        assertType('list<string>', $fetch);

        $fetch = $result->fetchAssociative();
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $fetch);

        $fetch = $result->fetchAllNumeric();
        assertType('list<array{string, int<-32768, 32767>}>', $fetch);

        $fetch = $result->fetchAllAssociative();
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $fetch);

        $fetch = $result->fetchAllKeyValue();
        assertType('array<string, int<-32768, 32767>>', $fetch);

        $fetch = $result->iterateNumeric();
        assertType('Traversable<int, array{string, int<-32768, 32767>}>', $fetch);

        $fetch = $result->iterateAssociative();
        assertType('Traversable<int, array{email: string, adaid: int<-32768, 32767>}>', $fetch);

        $fetch = $result->iterateColumn();
        assertType('Traversable<int, string>', $fetch);

        $fetch = $result->iterateKeyValue();
        assertType('Traversable<string, int<-32768, 32767>>', $fetch);
    }

    public function unionQueriesResult(Connection $conn)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $conn->query($query);
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false', $result->fetchAssociative());
        }
    }
}