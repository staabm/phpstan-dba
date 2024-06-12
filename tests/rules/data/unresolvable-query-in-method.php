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

    public function noErrorOnStringQuery(PDO $pdo, string $query)
    {
        $pdo->query($query);
    }

    public function stringParam(PDO $pdo, string $string)
    {
        $pdo->query('SELECT email FROM ada WHERE gesperrt='.$string);
    }

    public function stringQueryFragment(PDO $pdo, string $string)
    {
        $pdo->query('SELECT email FROM ada WHERE '.$string);
    }

    public function writes(PDO $pdo, int $adaid): void
    {
        $pdo->query('UPDATE `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('INSERT INTO `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('REPLACE INTO `ada` SET email="test" WHERE adaid = '.$adaid);
        $pdo->query('DELETE FROM `ada` WHERE adaid = '.$adaid);
    }
}
