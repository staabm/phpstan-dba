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
     * @param callable():QueryReflector $reflectorFactory
     */
    public function __construct(callable $reflectorFactory)
    {
        $this->reflectorFactory = $reflectorFactory;
    }

    public function validateQueryString(string $simulatedQueryString): ?Error
    {
        if (null === $this->reflector) {
            $this->reflector = ($this->reflectorFactory)();
        }

        return $this->reflector->validateQueryString($simulatedQueryString);
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        if (null === $this->reflector) {
            $this->reflector = ($this->reflectorFactory)();
        }

        return $this->reflector->getResultType($simulatedQueryString, $fetchType);
    }
}
