<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;

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

    public function containsSyntaxError(string $simulatedQueryString): bool
    {
        return $this->reflectionCache->getContainsSyntaxError($simulatedQueryString);
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
		// queries with errors don't have a cached result type
		if ($this->reflectionCache->hasResultType($simulatedQueryString, $fetchType) === false) {
			return null;
		}
        return $this->reflectionCache->getResultType($simulatedQueryString, $fetchType);
    }
}
