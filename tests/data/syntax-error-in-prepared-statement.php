<?php

namespace SyntaxErrorInPreparedStatementMethodRuleTest;

class Foo
{
    public function syntaxError(\My\Connection $connection)
    {
        $connection->preparedQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);
    }

    public function preparedParams(\My\Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = ?', [1]);
    }

    public function preparedNamedParams(\My\Connection $connection)
    {
        $connection->preparedQuery('SELECT email, adaid FROM ada WHERE gesperrt = :gesperrt', ['gesperrt' => 1]);
    }
}
