<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;

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

    public function containsSyntaxError(string $simulatedQueryString): bool
    {
        if (null === $this->reflector) {
            $this->reflector = ($this->reflectorFactory)();
        }

        return $this->reflector->containsSyntaxError($simulatedQueryString);
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        if (null === $this->reflector) {
            $this->reflector = ($this->reflectorFactory)();
        }

        return $this->reflector->getResultType($simulatedQueryString, $fetchType);
    }
}
