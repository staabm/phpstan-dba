<?php

namespace SyntaxErrorInDibiPreparedStatementMethodRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;
use staabm\PHPStanDba\Tests\Fixture\PreparedStatement;

class Foo
{

    public function testQuery(\Dibi\Connection $conn)
    {
        $conn->query('SELECT email adaid WHERE gesperrt FROM ada');
        $conn->query('SELECT email,adaid FROM ada');
    }

    public function testFetch(\Dibi\Connection $conn)
    {
        $conn->fetch('SELECT email adaid WHERE gesperrt FROM ada');
        $conn->fetch('SELECT email,adaid FROM ada');
    }

    public function testFetchSingle(\Dibi\Connection $conn)
    {
        $conn->fetchSingle('SELECT email adaid WHERE gesperrt FROM ada');
        $conn->fetchSingle('SELECT email,adaid FROM ada');
        $conn->fetchSingle('SELECT email FROM ada');
    }

    public function testFetchPairs(\Dibi\Connection $conn)
    {
        $conn->fetchPairs('SELECT email adaid WHERE gesperrt FROM ada');
        $conn->fetchPairs('SELECT email FROM ada');
        $conn->fetchPairs('SELECT email,adaid FROM ada');
    }

    public function testPlaceholders(\Dibi\Connection $conn)
    {
        $conn->fetchPairs('SELECT email FROM ada where email = %s');
        $conn->fetchPairs('SELECT email FROM ada where email = ""', 1);
        $conn->fetchPairs('SELECT email FROM ada where email = %s', 'email@github.com');
    }

    /* phpstan-dba does not yet support writable queries
    public function testDeleteUpdateInsert(\Dibi\Connection $conn)
    {
        $conn->query('DELETE from adasfd');
        $conn->query('DELETE from ada');
        $conn->query('UPDATE ada set email = ""');
        $conn->query('INSERT into ada', [
            'email' => 'sdf',
        ]);
        $conn->query('INSERT into %n', 'ada', [
            'email' => 'sdf',
        ]);
    }
    */

}
