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
        $conn->executeQuery("SELECT * FROM ada WHERE email = ?", [$email]);
    }

    public function syntaxError(Connection $conn): void
    {
        $conn->executeQuery("SELECT FROM WHERE");
    }

    public function indexed(PDO $pdo, int $adaid): void
    {
        $pdo->query('SELECT * FROM `ada` WHERE adaid = '.$adaid);
    }
}
