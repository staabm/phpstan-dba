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
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::IFNULL], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (2 !== \count($args)) {
            return null;
        }

        $argType1 = $scope->getType($args[0]);
        $argType2 = $scope->getType($args[1]);

        // If arg1 is literal null, arg2 is always returned
        if ($argType1->isNull()->yes()) {
            return $argType2;
        }

        $arg1ContainsNull = TypeCombinator::containsNull($argType1);
        $arg2ContainsNull = TypeCombinator::containsNull($argType2);
        $argType1NoNull = TypeCombinator::removeNull($argType1);
        $argType2NoNull = TypeCombinator::removeNull($argType2);

        // If arg1 can be null, the result can be arg1 or arg2;
        // otherwise, the result can only be arg1.
        if ($arg1ContainsNull) {
            $resultType = TypeCombinator::union($argType1NoNull, $argType2NoNull);
        } else {
            $resultType = $argType1;
        }

        // The result type is always the "more general" of the two args
        // in the order: string, float, integer.
        // see https://dev.mysql.com/doc/refman/5.7/en/flow-control-functions.html#function_ifnull
        if ($this->isResultString($argType1NoNull, $argType2NoNull)) {
            $resultType = $resultType->toString();
        } elseif ($this->isResultFloat($argType1NoNull, $argType2NoNull)) {
            $resultType = $resultType->toFloat();
        }

        // Re-add null if arg2 can contain null
        if ($arg2ContainsNull) {
            $resultType = TypeCombinator::addNull($resultType);
        }
        return $resultType;
    }

    private function isResultString(Type $type1, Type $type2): bool
    {
        return (
            // If either arg is a string, the result is a string
            $type1->isString()->yes() ||
            $type2->isString()->yes() ||

            // Special case where args are a constant float and an int
            // results in a numeric string
            (
                $type1->isConstantScalarValue()->yes() &&
                $type1->isFloat()->yes() &&
                $type2->isInteger()->yes()
            ) ||
            (
                $type2->isConstantScalarValue()->yes() &&
                $type2->isFloat()->yes() &&
                $type1->isInteger()->yes()
            )
        );
    }

    private function isResultFloat(Type $type1, Type $type2): bool
    {
        // If either arg is a float, the result is a float
        return $type1->isFloat()->yes() || $type2->isFloat()->yes();
    }
}
