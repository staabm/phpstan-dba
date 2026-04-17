<?php

namespace PdoStatementExecuteSubclassedMethodRuleTest;

use PDO;
use staabm\PHPStanDba\Tests\Fixture\MyPdoStatement;

class Foo
{
    public function errors(PDO $pdo): void
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assert($stmt instanceof MyPdoStatement);
        $stmt->execute(['wrongParamName' => 1]); // error: wrong param name

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assert($stmt instanceof MyPdoStatement);
        $stmt->execute(); // error: missing parameter
    }

    public function noErrors(PDO $pdo): void
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assert($stmt instanceof MyPdoStatement);
        $stmt->execute(['adaid' => 1]); // correct
    }
}
