<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class LazyQueryReflector implements QueryReflector
{
    /**
     * @var callable():QueryReflector
     */
    private $reflectorFactory;

    /**
     * @var QueryReflector|null
     */
    private $reflector;

    /**
     * @var DbaApi|null
     */
    private $dbaApi;

    /**
     * @param callable():QueryReflector $reflectorFactory
     */
    public function __construct(callable $reflectorFactory)
    {
        $this->reflectorFactory = $reflectorFactory;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $this->reflector = $this->createReflector();

        return $this->reflector->validateQueryString($queryString);
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $this->reflector = $this->createReflector();

        return $this->reflector->getResultType($queryString, $fetchType);
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->dbaApi = $dbaApi;
    }

    private function createReflector(): QueryReflector
    {
        if (null === $this->reflector) {
            $this->reflector = ($this->reflectorFactory)();
            $this->reflector->setupDbaApi($this->dbaApi);
        }

        return $this->reflector;
    }
}
