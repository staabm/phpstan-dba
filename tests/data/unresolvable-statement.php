<?php

namespace UnresolvableQueryTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;
use staabm\PHPStanDba\Tests\Fixture\PreparedStatement;

class Foo
{
    public function syntaxError(Connection $connection, $mixed)
    {
        $connection->preparedQuery('SELECT email FROM ada WHERE gesperrt=:gesperrt', [
            'gesperrt' => $mixed,
        ]);
    }
}
