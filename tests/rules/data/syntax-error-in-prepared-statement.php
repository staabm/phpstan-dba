<?php

namespace SyntaxErrorInPreparedStatementMethodRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;
use staabm\PHPStanDba\Tests\Fixture\PreparedStatement;

class Foo
{
    public function syntaxError(Connection $connection)
    {
        $connection->preparedQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function syntaxErrorInConstruct()
    {
        $stmt = new PreparedStatement('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function syntaxErrorOnKnownParamType(Connection $connection, int $i, bool $bool)
    {
        $connection->preparedQuery('
            SELECT email adaid
            WHERE gesperrt = ? AND email LIKE ?
            FROM ada
            LIMIT        1
        ', [$i, '%@example.com']);

        $connection->preparedQuery('
            SELECT email adaid
            WHERE gesperrt = ? AND email LIKE ?
            FROM ada
            LIMIT        1
        ', [$bool, '%@example.com']);
    }

    public function noErrorOnMixedParams(Connection $connection, $unknownType)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ? AND email LIKE ?
            LIMIT        1
        ', [1, $unknownType]);
    }

    public function noErrorOnPlaceholderInLimit(Connection $connection, int $limit)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
        ', [':gesperrt' => 1, ':limit' => $limit]);
    }

    public function noErrorOnPlaceholderInOffsetAndLimit(Connection $connection, int $offset, int $limit)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?,  ?
        ', [1, $offset, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT   :offset,     :limit
        ', [':gesperrt' => 1, ':offset' => $offset, ':limit' => $limit]);
    }

    public function preparedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = ?', [1]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ? AND email LIKE ?
            LIMIT        1
        ', [1, '%@example%']);
    }

    public function preparedNamedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :gesperrt', ['gesperrt' => 1]);
    }

    public function camelCase(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :myGesperrt', ['myGesperrt' => 1]);
    }

    public function syntaxErrorInDoctrineDbal(\Doctrine\DBAL\Connection $conn, $types, \Doctrine\DBAL\Cache\QueryCacheProfile $qcp)
    {
        $conn->executeQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
        $conn->executeCacheQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', [], $types, $qcp);
        $conn->executeStatement('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function conditionalSyntaxError(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=?';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=?';
        }

        $connection->preparedQuery($query, [1]);
    }

    public function placeholderValidation(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=?';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=?';
        }

        $connection->preparedQuery($query, [':gesperrt' => 1]);
    }

    public function samePlaceholderMultipleTimes(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada
            WHERE (gesperrt=:gesperrt AND freigabe1u1=1) OR (gesperrt=:gesperrt AND freigabe1u1=0)';
        $connection->preparedQuery($query, [':gesperrt' => 1]);
    }

    public function noErrorOnTrailingSemicolon(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada;';
        $connection->preparedQuery($query, []);
    }

    public function noErrorOnPlaceholderInData(Connection $connection)
    {
        $query = "SELECT adaid FROM ada WHERE email LIKE 'hello?%'";
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $connection->preparedQuery($query, []);

        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $connection->preparedQuery($query, []);

        $query = "SELECT adaid FROM ada WHERE email LIKE 'some strange string - :gesperrt it is'";
        $connection->preparedQuery($query, []);
    }

    public function arrayParam(Connection $connection)
    {
        $query = 'SELECT email FROM ada WHERE adaid IN (:adaids)';
        $connection->preparedQuery($query, ['adaids' => [1, 2, 3]]);
    }

    public function noErrorInBug156(Connection $connection, array $idsToUpdate, string $time)
    {
        $query = 'UPDATE package SET indexedAt=:indexed WHERE id IN (:ids) AND (indexedAt IS NULL OR indexedAt <= crawledAt)';
        $connection->preparedQuery($query, [
            'ids' => $idsToUpdate,
            'indexed' => $time,
        ]);
    }

    /*
    intentionally left empty - do not remove whitespace





















    intentionally left empty - do not remove whitespace
    */

    public function noErrorOnBug175(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET '.$offset.'
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET '.((int) $offset).'
        ', [1, $limit]);
    }

    public function noErrorOnOffsetAfterLimit(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET ?
        ', [1, $limit, $offset]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
            OFFSET :offset
        ', [':gesperrt' => 1, ':limit' => $limit, ':offset' => $offset]);
    }

    public function noErrorOnLockedRead(Connection $connection, int $limit, int $offset)
    {
        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            FOR UPDATE
        ', [1]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            FOR UPDATE
        ', [1, $limit]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ?
            LIMIT        ?
            OFFSET ?
            FOR UPDATE
        ', [1, $limit, $offset]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = :gesperrt
            LIMIT        :limit
            OFFSET :offset
            FOR SHARE
        ', [':gesperrt' => 1, ':limit' => $limit, ':offset' => $offset]);
    }

    public function noErrorInBug174(Connection $connection, string $name, ?int $gesperrt = null, ?int $adaid = null)
    {
        $sql = 'SELECT adaid FROM ada WHERE email = :name';
        $args = ['name' => $name];
        if (null !== $gesperrt) {
            $sql .= ' AND gesperrt = :gesperrt';
            $args['gesperrt'] = $gesperrt;
        }

        $connection->preparedQuery($sql, $args);
    }

    public function conditionalNumberOfPlaceholders(Connection $connection, string $name, ?int $gesperrt = null, ?int $adaid = null)
    {
        $sql = 'SELECT adaid FROM ada WHERE email = :name';
        $args = [];
        if (null !== $gesperrt) {
            $sql .= ' AND gesperrt = :gesperrt';
            $args['gesperrt'] = $gesperrt;
        }

        $connection->preparedQuery($sql, $args);
    }

    public function noErrorOnTrailingSemicolonAndWhitespace(Connection $connection)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada;  ';
        $connection->preparedQuery($query, []);
    }

    public function errorOnQueryWithoutArgs(\Doctrine\DBAL\Connection $connection)
    {
        $query = 'SELECT email adaid gesperrt freigabe1u1 FROM ada';
        $connection->executeQuery($query);
    }

    public function preparedNamedParamsSubstitution(Connection $connection)
    {
        $connection->preparedQuery('SELECT email FROM ada WHERE email = :param OR email = :parameter', ['param' => 'abc', 'parameter' => 'def']);
        $connection->preparedQuery('INSERT into ada(adaid, gesperrt, email,freigabe1u1) values(:adaid, :gesperrt, :email, :freigabe1u1)', ['adaid' => 1, 'gesperrt' => 0, 'email' => 'test@github.com', 0]);
    }

    public function bug442(Connection $conn, string $table)
    {
        $conn->executeQuery("SELECT * FROM `$table`");
    }

    public function testDeleteUpdateInsert(Connection $conn)
    {
        $conn->query('DELETE from ada');
        $conn->query('UPDATE ada set email = ""');
        $conn->query('INSERT into ada', [
            'email' => 'sdf',
        ]);
    }

    public function testInvalidDeleteUpdateInsert(Connection $conn)
    {
        $conn->query('DELETE from adasfd');
        $conn->query('UPDATE from adasfd');
        $conn->query('REPLACE into adasfd', [
            'email' => 'sdf',
        ]);
        $conn->query('INSERT into adasfd', [
            'email' => 'sdf',
        ]);
    }

    /**
     * @return string|false
     */
    private function returnsUnion() {}

    public function bug458(Connection $conn)
    {
        $table = $this->returnsUnion();
        $conn->executeQuery('SELECT * FROM ' . $table . ' LIMIT 1');
    }

    /**
     * @param array<int> $ids
     */
    protected function conditionalUnnamedPlaceholder(Connection $connection, array $ids, int $adaid): void
    {
        $values = [];
        $query = 'SELECT email, adaid FROM ada';

        $query .= ' AND adaid IN (' . someCall($ids) . ')';
        $values = array_merge($values, $ids);

        if (rand(0, 1) === 1) {
            $query .= ' AND email = ?';
            $values[] = 'test@example.org';
        }

        $query .= ' ORDER BY adaid';

        $connection->preparedQuery($query, $values);
    }
}
