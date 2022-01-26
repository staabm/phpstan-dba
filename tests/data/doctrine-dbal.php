<?php

namespace DoctrineDbalTest;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Foo
{
    public function foo(Connection $conn)
    {
        $result = $conn->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $result);

        $columnCount = $result->columnCount();
        assertType('4', $columnCount);

        $fetch = $result->fetchOne();
        assertType('string', $fetch);

        $fetch = $result->fetchNumeric();
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}|false', $fetch);

        $fetch = $result->fetchFirstColumn();
        assertType('array<int<0, max>, string>', $fetch);

        $fetch = $result->fetchAssociative();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $fetch);

        $fetch = $result->fetchAllNumeric();
        assertType('array<int<0, max>, array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $fetch);

        $fetch = $result->fetchAllAssociative();
        assertType('array<int<0, max>, array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $fetch);

        $fetch = $result->fetchAllKeyValue();
        assertType('array<string, int<0, 4294967295>>', $fetch);

        $fetch = $result->iterateNumeric();
        assertType('Traversable<int, array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $fetch);

        $fetch = $result->iterateAssociative();
        assertType('Traversable<int, array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $fetch);

        $fetch = $result->iterateColumn();
        assertType('Traversable<int, string>', $fetch);

        $fetch = $result->iterateKeyValue();
        assertType('Traversable<string, int<0, 4294967295>>', $fetch);
    }

    public function executeQuery(Connection $conn, array $types, QueryCacheProfile $qcp)
    {
        $stmt = $conn->executeQuery('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?', [1]);
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $stmt);

        $stmt = $conn->executeCacheQuery('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?', [1], $types, $qcp);
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $stmt);
    }

    public function executeStatement(Connection $conn, int $adaid)
    {
        $stmt = $conn->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('Doctrine\DBAL\Statement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $stmt);

        $stmt = $conn->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?');
        $result = $stmt->execute([$adaid]);
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $result);

        $stmt = $conn->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?');
        $result = $stmt->executeQuery([$adaid]);
        assertType('Doctrine\DBAL\Result<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}>', $result);
    }

    public function fetchAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAssociative($query, [1]);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $fetchResult);
    }

    public function fetchNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchNumeric($query, [1]);
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}|false', $fetchResult);
    }

    public function iterateAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateAssociative($query, [1]);
        assertType('Traversable<int, array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $fetchResult);
    }

    public function iterateNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateNumeric($query, [1]);
        assertType('Traversable<int, array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $fetchResult);
    }

    public function iterateColumn(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateColumn($query, [1]);
        assertType('Traversable<int, string>', $fetchResult);
    }

    public function iterateKeyValue(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateKeyValue($query, [1]);
        assertType('Traversable<string, int<0, 4294967295>>', $fetchResult);
    }

    public function fetchOne(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchOne($query, [1]);
        assertType('string', $fetchResult);
    }

    public function fetchFirstColumn(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchFirstColumn($query, [1]);
        assertType('array<int<0, max>, string>', $fetchResult);
    }

    public function fetchAllNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllNumeric($query, [1]);
        assertType('array<int<0, max>, array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $fetchResult);
    }

    public function fetchAllAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllAssociative($query, [1]);
        assertType('array<int<0, max>, array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $fetchResult);
    }

    public function fetchAllKeyValue(Connection $conn)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllKeyValue($query, [1]);
        assertType('array<string, int<0, 4294967295>>', $fetchResult);
    }
}
