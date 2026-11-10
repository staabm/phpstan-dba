<?php // lint >= 8.0

namespace MysqliFetchColumnNamedArgTest;

use mysqli;
use function PHPStan\Testing\assertType;

class AdaClass1 {
}

class AdaClass2 {
}

class Foo
{
    public function fetchColumnNamedArg(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT c_varchar255, c_smallint, c_float FROM typemix');

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

    public function proceduralFetchColumnNamedArg(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT c_varchar255, c_smallint, c_float FROM typemix');

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

    public function mysqliResultfetchNamedArg(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT adaid FROM ada');
        assertType('list<array{adaid: int<-32768, 32767>}>', $result->fetch_all(mode: MYSQLI_ASSOC));
        assertType('list<array{int<-32768, 32767>}>', $result->fetch_all(mode: MYSQLI_NUM));
    }

    public function fetchObjectNamedArg(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');

        $row = $result->fetch_object(class: 'AdaClass1');
        assertType('AdaClass1|false|null', $row);
        $row = $result->fetch_object(class: rand() ? 'AdaClass1' : 'AdaClass2');
        assertType('AdaClass1|AdaClass2|false|null', $row);
    }

    public function proceduralFetchAllColumnsNamedArg(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', mysqli_fetch_all($result, mode: MYSQLI_ASSOC));
        assertType('list<array{string, int<-32768, 32767>}>', mysqli_fetch_all($result, mode: MYSQLI_NUM));
    }

    public function proceduralFetchObjectNamedArg(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');

        $row = mysqli_fetch_object($result, class: rand() ? 'AdaClass1' : 'AdaClass2');
        assertType('AdaClass1|AdaClass2|false|null', $row);

    }
}
