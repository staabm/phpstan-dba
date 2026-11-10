<?php

namespace SyntaxErrorInQueryMethodDbal3RuleTest;

use PDO;

class Foo
{
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
}