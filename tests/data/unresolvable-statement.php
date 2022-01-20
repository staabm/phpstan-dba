<?php

namespace UnresolvableQueryTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function mixedParam(Connection $connection, $mixed)
    {
        $connection->preparedQuery('SELECT email FROM ada WHERE gesperrt=:gesperrt', [
            'gesperrt' => $mixed,
        ]);
    }
}
