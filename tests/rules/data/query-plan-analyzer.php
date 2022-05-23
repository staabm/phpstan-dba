<?php

namespace QueryPlanAnalyzerTest;

use Doctrine\DBAL\Connection;
use PDO;

class Foo
{
    public function noindex(PDO $pdo, int $adaid): void
    {
        $pdo->query("SELECT * FROM `ada` WHERE email = 'test@example.com';");
    }

    public function noindexDbal(Connection $conn, int $adaid): void
    {
        $conn->executeQuery("SELECT * FROM `ada` WHERE email = 'test@example.com';");
    }

    public function indexed(PDO $pdo, int $adaid): void
    {
        $pdo->query('SELECT * FROM `ada` WHERE adaid = '.$adaid);
    }
}
