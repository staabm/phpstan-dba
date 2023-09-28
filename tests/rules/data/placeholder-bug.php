<?php

namespace PlaceholderBug;


use PDO;

class Foo
{
    public function errors(PDO $pdo, $vkFrom)
    {
        $values = [];

        $fromCondition = '';
        if ('0' !== $vkFrom) {
            $fromCondition = 'and email = ?';
            $values[] = 'hello world';
        }

        $values[] = 1;

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? ' . $fromCondition);
        $stmt->execute($values);
    }
}
