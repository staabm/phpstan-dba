<?php

namespace UnresolvablePdoStatementTest;

use PDO;

class Foo
{
    public function mixedParam(PDO $pdo, $mixed)
    {
        $query = 'SELECT email FROM ada WHERE gesperrt=:gesperrt';
        $stmt = $pdo->prepare($query);
        $stmt->execute([':gesperrt' => $mixed]);
    }
}
