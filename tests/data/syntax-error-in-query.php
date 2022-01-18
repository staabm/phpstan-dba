<?php

namespace SyntaxErrorInQueryMethodRuleTest;

use PDO;

class Foo
{
    public function syntaxErrorPdoQuery(PDO $pdo)
    {
        $pdo->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

    public function syntaxErrorMysqli(\mysqli $mysqli)
    {
        $mysqli->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

    public function unknownColumn(PDO $pdo)
    {
        $pdo->query('SELECT doesNotExist, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

    public function unknownWhereColumn(PDO $pdo)
    {
        $pdo->query('SELECT * FROM ada WHERE doesNotExist=1', PDO::FETCH_ASSOC);
    }

    public function unknownOrderColumn(PDO $pdo)
    {
        $pdo->query('SELECT * FROM ada ORDER BY doesNotExist', PDO::FETCH_ASSOC);
    }

    public function unknownGroupByColumn(PDO $pdo)
    {
        $pdo->query('SELECT * FROM ada GROUP BY doesNotExist', PDO::FETCH_ASSOC);
    }

    public function unknownTable(PDO $pdo)
    {
        $pdo->query('SELECT * FROM unknownTable', PDO::FETCH_ASSOC);
    }

    public function incompleteQuery(PDO $pdo, string $tableName)
    {
        $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM '.$tableName.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function incompleteQueryUnion(PDO $pdo)
    {
        $add = '';
        if (rand(0, 1)) {
            $add .= 'my_other_table';
        }

        // XXX we might get smarter in query parsing and resolve this query at analysis time
        $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada .'.$add.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function conditionalSyntaxErrorInQueryUnion(PDO $pdo)
    {
        $add = "WHERE email='my_other_table'";
        if (rand(0, 1)) {
            $add = 'GROUP BY xy';
        }

        $pdo->query('SELECT email, adaid FROM ada '.$add.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function validQuery(PDO $pdo)
    {
        $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

    public function syntaxErrorPdoPrepare(PDO $pdo)
    {
        $pdo->prepare('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');
    }

    public function syntaxErrorDoctrineDbal(\Doctrine\DBAL\Connection $conn)
    {
        $sql = 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada';
        $conn->query($sql);
    }

    public function noErrorOnQueriesContainingPlaceholders(\Doctrine\DBAL\Connection $conn)
    {
        // errors in this scenario are reported by SyntaxErrorInPreparedStatementMethodRule only
        $conn->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid=?');
    }

    public function validPrepare(PDO $pdo)
    {
        $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid=?');
    }
}
