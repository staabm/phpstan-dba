<?php

namespace AmbiguousUsingColumnRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function preparedStatement(Connection $connection): void
    {
        $connection->preparedQuery(
            'SELECT first.adaid
             FROM ada first
             JOIN ada second ON second.adaid = first.adaid
             JOIN ada third USING(adaid)',
            [],
        );
    }
}
