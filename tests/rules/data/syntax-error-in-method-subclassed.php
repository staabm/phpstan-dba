<?php

namespace SyntaxErrorInPreparedStatementMethodSubclassedRuleTest;

use staabm\PHPStanDba\Tests\Fixture\MySubClass;

class HelloWorld
{
    public function syntaxError()
    {
        $sub = new MySubClass();
        $sub->doQuery('SELECT with syntax error GROUPY by x');
    }

    public function preparedError(int $i)
    {
        $sub = new MySubClass();
        $sub->doQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', []);

        $sub->doQuery('
            SELECT email adaid
            WHERE gesperrt = ? AND email LIKE ?
            FROM ada
            LIMIT        1
        ', [$i, '%@example.com']);
    }
}
