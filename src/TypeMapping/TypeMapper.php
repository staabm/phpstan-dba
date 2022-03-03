<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\Type\Type;

interface TypeMapper
{
    public function mapToPHPStanType(string $type, array $flags, int $length): Type;

    public function isNumericCol(string $mysqlType): bool;
}
