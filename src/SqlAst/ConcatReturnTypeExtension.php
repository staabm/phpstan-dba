<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class ConcatReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::CONCAT, BuiltInFunction::CONCAT_WS], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): Type
    {
        $args = $expression->getArguments();

        $results = [];
        $containsNull = false;
        $allNumeric = true;
        $isNonFalsyString = false;
        $isNonEmptyString = false;
        $isNumber = new UnionType([new IntegerType(), new FloatType()]);
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            if (BuiltInFunction::CONCAT === $expression->getFunction()->getName()) {
                if ($argType->isNull()->yes()) {
                    return new NullType();
                }

                if (TypeCombinator::containsNull($argType)) {
                    $containsNull = true;
                }
            }

            $argType = TypeCombinator::removeNull($argType);

            if (
                ! $argType->isNumericString()->yes()
                && ! $isNumber->isSuperTypeOf($argType)->yes()) {
                $allNumeric = false;
            }

            if ($argType->isNonFalsyString()->yes()) {
                $isNonFalsyString = true;
            } elseif ($argType->isNonEmptyString()->yes() || $isNumber->isSuperTypeOf($argType)->yes()) {
                $isNonEmptyString = true;
            }
        }

        $accessories = [];
        if ($allNumeric) {
            $accessories[] = new AccessoryNumericStringType();
        }

        if ($isNonFalsyString) {
            $accessories[] = new AccessoryNonFalsyStringType();
        } elseif ($isNonEmptyString) {
            $accessories[] = new AccessoryNonEmptyStringType();
        }

        $results[] = TypeCombinator::intersect(new StringType(), ...$accessories);

        if ($containsNull) {
            $results[] = new NullType();
        }

        return TypeCombinator::union(...$results);
    }
}
