<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Types;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

/**
 * @see https://dev.mysql.com/doc/refman/8.0/en/integer-types.html
 */
final class MysqlIntegerRanges
{
    public function signedTinyInt(): Type
    {
        return IntegerRangeType::fromInterval(-128, 127);
    }

    public function signedSmallInt(): Type
    {
        return IntegerRangeType::fromInterval(-32768, 32767);
    }

    public function signedMediumInt(): Type
    {
        return IntegerRangeType::fromInterval(-8388608, 8388607);
    }

    public function signedInt(): Type
    {
        return IntegerRangeType::fromInterval(-2147483648, 2147483647);
    }

    public function signedBigInt(): Type
    {
        // the range is so big, we just assume its int
        return new IntegerType();
    }

    public function unsignedTinyInt(): Type
    {
        return IntegerRangeType::fromInterval(0, 255);
    }

    public function unsignedSmallInt(): Type
    {
        return IntegerRangeType::fromInterval(0, 65535);
    }

    public function unsignedMediumInt(): Type
    {
        return IntegerRangeType::fromInterval(0, 16777215);
    }

    public function unsignedInt(): Type
    {
        return IntegerRangeType::fromInterval(0, 4294967295);
    }

    public function unsignedBigInt(): Type
    {
        // the range is so big, we just assume its int
        return IntegerRangeType::fromInterval(0, null);
    }
}
