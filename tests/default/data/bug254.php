<?php

namespace Bug254;

class Foo {
    public function noRows(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid FROM ada');
        while ($row = $stmt->fetch()) {
            $email = $row['email'];
        }
    }

}
