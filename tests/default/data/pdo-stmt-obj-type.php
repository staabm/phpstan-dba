<?php

namespace PdoStmtObjectTypeTest;

use PDO;
use PDOStatement;
use function PHPStan\Testing\assertType;
use staabm\PHPStanDba\Tests\Fixture\MyRowClass;

class Foo
{
    /**
     * @return PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>
     */
    private function prepare(PDO $pdo): PDOStatement {
        return $pdo->prepare('SELECT email, adaid FROM ada');
    }

    public function fetch(PDO $pdo)
    {
        $stmt = $this->prepare($pdo);
        $stmt->execute();

        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);

        $all = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<0, 4294967295>}|false', $all);

        $all = $stmt->fetch(PDO::FETCH_COLUMN);
        assertType('string|false', $all);
    }
}
