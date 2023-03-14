<?php

namespace DoctrineKeyValueStyleRuleTest;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function errorIfValueIsPlainInt(Connection $conn, int $value) {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    /**
     * @param int<0, 65535> $value
     */
    public function errorIfValueIsImproperIntegerRangeType(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    /**
     * @param int<-32768, 32767> $value
     */
    public function noErrorIfIntegerRangeIsExact(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }

    /**
     * @param int<0, 127> $value
     */
    public function noErrorIfValueRangeIsNarrower(Connection $conn, int $value)
    {
        $conn->assembleOneArray('ada', ['adaid' => $value]);
    }
}
