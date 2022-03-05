<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

// default fetch-type is globally changed to ASSOC for this test-suite
class Foo
{
    public function assocModeQuery(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $result);
    }

    public function assocModeFetch(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}|false', $result);
    }

    public function assocModeFetchOverriden(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>, int<-128, 127>, int<-128, 127>}|false', $result);
    }

    public function assocModeQueryFetchOverriden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>, int<-128, 127>, int<-128, 127>}|false', $result);
    }

    public function assocModeQueryOverriden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<-32768, 32767>, int<-128, 127>, int<-128, 127>}>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>, int<-128, 127>, int<-128, 127>}|false', $result);
    }
}
