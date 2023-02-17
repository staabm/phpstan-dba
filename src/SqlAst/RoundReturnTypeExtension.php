<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class RoundReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::ROUND], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (\count($args) < 1) {
            return null;
        }

        $argType = $scope->getType($args[0]);
        if ($argType->isNull()->yes()) {
            return new NullType();
        }

        $containedNull = TypeCombinator::containsNull($argType);
        $argType = TypeCombinator::removeNull($argType);

        if (1 === \count($args)) {
            if (! $argType instanceof IntegerRangeType) {
                $argType = new IntegerType();
            }
        } else {
            $argType = new UnionType([
                new IntegerType(),
                new FloatType(),
            ]);
        }

        if ($containedNull) {
            $argType = TypeCombinator::addNull($argType);
        }

        return $argType;
    }
}
