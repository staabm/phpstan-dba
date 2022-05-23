<?php

namespace QueryPlanAnalyzerTest;

use PDO;

class Foo {
    public function noindex(PDO $pdo, int $adaid):void
    {
        $pdo->query("SELECT * FROM `ada` WHERE email = 'test@example.com';");
    }

    public function indexed(PDO $pdo, int $adaid):void {
        $pdo->query("SELECT * FROM `ada` WHERE adaid = ". $adaid);
    }
}
