<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

final class PostgresTypeMapper implements TypeMapper
{
    public function mapToPHPStanType(int $type, int $flags, int $length): Type
    {
        throw new ShouldNotHappenException();
    }
}
