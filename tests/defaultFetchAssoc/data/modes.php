<?php

namespace DefaultFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

// default fetch-type is globally changed to ASSOC for this test-suite
class Foo
{
    public function assocModeQuery(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }

    public function assocModeFetch(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch();
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }

    public function assocModeFetchOverridden(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }

    public function assocModeQueryFetchOverridden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }

    public function assocModeQueryOverridden(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }
}
