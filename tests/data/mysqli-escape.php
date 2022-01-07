<?php

namespace MysqliEscapeTest;

use mysqli;
use function PHPStan\Testing\assertType;

class Foo
{
    /**
     * @param numeric          $n
     * @param non-empty-string $nonE
     * @param numeric-string   $numericString
     */
    public function escape(mysqli $mysqli, int $i, float $f, $n, string $s, $nonE, string $numericString)
    {
        assertType('numeric-string', mysqli_real_escape_string($mysqli, (string) $i));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, (string) $f));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, (string) $n));
        assertType('numeric-string', mysqli_real_escape_string($mysqli, $numericString));
        assertType('non-empty-string', mysqli_real_escape_string($mysqli, $nonE));
        assertType('string', mysqli_real_escape_string($mysqli, $s));

        assertType('numeric-string', $mysqli->real_escape_string((string) $i));
        assertType('numeric-string', $mysqli->real_escape_string((string) $f));
        assertType('numeric-string', $mysqli->real_escape_string((string) $n));
        assertType('numeric-string', $mysqli->real_escape_string($numericString));
        assertType('non-empty-string', $mysqli->real_escape_string($nonE));
        assertType('string', $mysqli->real_escape_string($s));
    }

    /**
     * @param numeric          $n
     * @param non-empty-string $nonE
     * @param numeric-string   $numericString
     */
    public function quotedArguments(mysqli $mysqli, int $i, float $f, $n, string $s, $nonE, string $numericString)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string((string) $i));
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>}>|false', $result);

        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string((string) $f));
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>}>|false', $result);

        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string((string) $n));
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>}>|false', $result);

        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string($numericString));
        assertType('mysqli_result<array{email: string, adaid: int<0, 4294967295>}>|false', $result);

        // when quote() cannot return a numeric-string, we can't infer the precise result-type
        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string($s));
        assertType('bool|mysqli_result', $result);

        $result = $mysqli->query('SELECT email, adaid FROM ada WHERE adaid='.$mysqli->real_escape_string($nonE));
        assertType('bool|mysqli_result', $result);
    }
}
