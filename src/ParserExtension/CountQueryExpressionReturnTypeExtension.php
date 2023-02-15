<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;

/**
 * @implements QueryExpressionReturnTypeExtension<FunctionCall>
 */
final class CountQueryExpressionReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return $expression instanceof FunctionCall && BuiltInFunction::COUNT == $expression->getFunction()->getName();
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): Type
    {
        return IntegerRangeType::fromInterval(0, null);
    }
}
