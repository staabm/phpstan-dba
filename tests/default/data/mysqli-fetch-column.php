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
        assertType('string|false', $col0);

        // explicit column
        $col1 = $result->fetch_column(1);
        assertType('int<-32768, 32767>|false', $col1);

        // out-of-range column
        $col999 = $result->fetch_column(999);
        assertType('false', $col999);

        // multiple possible columns
        $col0_or_1 = $result->fetch_column(rand() ? 0 : 1);
        assertType('int<-32768, 32767>|string|false', $col0_or_1);

        // range of possible columns
        for($i=0; $i<3; $i++) {
            $col0_to_2 = mysqli_fetch_column($result, $i);
            assertType('float|int<-32768, 32767>|string|false', $col0_to_2);
        }

        // Function added in PHP 8.1
        if (PHP_VERSION_ID < 80100) {
            $unknown = $result->fetch_column();
            // TODO PHPStan shouldn't recognize this method at all <8.1. Fix stubs?
            assertType('float|int|string|false|null', $unknown);
        }

        // explict column (named arg)
        $col1 = $result->fetch_column(column: 1);
        assertType('int<-32768, 32767>|false', $col1);

        // out-of-range column (named arg)
        $col999 = $result->fetch_column(column: 999);
        assertType('false', $col999);

        // multiple possible columns (named arg)
        $col0_or_1 = $result->fetch_column(column: rand() ? 0 : 1);
        assertType('int<-32768, 32767>|string|false', $col0_or_1);
    }

    public function proceduralFetchColumn(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT c_varchar255, c_smallint, c_float FROM typemix');

        // default column
        $col0 = mysqli_fetch_column($result);
        assertType('string|false', $col0);

        // explicit column
        $col1 = mysqli_fetch_column($result, 1);
        assertType('int<-32768, 32767>|false', $col1);

        // out-of-range column
        $col999 = mysqli_fetch_column($result, 999);
        assertType('false', $col999);

        // multiple possible columns
        $col0_or_1 = mysqli_fetch_column($result, rand() ? 0 : 1);
        assertType('int<-32768, 32767>|string|false', $col0_or_1);

        // range of possible columns
        for($i=0; $i<3; $i++) {
            $col0_to_2 = mysqli_fetch_column($result, $i);
            assertType('float|int<-32768, 32767>|string|false', $col0_to_2);
        }

        // Function added in PHP 8.1
        if (PHP_VERSION_ID < 80100) {
            $unknown = mysqli_fetch_column($result);
            // TODO PHPStan shouldn't recognize this function at all <8.1. Fix stubs?
            assertType('float|int|string|false|null', $unknown);
        }

        // explicit column (named arg)
        $col1 = mysqli_fetch_column($result, column: 1);
        assertType('int<-32768, 32767>|false', $col1);

        // out-of-range column (named arg)
        $col999 = mysqli_fetch_column($result, column: 999);
        assertType('false', $col999);

        // multiple possible columns (named arg)
        $col0_or_1 = mysqli_fetch_column($result, column: rand() ? 0 : 1);
        assertType('int<-32768, 32767>|string|false', $col0_or_1);
    }
}
