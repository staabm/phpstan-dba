<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Keyword;

final class GroupConcatReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    private bool $hasGroupBy;

    public function __construct(bool $hasGroupBy)
    {
        $this->hasGroupBy = $hasGroupBy;
    }

    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::GROUP_CONCAT], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        // The concatenated values are the positional (integer-keyed) arguments plus the one
        // following DISTINCT; ORDER BY and SEPARATOR are keyed modifiers and do not contribute.
        $valueTypes = [];
        foreach ($expression->getArguments() as $key => $argument) {
            if (\is_int($key) || $key === Keyword::DISTINCT) {
                $valueTypes[] = $scope->getType($argument);
            }
        }

        if ([] === $valueTypes) {
            return null;
        }

        $allNull = true;
        $containsNull = false;
        foreach ($valueTypes as $valueType) {
            if (! $valueType->isNull()->yes()) {
                $allNull = false;
            }
            if (TypeCombinator::containsNull($valueType)) {
                $containsNull = true;
            }
        }

        // Every concatenated value is NULL, so there is nothing to concatenate.
        if ($allNull) {
            return new NullType();
        }

        // GROUP_CONCAT stringifies its input regardless of type. The result is NULL when the group
        // is empty (possible without GROUP BY) or every value in the group is NULL.
        $result = new StringType();
        if ($containsNull || ! $this->hasGroupBy) {
            $result = TypeCombinator::addNull($result);
        }
        return $result;
    }
}
