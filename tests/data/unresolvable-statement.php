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

    public function noErrorOnMixedQuery(Connection $connection, $mixed)
    {
        // we should not report a error here, as this is like a call somewhere in between software layers
        // which don't know anything about the actual query
        $connection->preparedQuery($mixed, []);
    }
}
