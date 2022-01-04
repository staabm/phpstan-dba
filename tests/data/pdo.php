<?php

namespace PdoTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public const FOO = 'foo';
    public const INT = 1;
    public const FLOAT = 1.1;

    public function querySelected(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        foreach ($stmt as $row) {
            assertType('int<0, 4294967295>', $row['adaid']);
            assertType('string', $row['email']);
            assertType('int<-128, 127>', $row['gesperrt']);
            assertType('int<-128, 127>', $row['freigabe1u1']);
        }
    }

    public function queryVariants(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada LIMIT 1', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada LIMIT 1, 10', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
    }

    public function queryWithNullColumn(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT eladaid FROM ak', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{eladaid: int<-2147483648, 2147483647>|null}>', $stmt);
    }

    public function syntaxError(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }

    /**
     * @param numeric-string   $numericString
     * @param non-empty-string $nonEmptyString
     * @param mixed            $mixed
     */
    public function concatedQuerySelected(PDO $pdo, int $int, string $string, float $float, bool $bool, $numericString, $nonEmptyString, $mixed)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.$int, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.self::INT, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query("SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE email='".self::FOO."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.$numericString, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT akid FROM ak WHERE eadavk>'.$float, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>}>', $stmt); // akid is not an auto-increment

        $stmt = $pdo->query('SELECT akid FROM ak WHERE eadavk>'.self::FLOAT, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>}>', $stmt); // akid is not an auto-increment

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.$bool, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        // ---- too queries, for which we cannot infer the return type

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$string, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$nonEmptyString, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$mixed, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }

    public function dynamicQuery(PDO $pdo, string $query)
    {
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }

    public function insertQuery(PDO $pdo)
    {
        $query = "INSERT INTO ada SET email='test@complex-it.de'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }

    public function updateQuery(PDO $pdo)
    {
        $query = "UPDATE ada SET email='test@complex-it.de' where adaid=-5";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array>|false', $stmt);
    }

    public function supportedFetchTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_BOTH);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);
    }

    public function unsupportedFetchTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada');
        assertType('PDOStatement<array>|false', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_COLUMN);
        assertType('PDOStatement<array>|false', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_OBJ);
        assertType('PDOStatement<array>|false', $stmt);
    }

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
