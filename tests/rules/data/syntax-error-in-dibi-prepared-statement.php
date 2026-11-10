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

    public function testIgnoredPlaceholders(\Dibi\Connection $conn)
    {
        $conn->query('SELECT %n, %n from %n', 'email', 'dadid', 'ada');
        $conn->query('SELECT email, adaid FROM ada group by %by', 'email');
        $conn->query('SELECT email, adaid FROM ada where email = %bin group by %by', 1, 'email');
        // has syntax error but will not be caught due to unsupported placeholder
        $conn->query('SELECT %n, %n frommmm %n', 'email', 'dadid', 'ada');
    }

    public function testDeleteUpdateInsert(\Dibi\Connection $conn)
    {
        $conn->query('DELETE from ada');
        $conn->query('UPDATE ada set email = ""');
        $conn->query('INSERT into ada', [
            'email' => 'sdf',
        ]);
        $conn->query('INSERT into %n', 'ada', [
            'email' => 'sdf',
        ]);
    }
    public function testInvalidDeleteUpdateInsert(\Dibi\Connection $conn)
    {
        $conn->query('DELETE from adasfd');
        $conn->query('UPDATE adasfd SET email = ""');
        $conn->query('INSERT into adasfd SET email="sdf"');
        $conn->query('REPLACE into adasfd SET email="sdf"');
    }

}
