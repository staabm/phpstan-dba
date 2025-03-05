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
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_BOTH);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_OBJ);
        foreach ($stmt as $row) {
            assertType('stdClass', $row);
        }
    }

    public function unsupportedFetchTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_COLUMN);
        foreach ($stmt as $row) {
            assertType('array<int|string, mixed>', $row);
        }
    }
}
