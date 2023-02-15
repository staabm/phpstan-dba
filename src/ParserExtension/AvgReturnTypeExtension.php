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
final class AvgReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return
            $expression instanceof FunctionCall
            && \in_array($expression->getFunction()->getName(), [BuiltInFunction::AVG, BuiltInFunction::MIN, BuiltInFunction::MAX], true);
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (count($args) !== 1) {
            return null;
        }

        return TypeCombinator::addNull($scope->getType($args[0])->toNumber());
    }
}
