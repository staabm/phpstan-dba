<?php

namespace MysqliTest;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function ooQuerySelected(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<-32768, 32767>}>', $result);

        $field = 'email';
        if (rand(0, 1)) {
            $field = 'adaid';
        }

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
            assertType('int<-32768, 32767>|string', $row[$field]);
        }
    }

    public function ooQuery(mysqli $mysqli, string $query)
    {
        $result = $mysqli->query($query);
        assertType('mysqli_result|true', $result);
    }

    public function fnQuerySelected(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<-32768, 32767>}>', $result);

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }

    public function fnQuery(mysqli $mysqli, string $query)
    {
        $result = mysqli_query($mysqli, $query);
        assertType('mysqli_result|true', $result);
    }

    public function fnFetchTypes(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<-32768, 32767>}>', $result);

        while ($row = mysqli_fetch_assoc($result)) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        while ($row = mysqli_fetch_row($result)) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        while ($row = mysqli_fetch_object($result)) {
            assertType('int<-32768, 32767>', $row->adaid);
            assertType('string', $row->email);
        }

        while ($row = mysqli_fetch_array($result)) {
            assertType('array{0: string, 1: int<-32768, 32767>, email: string, adaid: int<-32768, 32767>}', $row);
        }
    }
}
