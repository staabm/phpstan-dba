<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class AbsReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::ABS], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);

        if ($argType->isNull()->yes()) {
            return new NullType();
        }

        $containedNull = TypeCombinator::containsNull($argType);
        $argType = TypeCombinator::removeNull($argType);

        if ($argType instanceof IntegerRangeType) {
            $argType = IntegerRangeType::fromInterval(0, $argType->getMax());
        }

        if ($containedNull) {
            $argType = TypeCombinator::addNull($argType);
        }

        return $argType;
    }
}
