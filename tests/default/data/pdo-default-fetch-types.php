<?php

namespace PdoDefaultFetchTypes;

use PDO;
use function PHPStan\Testing\assertType;

class HelloWorld
{
    public function defaultFetchType(PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q);
        foreach ($stmt as $row) {
            assertType('array<float|int|string|null>', $row);
        }
    }

    public function specifiedFetchTypes(PDO $pdo, string $q): void
    {
        $stmt = $pdo->query($q, PDO::FETCH_CLASS);
        foreach ($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_OBJ);
        foreach ($stmt as $row) {
            assertType('stdClass', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_KEY_PAIR);
        foreach ($stmt as $row) {
            assertType('array{mixed, mixed}', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array<string, float|int|string|null>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array<int<0, max>, float|int|string|null>', $row); // could be list
        }

        $stmt = $pdo->query($q, PDO::FETCH_BOTH);
        foreach ($stmt as $row) {
            assertType('array<float|int|string|null>', $row);
        }

        $stmt = $pdo->query($q, PDO::FETCH_COLUMN);
        foreach ($stmt as $row) {
            assertType('array<int|string, mixed>', $row); // could be array<int|string, float|int|string|null>
        }
    }
}
