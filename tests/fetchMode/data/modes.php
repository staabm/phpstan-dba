<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function assocModeQuery(PDO $pdo)
    {
        // default fetch-type is globally changed to assoc for this test-suite

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }

    public function assocModeFetch(PDO $pdo)
    {
        // default fetch-type is globally changed to assoc for this test-suite

        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }

    public function assocModeFetchOverriden(PDO $pdo)
    {
        // default fetch-type is globally changed to assoc for this test-suite

        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }

    public function assocModeQueryFetchOverriden(PDO $pdo)
    {
        // default fetch-type is globally changed to assoc for this test-suite

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }
}
