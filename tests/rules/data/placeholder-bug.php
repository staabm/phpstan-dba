<?php

namespace PlaceholderBug;


use PDO;

class Foo
{
    public function allGood(PDO $pdo, $vkFrom)
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

    public function sometimesWrongNumberOfParameters(PDO $pdo, $vkFrom)
    {
        $values = [];

        $values[] = 1;
        if (rand(0,1)) {
            $values[] = 10;
        }

        $fromCondition = '';
        if ('0' !== $vkFrom) {
            $fromCondition = 'and email = ?';
            $values[] = 'hello world';
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? OR adaid = ? ' . $fromCondition);
        $stmt->execute($values);
    }
}
