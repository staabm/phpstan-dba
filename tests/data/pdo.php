<?php

namespace PdoTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
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
     * @param mixed $mixed
     */
    public function concatedQuerySelected(PDO $pdo, int $int, string $string, float $float, bool $bool, $mixed)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.$int, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE '.$string, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

        $stmt = $pdo->query('SELECT akid FROM ak WHERE eadavk>'.$float, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{akid: int<-2147483648, 2147483647>}>', $stmt); // akid is not an auto-increment

        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada WHERE adaid='.$bool, PDO::FETCH_ASSOC);
        assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>, gesperrt: int<-128, 127>, freigabe1u1: int<-128, 127>}>', $stmt);

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

	public function dynamicTableQuery(PDO $pdo) {
		$stmt = $pdo->query('SELECT email, adaid FROM '. $this->getTable(), PDO::FETCH_ASSOC);
		assertType('PDOStatement<array{email: string, adaid: int<0, 4294967295>}>', $stmt);
	}

	public function getTable() {
		return 'ada';
	}
}
