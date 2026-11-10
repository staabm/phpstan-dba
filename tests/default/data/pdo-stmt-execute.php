<?php

namespace PdoExecuteTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function execute(PDO $pdo)
    {
        
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute([':adaid' => 1]);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(['adaid' => 1]); // prefixed ":" is optional
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :email');
        $stmt->execute([':email' => 'email@example.org']);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        $stmt->execute([1]);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
        $stmt->execute([1, 'email@example.org']);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
        
    }

    public function executeWithBindCalls(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :test1 AND email = :test2');
        $test = 1337;
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindParam(':test1', $test);
        $stmt->bindValue(':test2', 1001);
        $stmt->execute();

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->bindValue(':adaid', 1);
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->bindValue('adaid', 1);
        $stmt->execute(); // prefixed ":" is optional
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email = :email');
        $stmt->bindValue(':email', 'email@example.org');
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        $stmt->bindValue(1, 1);
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ? ');
        $stmt->bindValue(1, 1);
        $stmt->bindValue(2, 'email@example.org');
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
        
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
