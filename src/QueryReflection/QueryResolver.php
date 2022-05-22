<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use staabm\PHPStanDba\UnresolvableQueryException;

final class QueryResolver
{
    /**
     * @return iterable<non-empty-string>
     *
     * @throws UnresolvableQueryException
     */
    public function resolve(Scope $scope, Expr $queryExpr, ?Type $parameterTypes):iterable {
        $queryReflection = new QueryReflection();

        $parameters = null;
        if ($parameterTypes !== null) {
            $parameters = $queryReflection->resolveParameters($parameterTypes) ?? [];
        }

        if (null === $parameters) {
            $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
        } else {
            $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);
        }

        foreach ($queryStrings as $queryString) {
            yield $queryString;
        }
    }

}
