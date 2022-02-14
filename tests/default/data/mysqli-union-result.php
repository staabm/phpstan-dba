<?php

namespace PdoUnionResult;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function doBar(mysqli $mysqli)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $mysqli->query($query);
            assertType('mysqli_result<array{adaid: int<0, 4294967295>}>|mysqli_result<array{email: string}>', $result);
        }
    }
}
