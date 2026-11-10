<?php

namespace Bug548;

use PDO;

function taintEscapedAndInferencePlaceholder(PDO $pdo, string $s, int $start, int $max)
{

    $pdo->query('SELECT * FROM ' . X::escapeIdentifier($s) . ' LIMIT ' . $start . ',' . $max, PDO::FETCH_ASSOC);
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
