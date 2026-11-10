<?php

namespace UnresolvableQueryInFunctionTest;

class Foo
{
    public function mixedParam(\mysqli $mysqli, $mixed)
    {
        mysqli_query($mysqli, 'SELECT adaid FROM ada WHERE gesperrt='.$mixed);
    }

    public function mixedParam2(\mysqli $mysqli, $mixed)
    {
        $query = 'SELECT adaid FROM ada WHERE gesperrt='.$mixed;
        mysqli_query($mysqli, $query);
    }

    public function noErrorOnMixedQuery(\mysqli $mysqli, $mixed)
    {
        // we should not report a error here, as this is like a call somewhere in between software layers
        // which don't know anything about the actual query
        mysqli_query($mysqli, $mixed);
    }

    public function noErrorOnStringQuery(\mysqli $mysqli, string $query)
    {
        mysqli_query($mysqli, $query);
    }

    public function stringParam(\mysqli $mysqli, string $string)
    {
        mysqli_query($mysqli, 'SELECT adaid FROM ada WHERE gesperrt='.$string);
    }

    public function queryStringFragment(\mysqli $mysqli, string $string)
    {
        mysqli_query($mysqli, 'SELECT adaid FROM ada WHERE '.$string);
    }
}
