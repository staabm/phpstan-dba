<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class CoalesceReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::COALESCE], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): Type
    {
        $args = $expression->getArguments();

        $results = [];
        $containsNonNullable = false;
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            $results[] = $argType;
            if (! TypeCombinator::containsNull($argType)) {
                $containsNonNullable = true;
                break;
            }
        }

        $union = TypeCombinator::union(...$results);
        if ($containsNonNullable) {
            return TypeCombinator::removeNull($union);
        }

        return $union;
    }
}
