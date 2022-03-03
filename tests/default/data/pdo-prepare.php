<?php

namespace PdoPrepareTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function prepareSelected(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);

        foreach ($stmt as $row) {
            assertType('int<0, 4294967295>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }

    /**
     * @param 1|2|3                                      $adaid
     * @param 'test@example.org'|'webmaster@example.org' $email
     */
    public function unionParam(PDO $pdo, $adaid, $email)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ?');
        $stmt->execute([$adaid]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE email = ?');
        $stmt->execute([$email]);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);
    }

    public function queryBranches(PDO $pdo, bool $bool)
    {
        if ($bool) {
            $query = 'SELECT email, adaid FROM ada WHERE adaid=1';
        } else {
            $query = "SELECT email, adaid FROM ada WHERE email='test@example.org'";
        }

        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{email: string, 0: string, adaid: int<0, 4294967295>, 1: int<0, 4294967295>}>', $stmt);
    }

    public function placeholderInData(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada WHERE email LIKE "hello?%"';
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);

        $query = 'SELECT adaid FROM ada WHERE email LIKE ":gesperrt%"';
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);

        $query = "SELECT adaid FROM ada WHERE email LIKE ':gesperrt%'";
        $stmt = $pdo->prepare($query);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
        $stmt->execute();
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
    }

    public function arrayParam(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:adaids)';
        $stmt = $pdo->prepare($query);
        $stmt->execute(['adaids' => [1, 2, 3]]);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
    }

    public function unspecifiedArray(PDO $pdo, array $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
    }

    /**
     * @param number $idsToUpdate
     */
    public function numberType(PDO $pdo, $idsToUpdate)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids)';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
        ]);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
    }

    /**
     * @param int[] $idsToUpdate
     */
    public function specifiedArray(PDO $pdo, array $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);
        assertType('PDOStatement<array{adaid: int<0, 4294967295>, 0: int<0, 4294967295>}>', $stmt);
    }

    public function noInferenceOnBug196(PDO $pdo, array $minorPhpVersions, \DateTimeImmutable $updateDate)
    {
        $sumQueries = [];
        $dataPointDate = $updateDate->format('Ymd');
        foreach ($minorPhpVersions as $index => $version) {
            $sumQueries[] = 'SUM(DATA->\'$."'.$version.'"."'.$dataPointDate.'"\')';
        }
        $stmt = $pdo->prepare(
            'SELECT '.implode(', ', $sumQueries).' FROM ada WHERE adaid = :package'
        );
        $stmt->execute(['package' => 'abc']);
        // this query is too dynamic for beeing analyzed.
        // make sure we don't infer a wrong type.
        assertType('PDOStatement', $stmt);
    }
}
