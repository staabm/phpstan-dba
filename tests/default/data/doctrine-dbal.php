<?php

namespace DoctrineDbalTest;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use function PHPStan\Testing\assertType;
use staabm\PHPStanDba\Tests\Fixture\StringableObject;

class Foo
{
    public function resultFetching(Connection $conn)
    {
        $result = $conn->executeQuery('SELECT email, adaid FROM ada');

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

    public function executeQuery(Connection $conn, array $types, QueryCacheProfile $qcp)
    {
        $result = $conn->executeQuery('SELECT email, adaid FROM ada WHERE adaid = ?', [1]);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result->fetchAssociative());

        $result = $conn->executeCacheQuery('SELECT email, adaid FROM ada WHERE adaid = ?', [1], $types, $qcp);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result->fetchAssociative());

        $result = $conn->executeQuery('SELECT email, adaid FROM ada');
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result->fetchAssociative());
    }

    public function executeStatement(Connection $conn, int $adaid)
    {
        $stmt = $conn->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        $result = $stmt->execute([$adaid]);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result->fetchAssociative());

        $stmt = $conn->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        $result = $stmt->executeQuery([$adaid]);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result->fetchAssociative());
    }

    public function fetchAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAssociative($query, [1]);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchAssociative($query);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $fetchResult);
    }

    public function fetchNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchNumeric($query, [1]);
        assertType('array{string, int<-32768, 32767>}|false', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchNumeric($query);
        assertType('array{string, int<-32768, 32767>}|false', $fetchResult);
    }

    public function iterateAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateAssociative($query, [1]);
        assertType('Traversable<int, array{email: string, adaid: int<-32768, 32767>}>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->iterateAssociative($query);
        assertType('Traversable<int, array{email: string, adaid: int<-32768, 32767>}>', $fetchResult);
    }

    public function iterateNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateNumeric($query, [1]);
        assertType('Traversable<int, array{string, int<-32768, 32767>}>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->iterateNumeric($query);
        assertType('Traversable<int, array{string, int<-32768, 32767>}>', $fetchResult);
    }

    public function iterateColumn(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateColumn($query, [1]);
        assertType('Traversable<int, string>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->iterateColumn($query);
        assertType('Traversable<int, string>', $fetchResult);
    }

    public function iterateKeyValue(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->iterateKeyValue($query, [1]);
        assertType('Traversable<string, int<-32768, 32767>>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->iterateKeyValue($query);
        assertType('Traversable<string, int<-32768, 32767>>', $fetchResult);
    }

    public function fetchOne(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchOne($query, [1]);
        assertType('string|false', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchOne($query);
        assertType('string|false', $fetchResult);
    }

    public function fetchFirstColumn(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchFirstColumn($query, [1]);
        assertType('list<string>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchFirstColumn($query);
        assertType('list<string>', $fetchResult);
    }

    public function fetchAllNumeric(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllNumeric($query, [1]);
        assertType('list<array{string, int<-32768, 32767>}>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchAllNumeric($query);
        assertType('list<array{string, int<-32768, 32767>}>', $fetchResult);
    }

    public function fetchAllAssociative(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllAssociative($query, [1]);
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchAllAssociative($query);
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $fetchResult);
    }

    public function fetchAllKeyValue(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
        $fetchResult = $conn->fetchAllKeyValue($query, [1]);
        assertType('array<string, int<-32768, 32767>>', $fetchResult);

        $query = 'SELECT email, adaid FROM ada';
        $fetchResult = $conn->fetchAllKeyValue($query);
        assertType('array<string, int<-32768, 32767>>', $fetchResult);
    }

    public function fetchStringable(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE email = ?';
        $fetchResult = $conn->fetchAllKeyValue($query, [new StringableObject()]);
        assertType('array<string, int<-32768, 32767>>', $fetchResult);
    }

    public function datetimeParameter(Connection $conn)
    {
        $query = 'SELECT count(*) FROM typemix WHERE c_datetime = ?';
        $fetchResult = $conn->fetchOne($query, [date('Y-m-d H:i:s', strtotime('-3hour'))]);
        assertType('int|false', $fetchResult);
    }

    public function dateParameter(Connection $conn)
    {
        $query = 'SELECT count(*) FROM typemix WHERE c_date = ?';
        $fetchResult = $conn->fetchOne($query, [date('Y-m-d', strtotime('-3hour'))]);
        assertType('int|false', $fetchResult);
    }

    public function customTypeParameters(Connection $conn)
    {
        $result = $conn->executeQuery(
            'SELECT count(*) AS c FROM typemix WHERE c_datetime=:last_dt',
            ['dt' => new \DateTime()], ['dt' => Types::DATETIME_MUTABLE]
        );
        assertType('Doctrine\DBAL\Result', $result);
    }

    /**
     * @param list<positive-int> $idsToUpdate
     */
    public function fetchFromListParam(Connection $conn, array $idsToUpdate)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (?)';
        $fetchResult = $conn->fetchOne($query, $idsToUpdate);
        assertType('string|false', $fetchResult);
    }

    /**
     * @param array<positive-int> $idsToUpdate
     */
    public function fetchFromArrayParam(Connection $conn, array $idsToUpdate)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN(?)';
        $fetchResult = $conn->fetchOne($query, $idsToUpdate);
        assertType('string|false', $fetchResult);
    }

}
