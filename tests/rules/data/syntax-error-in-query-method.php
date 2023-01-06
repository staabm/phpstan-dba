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
        $pdo->query('SELECT * FROM unknown_table', PDO::FETCH_ASSOC);
    }

    public function incompleteQuery(PDO $pdo, string $tableName)
    {
        $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM '.$tableName.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function syntaxErrorInQueryUnion(PDO $pdo)
    {
        $add = '';
        if (rand(0, 1)) {
            $add .= " WHERE email='my_other_table'";
        }

        $pdo->query('SELECT email, adaid GROUP BY xy FROM ada '.$add.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function queryUnion(PDO $pdo)
    {
        $add = '';
        if (rand(0, 1)) {
            $add .= " WHERE email='my_other_table'";
        }

        $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada '.$add.' LIMIT 1', PDO::FETCH_ASSOC);
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

    public function conditionalSyntaxError(PDO $pdo)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=1';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=1';
        }

        $pdo->query($query);
    }

    public function validPrepare(PDO $pdo)
    {
        $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid=?');
    }

    public function conditionalSyntaxErrorInQueryUnion(PDO $pdo)
    {
        $add = "WHERE email='my_other_table'";
        if (rand(0, 1)) {
            $add = 'GROUP BY xy';
        }

        $pdo->query('SELECT email, adaid FROM ada '.$add.' LIMIT 1', PDO::FETCH_ASSOC);
    }

    public function bug442(PDO $pdo, string $table)
    {
        $pdo->query("SELECT * FROM `$table`");
    }

    public function testDeleteUpdateInsert(PDO $pdo)
    {
        $pdo->query('DELETE from ada');
        $pdo->query('UPDATE ada set email = ""'); // pgsql-only syntax error
        $pdo->query('INSERT into ada SET email="sdf"'); // pgsql-only syntax error
    }

    public function testInvalidDeleteUpdateInsert(PDO $pdo)
    {
        $pdo->query('DELETE from adasfd');
        $pdo->query('UPDATE adasfd SET email = ""');
        $pdo->query('INSERT into adasfd SET email="sdf"');
        $pdo->query('REPLACE into adasfd SET email="sdf"');
    }

}
