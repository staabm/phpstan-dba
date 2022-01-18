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
}
