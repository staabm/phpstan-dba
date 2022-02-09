<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

// default fetch-type is globally changed to assoc for this test-suite
class Foo
{
    public function assocModeQuery(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}';

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }

    public function assocModeFetch(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}';

        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}', $result);
    }

    public function assocModeFetchOverriden(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}';

        $stmt = $pdo->prepare('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}', $result);
    }

    public function assocModeQueryFetchOverriden(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}';

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}'. $bothType .'>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}', $result);
    }

    public function assocModeQueryOverriden(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>, gesperrt: int<-128, 127>, 2: int<-128, 127>, freigabe1u1: int<-128, 127>, 3: int<-128, 127>}';

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}'. $bothType .'>', $stmt);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>, int<-128, 127>, int<-128, 127>}', $result);
    }
}
