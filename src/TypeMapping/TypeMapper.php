<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\Type\Type;

interface TypeMapper
{
    public const FLAG_AUTO_INCREMENT = 'AUTO_INCREMENT';
    public const FLAG_NOT_NULL = 'NOT_NULL';
    public const FLAG_NUMERIC = 'NUM';
    public const FLAG_UNSIGNED = 'UNSIGNED';

    /**
     * @param list<string> $flags
     */
    public function mapToPHPStanType(string $type, array $flags, int $length): Type;

    public function isNumericCol(string $mysqlType): bool;
}
