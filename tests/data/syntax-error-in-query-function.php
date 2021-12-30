<?php

namespace SyntaxErrorInQueryFunctionRuleTest;

use Deployer\DbCredentials;

class Foo
{
    public function syntaxError(DbCredentials $dbCredentials)
    {
        \Deployer\runMysqlQuery('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', $dbCredentials);
    }
}
