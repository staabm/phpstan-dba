<?php

namespace UnresolvableQueryInFunctionTest;

use Deployer\DbCredentials;

class Foo
{
    public function mixedParam(DbCredentials $dbCredentials, $mixed)
    {
        \Deployer\runMysqlQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada gesperrt='.$mixed, $dbCredentials);
    }

    public function mixedParam2(DbCredentials $dbCredentials, $mixed)
    {
        $query = 'SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada gesperrt='.$mixed;
        \Deployer\runMysqlQuery($query, $dbCredentials);
    }
}
