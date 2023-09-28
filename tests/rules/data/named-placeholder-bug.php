<?php

namespace NamedPlaceholderBug;


use PDO;

class Foo
{
    public function errors(PDO $pdo, $vkFrom)
    {
        $fromCondition = '';
        if ('0' !== $vkFrom) {
            $fromCondition = 'and email = :vkFrom';
        }

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid ' . $fromCondition);
        $stmt->execute([
            'adaid' => 1,
            'vkFrom' => 'hello world'
        ]);
    }
}
