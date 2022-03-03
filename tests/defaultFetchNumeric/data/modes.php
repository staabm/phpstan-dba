<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

// default fetch-type is globally changed to NUMERIC for this test-suite
class Foo
{
    public function numericModeQuery(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}|false', $result);
    }

    public function numericModeFetch(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}|false', $result);
    }

    public function numericModeFetchOverriden(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $result);
    }

    public function numericModeQueryFetchOverriden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $result);
    }

    public function numericModeQueryOverriden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $result);
    }
}
