<?php

namespace staabm\PHPStanDba\Types;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;

/**
 * @see https://www.postgresql.org/docs/9.1/datatype-numeric.html
 */
final class PgsqlIntegerRanges
{
    public function smallint(): Type
    {
        return IntegerRangeType::fromInterval(-32768, 32767);
    }

    public function integer(): Type
    {
        return IntegerRangeType::fromInterval(-2147483648, 2147483647);
    }

    public function bigint(): Type
    {
        // min should be -9223372036854775808 but that turns the integer to float in php in runtime
        return IntegerRangeType::fromInterval(-9223372036854775807, 9223372036854775807);
    }

    public function serial(): Type
    {
        return IntegerRangeType::fromInterval(1, 2147483647);
    }

    public function bigserial(): Type
    {
        return IntegerRangeType::fromInterval(1, 9223372036854775807);
    }
}
