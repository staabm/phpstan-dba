<?php

namespace UnresolvableQueryInFunctionTest;

use Deployer\DbCredentials;

class Foo
{
    public function mixedParam(DbCredentials $dbCredentials, $mixed)
    {
        \Deployer\runMysqlQuery('SELECT adaid FROM ada WHERE gesperrt='.$mixed, $dbCredentials);
    }

    public function mixedParam2(DbCredentials $dbCredentials, $mixed)
    {
        $query = 'SELECT adaid FROM ada WHERE gesperrt='.$mixed;
        \Deployer\runMysqlQuery($query, $dbCredentials);
    }

    public function noErrorOnMixedQuery(DbCredentials $dbCredentials, $mixed)
    {
        // we should not report a error here, as this is like a call somewhere in between software layers
        // which don't know anything about the actual query
        \Deployer\runMysqlQuery($mixed, $dbCredentials);
    }

    public function noErrorOnStringQuery(DbCredentials $dbCredentials, string $query)
    {
        \Deployer\runMysqlQuery($query, $dbCredentials);
    }
}
