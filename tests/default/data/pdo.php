<?php

namespace PdoTest;

use PDO;
use staabm\PHPStanDba\Tests\Fixture\Escaper;
use function PHPStan\Testing\assertType;

class Foo
{
    public const FOO = 'foo';
    public const INT = 1;
    public const FLOAT = 1.1;

    public function querySelected(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        foreach ($stmt as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }

    public function queryVariants(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada LIMIT 1', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada LIMIT 1, 10', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }

    public function queryWithNullColumn(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT eladaid FROM ak', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{eladaid: int<-2147483648, 2147483647>|null}>', $stmt);
    }

    public function syntaxError(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    /**
     * @param numeric-string   $numericString
     * @param non-empty-string $nonEmptyString
     * @param mixed            $mixed
     */
    public function concatedQuerySelected(PDO $pdo, int $int, string $string, float $float, bool $bool, $numericString, $nonEmptyString, $mixed)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$int, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.self::INT, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        // requires phpstan 1.4.6+
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid IN('.implode(',', [self::INT, 3]).')', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE email='".self::FOO."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$numericString, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid='.$bool, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        // ----

        $stmt = $pdo->query('SELECT akid FROM ak WHERE eadavk>'.$float, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>}>', $stmt); // akid is not an auto-increment

        $stmt = $pdo->query('SELECT akid FROM ak WHERE eadavk>'.self::FLOAT, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>}>', $stmt); // akid is not an auto-increment

        // ---- queries, for which we cannot infer the return type

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE '.$string, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array<string, float|int|string|null>>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE '.$nonEmptyString, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array<string, float|int|string|null>>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE '.$mixed, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array<string, float|int|string|null>>', $stmt);
    }

    public function dynamicQuery(PDO $pdo, string $query)
    {
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array<string, float|int|string|null>>', $stmt);
    }

    public function insertQuery(PDO $pdo)
    {
        $query = "INSERT INTO ada SET email='test@complex-it.de'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    public function replaceQuery(PDO $pdo)
    {
        $query = "REPLACE INTO ada SET email='test@complex-it.de'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    public function queryBranches(PDO $pdo, bool $bool, int $adaid)
    {
        $query = 'SELECT email, adaid FROM ada';
        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<-32768, 32767>}>', $stmt);

        if ($bool) {
            $query .= ' WHERE adaid='.$adaid;
        }

        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<-32768, 32767>}>', $stmt);
    }

    public function updateQuery(PDO $pdo)
    {
        $query = "UPDATE ada SET email='test@complex-it.de' where adaid=-5";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    /**
     * @param 1|2|3                                      $adaid
     * @param 'test@example.org'|'webmaster@example.org' $email
     */
    public function unionParam(PDO $pdo, $adaid, $email)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid = '.$adaid, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE email = '".$email."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }

    public function placeholderInData(PDO $pdo)
    {
        $query = "SELECT adaid FROM ada WHERE email LIKE 'hello?%'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }

    public function offsetAfterLimit(PDO $pdo, int $limit, int $offset)
    {
        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' OFFSET '.$offset;
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }

    public function readlocks(PDO $pdo, int $limit, int $offset)
    {
        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' OFFSET '.$offset.' FOR UPDATE';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);

        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' FOR SHARE';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }

    public function readForUpdateSkipLocked(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada LIMIT 1 FOR UPDATE SKIP LOCKED';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);

        $query = 'SELECT adaid FROM ada LIMIT 1 FOR SHARE SKIP LOCKED';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }

    public function readForUpdateNowait(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada LIMIT 1 FOR UPDATE NOWAIT';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);

        $query = 'SELECT adaid FROM ada LIMIT 1 FOR SHARE NOWAIT';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<-32768, 32767>}>', $stmt);
    }

    /**
     * @param int|numeric-string $adaid
     * @param string|int         $gesperrt
     */
    public function mixInUnionParam(PDO $pdo, $adaid, $gesperrt)
    {
        // union of simulatable and simulatable is simulatable
        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE adaid = '.$adaid, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        // union of simulatable and non-simulatable is simulatable
        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE gesperrt = '".$gesperrt."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }

    public function queryEncapsedString(PDO $pdo, int $adaid)
    {
        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE adaid=$adaid", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);

        $fn = function (): int {
            return self::INT;
        };
        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE adaid={$fn()}", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }

    public function taintStaticEscaped(PDO $pdo, string $s)
    {
        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE adaid=". Escaper::staticEscape($s), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }

    public function taintEscaped(PDO $pdo, string $s)
    {
        $escapeer = new Escaper();
        $stmt = $pdo->query("SELECT email, adaid FROM ada WHERE adaid=". $escapeer->escape($s), PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<-32768, 32767>}>', $stmt);
    }
}
