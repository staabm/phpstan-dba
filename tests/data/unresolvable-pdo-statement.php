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

    public function noErrorOnMixedQuery(PDO $pdo, $mixed)
    {
        // we should not report a error here, as this is like a call somewhere in between software layers
        // which don't know anything about the actual query
        $stmt = $pdo->prepare($mixed);
        $stmt->execute([]);
    }
}
