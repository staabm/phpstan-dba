<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class IfNullReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::IFNULL, BuiltInFunction::NULLIF], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): Type
    {
        $args = $expression->getArguments();

        $results = [];
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            $results[] = $argType;
        }

        return TypeCombinator::union(...$results);
    }
}
