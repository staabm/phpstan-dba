<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class NullIfReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::NULLIF], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (2 !== \count($args)) {
            return null;
        }

        $argType1 = $scope->getType($args[0]);
        $argType2 = $scope->getType($args[1]);

        // Return null type if scalar constants are equal
        if (
            $argType1->isConstantScalarValue()->yes() &&
            $argType1->equals($argType2)
        ) {
            return new NullType();
        }

        // If the types *can* be equal, we return the first type or null type
        if ($argType1->isSuperTypeOf($argType2)->yes() || $argType2->isSuperTypeOf($argType1)->yes()) {
            return TypeCombinator::addNull($argType1);
        }

        // Otherwise the first type is returned
        return $argType1;
    }
}
