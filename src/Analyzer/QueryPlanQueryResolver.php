<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Analyzer;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;
use staabm\PHPStanDba\UnresolvableQueryException;

final class QueryPlanQueryResolver
{
    /**
     * @return iterable<string>
     *
     * @throws UnresolvableQueryException
     */
    public function resolve(Scope $scope, Expr $queryExpr, ?Type $parameterTypes): iterable
    {
        $queryReflection = new QueryReflection();

        $parameters = null;
        if (null !== $parameterTypes) {
            $parameters = $queryReflection->resolveParameters($parameterTypes) ?? [];
        }

        if (null === $parameters) {
            $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
        } else {
            $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);
        }

        foreach ($queryStrings as $queryString) {
            $normalizedQuery = QuerySimulation::stripTrailers($queryString);

            if (null !== $normalizedQuery) {
                yield $normalizedQuery;
            }
        }
    }
}
