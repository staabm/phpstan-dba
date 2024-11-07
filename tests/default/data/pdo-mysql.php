<?php

namespace PdoMysqlTests;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function execute(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE email <=> :email');
        $stmt->execute([':email' => null]);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
    }

    public function aggregateFunctions(PDO $pdo)
    {
        $query = 'SELECT MAX(adaid), MIN(adaid), COUNT(adaid), AVG(adaid) FROM ada WHERE adaid = 1';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{MAX(adaid): int<-32768, 32767>|null, MIN(adaid): int<-32768, 32767>|null, COUNT(adaid): int, AVG(adaid): numeric-string|null}', $row);
        }
    }

    public function placeholderInDataPrepared(PDO $pdo)
    {
        // double quotes within the query
        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }

        // single quotes within the query
        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    public function placeholderInDataQuery(PDO $pdo)
    {
        // double quotes within the query
        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>}', $row);
        }
    }

    public function bug541(PDO $pdo)
    {
        $query = 'SELECT email, adaid FROM ada';
        $query .= 'WHERE email <=> :email';
        $stmt = $pdo->prepare($query);
        $stmt->execute([':email' => null]);
        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
    }
}
