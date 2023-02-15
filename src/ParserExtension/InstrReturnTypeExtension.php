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
final class InstrReturnTypeExtension implements QueryExpressionReturnTypeExtension
{
    public function isExpressionSupported(ExpressionNode $expression): bool
    {
        return
            $expression instanceof FunctionCall
            && \in_array($expression->getFunction()->getName(), [BuiltInFunction::INSTR], true);
    }

    public function getTypeFromExpression(ExpressionNode $expression, QueryScope $scope): Type
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
