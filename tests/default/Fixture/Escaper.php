<?php

namespace staabm\PHPStanDba\Tests\Fixture;

final class Escaper {
    /**
     * @psalm-taint-escape sql
     *
     * @return string
     */
    public function escape($s):string {

    }

    /**
     * @psalm-taint-escape sql
     *
     * @return string
     */
    static public function staticEscape($s):string {

    }
}
