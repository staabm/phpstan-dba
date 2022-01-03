<?php

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

interface QueryReflector
{
    public const FETCH_TYPE_ASSOC = 1;
    public const FETCH_TYPE_NUMERIC = 2;
    public const FETCH_TYPE_BOTH = 3;

    public function validateQueryString(string $queryString): ?Error;

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type;
}
