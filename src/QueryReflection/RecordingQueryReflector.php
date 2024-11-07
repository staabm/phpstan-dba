<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class RecordingQueryReflector implements QueryReflector, RecordingReflector
{
    private ReflectionCache $reflectionCache;

    private QueryReflector $reflector;

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

        if (null !== $error) {
            $this->reflectionCache->putValidationError(
                $queryString,
                $error
            );
        }

        return $error;
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $resultType = $this->reflector->getResultType($queryString, $fetchType);

        if (null !== $resultType) {
            $this->reflectionCache->putResultType(
                $queryString,
                $fetchType,
                $resultType
            );
        }

        return $resultType;
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->reflector->setupDbaApi($dbaApi);
    }

    public function getDatasource()
    {
        if ($this->reflector instanceof RecordingReflector) {
            return $this->reflector->getDatasource();
        }

        return null;
    }
}
