<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\Type\Type;

interface TypeMapper
{
    public function mapToPHPStanType(int $mysqlType, int $mysqlFlags, int $length): Type;
}
