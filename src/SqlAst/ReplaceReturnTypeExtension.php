<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class ReplaceReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::REPLACE], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (count($args) < 3) {
            return null;
        }

        $containsNullable = false;
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            if (TypeCombinator::containsNull($argType)) {
                $containsNullable = true;
                break;
            }
        }

        $hackstack = $scope->getType($args[0]);
        $replace = $scope->getType($args[2]);

        $result = new StringType();
        if ($hackstack->isNonEmptyString()->yes() && $replace->isNonEmptyString()->yes()) {
            $result = TypeCombinator::intersect($result, new AccessoryNonEmptyStringType());
        }
        if ($containsNullable) {
            $result = TypeCombinator::addNull($result);
        }

        return $result;
    }
}
