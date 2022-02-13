<?php

namespace PdoStmtFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo {
    public function setFetchModeNum(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt->setFetchMode(PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>}'.$bothType.'>', $stmt);

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>}|false', $result);
    }

    public function setFetchModeAssoc(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>}|false', $result);
    }

    public function setFetchModeOnQuery(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>}'.$bothType.'>', $stmt);

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}'.$bothType.'>', $stmt);

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>}|false', $result);
    }
}
