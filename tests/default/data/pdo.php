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
        assertType('PDOStatement', $stmt);
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

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid IN('. implode(',', [self::INT, 3]).')', PDO::FETCH_ASSOC);
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

        // ---- queries, for which we cannot infer the return type

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$string, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$nonEmptyString, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$mixed, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    public function dynamicQuery(PDO $pdo, string $query)
    {
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
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
        assertType('PDOStatement<array{string, int<0, 4294967295>}>', $stmt);

        if ($bool) {
            $query .= ' WHERE adaid='.$adaid;
        }

        $stmt = $pdo->query($query, PDO::FETCH_NUM);
        assertType('PDOStatement<array{string, int<0, 4294967295>}>', $stmt);
    }

    public function updateQuery(PDO $pdo)
    {
        $query = "UPDATE ada SET email='test@complex-it.de' where adaid=-5";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement', $stmt);
    }

    public function leftJoinQuery(PDO $pdo)
    {
        $query = 'SELECT a.email, b.adaid, b.gesperrt FROM ada a LEFT JOIN ada b ON a.adaid=b.adaid';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>|null, gesperrt: int<-128, 127>|null}>', $stmt);
    }

    /**
     * @param 1|2|3                                      $adaid
     * @param 'test@example.org'|'webmaster@example.org' $email
     */
    public function unionParam(PDO $pdo, $adaid, $email)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = '.$adaid, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query("SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE email = '".$email."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
    }

    public function mysqlTypes(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM typemix', PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{pid: int<0, 4294967295>, c_char5: string, c_varchar255: string, c_varchar25: string|null, c_varbinary255: string, c_varbinary25: string|null, c_date: string|null, c_time: string|null, c_datetime: string|null, c_timestamp: string|null, c_year: int<0, 255>|null, c_tiny_text: string|null, c_medium_text: string|null, c_text: string|null, c_long_text: string|null, c_enum: string, c_set: string, c_bit: int|null, c_int: int<-2147483648, 2147483647>, c_tinyint: int<-128, 127>, c_smallint: int<-32768, 32767>, c_mediumint: int<-8388608, 8388607>, c_bigint: int, c_double: int, c_real: int, c_boolean: int<-128, 127>, c_blob: string, c_tinyblob: string, c_mediumblog: string, c_longblob: string, c_unsigned_tinyint: int<0, 255>, c_unsigned_int: int<0, 4294967295>, c_unsigned_smallint: int<0, 65535>, c_unsigned_mediumint: int<0, 16777215>, c_unsigned_bigint: int<0, max>}>', $stmt);
    }

    public function aggregateFunctions(PDO $pdo)
    {
        $query = 'SELECT MAX(adaid), MIN(adaid), COUNT(adaid), AVG(adaid) FROM ada WHERE adaid = 1';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{MAX(adaid): int<-2147483648, 2147483647>|null, MIN(adaid): int<-2147483648, 2147483647>|null, COUNT(adaid): int, AVG(adaid): float|null}>', $stmt);
    }

    public function placeholderInData(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada WHERE email LIKE "hello?%"';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);

        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);
    }

    public function offsetAfterLimit(PDO $pdo, int $limit, int $offset)
    {
        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' OFFSET '.$offset;
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);
    }

    public function readlocks(PDO $pdo, int $limit, int $offset)
    {
        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' OFFSET '.$offset.' FOR UPDATE';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);

        $query = 'SELECT adaid FROM ada LIMIT '.$limit.' FOR SHARE';
        $stmt = $pdo->query($query, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>}>', $stmt);
    }

    /**
     * @param int|numeric-string $adaid
     * @param string|int         $gesperrt
     */
    public function mixInUnionParam(PDO $pdo, $adaid, $gesperrt)
    {
        // union of simulatable and simulatable is simulatable
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid = '.$adaid, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        // union of simulatable and non-simulatable is simulatable
        $stmt = $pdo->query("SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE gesperrt = '".$gesperrt."'", PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);
    }
}
