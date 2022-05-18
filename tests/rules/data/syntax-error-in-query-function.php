<?php

namespace SyntaxErrorInQueryFunctionRuleTest;

use Deployer\DbCredentials;

class Foo
{
    public function syntaxError(DbCredentials $dbCredentials)
    {
        \Deployer\runMysqlQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', $dbCredentials);
    }

    public function validQuery(DbCredentials $dbCredentials)
    {
        \Deployer\runMysqlQuery('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', $dbCredentials);
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
}
