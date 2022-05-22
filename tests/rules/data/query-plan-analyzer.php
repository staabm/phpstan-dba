<?php

namespace QueryPlanAnalyzerTest;

class Foo {
    public function doFoo(\PDO $pdo) {
        $pdo->query("SELECT * FROM `ada` WHERE email = 'test@example.com';");
    }
}
