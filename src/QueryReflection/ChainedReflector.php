<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class ChainedReflector implements QueryReflector
{
    /**
     * @var list<QueryReflector>
     */
    private $reflectors;

    /**
     * @param QueryReflector ...$reflectors
     */
    public function __construct(...$reflectors)
    {
        $this->reflectors = $reflectors;
    }

    public function validateQueryString(string $simulatedQueryString): ?Error
    {
        foreach ($this->reflectors as $reflector) {
            $reflectorError = $reflector->validateQueryString($simulatedQueryString);

            // on "not found" error, we try the next reflector.
            if ($reflectorError && !\in_array($reflectorError->getCode(), [MysqliQueryReflector::MYSQL_UNKNOWN_TABLE])) {
                return $reflectorError;
            }
        }

        return null;
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        foreach ($this->reflectors as $reflector) {
            $reflectorResult = $reflector->getResultType($simulatedQueryString, $fetchType);

            if ($reflectorResult) {
                return $reflectorResult;
            }
        }

        return null;
    }
}
