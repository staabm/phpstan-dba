<?php

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

interface QueryReflector
{
    public const FETCH_TYPE_ONE = 1;
    public const FETCH_TYPE_ASSOC = 2;
    public const FETCH_TYPE_NUMERIC = 3;
    public const FETCH_TYPE_BOTH = 4;

    public function validateQueryString(string $queryString): ?Error;

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type;
}
