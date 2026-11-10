<?php

namespace Bug547;

use PDO;

function taintEscapedAndInferencePlaceholder(PDO $pdo, string $s)
{

    $pdo->query('SELECT * FROM ' . X::escapeIdentifier($s), PDO::FETCH_ASSOC);
}

class X {
    /**
     * Escapes and adds backsticks around.
     *
     * @param string $name
     *
     * @return string
     *
     * @psalm-taint-escape sql
     */
    public static function escapeIdentifier($name)
    {
        return '';
    }

}
