<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class RecordingQueryReflector implements QueryReflector, RecordingReflector
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
        $error = $this->reflector->validateQueryString($queryString);

        $this->reflectionCache->putValidationError(
            $queryString,
            $error
        );

        return $error;
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $resultType = $this->reflector->getResultType($queryString, $fetchType);

        $this->reflectionCache->putResultType(
            $queryString,
            $fetchType,
            $resultType
        );

        return $resultType;
    }

    public function getDatasource()
    {
        if ($this->reflector instanceof RecordingReflector) {
            return $this->reflector->getDatasource();
        }

        return null;
    }
}
