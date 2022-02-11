<?php

namespace PdoExecuteTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function execute(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':adaid' => 1]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->execute(['adaid' => 1]); // prefixed ":" is optional
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :email');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':email' => 'email@example.org']);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email <=> :email');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':email' => null]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        assertType('PDOStatement', $stmt);
        $stmt->execute([1]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
        assertType('PDOStatement', $stmt);
        $stmt->execute([1, 'email@example.org']);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);
    }

    public function executeWithBindCalls(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :test1 AND email = :test2');
        $test = 1337;
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindParam(':test1', $test);
        $stmt->bindValue(':test2', 1001);
        $stmt->execute();
    }

    public function errors(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':wrongParamName' => 1]);
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->execute([':wrongParamValue' => 'hello world']);
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->execute(); // missing parameter
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->bindValue(':wrongParamName', 1);
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        assertType('PDOStatement', $stmt);
        $stmt->bindValue(':wrongParamValue', 'hello world');
        $stmt->execute();
        assertType('PDOStatement', $stmt);
    }
}
