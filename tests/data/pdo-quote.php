<?php

namespace PdoQuoteTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    /**
     * @param numeric          $n
     * @param non-empty-string $nonE
     * @param numeric-string   $numericString
     */
    public function quote(PDO $pdo, int $i, float $f, $n, string $s, $nonE, string $numericString)
    {
        assertType('numeric-string', $pdo->quote((string) $i));
        assertType('numeric-string', $pdo->quote((string) $f));
        assertType('numeric-string', $pdo->quote((string) $n));
        assertType('numeric-string', $pdo->quote($numericString));
        assertType('non-empty-string', $pdo->quote($nonE));
        assertType('string', $pdo->quote($s));

        assertType('numeric-string', $pdo->quote((string) $i, PDO::PARAM_STR));
        assertType('numeric-string', $pdo->quote((string) $f, PDO::PARAM_STR));
        assertType('numeric-string', $pdo->quote((string) $n, PDO::PARAM_STR));
        assertType('numeric-string', $pdo->quote($numericString, PDO::PARAM_STR));
        assertType('non-empty-string', $pdo->quote($nonE, PDO::PARAM_STR));
        assertType('string', $pdo->quote($s, PDO::PARAM_STR));

        assertType('numeric-string', $pdo->quote((string) $i, PDO::PARAM_INT));
        assertType('numeric-string', $pdo->quote((string) $f, PDO::PARAM_INT));
        assertType('numeric-string', $pdo->quote((string) $n, PDO::PARAM_INT));
        assertType('numeric-string', $pdo->quote($numericString, PDO::PARAM_INT));
        assertType('non-empty-string', $pdo->quote($nonE, PDO::PARAM_INT));
        assertType('string', $pdo->quote($s, PDO::PARAM_INT));

        assertType('numeric-string', $pdo->quote((string) $i, PDO::PARAM_BOOL));
        assertType('numeric-string', $pdo->quote((string) $f, PDO::PARAM_BOOL));
        assertType('numeric-string', $pdo->quote((string) $n, PDO::PARAM_BOOL));
        assertType('numeric-string', $pdo->quote($numericString, PDO::PARAM_BOOL));
        assertType('non-empty-string', $pdo->quote($nonE, PDO::PARAM_BOOL));
        assertType('string', $pdo->quote($s, PDO::PARAM_BOOL));

        // not 100% sure, whether LOB is really not supported across the board
        assertType('numeric-string|false', $pdo->quote((string) $i, PDO::PARAM_LOB));
        assertType('numeric-string|false', $pdo->quote((string) $f, PDO::PARAM_LOB));
        assertType('numeric-string|false', $pdo->quote((string) $n, PDO::PARAM_LOB));
        assertType('numeric-string|false', $pdo->quote($numericString, PDO::PARAM_LOB));
        assertType('non-empty-string|false', $pdo->quote($nonE, PDO::PARAM_LOB));
        assertType('string|false', $pdo->quote($s, PDO::PARAM_LOB));
    }

    /**
     * @param numeric          $n
     * @param non-empty-string $nonE
     * @param numeric-string   $numericString
     */
    public function quotedArguments(PDO $pdo, int $i, float $f, $n, string $s, $nonE, string $numericString)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote((string) $i), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote((string) $f), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote((string) $n), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote($numericString), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);

        // when quote() cannot return a numeric-string, we can't infer the precise result-type
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote($s), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$pdo->quote($nonE), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }
}
