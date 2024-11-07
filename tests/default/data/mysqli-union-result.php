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

            foreach ($result as $row) {
                assertType('int<-32768, 32767>', $row['adaid']);
                assertType('string', $row['email']);
            }
        }
    }
}
