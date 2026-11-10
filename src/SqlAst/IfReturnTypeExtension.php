<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class IfReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::IF], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (3 !== \count($args)) {
            return null;
        }

        return TypeCombinator::union($scope->getType($args[1]), $scope->getType($args[2]));
    }
}
