<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

interface QueryReflector
{
    public const FETCH_TYPE_ONE = 1;

    public const FETCH_TYPE_FIRST_COL = 2;

    public const FETCH_TYPE_ASSOC = 3;

    public const FETCH_TYPE_NUMERIC = 4;

    public const FETCH_TYPE_BOTH = 5;

    public const FETCH_TYPE_KEY_VALUE = 6;

    public const FETCH_TYPE_COLUMN = 7;

    public const FETCH_TYPE_CLASS = 8;

    public function validateQueryString(string $queryString): ?Error;

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type;

    public function setupDbaApi(?DbaApi $dbaApi): void;
}
