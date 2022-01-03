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

    public function validateQueryString(string $simulatedQueryString): ?Error
    {
        $error = $this->reflector->validateQueryString($simulatedQueryString);

        $this->reflectionCache->putValidationError(
            $simulatedQueryString,
            $error
        );

        return $error;
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        $resultType = $this->reflector->getResultType($simulatedQueryString, $fetchType);

        $this->reflectionCache->putResultType(
            $simulatedQueryString,
            $fetchType,
            $resultType
        );

        return $resultType;
    }
}
