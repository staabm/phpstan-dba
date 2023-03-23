<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class AvgReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::AVG], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);

        if ($argType->isNull()->yes()) {
            return $argType;
        }
        $argType = TypeCombinator::removeNull($argType);

        if ($argType->isInteger()->yes()) {
            $numberType = new IntersectionType([
                new StringType(),
                new AccessoryNumericStringType(),
            ]);
        } elseif ($argType->isSuperTypeOf(new StringType())->yes()) {
            $numberType = $argType->toFloat();
        } else {
            // We don't know how to handle any other types
            return null;
        }

        return TypeCombinator::addNull($numberType);
    }
}
