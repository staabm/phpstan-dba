<?php

namespace PdoDefaultFetchTypes;

use PDO;
use function PHPStan\Testing\assertType;

class HelloWorld
{
    public function defaultFetchType(PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q);
        assertType('PDOStatement<array<float|int|string>>', $stmt);
        foreach ($stmt as $row) {
            assertType('array<float|int|string>', $row);
        }
    }

    public function specifiedFetchTypes(PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q, PDO::FETCH_CLASS);
        assertType('PDOStatement<stdClass>', $stmt);
        foreach ($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_OBJ);
        assertType('PDOStatement<stdClass>', $stmt);
        foreach ($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_KEY_PAIR);
        assertType('PDOStatement<array{mixed, mixed}>', $stmt);
        foreach ($stmt as $row) {
            assertType('array{mixed, mixed}', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array<string, float|int|string>>', $stmt);
        foreach ($stmt as $row) {
            assertType('array<string, float|int|string>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_NUM);
        assertType('PDOStatement<array<int<0, max>, float|int|string>>', $stmt); // could be list
        foreach ($stmt as $row) {
            assertType('array<int<0, max>, float|int|string>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_BOTH);
        assertType('PDOStatement<array<float|int|string>>', $stmt);
        foreach ($stmt as $row) {
            assertType('array<float|int|string>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_COLUMN);
        assertType('PDOStatement', $stmt); // could be PDOStatement<scalar>
        foreach ($stmt as $row) {
            assertType('mixed', $row); // could be scalar
        }
    }
}
