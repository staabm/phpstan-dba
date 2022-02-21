<?php

namespace PdoFetchTypeTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function supportedFetchTypes(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        // default fetch-type is BOTH
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_BOTH);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_OBJ);
        assertType('PDOStatement<array<int, stdClass>'.$bothType.'>', $stmt);
    }

    public function unsupportedFetchTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_COLUMN);
        assertType('PDOStatement', $stmt);
    }
}
