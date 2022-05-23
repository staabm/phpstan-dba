<?php

namespace Bug372;

use function PHPStan\Testing\assertType;

class HelloWorld
{
    public function doFoo(\PDO $pdo): void {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        assertType('PDOStatement', $stmt);

        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);

        }
    }

    public function defaultFetchType(\PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('array', $row);
        }
    }

    public function specifiedFetchTypes(\PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q, PDO::FETCH_CLASS);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_OBJ);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_KEY_PAIR);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('array{mixed, mixed}', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('array<string, mixed>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_NUM);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('list<mixed>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_BOTH);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('array<array-key, mixed>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_COLUMN);
        assertType('PDOStatement', $stmt);
        foreach($stmt as $row) {
            assertType('list<mixed>', $row);
        }

    }
}
