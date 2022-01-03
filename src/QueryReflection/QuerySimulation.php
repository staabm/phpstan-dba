<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

/**
 * @internal
 */
final class QuerySimulation
{
    public static function simulate(string $queryString): ?string
    {
        $queryString = self::stripTraillingLimit($queryString);

        if (null === $queryString) {
            return null;
        }
        $queryString .= ' LIMIT 0';

        return $queryString;
    }

    private static function stripTraillingLimit(string $queryString): ?string
    {
        return preg_replace('/\s*LIMIT\s+\d+\s*(,\s*\d*)?$/i', '', $queryString);
    }
}
