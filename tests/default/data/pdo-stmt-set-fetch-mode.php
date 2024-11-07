<?php

namespace PdoStmtFetchModeTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function setFetchModeNum(PDO $pdo)
    {
        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }

    public function setFetchModeAssoc(PDO $pdo)
    {
        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $result);
    }

    public function setFetchModeOnQuery(PDO $pdo)
    {
        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        $result = $stmt->fetch(PDO::FETCH_NUM);
        assertType('array{string, int<-32768, 32767>}|false', $result);
    }
}
