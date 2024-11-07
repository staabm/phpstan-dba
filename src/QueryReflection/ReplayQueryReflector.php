<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class ReplayQueryReflector implements QueryReflector
{
    private ReflectionCache $reflectionCache;

    public function __construct(ReflectionCache $cache)
    {
        $this->reflectionCache = $cache;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        if (! $this->reflectionCache->hasValidationError($queryString)) {
            return null;
        }

        return $this->reflectionCache->getValidationError($queryString);
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if (! $this->reflectionCache->hasResultType($queryString, $fetchType)) {
            return null;
        }

        return $this->reflectionCache->getResultType($queryString, $fetchType);
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
    }
}
