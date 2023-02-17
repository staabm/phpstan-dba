<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Types;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
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
        return new IntegerType();
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
