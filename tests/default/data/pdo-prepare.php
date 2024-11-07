<?php

namespace PdoPrepareTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function prepareSelected(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada');
        $stmt->execute();

        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
            assertType('int<-32768, 32767>', $row['adaid']);
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

        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }

        $stmt = $pdo->query('SELECT email, adaid FROM ada WHERE email = ?');
        $stmt->execute([$email]);

        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
    }

    public function queryBranches(PDO $pdo, bool $bool)
    {
        if ($bool) {
            $query = 'SELECT email, adaid FROM ada WHERE adaid=1';
        } else {
            $query = "SELECT email, adaid FROM ada WHERE email='test@example.org'";
        }
        $stmt = $pdo->prepare($query);

        foreach ($stmt as $row) {
            assertType('array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}', $row);
        }
    }

    public function placeholderInData(PDO $pdo)
    {
        $query = "SELECT adaid FROM ada WHERE email LIKE 'hello?%'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }

        $query = "SELECT adaid FROM ada WHERE email LIKE '%questions ?%'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    public function arrayParam(PDO $pdo)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:adaids)';
        $stmt = $pdo->prepare($query);
        $stmt->execute(['adaids' => [1, 2, 3]]);

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    public function unspecifiedArray(PDO $pdo, array $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    /**
     * @param list $idsToUpdate
     */
    public function unspecifiedList(PDO $pdo, array $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    /**
     * @param list<positive-int> $idsToUpdate
     */
    public function specifiedList(PDO $pdo, array $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
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

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
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

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
    }

    /**
     * @param iterable<int> $idsToUpdate
     */
    public function specifiedIterable(PDO $pdo, iterable $idsToUpdate, string $time)
    {
        $query = 'SELECT adaid FROM ada WHERE adaid IN (:ids) AND email LIKE :time';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'ids' => $idsToUpdate,
            'time' => $time,
        ]);

        foreach ($stmt as $row) {
            assertType('array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}', $row);
        }
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
        
        // this query is too dynamic for being analyzed.
        // make sure we don't infer a wrong type.
        foreach ($stmt as $row) {
            assertType('mixed', $row);
        }
    }
}
