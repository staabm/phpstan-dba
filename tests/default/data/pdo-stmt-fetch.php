<?php

namespace PdoStmtFetchTest;

use PDO;
use function PHPStan\Testing\assertType;
use staabm\PHPStanDba\Tests\Fixture\MyRowClass;

class Foo
{
    public function fetchAll(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        // default fetch-mode is BOTH
        $all = $stmt->fetchAll();
        assertType('array<int, array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_BOTH);
        assertType('array<int, array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_NUM);
        assertType('array<int, array{string, int<0, 4294967295>}>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        assertType('array<int, array{email: string, adaid: int<0, 4294967295>}>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
        assertType('array<int, string>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        assertType('array<int, string>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        assertType('array<int, int<0, 4294967295>>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        assertType('array<string, int<0, 4294967295>>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_CLASS, MyRowClass::class);
        assertType('array<int, staabm\PHPStanDba\Tests\Fixture\MyRowClass>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_CLASS);
        assertType('array<int, stdClass>', $all);

        $all = $stmt->fetchAll(PDO::FETCH_OBJ);
        assertType('array<int, stdClass>', $all);
    }

    public function fetch(PDO $pdo)
    {
        $bothType = ', array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}';

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}'.$bothType.'>', $stmt);

        // default fetch-mode is BOTH
        $all = $stmt->fetch();
        assertType('array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_BOTH);
        assertType('array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_COLUMN);
        assertType('string|false', $all);

        $all = $stmt->fetch(PDO::FETCH_COLUMN, 0);
        assertType('string|false', $all);

        $all = $stmt->fetch(PDO::FETCH_COLUMN, 1);
        assertType('int<0, 4294967295>|false', $all);

        $all = $stmt->fetch(PDO::FETCH_KEY_PAIR);
        assertType('array<string, int<0, 4294967295>>|false', $all);

        $all = $stmt->fetchObject(MyRowClass::class);
        assertType('staabm\PHPStanDba\Tests\Fixture\MyRowClass|false', $all);

        $all = $stmt->fetchObject();
        assertType('stdClass|false', $all);

        $all = $stmt->fetch(PDO::FETCH_OBJ);
        assertType('stdClass|false', $all);
    }
}
