<?php

namespace PdoCteTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function simpleCte(PDO $pdo)
    {
        $query = 'WITH active_ada AS (SELECT email, adaid FROM ada WHERE gesperrt = 0) '
            .'SELECT email, adaid FROM active_ada';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
    }

    public function cteSelectStar(PDO $pdo)
    {
        $query = 'WITH cte AS (SELECT email, adaid FROM ada) SELECT * FROM cte';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
    }

    public function multipleCtes(PDO $pdo)
    {
        $query = 'WITH '
            .'emails AS (SELECT adaid, email FROM ada), '
            .'unlocked AS (SELECT adaid FROM ada WHERE gesperrt = 0) '
            .'SELECT e.email, e.adaid FROM emails e JOIN unlocked u ON u.adaid = e.adaid';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
    }

    public function cteWithAggregation(PDO $pdo)
    {
        $query = 'WITH counts AS (SELECT gesperrt, COUNT(*) AS total FROM ada GROUP BY gesperrt) '
            .'SELECT gesperrt, total FROM counts';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{gesperrt: int<-128, 127>, total: int}', $row);
        }
    }

    public function recursiveCte(PDO $pdo)
    {
        $query = 'WITH RECURSIVE cnt(n) AS ('
            .'SELECT 1 UNION ALL SELECT n + 1 FROM cnt WHERE n < 5'
            .') SELECT n FROM cnt';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{n: int|null}', $row);
        }
    }

    public function cteFetchNumeric(PDO $pdo)
    {
        $query = 'WITH cte AS (SELECT email, adaid FROM ada) SELECT email, adaid FROM cte';
        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }
    }
}
