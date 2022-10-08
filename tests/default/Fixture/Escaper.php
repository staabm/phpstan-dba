<?php

namespace staabm\PHPStanDba\Tests\Fixture;

final class Escaper
{
    /**
     * @psalm-taint-escape sql
     */
    public function escape($s): string
    {
    }

    /**
     * @psalm-taint-escape sql
     */
    public static function staticEscape($s): string
    {
    }
}
