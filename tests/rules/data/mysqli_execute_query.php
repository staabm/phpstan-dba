<?php

namespace MysqliExecuteQuery;

class Foo
{
    public function syntaxError(\mysqli $mysqli)
    {
        $mysqli->execute_query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');

        mysqli_execute_query($mysqli, 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada');
    }
}
