<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class RecordingQueryReflector implements QueryReflector
{
    /**
     * @var ReflectionCache
     */
    private $reflectionCache;

    /**
     * @var QueryReflector
     */
    private $reflector;

    public function __construct(ReflectionCache $cache, QueryReflector $wrappedReflector)
    {
        $this->reflectionCache = $cache;
        $this->reflector = $wrappedReflector;
    }

    public function __destruct()
    {
        $this->reflectionCache->persist();
    }

    public function validateQueryString(string $queryString): ?Error
    {
		$simulatedQuery = QuerySimulation::simulate($queryString);
		if (null === $simulatedQuery) {
			return null;
		}

        $error = $this->reflector->validateQueryString($queryString);

        $this->reflectionCache->putValidationError(
			$simulatedQuery,
            $error
        );

        return $error;
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
		$simulatedQuery = QuerySimulation::simulate($queryString);
		if (null === $simulatedQuery) {
			return null;
		}

        // built the query string cache, also on result-type checking, to make sure the cachefile contains all required information.
        // result-type checking is triggered by phpstan analysis via our phpstan-extensions, while the query-validation isn't.
        $this->validateQueryString($queryString);

        $resultType = $this->reflector->getResultType($queryString, $fetchType);

        $this->reflectionCache->putResultType(
			$simulatedQuery,
            $fetchType,
            $resultType
        );

        return $resultType;
    }
}
