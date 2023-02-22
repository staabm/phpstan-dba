<?php

namespace Bug536;

use PDO;

function taintEscapedAndInferencePlaceholder(PDO $pdo, string $s)
{
    $pdo->query('SELECT email, adaid FROM '. X::getTablePrefix('ada'), PDO::FETCH_ASSOC);
}

class X {
    /**
     * Returns the table prefix.
     *
     * @return non-empty-string
     *
     * @phpstandba-inference-placeholder 'ada'
     * @psalm-taint-escape sql
     */
    public static function getTablePrefix()
    {

    }

}
