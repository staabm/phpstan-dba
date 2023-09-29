<?php

namespace PlaceholderBug;


use PDO;

class Foo
{
    public function errors(PDO $pdo, $vkFrom)
    {
        $values = [];
        $values[] = 1;

        $fromCondition = '';
        if ('0' !== $vkFrom) {
            $fromCondition = 'and email = ?';
            $values[] = 'hello world';
        }


        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? ' . $fromCondition);
        $stmt->execute($values);
    }
}
