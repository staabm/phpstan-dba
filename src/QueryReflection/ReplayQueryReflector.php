<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class ReplayQueryReflector implements QueryReflector
{
    /**
     * @var ReflectionCache
     */
    private $reflectionCache;

    public function __construct(ReflectionCache $cache)
    {
        $this->reflectionCache = $cache;
    }

    public function validateQueryString(string $queryString): ?Error
    {
		$simulatedQuery = QuerySimulation::simulate($queryString);

		if ($simulatedQuery === null) {
			return null;
		}

		return $this->reflectionCache->getValidationError($simulatedQuery);
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
		$simulatedQuery = QuerySimulation::simulate($queryString);

		if ($simulatedQuery === null) {
			return null;
		}

		// queries with errors don't have a cached result type
        if (false === $this->reflectionCache->hasResultType($simulatedQuery, $fetchType)) {
            return null;
        }

        return $this->reflectionCache->getResultType($simulatedQuery, $fetchType);
    }
}
