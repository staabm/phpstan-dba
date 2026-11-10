<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class InstrReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::INSTR, BuiltInFunction::LOCATE], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): Type
    {
        $args = $expression->getArguments();

        $containsNullable = false;
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            if (TypeCombinator::containsNull($argType)) {
                $containsNullable = true;
            }
        }

        $result = IntegerRangeType::fromInterval(0, null);
        if ($containsNullable) {
            $result = TypeCombinator::addNull($result);
        }

        return $result;
    }
}
