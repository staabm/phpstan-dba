<?php

namespace PdoStatementExecuteErrorMethodRuleTest;

use PDO;

class Foo
{
    public function errors(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(['wrongParamName' => 1]);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute([':wrongParamName' => 1]);

        // $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        // $stmt->execute([':adaid' => 'hello world']); // wrong param type

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(); // missing parameter

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
        $stmt->execute([1]); // wrong number of parameters

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->execute(['adaid' => 1]); // wrong number of parameters

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->execute([':email' => 'email@example.org']); // wrong number of parameters
    }

    public function multi_execute(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(['wrongParamName' => 1]); // wrong param name
        $stmt->execute(['adaid' => 1]); // everything correct
        $stmt->execute([':email' => 'email@example.org', 'adaid' => 1]); // wrong number of parameters
    }

    public function camelCase(PDO $pdo) {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :eMail');
        $stmt->execute([':eMail' => 'email@example.org']);
    }
}
