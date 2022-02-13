<?php

namespace Bug254;

use function PHPStan\Testing\assertType;

class Foo {
    public function noRows(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        while ($row = $stmt->fetch()) {
            assertType('string', $row['email']);
        }
    }

}
