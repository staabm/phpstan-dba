<?php

namespace PdoFetchTypeTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function supportedFetchTypes(PDO $pdo)
    {
        // default fetch-type is BOTH
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_BOTH);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_OBJ);
        assertType('PDOStatement<array<int, stdClass>>', $stmt);
    }

    public function unsupportedFetchTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_COLUMN);
        assertType('PDOStatement', $stmt);
    }
}
