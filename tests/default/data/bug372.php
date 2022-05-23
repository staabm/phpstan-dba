<?php

namespace Bug372;

use function PHPStan\Testing\assertType;

class Foo
{
    public function differentLineDelims(\PDO $pdo): void
    {
        $stmt = $pdo->query('SELECT email '."\r\n".'FROM ada');
        while ($row = $stmt->fetch()) {
            assertType('string', $row['email']);
        }

        $stmt = $pdo->query('SELECT adaid '."\n".'FROM ada');
        while ($row = $stmt->fetch()) {
            assertType('int<-32768, 32767>', $row['adaid']);
        }

        $stmt = $pdo->query(
            '
            SELECT
                adaid
            FROM
                ada'); // intentioanlly across separate lines
        while ($row = $stmt->fetch()) {
            assertType('int<-32768, 32767>', $row['adaid']);
        }
    }
}
