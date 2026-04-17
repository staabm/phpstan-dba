<?php

namespace DoctrineKeyValueStyleRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function noErrorWithNamedParameters(Connection $conn)
    {
        $conn->assembleOneArray(cols: ['email' => 'foo'], tableName: 'ada');
    }

    public function errorWithNamedParameters(Connection $conn)
    {
        $conn->assembleOneArray(cols: ['not_a_column' => 'foo'], tableName: 'ada');
    }
}
