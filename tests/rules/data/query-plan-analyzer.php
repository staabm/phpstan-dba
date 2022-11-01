<?php

namespace QueryPlanAnalyzerTest;

use Doctrine\DBAL\Connection;
use PDO;

class Foo
{
    public function noindex(PDO $pdo): void
    {
        $pdo->query("SELECT * FROM `ada` WHERE email = 'test@example.com';");
    }

    public function noindexDbal(Connection $conn): void
    {
        $conn->executeQuery("SELECT *,adaid FROM `ada` WHERE email = 'test@example.com';");
    }

    public function noindexPreparedDbal(Connection $conn, string $email): void
    {
        $conn->executeQuery('SELECT * FROM ada WHERE email = ?', [$email]);
        $conn->executeQuery('SELECT * FROM ada WHERE email = ?  LIMIT 5 OFFSET 2', [$email]);
    }

    public function noindexPreparedDbalWithLimitAndOffset(Connection $conn, string $email): void
    {
        $conn->executeQuery('SELECT * FROM ada WHERE email = ?  LIMIT ? OFFSET ?', [$email, 27, 15], [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]);
    }

    public function syntaxError(Connection $conn): void
    {
        $conn->executeQuery('SELECT FROM WHERE');
    }

    public function indexed(PDO $pdo, int $adaid): void
    {
        $pdo->query('SELECT * FROM `ada` WHERE adaid = '.$adaid);
    }

    public function indexedPrepared(Connection $conn, int $adaidl): void
    {
        $conn->executeQuery('SELECT * FROM ada WHERE adaid = ?', [$adaidl]);
    }

    public function writes(PDO $pdo, int $adaid): void
    {
        $pdo->query('UPDATE `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('INSERT INTO `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('REPLACE INTO `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('DELETE FROM `ada` WHERE adaid = '.$adaid);
    }

    public function unknownQuery(Connection $conn, string $query): void
    {
        $conn->executeQuery($query);
    }

    public function nonSimulatableQuery(Connection $conn, $email): void
    {
        $conn->executeQuery('SELECT * FROM ada WHERE email = ' . $email);
    }

    public function unknownConstant(Connection $conn): void
    {
        $conn->executeQuery('SELECT * FROM ada WHERE adaid = ?', [CONSTANT_DOES_NOT_EXIST]);
    }

    public function bug442(Connection $conn, string $table)
    {
        // just make sure we don't fatal error
        $conn->fetchAllAssociative("SELECT * FROM `$table`");

        $query = 'SELECT email, adaid FROM '. $table .' WHERE adaid = ?';
        $conn->fetchAssociative($query, [1]);

        $query = "SELECT email, adaid FROM `$table` WHERE adaid = ?";
        $conn->fetchAssociative($query, [1]);
    }
}
