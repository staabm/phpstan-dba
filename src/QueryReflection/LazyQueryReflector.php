<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class LazyQueryReflector implements QueryReflector, RecordingReflector
{
    /**
     * @var callable():QueryReflector
     */
    private $reflectorFactory;

    private ?QueryReflector $reflector = null;

    private ?DbaApi $dbaApi = null;

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

    public function getDatasource()
    {
        $this->reflector = $this->createReflector();

        if ($this->reflector instanceof RecordingReflector) {
            return $this->reflector->getDatasource();
        }

        return null;
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
