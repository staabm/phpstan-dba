<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\Type\Type;

interface TypeMapper
{
    public function mapToPHPStanType(int|string $mysqlType, int|array $mysqlFlags, int $length): Type;
}
