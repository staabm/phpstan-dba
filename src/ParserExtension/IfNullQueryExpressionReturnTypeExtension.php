<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

/**
 * @implements QueryExpressionReturnTypeExtension<FunctionCall>
 */
final class IfNullQueryExpressionReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return $expression instanceof FunctionCall && BuiltInFunction::IFNULL == $expression->getFunction()->getName();
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();
        if (count($args) !== 2) {
            return null;
        }

        $results = [];
        $containsNonNullable = false;
        foreach ($args as $arg) {
            $argType = $scope->getType($arg);

            $results[] = $argType;
            if (!TypeCombinator::containsNull($argType)) {
                $containsNonNullable = true;
            }
        }

        $union = TypeCombinator::union(...$results);
        if ($containsNonNullable) {
            return TypeCombinator::removeNull($union);
        }

        return $union;
    }
}
