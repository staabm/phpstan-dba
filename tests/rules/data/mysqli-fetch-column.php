<?php

namespace MysqliFetchColumnTest;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function fetchColumn(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT c_varchar255, c_smallint, c_float FROM typemix');

        // default column
        $col0 = $result->fetch_column();

        // valid column
        $col1 = $result->fetch_column(1);

        // out-of-range column
        $col999 = $result->fetch_column(999);

        // multiple possible columns (one invalid)
        $col0_or_999 = $result->fetch_column(rand() ? 0 : 999);

        // range of possible columns (some invalid)
        for($i=0; $i<9; $i++) {
            $col0_to_9 = $result->fetch_column($i);
        }
    }

    public function proceduralFetchColumn(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT c_varchar255, c_smallint, c_float FROM typemix');

        // default column
        $col0 = mysqli_fetch_column($result);

        // valid column
        $col1 = mysqli_fetch_column($result, 1);

        // out-of-range column
        $col999 = mysqli_fetch_column($result, 999);

        // multiple possible columns (one invalid)
        $col0_or_999 = mysqli_fetch_column($result, rand() ? 0 : 999);

        // range of possible columns (some invalid)
        for($i=0; $i<9; $i++) {
            $col0_to_9 = mysqli_fetch_column($result, $i);
            assertType('float|int<-32768, 32767>|string|false', $col0_to_2);
        }
    }
}
