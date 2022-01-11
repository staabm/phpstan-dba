<?php

namespace SyntaxErrorInPreparedStatementMethodRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function syntaxError(Connection $connection)
    {
        $connection->preparedQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function preparedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = ?', [1]);

        $connection->preparedQuery('
            SELECT email, adaid
            FROM ada
            WHERE gesperrt = ? AND email LIKE ?
            LIMIT        1
        ', [1, '%@example%']);
    }

    public function preparedNamedParams(Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :gesperrt', ['gesperrt' => 1]);
    }
}
