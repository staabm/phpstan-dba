<?php

namespace UnresolvableQueryInMethodTest;

use PDO;

class Foo
{
    public function mixedParam(PDO $pdo, $mixed)
    {
        $pdo->query('SELECT email FROM ada WHERE gesperrt='.$mixed);
    }

    public function mixedParam2(PDO $pdo, $mixed)
    {
        $query = 'SELECT email FROM ada WHERE gesperrt='.$mixed;
        $pdo->query($query);
    }

    public function noErrorOnMixedQuery(PDO $pdo, $mixed)
    {
        // we should not report a error here, as this is like a call somewhere in between software layers
        // which don't know anything about the actual query
        $pdo->query($mixed);
    }
}
