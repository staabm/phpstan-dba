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
}
