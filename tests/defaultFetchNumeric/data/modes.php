<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

// default fetch-type is globally changed to NUMERIC for this test-suite
class Foo
{
    public function numericModeQuery(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch();
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }

    public function numericModeFetch(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch();
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }

    public function numericModeFetchOverridden(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }

    public function numericModeQueryFetchOverridden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }

    public function numericModeQueryOverridden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }
}
