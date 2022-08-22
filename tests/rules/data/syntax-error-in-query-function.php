<?php

namespace SyntaxErrorInQueryFunctionRuleTest;

class Foo
{
    public function syntaxError(\mysqli $mysqli)
    {
        mysqli_query($mysqli, 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');
    }

    public function validQuery(\mysqli $mysqli)
    {
        mysqli_query($mysqli, 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
    }

    public function mysqliSyntaxError(\mysqli $mysqli)
    {
        mysqli_query($mysqli, 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');
    }

    public function mysqliValidQuery(\mysqli $mysqli)
    {
        mysqli_query($mysqli, 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
    }

    public function conditionalSyntaxError(\mysqli $mysqli)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE gesperrt=1';
        } else {
            // unknown column
            $query .= ' WHERE asdsa=1';
        }

        mysqli_query($mysqli, $query);
    }

    public function mysqli_execute_query(\mysqli $mysqli)
    {
        mysqli_execute_query($mysqli, 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');
    }
}
