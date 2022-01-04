<?php

namespace MysqliTest;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    public function ooQuerySelected(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>|false', $result);

        if ($result) {
            foreach ($result as $row) {
                assertType('int<0, 4294967295>', $row['adaid']);
                assertType('string', $row['email']);
                assertType('int<-128, 127>', $row['gesperrt']);
                assertType('int<-128, 127>', $row['freigabe1u1']);
            }
        }
    }

    public function ooQuery(mysqli $mysqli, string $query)
    {
        $result = $mysqli->query($query);
        assertType('bool|mysqli_result', $result);
    }

    public function fnQuerySelected(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>|false', $result);

        if ($result) {
            foreach ($result as $row) {
                assertType('int<0, 4294967295>', $row['adaid']);
                assertType('string', $row['email']);
                assertType('int<-128, 127>', $row['gesperrt']);
                assertType('int<-128, 127>', $row['freigabe1u1']);
            }
        }
    }

    public function fnQuery(mysqli $mysqli, string $query)
    {
        $result = mysqli_query($mysqli, $query);
        assertType('bool|mysqli_result', $result);
    }

    /**
     * @param numeric          $n
     * @param non-empty-string $nonE
     * @param numeric-string   $numericString
     */
    public function escape(mysqli $mysqli, int $i, float $f, $n, string $s, $nonE, string $numericString)
    {
        assertType('numeric-string', mysqli_real_escape_string($mysqli, $i));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, $f));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, $n));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, $numericString));
        assertType('non-empty-string', mysqli_real_escape_string($mysqli, $nonE));
        assertType('string', mysqli_real_escape_string($mysqli, $s));

        assertType('numeric-string', $mysqli->real_escape_string($i));
        assertType('numeric-string', $mysqli->real_escape_string($f));
        assertType('numeric-string', $mysqli->real_escape_string($n));
        assertType('numeric-string', $mysqli->real_escape_string($numericString));
        assertType('non-empty-string', $mysqli->real_escape_string($nonE));
        assertType('string', $mysqli->real_escape_string($s));
    }
}
