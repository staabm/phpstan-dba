<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class ChainedReflector implements QueryReflector
{
    /**
     * @var QueryReflector[]
     */
    private $reflectors;

    /**
     * @param QueryReflector ...$reflectors
     */
    public function __construct(...$reflectors)
    {
        $this->reflectors = $reflectors;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $nooneKnows = true;

        foreach ($this->reflectors as $reflector) {
            $reflectorError = $reflector->validateQueryString($queryString);

            // on "not found" error, we try the next reflector.
            if (null !== $reflectorError) {
                if (! \in_array($reflectorError->getCode(), [MysqliQueryReflector::MYSQL_UNKNOWN_TABLE], true)) {
                    return $reflectorError;
                }
                if (true === $nooneKnows) {
                    $nooneKnows = $reflectorError;
                }
            } else {
                $nooneKnows = false;
            }
        }

        if ($nooneKnows instanceof Error) {
            return $nooneKnows;
        }

        return null;
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        foreach ($this->reflectors as $reflector) {
            $reflectorResult = $reflector->getResultType($queryString, $fetchType);

            if (null !== $reflectorResult) {
                return $reflectorResult;
            }
        }

        return null;
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        foreach ($this->reflectors as $reflector) {
            $reflector->setupDbaApi($dbaApi);
        }
    }
}
