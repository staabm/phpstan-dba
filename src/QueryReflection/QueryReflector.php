<?php

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;

interface QueryReflector
{
    public const FETCH_TYPE_ASSOC = 1;
    public const FETCH_TYPE_NUMERIC = 2;
    public const FETCH_TYPE_BOTH = 3;

    public function containsSyntaxError(string $simulatedQueryString): bool;

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type;
}
